<?php
/**
 * Posts do Instagram na página inicial.
 * Dois modos:
 *  - Manual (padrão): a dona escolhe até 9 fotos e cola o link de cada post. Funciona sempre.
 *  - Automático: com um token da API do Instagram (conta profissional), as últimas fotos aparecem sozinhas.
 *    O token fica salvo no banco de dados (nunca no repositório) e é renovado automaticamente.
 */

defined( 'ABSPATH' ) || exit;

class Lorie_Instagram {

	const OPTION = 'lorie_instagram';
	const TOKEN  = 'lorie_instagram_token';

	public static function init() {
		add_action( 'admin_post_lorie_save_instagram', array( __CLASS__, 'save' ) );
		add_action( 'lorie_instagram_refresh', array( __CLASS__, 'refresh_token' ) );
		if ( ! wp_next_scheduled( 'lorie_instagram_refresh' ) ) {
			wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', 'lorie_instagram_refresh' );
		}
	}

	public static function get() {
		$o = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $o ) ? $o : array(), array( 'mode' => 'manual', 'posts' => array() ) );
	}

	public static function save() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( 'Sem permissão.' );
		}
		check_admin_referer( 'lorie_save_instagram' );
		$posts = array();
		$imgs  = (array) ( $_POST['ig_image'] ?? array() );
		$links = (array) ( $_POST['ig_link'] ?? array() );
		foreach ( $imgs as $i => $img ) {
			$img = absint( $img );
			if ( $img ) {
				$posts[] = array( 'image' => $img, 'link' => esc_url_raw( wp_unslash( $links[ $i ] ?? '' ) ) );
			}
		}
		update_option( self::OPTION, array(
			'mode'  => 'auto' === ( $_POST['ig_mode'] ?? '' ) ? 'auto' : 'manual',
			'posts' => array_slice( $posts, 0, 9 ),
		) );
		$token = trim( sanitize_text_field( wp_unslash( $_POST['ig_token'] ?? '' ) ) );
		if ( $token && false === strpos( $token, '•' ) ) {
			update_option( self::TOKEN, $token, false );
		}
		if ( ! empty( $_POST['ig_token_clear'] ) ) {
			delete_option( self::TOKEN );
		}
		delete_transient( 'lorie_ig_feed' );
		wp_safe_redirect( add_query_arg( 'lorie_msg', 'saved', admin_url( 'admin.php?page=lorie-instagram' ) ) );
		exit;
	}

	/** Lista de posts prontos para exibir: [ ['src','link','alt'] ]. */
	public static function feed( $limit = 6 ) {
		$o = self::get();
		if ( 'auto' === $o['mode'] && get_option( self::TOKEN ) ) {
			$auto = self::auto_feed( $limit );
			if ( $auto ) {
				return $auto;
			}
		}
		$out = array();
		foreach ( array_slice( $o['posts'], 0, $limit ) as $p ) {
			$src = wp_get_attachment_image_url( $p['image'], 'medium_large' );
			if ( $src ) {
				$out[] = array( 'src' => $src, 'link' => $p['link'] ? $p['link'] : lorie_instagram_url(), 'alt' => 'Post do Instagram' );
			}
		}
		return $out;
	}

	private static function auto_feed( $limit ) {
		$cached = get_transient( 'lorie_ig_feed' );
		if ( is_array( $cached ) ) {
			return array_slice( $cached, 0, $limit );
		}
		$url  = add_query_arg( array(
			'fields'       => 'id,caption,media_type,media_url,thumbnail_url,permalink',
			'limit'        => 12,
			'access_token' => get_option( self::TOKEN ),
		), 'https://graph.instagram.com/me/media' );
		$resp = wp_remote_get( $url, array( 'timeout' => 10 ) );
		$data = is_wp_error( $resp ) ? array() : json_decode( wp_remote_retrieve_body( $resp ), true );
		$out  = array();
		foreach ( (array) ( $data['data'] ?? array() ) as $m ) {
			$src = 'VIDEO' === ( $m['media_type'] ?? '' ) ? ( $m['thumbnail_url'] ?? '' ) : ( $m['media_url'] ?? '' );
			if ( $src ) {
				$out[] = array( 'src' => $src, 'link' => $m['permalink'] ?? lorie_instagram_url(), 'alt' => wp_trim_words( $m['caption'] ?? 'Post do Instagram', 12 ) );
			}
		}
		set_transient( 'lorie_ig_feed', $out, $out ? HOUR_IN_SECONDS : 10 * MINUTE_IN_SECONDS );
		return array_slice( $out, 0, $limit );
	}

	/** Tokens de longa duração valem 60 dias; renovamos diariamente. */
	public static function refresh_token() {
		$token = get_option( self::TOKEN );
		if ( ! $token ) {
			return;
		}
		$resp = wp_remote_get( add_query_arg( array( 'grant_type' => 'ig_refresh_token', 'access_token' => $token ), 'https://graph.instagram.com/refresh_access_token' ), array( 'timeout' => 10 ) );
		$data = is_wp_error( $resp ) ? array() : json_decode( wp_remote_retrieve_body( $resp ), true );
		if ( ! empty( $data['access_token'] ) ) {
			update_option( self::TOKEN, $data['access_token'], false );
		}
	}
}
