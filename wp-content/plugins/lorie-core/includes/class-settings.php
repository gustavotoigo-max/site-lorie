<?php
/**
 * Salva as configurações gerais (Lorié › Configurações).
 */

defined( 'ABSPATH' ) || exit;

class Lorie_Settings {

	public static function init() {
		add_action( 'admin_post_lorie_save_settings', array( __CLASS__, 'save' ) );
	}

	public static function fields() {
		return array(
			'Loja'                 => array(
				'store_name'        => array( 'Nome da loja', 'text' ),
				'tagline'           => array( 'Frase da marca', 'text' ),
				'email_contact'     => array( 'E-mail de contato', 'email' ),
				'whatsapp'          => array( 'WhatsApp da loja (55 + DDD + número, só dígitos)', 'text', '5511987654321' ),
				'whatsapp_message'  => array( 'Mensagem inicial do WhatsApp', 'text' ),
				'instagram_handle'  => array( 'Usuário do Instagram (sem @)', 'text' ),
				'production_days'   => array( 'Prazo padrão de produção (dias)', 'number' ),
				'free_shipping_min' => array( 'Valor para frete grátis (R$) — usado na barra do carrinho', 'number' ),
			),
			'Remetente (etiqueta)' => array(
				'sender_name'     => array( 'Nome do remetente', 'text' ),
				'sender_street'   => array( 'Rua e número', 'text' ),
				'sender_district' => array( 'Bairro', 'text' ),
				'sender_city'     => array( 'Cidade', 'text' ),
				'sender_state'    => array( 'UF', 'text' ),
				'sender_cep'      => array( 'CEP', 'text' ),
				'sender_phone'    => array( 'Telefone', 'text' ),
			),
		);
	}

	/** Mantém o "frete grátis acima de" do WooCommerce igual ao valor das configurações. */
	public static function sync_free_shipping( $min ) {
		if ( ! class_exists( 'WC_Shipping_Zones' ) || $min <= 0 ) {
			return;
		}
		foreach ( WC_Shipping_Zones::get_zones() as $zone ) {
			foreach ( $zone['shipping_methods'] as $method ) {
				if ( 'free_shipping' === $method->id ) {
					$opt                = 'woocommerce_free_shipping_' . $method->instance_id . '_settings';
					$s                  = get_option( $opt, array() );
					$s['min_amount']    = (string) $min;
					$s['requires']      = 'min_amount';
					update_option( $opt, $s );
				}
			}
		}
		WC_Cache_Helper::get_transient_version( 'shipping', true );
	}

	public static function save() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( 'Sem permissão.' );
		}
		check_admin_referer( 'lorie_save_settings' );
		$data = get_option( 'lorie_settings', array() );
		$data = is_array( $data ) ? $data : array();
		foreach ( self::fields() as $group ) {
			foreach ( $group as $key => $def ) {
				if ( ! isset( $_POST[ $key ] ) ) {
					continue;
				}
				$val = wp_unslash( $_POST[ $key ] ); // phpcs:ignore
				switch ( $def[1] ) {
					case 'email':
						$val = sanitize_email( $val );
						break;
					case 'number':
						$val = (float) str_replace( ',', '.', $val );
						break;
					default:
						$val = sanitize_text_field( $val );
				}
				if ( 'whatsapp' === $key ) {
					$val = lorie_digits( $val );
				}
				$data[ $key ] = $val;
			}
		}
		update_option( 'lorie_settings', $data );
		self::sync_free_shipping( (float) ( $data['free_shipping_min'] ?? 0 ) );

		// Login com Google (o segredo nunca é exibido de volta).
		if ( ! defined( 'LORIE_GOOGLE_CLIENT_ID' ) && isset( $_POST['google_client_id'] ) ) {
			update_option( 'lorie_google_client_id', sanitize_text_field( wp_unslash( $_POST['google_client_id'] ) ) );
		}
		$secret = sanitize_text_field( wp_unslash( $_POST['google_client_secret'] ?? '' ) );
		if ( ! defined( 'LORIE_GOOGLE_CLIENT_SECRET' ) && $secret && false === strpos( $secret, '•' ) ) {
			update_option( 'lorie_google_client_secret', $secret, false );
		}

		wp_safe_redirect( add_query_arg( 'lorie_msg', 'saved', admin_url( 'admin.php?page=lorie-config' ) ) );
		exit;
	}
}
