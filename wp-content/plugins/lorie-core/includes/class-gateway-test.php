<?php
/**
 * Pagamento FICTÍCIO desta versão: o pedido é aprovado quando a cliente digita o código
 * configurado (padrão COMPREAQUI). Nenhum valor é cobrado. Será substituído pelo Mercado Pago.
 */

defined( 'ABSPATH' ) || exit;

class Lorie_Gateway_Test extends WC_Payment_Gateway {

	public function __construct() {
		$this->id                 = 'lorie_test';
		$this->method_title       = 'Lorié — Pagamento de teste (código)';
		$this->method_description = 'Aprova o pedido quando a cliente digita o código de teste (Lorié › Pagamentos). Não cobra nada. Use somente enquanto o Mercado Pago não estiver integrado.';
		$this->has_fields         = true;
		$this->supports           = array( 'products', 'refunds' );

		$this->init_form_fields();
		$this->init_settings();
		$this->title       = $this->get_option( 'title', 'Código de pagamento' );
		$this->description = $this->get_option( 'description' );
		$this->enabled     = $this->get_option( 'enabled', 'yes' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'     => array( 'title' => 'Ativo', 'type' => 'checkbox', 'label' => 'Ativar pagamento de teste', 'default' => 'yes' ),
			'title'       => array( 'title' => 'Título', 'type' => 'text', 'default' => 'Código de pagamento' ),
			'description' => array( 'title' => 'Descrição', 'type' => 'textarea', 'default' => 'Loja em fase de testes: nenhum valor é cobrado. Digite o código de pagamento para concluir seu pedido.' ),
		);
	}

	public function payment_fields() {
		if ( $this->description ) {
			echo '<p>' . wp_kses_post( $this->description ) . '</p>';
		}
		?>
		<p class="form-row form-row-wide lorie-paycode">
			<label for="lorie_paycode">Código <abbr class="required" title="obrigatório">*</abbr></label>
			<input id="lorie_paycode" name="lorie_paycode" type="text" class="input-text" autocomplete="off" autocapitalize="characters" placeholder="Digite o código">
		</p>
		<?php
	}

	private function code() {
		$s = Lorie_Payments::get();
		return strtoupper( $s['test_code'] ? $s['test_code'] : 'COMPREAQUI' );
	}

	public function validate_fields() {
		$typed = strtoupper( trim( sanitize_text_field( wp_unslash( $_POST['lorie_paycode'] ?? '' ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! hash_equals( $this->code(), $typed ) ) {
			wc_add_notice( 'Código de pagamento inválido.', 'error' );
			return false;
		}
		return true;
	}

	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		// Idempotência: um pedido já pago não é "pago" de novo.
		if ( $order->is_paid() ) {
			return array( 'result' => 'success', 'redirect' => $this->get_return_url( $order ) );
		}
		$order->update_meta_data( '_lorie_payment_label', 'Código de teste' );
		$order->save();
		$order->payment_complete( 'TESTE-' . $order->get_id() . '-' . time() );
		lorie_order_log( $order, 'Pagamento de teste aprovado com código.', 0 );
		WC()->cart->empty_cart();
		return array( 'result' => 'success', 'redirect' => $this->get_return_url( $order ) );
	}

	/** No teste o reembolso é concluído na hora; no Mercado Pago ele será assíncrono (via webhook). */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		return true;
	}
}
