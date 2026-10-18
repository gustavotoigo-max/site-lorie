<?php
/**
 * Login com Google (OAuth 2.0 / OpenID Connect, fluxo "authorization code" no servidor).
 *
 * Credenciais: Lorié › Configurações › Login com Google, ou constantes no wp-config.php:
 *   define( 'LORIE_GOOGLE_CLIENT_ID', '...' );
 *   define( 'LORIE_GOOGLE_CLIENT_SECRET', '...' );
 * URI de redirecionamento a cadastrar no Google Cloud: https://SEU-DOMINIO/wp-json/lorie/v1/google/callback
 */

defined( 'ABSPATH' ) || exit;

class Lorie_Google {

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	public static function client_id() {
		return defined( 'LORIE_GOOGLE_CLIENT_ID' ) ? LORIE_GOOGLE_CLIENT_ID : (string) get_option( 'lorie_google_client_id', '' );
	}

	public static function client_secret() {
		return defined( 'LORIE_GOOGLE_CLIENT_SECRET' ) ? LORIE_GOOGLE_CLIENT_SECRET : (string) get_option( 'lorie_google_client_secret', '' );
	}

	public static function enabled() {
		return self::client_id() && self::client_secret();
	}

	public static function callback_url() {
		return rest_url( 'lorie/v1/google/callback' );
	}

	public static function routes() {
		register_rest_route( 'lorie/v1', '/google/start', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'start' ),
			'permission_callback' => '__return_true',
		) );
		register_rest_route( 'lorie/v1', '/google/callback', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'callback' ),
			'permission_callback' => '__return_true',
		) );
	}

	private static function fail( $code = 'google_fail' ) {
		wp_safe_redirect( add_query_arg( 'lorie_err', $code, wc_get_page_permalink( 'myaccount' ) ) );
		exit;
	}

	public static function start( WP_REST_Request $req ) {
		if ( ! self::enabled() ) {
			self::fail( 'google_off' );
		}
		$state = wp_generate_password( 32, false );
		set_transient( 'lorie_gstate_' . $state, array(
			'redirect' => wp_validate_redirect( esc_url_raw( (string) $req->get_param( 'redirect' ) ), '' ),
		), 10 * MINUTE_IN_SECONDS );

		$url = add_query_arg( array(
			'client_id'     => rawurlencode( self::client_id() ),
			'redirect_uri'  => rawurlencode( self::callback_url() ),
			'response_type' => 'code',
			'scope'         => rawurlencode( 'openid email profile' ),
			'state'         => $state,
			'prompt'        => 'select_account',
		), 'https://accounts.google.com/o/oauth2/v2/auth' );

		wp_redirect( $url ); // phpcs:ignore WordPress.Security.SafeRedirect -- domínio do Google
		exit;
	}

	public static function callback( WP_REST_Request $req ) {
		$state = sanitize_text_field( (string) $req->get_param( 'state' ) );
		$code  = (string) $req->get_param( 'code' );
		$saved = $state ? get_transient( 'lorie_gstate_' . $state ) : false;
		if ( $state ) {
			delete_transient( 'lorie_gstate_' . $state );
		}
		if ( ! $saved || ! $code ) {
			self::fail();
		}

		$resp = wp_remote_post( 'https://oauth2.googleapis.com/token', array(
			'timeout' => 15,
			'body'    => array(
				'code'          => $code,
				'client_id'     => self::client_id(),
				'client_secret' => self::client_secret(),
				'redirect_uri'  => self::callback_url(),
				'grant_type'    => 'authorization_code',
			),
		) );
		$body = is_wp_error( $resp ) ? array() : json_decode( wp_remote_retrieve_body( $resp ), true );
		if ( empty( $body['id_token'] ) ) {
			self::fail();
		}

		// Validação do token pelo próprio Google (assinatura, expiração e emissor).
		$info = wp_remote_get( 'https://oauth2.googleapis.com/tokeninfo?id_token=' . rawurlencode( $body['id_token'] ), array( 'timeout' => 15 ) );
		$info = is_wp_error( $info ) ? array() : json_decode( wp_remote_retrieve_body( $info ), true );

		$valid = ! empty( $info['sub'] )
			&& ( $info['aud'] ?? '' ) === self::client_id()
			&& in_array( $info['iss'] ?? '', array( 'accounts.google.com', 'https://accounts.google.com' ), true )
			&& in_array( $info['email_verified'] ?? '', array( true, 'true' ), true )
			&& is_email( $info['email'] ?? '' );
		if ( ! $valid ) {
			self::fail();
		}

		$user_id = self::find_or_create_user( $info );
		if ( ! $user_id ) {
			self::fail();
		}
		wc_set_customer_auth_cookie( $user_id );

		$redirect = $saved['redirect'] ? $saved['redirect'] : wc_get_page_permalink( 'myaccount' );
		if ( ! lorie_profile_complete( $user_id ) ) {
			$redirect = add_query_arg( 'redirect_to', rawurlencode( $redirect ), wc_get_account_endpoint_url( Lorie_Account::ENDPOINT ) );
		}
		wp_safe_redirect( $redirect );
		exit;
	}

	private static function find_or_create_user( $info ) {
		$users = get_users( array( 'meta_key' => 'lorie_google_sub', 'meta_value' => $info['sub'], 'number' => 1, 'fields' => 'ID' ) ); // phpcs:ignore
		if ( $users ) {
			return (int) $users[0];
		}
		$user = get_user_by( 'email', $info['email'] );
		if ( $user ) {
			update_user_meta( $user->ID, 'lorie_google_sub', $info['sub'] );
			return $user->ID;
		}
		$user_id = wc_create_new_customer( $info['email'], '', wp_generate_password( 32, true, true ), array(
			'first_name' => $info['given_name'] ?? '',
			'last_name'  => $info['family_name'] ?? '',
		) );
		if ( is_wp_error( $user_id ) ) {
			return 0;
		}
		update_user_meta( $user_id, 'lorie_google_sub', $info['sub'] );
		update_user_meta( $user_id, 'billing_first_name', $info['given_name'] ?? '' );
		update_user_meta( $user_id, 'billing_last_name', $info['family_name'] ?? '' );
		update_user_meta( $user_id, 'billing_email', $info['email'] );
		return $user_id;
	}
}
