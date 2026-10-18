<?php
/**
 * Envios: encomendas prontas que faltam enviar/entregar. Gera a etiqueta (imagem PNG 10×15 cm)
 * com o logo da marca, destinatário e remetente — para imprimir e colar no pacote.
 */

defined( 'ABSPATH' ) || exit;

class Lorie_Shipping {

	public static function init() {}

	public static function label_data( $o ) {
		$ship = $o->has_shipping_address();
		$g    = fn( $f ) => $ship ? $o->{"get_shipping_{$f}"}() : $o->{"get_billing_{$f}"}();
		$num  = $ship && $o->get_meta( '_shipping_number' ) ? $o->get_meta( '_shipping_number' ) : $o->get_meta( '_billing_number' );
		$nb   = $ship && $o->get_meta( '_shipping_neighborhood' ) ? $o->get_meta( '_shipping_neighborhood' ) : $o->get_meta( '_billing_neighborhood' );
		$qty  = 0;
		foreach ( $o->get_items() as $it ) {
			$qty += $it->get_quantity();
		}
		return array(
			'order'    => $o->get_order_number(),
			'date'     => $o->get_date_created() ? $o->get_date_created()->date_i18n( 'd/m/Y' ) : '',
			'name'     => trim( $g( 'first_name' ) . ' ' . $g( 'last_name' ) ),
			'street'   => $g( 'address_1' ) . ( $num ? ', ' . $num : '' ),
			'comp'     => $g( 'address_2' ),
			'district' => $nb,
			'city'     => $g( 'city' ),
			'state'    => $g( 'state' ),
			'cep'      => lorie_format_cep( $g( 'postcode' ) ),
			'phone'    => lorie_format_phone( $o->get_billing_phone() ),
			'items'    => $qty . ' peça(s) de crochê',
			'tracking' => (string) $o->get_meta( '_lorie_tracking' ),
			'method'   => $o->get_shipping_method(),
		);
	}

	public static function page() {
		$ready   = wc_get_orders( array( 'status' => 'lorie-ready', 'limit' => 500, 'orderby' => 'date', 'order' => 'ASC' ) );
		$shipped = wc_get_orders( array( 'status' => 'lorie-shipped', 'limit' => 500, 'orderby' => 'date', 'order' => 'ASC' ) );

		Lorie_Admin::header( 'Envios', 'Encomendas finalizadas que ainda faltam ser enviadas ou entregues.' );
		?>
		<div id="lorie-board-root">
			<div class="lorie-ship-cols">
				<section>
					<h2 class="lorie-h2">A enviar <span class="lorie-count"><?php echo count( $ready ); ?></span></h2>
					<?php if ( ! $ready ) : ?><section class="lorie-card"><p class="lorie-empty">Nenhuma encomenda esperando envio.</p></section><?php endif; ?>
					<?php foreach ( $ready as $o ) { self::card( $o, 'ready' ); } ?>
				</section>
				<section>
					<h2 class="lorie-h2">A caminho <span class="lorie-count"><?php echo count( $shipped ); ?></span></h2>
					<?php if ( ! $shipped ) : ?><section class="lorie-card"><p class="lorie-empty">Nenhum pacote em trânsito.</p></section><?php endif; ?>
					<?php foreach ( $shipped as $o ) { self::card( $o, 'shipped' ); } ?>
				</section>
			</div>
		</div>

		<dialog class="lorie-modal lorie-modal--label" id="lorie-label-modal">
			<div class="lorie-modal__box">
				<h2 class="lorie-h2">Etiqueta de envio <small class="lorie-muted">10 × 15 cm</small></h2>
				<canvas id="lorie-label-canvas" width="1181" height="1772" aria-label="Pré-visualização da etiqueta"></canvas>
				<div class="lorie-modal__actions">
					<button type="button" class="lorie-btn" data-close>Fechar</button>
					<button type="button" class="lorie-btn" data-label-print>Imprimir</button>
					<a class="lorie-btn lorie-btn--primary" data-label-download download="etiqueta.png" href="#">Baixar imagem (PNG)</a>
				</div>
			</div>
		</dialog>
		<?php
		Lorie_Admin::footer();
	}

	public static function card( $o, $mode ) {
		$d = self::label_data( $o );
		?>
		<article class="lorie-card lorie-ship" data-order="<?php echo (int) $o->get_id(); ?>" data-label="<?php echo esc_attr( wp_json_encode( $d ) ); ?>">
			<div class="lorie-ship__head">
				<a class="lorie-order__num" href="<?php echo esc_url( $o->get_edit_order_url() ); ?>">#<?php echo esc_html( $d['order'] ); ?></a>
				<span class="lorie-muted"><?php echo esc_html( $d['date'] ); ?> · <?php echo esc_html( $d['method'] ? $d['method'] : 'Envio' ); ?></span>
			</div>
			<div class="lorie-ship__addr">
				<strong><?php echo esc_html( $d['name'] ); ?></strong><br>
				<?php echo esc_html( $d['street'] ); ?><?php echo $d['comp'] ? ' — ' . esc_html( $d['comp'] ) : ''; ?><br>
				<?php echo esc_html( $d['district'] . ' · ' . $d['city'] . '/' . $d['state'] ); ?><br>
				CEP <?php echo esc_html( $d['cep'] ); ?>
			</div>
			<ul class="lorie-ship__items">
				<?php foreach ( lorie_order_items( $o ) as $it ) : ?>
					<li><?php echo (int) $it['qty']; ?>× <?php echo esc_html( $it['name'] ); ?> <small class="lorie-muted"><?php echo esc_html( $it['variant'] ); ?></small></li>
				<?php endforeach; ?>
			</ul>
			<div class="lorie-ship__actions">
				<button type="button" class="lorie-btn lorie-btn--sm" data-make-label>🏷 Gerar etiqueta</button>
				<?php if ( 'ready' === $mode ) : ?>
					<input type="text" class="lorie-input-sm" data-tracking placeholder="Código de rastreio (opcional)" value="<?php echo esc_attr( $d['tracking'] ); ?>">
					<button type="button" class="lorie-btn lorie-btn--sm lorie-btn--primary" data-move="lorie-shipped" data-with-tracking>Marcar como enviado</button>
					<button type="button" class="lorie-link-btn" data-move="lorie-production">voltar p/ produção</button>
				<?php else : ?>
					<?php if ( $d['tracking'] ) : ?><span class="lorie-pill">Rastreio: <?php echo esc_html( $d['tracking'] ); ?></span><?php endif; ?>
					<button type="button" class="lorie-btn lorie-btn--sm lorie-btn--primary" data-move="completed">Marcar como entregue ✓</button>
				<?php endif; ?>
			</div>
		</article>
		<?php
	}
}
