<?php
/**
 * Configurações de pagamento controladas pela artesã (seção 5 da especificação):
 * Pix ligado/desligado, cartão ligado/desligado, máximo de parcelas, histórico de alterações,
 * e o modo de teste com o código COMPREAQUI desta versão.
 */

defined( 'ABSPATH' ) || exit;

class Lorie_Payments {

	const OPTION = 'lorie_payment_settings';
	const LOG    = 'lorie_payment_log';

	public static function init() {
		add_action( 'admin_post_lorie_save_payments', array( __CLASS__, 'save' ) );
		add_action( 'woocommerce_before_cart', array( __CLASS__, 'test_hint' ) );
	}

	public static function get() {
		$saved = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), array(
			'pix_enabled'         => true,
			'credit_card_enabled' => true,
			'max_installments'    => 3,
			'test_mode'           => true,
			'test_code'           => 'COMPREAQUI',
			'show_hint'           => true,
		) );
	}

	public static function save() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( 'Sem permissão.' );
		}
		check_admin_referer( 'lorie_save_payments' );
		$old = self::get();
		$new = array(
			'pix_enabled'         => ! empty( $_POST['pix_enabled'] ),
			'credit_card_enabled' => ! empty( $_POST['credit_card_enabled'] ),
			'max_installments'    => max( 1, min( 12, absint( $_POST['max_installments'] ?? 1 ) ) ),
			'test_mode'           => ! empty( $_POST['test_mode'] ),
			'test_code'           => strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', wp_unslash( $_POST['test_code'] ?? '' ) ) ),
			'show_hint'           => ! empty( $_POST['show_hint'] ),
		);
		if ( ! $new['test_code'] ) {
			$new['test_code'] = 'COMPREAQUI';
		}
		$back = admin_url( 'admin.php?page=lorie-pagamentos' );

		// Regra: a loja não pode ficar sem nenhuma forma de pagamento ativa.
		if ( ! $new['pix_enabled'] && ! $new['credit_card_enabled'] && ! $new['test_mode'] ) {
			wp_safe_redirect( add_query_arg( 'lorie_msg', 'none', $back ) );
			exit;
		}

		$changes = array();
		$labels  = array(
			'pix_enabled'         => 'Pix',
			'credit_card_enabled' => 'Cartão de crédito',
			'max_installments'    => 'Máximo de parcelas',
			'test_mode'           => 'Pagamento de teste',
			'test_code'           => 'Código de teste',
			'show_hint'           => 'Dica do código no carrinho',
		);
		foreach ( $labels as $k => $label ) {
			if ( $old[ $k ] !== $new[ $k ] ) {
				$fmt       = fn( $v ) => is_bool( $v ) ? ( $v ? 'ativado' : 'desativado' ) : (string) $v;
				$changes[] = $label . ': ' . $fmt( $old[ $k ] ) . ' → ' . $fmt( $new[ $k ] );
			}
		}
		update_option( self::OPTION, $new );

		// Mantém o gateway de teste do WooCommerce sincronizado com a chave "Pagamento de teste".
		$gw            = get_option( 'woocommerce_lorie_test_settings', array() );
		$gw            = is_array( $gw ) ? $gw : array();
		$gw['enabled'] = $new['test_mode'] ? 'yes' : 'no';
		update_option( 'woocommerce_lorie_test_settings', $gw );

		if ( $changes ) {
			$log   = get_option( self::LOG, array() );
			$log   = is_array( $log ) ? $log : array();
			$user  = wp_get_current_user();
			array_unshift( $log, array( 't' => time(), 'user' => $user->display_name, 'changes' => $changes ) );
			update_option( self::LOG, array_slice( $log, 0, 100 ), false );
		}
		wp_safe_redirect( add_query_arg( 'lorie_msg', 'saved', $back ) );
		exit;
	}

	/**
	 * Configuração que será enviada ao Mercado Pago (Checkout Pro) ao criar cada nova preferência.
	 * É calculada no servidor a cada checkout — esconder botões no navegador não basta.
	 *
	 * ATENÇÃO (próxima versão): confirmar os nomes exatos dos campos e IDs de tipos de pagamento
	 * na documentação oficial vigente antes de usar em produção. Exemplo de estrutura:
	 *   'payment_methods' => [ 'excluded_payment_types' => [ ['id' => 'ticket'] ], 'installments' => 3 ]
	 */
	public static function mercado_pago_payment_methods() {
		$s        = self::get();
		$excluded = array( array( 'id' => 'ticket' ), array( 'id' => 'atm' ), array( 'id' => 'debit_card' ) );
		if ( ! $s['pix_enabled'] ) {
			$excluded[] = array( 'id' => 'bank_transfer' ); // Pix
		}
		if ( ! $s['credit_card_enabled'] ) {
			$excluded[] = array( 'id' => 'credit_card' );
		}
		return array(
			'excluded_payment_types' => $excluded,
			'installments'           => $s['credit_card_enabled'] ? (int) $s['max_installments'] : 1,
		);
	}

	public static function test_hint() {
		$s = self::get();
		if ( $s['test_mode'] && $s['show_hint'] ) {
			echo '<div class="lorie-alert lorie-alert--test"><strong>Loja em fase de testes:</strong> nenhum valor real é cobrado. No pagamento, use o código <code>' . esc_html( $s['test_code'] ) . '</code>.</div>';
		}
	}
}
