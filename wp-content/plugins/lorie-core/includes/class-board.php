<?php
/**
 * Encomendas (somente para a dona/administradores): quadro com os pedidos ativos
 * e a lista de produção — o que precisa ser feito, peça por peça.
 */

defined( 'ABSPATH' ) || exit;

class Lorie_Board {

	public static function init() {
		add_action( 'wp_ajax_lorie_order_status', array( __CLASS__, 'ajax_status' ) );
		add_action( 'wp_ajax_lorie_item_done', array( __CLASS__, 'ajax_item_done' ) );
	}

	public static function columns() {
		return array(
			'processing'       => array( 'Novos', 'Pagos, prontos para começar' ),
			'lorie-review'     => array( 'Avaliar personalização', 'Decida se dá para fazer' ),
			'lorie-production' => array( 'Em produção', 'Mãos à obra' ),
		);
	}

	/** Transições permitidas a partir do painel. */
	public static function allowed( $from, $to ) {
		$map = array(
			'processing'       => array( 'lorie-production', 'lorie-review' ),
			'lorie-review'     => array( 'lorie-production' ),
			'lorie-production' => array( 'lorie-ready', 'processing' ),
			'lorie-ready'      => array( 'lorie-shipped', 'lorie-production' ),
			'lorie-shipped'    => array( 'completed', 'lorie-ready' ),
		);
		return in_array( $to, $map[ $from ] ?? array(), true );
	}

	public static function ajax_status() {
		check_ajax_referer( 'lorie_admin', 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => 'Sem permissão.' ), 403 );
		}
		$order = wc_get_order( absint( $_POST['order_id'] ?? 0 ) );
		$to    = sanitize_key( $_POST['to'] ?? '' );
		if ( ! $order ) {
			wp_send_json_error( array( 'message' => 'Pedido não encontrado.' ) );
		}
		$from = $order->get_status();
		if ( $from === $to ) {
			wp_send_json_success( array( 'message' => 'Nada a mudar.' ) );
		}
		if ( ! self::allowed( $from, $to ) ) {
			wp_send_json_error( array( 'message' => 'Mudança de status não permitida.' ) );
		}
		if ( isset( $_POST['tracking'] ) ) {
			$order->update_meta_data( '_lorie_tracking', strtoupper( sanitize_text_field( wp_unslash( $_POST['tracking'] ) ) ) );
			$order->save();
		}
		$labels = wc_get_order_statuses();
		$order->update_status( $to );
		lorie_order_log( $order, 'Status: ' . ( $labels[ 'wc-' . $from ] ?? $from ) . ' → ' . ( $labels[ 'wc-' . $to ] ?? $to ) );
		wp_send_json_success( array( 'message' => 'Pedido atualizado.' ) );
	}

	public static function ajax_item_done() {
		check_ajax_referer( 'lorie_admin', 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array(), 403 );
		}
		$item = WC_Order_Factory::get_order_item( absint( $_POST['item_id'] ?? 0 ) );
		if ( ! $item ) {
			wp_send_json_error();
		}
		$done = ! empty( $_POST['done'] );
		if ( $done ) {
			$item->update_meta_data( '_lorie_done', 1 );
		} else {
			$item->delete_meta_data( '_lorie_done' );
		}
		$item->save();
		wp_send_json_success();
	}

	private static function age( $order ) {
		$d = $order->get_date_paid() ? $order->get_date_paid() : $order->get_date_created();
		if ( ! $d ) {
			return array( 0, '' );
		}
		$days = (int) floor( ( time() - $d->getTimestamp() ) / DAY_IN_SECONDS );
		return array( $days, 0 === $days ? 'hoje' : ( 1 === $days ? 'há 1 dia' : "há {$days} dias" ) );
	}

	public static function page() {
		$cols   = self::columns();
		$orders = wc_get_orders( array( 'status' => array_keys( $cols ), 'limit' => 500, 'orderby' => 'date', 'order' => 'ASC' ) );
		$by     = array_fill_keys( array_keys( $cols ), array() );
		foreach ( $orders as $o ) {
			$by[ $o->get_status() ][] = $o;
		}

		// Lista de produção: tudo que ainda não foi marcado como feito.
		$todo = array();
		foreach ( $orders as $o ) {
			if ( 'lorie-review' === $o->get_status() ) {
				continue;
			}
			foreach ( lorie_order_items( $o ) as $it ) {
				if ( $it['done'] ) {
					continue;
				}
				$key = $it['name'] . '|' . $it['variant'] . '|' . $it['custom'];
				if ( ! isset( $todo[ $key ] ) ) {
					$todo[ $key ] = $it + array( 'orders' => array(), 'total' => 0 );
				}
				$todo[ $key ]['total']   += $it['qty'];
				$todo[ $key ]['orders'][] = '#' . $o->get_order_number();
			}
		}

		Lorie_Admin::header( 'Encomendas', count( $orders ) . ' pedido(s) ativo(s). Arraste nada — use os botões de cada cartão.' );
		?>
		<div id="lorie-board-root">
		<section class="lorie-card lorie-todo">
			<h2 class="lorie-h2">Lista de produção <small class="lorie-muted"><?php echo (int) array_sum( array_column( $todo, 'total' ) ); ?> peça(s) a fazer</small></h2>
			<?php if ( ! $todo ) : ?>
				<p class="lorie-empty">Nada na fila. Hora do café ☕</p>
			<?php else : ?>
				<ul class="lorie-todo__list">
					<?php foreach ( $todo as $t ) : ?>
						<li>
							<strong class="lorie-todo__qty"><?php echo (int) $t['total']; ?>×</strong>
							<span><?php echo esc_html( $t['name'] ); ?><?php if ( $t['variant'] ) : ?> <small class="lorie-muted">— <?php echo esc_html( $t['variant'] ); ?></small><?php endif; ?>
							<?php if ( $t['custom'] ) : ?><em class="lorie-todo__custom">✎ <?php echo esc_html( $t['custom'] ); ?></em><?php endif; ?></span>
							<small class="lorie-muted"><?php echo esc_html( implode( ', ', array_unique( $t['orders'] ) ) ); ?></small>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>

		<div class="lorie-board">
			<?php foreach ( $cols as $status => $meta ) : ?>
				<section class="lorie-board__col lorie-board__col--<?php echo esc_attr( $status ); ?>">
					<header><h2><?php echo esc_html( $meta[0] ); ?> <span class="lorie-count"><?php echo count( $by[ $status ] ); ?></span></h2><p><?php echo esc_html( $meta[1] ); ?></p></header>
					<?php if ( ! $by[ $status ] ) : ?>
						<p class="lorie-empty">Vazio</p>
					<?php endif; ?>
					<?php foreach ( $by[ $status ] as $o ) : ?>
						<?php self::card( $o ); ?>
					<?php endforeach; ?>
				</section>
			<?php endforeach; ?>
		</div>
		</div>
		<?php self::refund_modal(); ?>
		<?php
		Lorie_Admin::footer();
	}

	public static function card( $o ) {
		list( $days, $age ) = self::age( $o );
		$status   = $o->get_status();
		$deadline = (int) lorie_get( 'production_days', 10 );
		$late     = $days > $deadline;
		$pay      = lorie_pay_status( $o );
		?>
		<article class="lorie-order <?php echo $late ? 'is-late' : ''; ?>" data-order="<?php echo (int) $o->get_id(); ?>">
			<header class="lorie-order__head">
				<a href="<?php echo esc_url( $o->get_edit_order_url() ); ?>" class="lorie-order__num">#<?php echo esc_html( $o->get_order_number() ); ?></a>
				<span class="lorie-order__age <?php echo $late ? 'is-late' : ''; ?>" title="Prazo padrão: <?php echo (int) $deadline; ?> dias"><?php echo esc_html( $age ); ?></span>
			</header>
			<div class="lorie-order__client">
				<strong><?php echo esc_html( $o->get_formatted_billing_full_name() ); ?></strong>
				<span class="lorie-muted"><?php echo esc_html( $o->get_billing_city() . ' — ' . $o->get_billing_state() ); ?></span>
				<a class="lorie-wa" target="_blank" rel="noopener" href="https://wa.me/55<?php echo esc_attr( ltrim( lorie_digits( $o->get_billing_phone() ), '0' ) ); ?>">WhatsApp ↗</a>
			</div>
			<ul class="lorie-order__items">
				<?php foreach ( lorie_order_items( $o ) as $it ) : ?>
					<li class="<?php echo $it['done'] ? 'is-done' : ''; ?>">
						<label>
							<input type="checkbox" data-item-done="<?php echo (int) $it['id']; ?>" <?php checked( $it['done'] ); ?> <?php disabled( 'lorie-review' === $status ); ?>>
							<span><b><?php echo (int) $it['qty']; ?>×</b> <?php echo esc_html( $it['name'] ); ?><?php if ( $it['variant'] ) : ?><small><?php echo esc_html( $it['variant'] ); ?></small><?php endif; ?></span>
						</label>
						<?php if ( $it['custom'] ) : ?><p class="lorie-order__custom">✎ <?php echo esc_html( $it['custom'] ); ?></p><?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php if ( $o->get_customer_note() ) : ?>
				<p class="lorie-order__note">“<?php echo esc_html( $o->get_customer_note() ); ?>”</p>
			<?php endif; ?>
			<footer class="lorie-order__foot">
				<span class="lorie-order__total"><?php echo esc_html( Lorie_Admin::money( $o->get_total() ) ); ?> <span class="lorie-pay lorie-pay--<?php echo esc_attr( $pay ); ?>"><?php echo esc_html( lorie_pay_status_labels()[ $pay ] ?? '' ); ?></span></span>
				<div class="lorie-order__actions">
					<?php if ( 'processing' === $status ) : ?>
						<button class="lorie-btn lorie-btn--sm lorie-btn--primary" data-move="lorie-production">Iniciar produção</button>
					<?php elseif ( 'lorie-review' === $status ) : ?>
						<button class="lorie-btn lorie-btn--sm lorie-btn--primary" data-move="lorie-production">Aceitar e produzir</button>
						<button class="lorie-btn lorie-btn--sm lorie-btn--danger" data-refund data-total="<?php echo esc_attr( Lorie_Admin::money( $o->get_total() ) ); ?>" data-method="<?php echo esc_attr( $o->get_meta( '_lorie_payment_label' ) ? $o->get_meta( '_lorie_payment_label' ) : $o->get_payment_method_title() ); ?>" data-num="<?php echo esc_attr( $o->get_order_number() ); ?>">Recusar e reembolsar</button>
					<?php elseif ( 'lorie-production' === $status ) : ?>
						<button class="lorie-btn lorie-btn--sm lorie-btn--primary" data-move="lorie-ready">Pronto para envio →</button>
					<?php endif; ?>
					<?php if ( in_array( $status, array( 'processing', 'lorie-production' ), true ) && in_array( $pay, array( 'approved', 'refund_failed' ), true ) ) : ?>
						<button class="lorie-link-btn lorie-link-btn--danger" data-refund data-total="<?php echo esc_attr( Lorie_Admin::money( $o->get_total() ) ); ?>" data-method="<?php echo esc_attr( $o->get_meta( '_lorie_payment_label' ) ? $o->get_meta( '_lorie_payment_label' ) : $o->get_payment_method_title() ); ?>" data-num="<?php echo esc_attr( $o->get_order_number() ); ?>">Reembolsar</button>
					<?php endif; ?>
				</div>
			</footer>
		</article>
		<?php
	}

	public static function refund_modal() {
		?>
		<dialog class="lorie-modal" id="lorie-refund-modal">
			<form method="dialog" class="lorie-modal__box" data-refund-form>
				<h2 class="lorie-h2">Reembolsar pedido <span data-r-num></span></h2>
				<dl class="lorie-dl">
					<dt>Valor a devolver</dt><dd data-r-total></dd>
					<dt>Meio de pagamento</dt><dd data-r-method></dd>
				</dl>
				<div class="lorie-field">
					<label for="lorie-refund-reason">Motivo (fica no histórico do pedido)</label>
					<textarea id="lorie-refund-reason" rows="3" placeholder="Ex.: a personalização pedida não é possível com este ponto."></textarea>
				</div>
				<label class="lorie-check-sm"><input type="checkbox" data-r-confirm> Confirmo que quero devolver o valor integral para a cliente.</label>
				<p class="lorie-muted">Pix costuma voltar rápido, mas não é garantido que seja instantâneo. No cartão, o crédito depende do banco e pode aparecer em faturas seguintes.</p>
				<div class="lorie-modal__actions">
					<button type="button" class="lorie-btn" data-close>Cancelar</button>
					<button type="button" class="lorie-btn lorie-btn--danger" data-r-go disabled>Reembolsar</button>
				</div>
				<p class="lorie-form__msg" data-r-msg></p>
			</form>
		</dialog>
		<?php
	}
}
