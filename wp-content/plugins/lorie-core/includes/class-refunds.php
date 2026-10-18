<?php
/**
 * Reembolso iniciado manualmente pela artesã (seção 4 da especificação):
 * confirmação explícita, trava contra cliques duplicados, estados separados e histórico auditável.
 */

defined( 'ABSPATH' ) || exit;

class Lorie_Refunds {

	public static function init() {
		add_action( 'wp_ajax_lorie_refund', array( __CLASS__, 'ajax_refund' ) );
	}

	public static function ajax_refund() {
		check_ajax_referer( 'lorie_admin', 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => 'Sem permissão.' ), 403 );
		}
		$order_id = absint( $_POST['order_id'] ?? 0 );
		$reason   = sanitize_textarea_field( wp_unslash( $_POST['reason'] ?? '' ) );
		$confirm  = ! empty( $_POST['confirm'] );
		$result   = self::refund( $order_id, $reason, $confirm );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		wp_send_json_success( array( 'message' => $result ) );
	}

	/**
	 * @return string|WP_Error Mensagem de resultado.
	 */
	public static function refund( $order_id, $reason, $confirmed ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return new WP_Error( 'lorie', 'Pedido não encontrado.' );
		}
		if ( ! $confirmed ) {
			return new WP_Error( 'lorie', 'Confirme o reembolso marcando a caixa de confirmação.' );
		}
		$status = lorie_pay_status( $order );
		if ( in_array( $status, array( 'refunding', 'refunded' ), true ) ) {
			return new WP_Error( 'lorie', 'Este pedido já tem um reembolso ' . ( 'refunded' === $status ? 'concluído.' : 'em processamento.' ) );
		}
		if ( ! $order->is_paid() && 'refund_failed' !== $status ) {
			return new WP_Error( 'lorie', 'Só é possível reembolsar pedidos pagos.' );
		}
		$amount = (float) $order->get_total() - (float) $order->get_total_refunded();
		if ( $amount <= 0 ) {
			return new WP_Error( 'lorie', 'Não há valor a reembolsar.' );
		}

		// Trava atômica: add_option falha se a chave já existir (evita duplo clique / requisição repetida).
		$lock = 'lorie_refund_lock_' . $order_id;
		if ( ! add_option( $lock, time(), '', false ) ) {
			if ( (int) get_option( $lock ) > time() - 120 ) {
				return new WP_Error( 'lorie', 'Já existe um reembolso sendo processado para este pedido. Aguarde.' );
			}
			update_option( $lock, time(), false );
		}

		try {
			$order->update_meta_data( '_lorie_pay_status', 'refunding' );
			$order->save();
			lorie_order_log( $order, sprintf( 'Reembolso solicitado: %s. Motivo: %s', html_entity_decode( wp_strip_all_tags( wc_price( $amount ) ), ENT_QUOTES, 'UTF-8' ), $reason ? $reason : 'personalização inviável' ) );

			$refund = wc_create_refund( array(
				'order_id'       => $order_id,
				'amount'         => $amount,
				'reason'         => $reason ? $reason : 'Personalização inviável',
				'refund_payment' => true,
				'restock_items'  => true,
			) );

			$order = wc_get_order( $order_id );
			if ( is_wp_error( $refund ) ) {
				$order->update_meta_data( '_lorie_pay_status', 'refund_failed' );
				$order->save();
				lorie_order_log( $order, 'Reembolso FALHOU: ' . $refund->get_error_message() );
				return new WP_Error( 'lorie', 'O provedor não aceitou o reembolso: ' . $refund->get_error_message() . ' Verifique o saldo/conta e tente novamente.' );
			}

			$async = 'yes' === $order->get_meta( '_lorie_refund_async' ); // reservado ao Mercado Pago (confirmação por webhook)
			$order->update_meta_data( '_lorie_pay_status', $async ? 'refunding' : 'refunded' );
			$order->update_meta_data( '_lorie_refund_id', $refund->get_id() );
			$order->save();
			lorie_order_log( $order, $async ? 'Reembolso enviado ao provedor; aguardando confirmação.' : 'Reembolso concluído (pagamento de teste).' );

			return $async
				? 'Reembolso solicitado. Ele aparecerá como concluído quando o provedor confirmar.'
				: 'Reembolso registrado. O pedido foi marcado como reembolsado.';
		} finally {
			delete_option( $lock );
		}
	}
}
