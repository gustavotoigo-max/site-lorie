<?php
/**
 * Contas de clientes:
 * - cadastro com apenas nome completo, e-mail, telefone, CPF e endereço (sem senha);
 * - entrada por link enviado ao e-mail ("link mágico") ou pelo Google;
 * - complemento de cadastro para quem entrou pelo Google;
 * - campos brasileiros no checkout (CPF, número, bairro) e exigência de cadastro para comprar.
 */

defined( 'ABSPATH' ) || exit;

class Lorie_Account {

	const ENDPOINT = 'completar-cadastro';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'add_endpoint' ) );
		add_action( 'init', array( __CLASS__, 'maybe_magic_login' ), 5 );
		add_filter( 'woocommerce_get_query_vars', array( __CLASS__, 'query_vars' ) );
		add_action( 'woocommerce_account_' . self::ENDPOINT . '_endpoint', array( __CLASS__, 'render_complete_endpoint' ) );
		add_filter( 'woocommerce_account_menu_items', array( __CLASS__, 'menu_items' ) );
		add_filter( 'woocommerce_endpoint_' . self::ENDPOINT . '_title', fn() => 'Completar cadastro' );

		foreach ( array( 'lorie_register', 'lorie_magic_link' ) as $a ) {
			add_action( "wp_ajax_nopriv_{$a}", array( __CLASS__, $a ) );
			add_action( "wp_ajax_{$a}", array( __CLASS__, $a ) );
		}
		add_action( 'wp_ajax_lorie_complete_profile', array( __CLASS__, 'lorie_complete_profile' ) );

		add_shortcode( 'lorie_auth', array( __CLASS__, 'shortcode_auth' ) );

		add_action( 'template_redirect', array( __CLASS__, 'gate_checkout' ) );
		add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'checkout_fields' ) );
		add_filter( 'woocommerce_default_address_fields', array( __CLASS__, 'default_address_fields' ), 20 );
		add_filter( 'woocommerce_billing_fields', array( __CLASS__, 'address_fields' ), 20 );
		add_filter( 'woocommerce_shipping_fields', array( __CLASS__, 'shipping_address_fields' ), 20 );
		add_action( 'woocommerce_after_checkout_validation', array( __CLASS__, 'validate_checkout' ), 10, 2 );
		add_action( 'woocommerce_checkout_update_user_meta', array( __CLASS__, 'save_checkout_user_meta' ), 10, 2 );
		add_filter( 'woocommerce_localisation_address_formats', array( __CLASS__, 'address_format' ) );
		add_filter( 'woocommerce_formatted_address_replacements', array( __CLASS__, 'address_replacements' ), 10, 2 );
		add_filter( 'woocommerce_order_formatted_billing_address', array( __CLASS__, 'order_address_billing' ), 10, 2 );
		add_filter( 'woocommerce_order_formatted_shipping_address', array( __CLASS__, 'order_address_shipping' ), 10, 2 );
		add_filter( 'woocommerce_my_account_my_address_formatted_address', array( __CLASS__, 'myaccount_address' ), 10, 3 );
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( __CLASS__, 'admin_order_cpf' ) );

		// Sem senha no "Detalhes da conta" (o acesso é por link ou Google).
		add_filter( 'woocommerce_save_account_details_required_fields', function ( $f ) {
			unset( $f['account_display_name'] );
			return $f;
		} );
	}

	/* --------------------------------------------------------------------- Endpoint */

	public static function add_endpoint() {
		add_rewrite_endpoint( self::ENDPOINT, EP_ROOT | EP_PAGES );
	}

	public static function query_vars( $vars ) {
		$vars[ self::ENDPOINT ] = self::ENDPOINT;
		return $vars;
	}

	public static function menu_items( $items ) {
		if ( is_user_logged_in() && ! lorie_profile_complete( get_current_user_id() ) ) {
			$items = array( self::ENDPOINT => 'Completar cadastro' ) + $items;
		}
		unset( $items['downloads'] );
		return $items;
	}

	public static function render_complete_endpoint() {
		echo self::form_profile( get_current_user_id(), 'complete' ); // phpcs:ignore
	}

	/* --------------------------------------------------------------------- Formulários */

	public static function shortcode_auth( $atts = array() ) {
		if ( is_user_logged_in() ) {
			return '<p>Você já está conectada(o). <a href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '">Ir para minha conta</a></p>';
		}
		$raw      = isset( $_GET['redirect_to'] ) ? sanitize_text_field( wp_unslash( $_GET['redirect_to'] ) ) : ''; // phpcs:ignore
		$redirect = 'checkout' === $raw ? wc_get_checkout_url() : ( $raw ? esc_url_raw( $raw ) : '' );
		$redirect = wp_validate_redirect( $redirect, wc_get_page_permalink( 'myaccount' ) );

		$error = isset( $_GET['lorie_err'] ) ? sanitize_key( $_GET['lorie_err'] ) : ''; // phpcs:ignore
		$messages = array(
			'google_off'   => 'O login com Google ainda não foi configurado pela loja. Use o cadastro por e-mail.',
			'google_fail'  => 'Não foi possível entrar com o Google. Tente novamente.',
			'link_expired' => 'Esse link de acesso expirou ou já foi usado. Peça um novo abaixo.',
		);

		ob_start();
		?>
		<div class="lorie-auth" data-redirect="<?php echo esc_attr( $redirect ); ?>">
			<?php if ( $error && isset( $messages[ $error ] ) ) : ?>
				<div class="lorie-alert lorie-alert--error"><?php echo esc_html( $messages[ $error ] ); ?></div>
			<?php endif; ?>
			<div class="lorie-auth__tabs" role="tablist">
				<button type="button" class="is-active" data-auth-tab="login" role="tab">Entrar</button>
				<button type="button" data-auth-tab="register" role="tab">Criar cadastro</button>
			</div>

			<section class="lorie-auth__panel is-active" data-auth-panel="login">
				<h2 class="lorie-auth__title">Que bom te ver por aqui</h2>
				<p class="lorie-auth__lead">Entre com sua conta Google ou receba um link de acesso no seu e-mail — sem precisar decorar senha.</p>
				<?php echo self::google_button( $redirect ); // phpcs:ignore ?>
				<div class="lorie-divider"><span>ou</span></div>
				<form class="lorie-form" data-lorie-form="magic">
					<label class="lorie-field">
						<span>E-mail cadastrado</span>
						<input type="email" name="email" required autocomplete="email" placeholder="voce@email.com">
					</label>
					<input type="hidden" name="redirect" value="<?php echo esc_attr( $redirect ); ?>">
					<button type="submit" class="lorie-btn lorie-btn--primary lorie-btn--block">Receber link de acesso</button>
					<div class="lorie-form__msg" aria-live="polite"></div>
				</form>
				<p class="lorie-auth__switch">Ainda não tem cadastro? <button type="button" data-auth-tab="register">Criar agora</button></p>
			</section>

			<section class="lorie-auth__panel" data-auth-panel="register">
				<h2 class="lorie-auth__title">Crie seu cadastro</h2>
				<p class="lorie-auth__lead">Leva menos de um minuto. Usamos seus dados apenas para o envio das suas peças.</p>
				<?php echo self::google_button( $redirect, 'Cadastrar com Google' ); // phpcs:ignore ?>
				<div class="lorie-divider"><span>ou com seu e-mail</span></div>
				<?php echo self::form_profile( 0, 'register', $redirect ); // phpcs:ignore ?>
			</section>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function google_button( $redirect = '', $label = 'Entrar com Google' ) {
		$url = add_query_arg( 'redirect', rawurlencode( $redirect ), rest_url( 'lorie/v1/google/start' ) );
		return '<a class="lorie-google-btn" href="' . esc_url( $url ) . '" rel="nofollow">' . self::google_svg() . '<span>' . esc_html( $label ) . '</span></a>';
	}

	/** Símbolo oficial "G" do Google (multicolorido), conforme as diretrizes de marca do Google Sign-In. */
	public static function google_svg() {
		return '<svg class="lorie-google-btn__g" width="20" height="20" viewBox="0 0 48 48" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>';
	}

	/**
	 * Formulário de dados pessoais. $mode: register | complete.
	 */
	public static function form_profile( $user_id = 0, $mode = 'register', $redirect = '' ) {
		$v = function ( $k ) use ( $user_id ) {
			return $user_id ? (string) get_user_meta( $user_id, $k, true ) : '';
		};
		$name = '';
		if ( $user_id ) {
			$u    = get_userdata( $user_id );
			$name = trim( $v( 'billing_first_name' ) . ' ' . $v( 'billing_last_name' ) );
			$name = $name ? $name : trim( $u->first_name . ' ' . $u->last_name );
			$name = $name ? $name : $u->display_name;
		}
		$redirect = $redirect ? $redirect : ( isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : '' ); // phpcs:ignore
		if ( 'complete' === $mode && ! $redirect && WC()->cart && ! WC()->cart->is_empty() ) {
			$redirect = wc_get_checkout_url();
		}
		ob_start();
		?>
		<form class="lorie-form lorie-form--profile" data-lorie-form="<?php echo esc_attr( $mode ); ?>" novalidate>
			<?php if ( 'complete' === $mode ) : ?>
				<p class="lorie-auth__lead">Falta pouco! Precisamos destes dados para emitir seus pedidos e enviar suas peças.</p>
			<?php endif; ?>
			<div class="lorie-grid-2">
				<label class="lorie-field lorie-field--full">
					<span>Nome completo</span>
					<input type="text" name="full_name" required autocomplete="name" value="<?php echo esc_attr( $name ); ?>" placeholder="Como está no seu documento">
				</label>
				<?php if ( 'register' === $mode ) : ?>
				<label class="lorie-field">
					<span>E-mail</span>
					<input type="email" name="email" required autocomplete="email" placeholder="voce@email.com">
				</label>
				<?php endif; ?>
				<label class="lorie-field">
					<span>Telefone / WhatsApp</span>
					<input type="tel" name="phone" required autocomplete="tel" data-mask="phone" value="<?php echo esc_attr( lorie_format_phone( $v( 'billing_phone' ) ) ); ?>" placeholder="(11) 98765-4321">
				</label>
				<label class="lorie-field <?php echo 'complete' === $mode ? '' : ''; ?>">
					<span>CPF</span>
					<input type="text" name="cpf" required inputmode="numeric" data-mask="cpf" value="<?php echo esc_attr( lorie_format_cpf( $v( 'billing_cpf' ) ) ); ?>" placeholder="000.000.000-00">
				</label>
			</div>

			<fieldset class="lorie-fieldset">
				<legend>Endereço de entrega</legend>
				<div class="lorie-grid-6">
					<label class="lorie-field lorie-col-2">
						<span>CEP</span>
						<input type="text" name="cep" required inputmode="numeric" data-mask="cep" data-cep-lookup value="<?php echo esc_attr( lorie_format_cep( $v( 'billing_postcode' ) ) ); ?>" placeholder="00000-000">
					</label>
					<label class="lorie-field lorie-col-4">
						<span>Rua / Avenida</span>
						<input type="text" name="street" required autocomplete="address-line1" data-cep="logradouro" value="<?php echo esc_attr( $v( 'billing_address_1' ) ); ?>">
					</label>
					<label class="lorie-field lorie-col-2">
						<span>Número</span>
						<input type="text" name="number" required value="<?php echo esc_attr( $v( 'billing_number' ) ); ?>">
					</label>
					<label class="lorie-field lorie-col-4">
						<span>Complemento <em>(opcional)</em></span>
						<input type="text" name="complement" autocomplete="address-line2" value="<?php echo esc_attr( $v( 'billing_address_2' ) ); ?>" placeholder="Apto, bloco, referência">
					</label>
					<label class="lorie-field lorie-col-2">
						<span>Bairro</span>
						<input type="text" name="district" required data-cep="bairro" value="<?php echo esc_attr( $v( 'billing_neighborhood' ) ); ?>">
					</label>
					<label class="lorie-field lorie-col-3">
						<span>Cidade</span>
						<input type="text" name="city" required data-cep="localidade" value="<?php echo esc_attr( $v( 'billing_city' ) ); ?>">
					</label>
					<label class="lorie-field lorie-col-1">
						<span>UF</span>
						<select name="state" required data-cep="uf">
							<option value="">—</option>
							<?php foreach ( lorie_states() as $uf => $label ) : ?>
								<option value="<?php echo esc_attr( $uf ); ?>" <?php selected( $v( 'billing_state' ), $uf ); ?>><?php echo esc_html( $uf ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
				</div>
			</fieldset>

			<?php if ( 'register' === $mode ) : ?>
				<label class="lorie-check">
					<input type="checkbox" name="consent" value="1" required>
					<span>Concordo com a <a href="<?php echo esc_url( get_privacy_policy_url() ? get_privacy_policy_url() : home_url( '/politica-de-privacidade/' ) ); ?>" target="_blank">Política de Privacidade</a> e com o uso dos meus dados para processar meus pedidos.</span>
				</label>
			<?php endif; ?>

			<input type="hidden" name="redirect" value="<?php echo esc_attr( $redirect ); ?>">
			<button type="submit" class="lorie-btn lorie-btn--primary lorie-btn--block">
				<?php echo 'register' === $mode ? 'Criar cadastro e entrar' : 'Salvar e continuar'; ?>
			</button>
			<div class="lorie-form__msg" aria-live="polite"></div>
		</form>
		<?php
		return ob_get_clean();
	}

	/* --------------------------------------------------------------------- AJAX */

	private static function check_nonce() {
		if ( ! check_ajax_referer( 'lorie_front', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Sua sessão expirou. Recarregue a página e tente novamente.' ), 403 );
		}
	}

	/** Valida e normaliza os dados de perfil. Retorna [dados, erros]. */
	private static function read_profile( $require_email ) {
		$p = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification
		$d = array(
			'full_name'  => sanitize_text_field( $p['full_name'] ?? '' ),
			'email'      => sanitize_email( $p['email'] ?? '' ),
			'phone'      => lorie_digits( $p['phone'] ?? '' ),
			'cpf'        => lorie_digits( $p['cpf'] ?? '' ),
			'cep'        => lorie_digits( $p['cep'] ?? '' ),
			'street'     => sanitize_text_field( $p['street'] ?? '' ),
			'number'     => sanitize_text_field( $p['number'] ?? '' ),
			'complement' => sanitize_text_field( $p['complement'] ?? '' ),
			'district'   => sanitize_text_field( $p['district'] ?? '' ),
			'city'       => sanitize_text_field( $p['city'] ?? '' ),
			'state'      => strtoupper( sanitize_text_field( $p['state'] ?? '' ) ),
		);
		$e = array();
		if ( count( preg_split( '/\s+/', trim( $d['full_name'] ) ) ) < 2 ) {
			$e['full_name'] = 'Informe nome e sobrenome.';
		}
		if ( $require_email && ! is_email( $d['email'] ) ) {
			$e['email'] = 'E-mail inválido.';
		}
		if ( strlen( $d['phone'] ) < 10 || strlen( $d['phone'] ) > 11 ) {
			$e['phone'] = 'Telefone com DDD, por favor.';
		}
		if ( ! lorie_valid_cpf( $d['cpf'] ) ) {
			$e['cpf'] = 'CPF inválido.';
		}
		if ( strlen( $d['cep'] ) !== 8 ) {
			$e['cep'] = 'CEP inválido.';
		}
		foreach ( array( 'street' => 'Informe a rua.', 'number' => 'Informe o número.', 'district' => 'Informe o bairro.', 'city' => 'Informe a cidade.' ) as $k => $msg ) {
			if ( '' === $d[ $k ] ) {
				$e[ $k ] = $msg;
			}
		}
		if ( ! isset( lorie_states()[ $d['state'] ] ) ) {
			$e['state'] = 'UF?';
		}
		return array( $d, $e );
	}

	private static function cpf_in_use( $cpf, $except_user = 0 ) {
		$users = get_users( array(
			'meta_key'   => 'billing_cpf', // phpcs:ignore
			'meta_value' => $cpf, // phpcs:ignore
			'fields'     => 'ID',
			'number'     => 2,
		) );
		$users = array_diff( array_map( 'intval', $users ), array( (int) $except_user ) );
		return ! empty( $users );
	}

	/** Grava os dados no formato do WooCommerce (cobrança e entrega iguais). */
	public static function save_profile( $user_id, $d ) {
		$parts = preg_split( '/\s+/', trim( $d['full_name'] ) );
		$first = array_shift( $parts );
		$last  = implode( ' ', $parts );

		wp_update_user( array(
			'ID'           => $user_id,
			'first_name'   => $first,
			'last_name'    => $last,
			'display_name' => $first,
		) );

		$map = array(
			'first_name'   => $first,
			'last_name'    => $last,
			'phone'        => $d['phone'],
			'cpf'          => $d['cpf'],
			'postcode'     => lorie_format_cep( $d['cep'] ),
			'address_1'    => $d['street'],
			'number'       => $d['number'],
			'address_2'    => $d['complement'],
			'neighborhood' => $d['district'],
			'city'         => $d['city'],
			'state'        => $d['state'],
			'country'      => 'BR',
		);
		foreach ( $map as $k => $val ) {
			update_user_meta( $user_id, 'billing_' . $k, $val );
			if ( ! in_array( $k, array( 'phone', 'cpf' ), true ) ) {
				update_user_meta( $user_id, 'shipping_' . $k, $val );
			}
		}
		if ( ! empty( $d['email'] ) ) {
			update_user_meta( $user_id, 'billing_email', $d['email'] );
		}
	}

	public static function lorie_register() {
		self::check_nonce();
		list( $d, $e ) = self::read_profile( true );
		if ( empty( $_POST['consent'] ) ) { // phpcs:ignore
			$e['consent'] = 'É preciso concordar com a política de privacidade.';
		}
		if ( empty( $e['email'] ) && email_exists( $d['email'] ) ) {
			$e['email'] = 'Este e-mail já tem cadastro. Use a aba "Entrar" para receber o link de acesso.';
		}
		if ( empty( $e['cpf'] ) && self::cpf_in_use( $d['cpf'] ) ) {
			$e['cpf'] = 'Este CPF já tem cadastro. Use a aba "Entrar".';
		}
		if ( $e ) {
			wp_send_json_error( array( 'message' => 'Confira os campos destacados.', 'fields' => $e ) );
		}

		// Senha aleatória forte (o cliente não precisa dela: entra por link ou Google).
		$user_id = wc_create_new_customer( $d['email'], '', wp_generate_password( 32, true, true ), array(
			'first_name' => strtok( $d['full_name'], ' ' ),
		) );
		if ( is_wp_error( $user_id ) ) {
			wp_send_json_error( array( 'message' => $user_id->get_error_message() ) );
		}
		self::save_profile( $user_id, $d );
		update_user_meta( $user_id, 'lorie_privacy_consent', current_time( 'mysql' ) );

		wc_set_customer_auth_cookie( $user_id );
		$redirect = wp_validate_redirect( esc_url_raw( wp_unslash( $_POST['redirect'] ?? '' ) ), wc_get_page_permalink( 'myaccount' ) ); // phpcs:ignore
		wp_send_json_success( array( 'message' => 'Cadastro criado! Entrando…', 'redirect' => $redirect ) );
	}

	public static function lorie_complete_profile() {
		self::check_nonce();
		$user_id = get_current_user_id();
		list( $d, $e ) = self::read_profile( false );
		if ( empty( $e['cpf'] ) && self::cpf_in_use( $d['cpf'], $user_id ) ) {
			$e['cpf'] = 'Este CPF já está vinculado a outro cadastro. Fale com a loja pelo WhatsApp.';
		}
		if ( $e ) {
			wp_send_json_error( array( 'message' => 'Confira os campos destacados.', 'fields' => $e ) );
		}
		self::save_profile( $user_id, $d );
		$redirect = wp_validate_redirect( esc_url_raw( wp_unslash( $_POST['redirect'] ?? '' ) ), wc_get_page_permalink( 'myaccount' ) ); // phpcs:ignore
		wp_send_json_success( array( 'message' => 'Dados salvos!', 'redirect' => $redirect ) );
	}

	/** Envia um link de acesso válido por 20 minutos e de uso único. */
	public static function lorie_magic_link() {
		self::check_nonce();
		$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ); // phpcs:ignore
		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => 'Digite um e-mail válido.' ) );
		}
		$generic = 'Se este e-mail tiver cadastro, você receberá um link de acesso em instantes. Confira também a caixa de spam.';

		$rate_key = 'lorie_ml_' . md5( strtolower( $email ) );
		if ( get_transient( $rate_key ) ) {
			wp_send_json_success( array( 'message' => 'Já enviamos um link há pouco. Aguarde um minuto antes de pedir outro.' ) );
		}
		set_transient( $rate_key, 1, MINUTE_IN_SECONDS );

		$user = get_user_by( 'email', $email );
		if ( $user ) {
			$token = bin2hex( random_bytes( 24 ) );
			update_user_meta( $user->ID, '_lorie_magic', array(
				'hash'    => wp_hash_password( $token ),
				'expires' => time() + 20 * MINUTE_IN_SECONDS,
			) );
			$redirect = wp_validate_redirect( esc_url_raw( wp_unslash( $_POST['redirect'] ?? '' ) ), '' ); // phpcs:ignore
			$link     = add_query_arg( array(
				'lorie_magic' => $token,
				'u'           => $user->ID,
				'r'           => $redirect ? rawurlencode( $redirect ) : '',
			), home_url( '/' ) );
			self::send_magic_email( $user, $link );
		}
		wp_send_json_success( array( 'message' => $generic ) );
	}

	private static function send_magic_email( $user, $link ) {
		$store   = lorie_get( 'store_name' );
		$subject = "Seu link de acesso — {$store}";
		$name    = $user->first_name ? $user->first_name : $user->display_name;
		$body    = '<p>Olá, ' . esc_html( $name ) . '!</p>'
			. '<p>Clique no botão abaixo para entrar na sua conta da ' . esc_html( $store ) . '. O link vale por 20 minutos e só pode ser usado uma vez.</p>'
			. '<p style="text-align:center;margin:28px 0"><a href="' . esc_url( $link ) . '" style="background:#B88A3E;color:#fff;padding:14px 28px;border-radius:999px;text-decoration:none;font-weight:600;display:inline-block">Entrar na minha conta</a></p>'
			. '<p style="font-size:13px;color:#7A6A5E">Se não foi você quem pediu, ignore este e-mail.</p>';
		$mailer  = WC()->mailer();
		$html    = $mailer->wrap_message( 'Seu link de acesso', $body );
		$mailer->send( $user->user_email, $subject, $html, array( 'Content-Type: text/html; charset=UTF-8' ) );
	}

	public static function maybe_magic_login() {
		if ( empty( $_GET['lorie_magic'] ) || empty( $_GET['u'] ) ) { // phpcs:ignore
			return;
		}
		$token   = sanitize_text_field( wp_unslash( $_GET['lorie_magic'] ) ); // phpcs:ignore
		$user_id = absint( $_GET['u'] ); // phpcs:ignore
		$data    = get_user_meta( $user_id, '_lorie_magic', true );
		$ok      = is_array( $data ) && $data['expires'] > time() && wp_check_password( $token, $data['hash'] );
		delete_user_meta( $user_id, '_lorie_magic' ); // uso único (vale também para tentativas inválidas)

		if ( ! $ok ) {
			wp_safe_redirect( add_query_arg( 'lorie_err', 'link_expired', wc_get_page_permalink( 'myaccount' ) ) );
			exit;
		}
		wc_set_customer_auth_cookie( $user_id );
		$redirect = isset( $_GET['r'] ) ? esc_url_raw( rawurldecode( wp_unslash( $_GET['r'] ) ) ) : ''; // phpcs:ignore
		wp_safe_redirect( wp_validate_redirect( $redirect, wc_get_page_permalink( 'myaccount' ) ) );
		exit;
	}

	/* --------------------------------------------------------------------- Checkout */

	/** Para comprar é preciso ter cadastro completo (CPF e endereço são necessários para o envio). */
	public static function gate_checkout() {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_wc_endpoint_url( 'order-received' ) || is_wc_endpoint_url( 'order-pay' ) ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			wc_add_notice( 'Para finalizar a compra, entre ou crie seu cadastro — leva menos de um minuto.', 'notice' );
			wp_safe_redirect( add_query_arg( 'redirect_to', 'checkout', wc_get_page_permalink( 'myaccount' ) ) );
			exit;
		}
		if ( ! lorie_profile_complete( get_current_user_id() ) ) {
			wp_safe_redirect( add_query_arg( 'redirect_to', rawurlencode( wc_get_checkout_url() ), wc_get_account_endpoint_url( self::ENDPOINT ) ) );
			exit;
		}
	}

	/** Endereço no padrão brasileiro (vale para cobrança, entrega, checkout e "Minha conta"). */
	public static function default_address_fields( $f ) {
		unset( $f['company'] );
		$f['first_name'] = array_merge( $f['first_name'], array( 'label' => 'Nome', 'priority' => 10 ) );
		$f['last_name']  = array_merge( $f['last_name'], array( 'label' => 'Sobrenome', 'priority' => 20 ) );
		$f['country']    = array_merge( $f['country'], array( 'priority' => 95, 'class' => array( 'form-row-wide', 'lorie-hidden-field' ) ) );
		$f['postcode']   = array_merge( $f['postcode'], array(
			'label'             => 'CEP',
			'priority'          => 40,
			'class'             => array( 'form-row-first' ),
			'custom_attributes' => array( 'data-mask' => 'cep', 'data-cep-lookup' => '1', 'inputmode' => 'numeric' ),
		) );
		$f['address_1']  = array_merge( $f['address_1'], array( 'label' => 'Rua / Avenida', 'placeholder' => '', 'priority' => 50, 'class' => array( 'form-row-wide' ) ) );
		$f['number']     = array( 'label' => 'Número', 'required' => true, 'priority' => 52, 'class' => array( 'form-row-first' ) );
		$f['address_2']  = array_merge( $f['address_2'], array( 'label' => 'Complemento', 'label_class' => array(), 'placeholder' => 'Apto, bloco, referência', 'required' => false, 'priority' => 54, 'class' => array( 'form-row-last' ) ) );
		$f['neighborhood'] = array( 'label' => 'Bairro', 'required' => true, 'priority' => 56, 'class' => array( 'form-row-first' ) );
		$f['city']       = array_merge( $f['city'], array( 'label' => 'Cidade', 'priority' => 60, 'class' => array( 'form-row-last' ) ) );
		$f['state']      = array_merge( $f['state'], array( 'label' => 'Estado', 'priority' => 70, 'class' => array( 'form-row-wide' ) ) );
		return $f;
	}

	public static function address_fields( $fields ) {
		$fields['billing_cpf'] = array(
			'label'             => 'CPF',
			'required'          => true,
			'class'             => array( 'form-row-first' ),
			'priority'          => 25,
			'custom_attributes' => array( 'data-mask' => 'cpf', 'inputmode' => 'numeric' ),
		);
		if ( isset( $fields['billing_phone'] ) ) {
			$fields['billing_phone'] = array_merge( $fields['billing_phone'], array(
				'label'             => 'Telefone / WhatsApp',
				'required'          => true,
				'class'             => array( 'form-row-last' ),
				'priority'          => 26,
				'custom_attributes' => array( 'data-mask' => 'phone' ),
			) );
		}
		if ( isset( $fields['billing_email'] ) ) {
			$fields['billing_email']['label']    = 'E-mail';
			$fields['billing_email']['priority'] = 30;
		}
		unset( $fields['billing_company'] );
		return $fields;
	}

	public static function shipping_address_fields( $fields ) {
		unset( $fields['shipping_company'] );
		return $fields;
	}

	public static function checkout_fields( $fields ) {
		if ( isset( $fields['order']['order_comments'] ) ) {
			$fields['order']['order_comments']['label']       = 'Observações do pedido';
			$fields['order']['order_comments']['placeholder'] = 'Algo que a artesã precisa saber? (as personalizações de cada peça já vêm do carrinho)';
		}
		return $fields;
	}

	public static function validate_checkout( $data, $errors ) {
		if ( ! empty( $data['billing_cpf'] ) && ! lorie_valid_cpf( $data['billing_cpf'] ) ) {
			$errors->add( 'billing_cpf', '<strong>CPF</strong> inválido.' );
		}
	}

	public static function save_checkout_user_meta( $customer_id, $data ) {
		foreach ( array( 'billing_cpf', 'billing_number', 'billing_neighborhood', 'shipping_number', 'shipping_neighborhood' ) as $k ) {
			if ( isset( $data[ $k ] ) ) {
				update_user_meta( $customer_id, $k, 'billing_cpf' === $k ? lorie_digits( $data[ $k ] ) : $data[ $k ] );
			}
		}
	}

	/* --------------------------------------------------------------------- Endereço BR */

	public static function address_format( $formats ) {
		$formats['BR'] = "{name}\n{address_1}, {number}\n{address_2}\n{neighborhood}\n{city} — {state_code}\nCEP {postcode}";
		return $formats;
	}

	public static function address_replacements( $replace, $args ) {
		$replace['{number}']       = $args['number'] ?? '';
		$replace['{neighborhood}'] = $args['neighborhood'] ?? '';
		return $replace;
	}

	public static function order_address_billing( $address, $order ) {
		$address['number']       = $order->get_meta( '_billing_number' );
		$address['neighborhood'] = $order->get_meta( '_billing_neighborhood' );
		return $address;
	}

	public static function order_address_shipping( $address, $order ) {
		$address['number']       = $order->get_meta( '_shipping_number' ) ? $order->get_meta( '_shipping_number' ) : $order->get_meta( '_billing_number' );
		$address['neighborhood'] = $order->get_meta( '_shipping_neighborhood' ) ? $order->get_meta( '_shipping_neighborhood' ) : $order->get_meta( '_billing_neighborhood' );
		return $address;
	}

	public static function myaccount_address( $address, $customer_id, $type ) {
		$address['number']       = get_user_meta( $customer_id, $type . '_number', true );
		$address['neighborhood'] = get_user_meta( $customer_id, $type . '_neighborhood', true );
		return $address;
	}

	public static function admin_order_cpf( $order ) {
		$cpf = $order->get_meta( '_billing_cpf' );
		if ( $cpf ) {
			echo '<p><strong>CPF:</strong> ' . esc_html( lorie_format_cpf( $cpf ) ) . '</p>';
		}
	}
}
