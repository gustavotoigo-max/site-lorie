<?php
/**
 * Endpoints rápidos (wc-ajax) do carrinho lateral, compra rápida, busca e favoritos.
 */

defined( 'ABSPATH' ) || exit;

$lorie_wc_ajax = array( 'lorie_cart', 'lorie_add', 'lorie_qty', 'lorie_quick', 'lorie_search', 'lorie_wishlist' );
foreach ( $lorie_wc_ajax as $a ) {
	add_action( 'wc_ajax_' . $a, 'lorie_ajax_' . substr( $a, 6 ) );
}

function lorie_cart_count() {
	return WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
}

/** HTML do carrinho lateral. */
function lorie_cart_drawer_html() {
	ob_start();
	$cart = WC()->cart;
	if ( ! $cart || $cart->is_empty() ) {
		?>
		<div class="drawer__empty">
			<div class="drawer__empty-icon"><?php echo lorie_icon( 'bag', 40 ); // phpcs:ignore ?></div>
			<p class="drawer__empty-title">Seu carrinho está vazio</p>
			<p>Que tal dar uma olhada nas peças novas?</p>
			<a class="btn btn--primary" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Ver catálogo</a>
		</div>
		<?php
		return ob_get_clean();
	}
	?>
	<div class="drawer__ship"><?php lorie_free_shipping_bar(); ?></div>
	<ul class="drawer__items">
		<?php
		foreach ( $cart->get_cart() as $key => $item ) :
			$p = $item['data'];
			if ( ! $p || ! $p->exists() || $item['quantity'] <= 0 ) {
				continue;
			}
			$link = $p->is_visible() ? $p->get_permalink( $item ) : '';
			$meta = wc_get_formatted_cart_item_data( $item, true );
			$max  = $p->get_max_purchase_quantity();
			?>
			<li class="drawer__item" data-key="<?php echo esc_attr( $key ); ?>">
				<a class="drawer__thumb" href="<?php echo esc_url( $link ); ?>"><?php echo $p->get_image( 'woocommerce_gallery_thumbnail' ); // phpcs:ignore ?></a>
				<div class="drawer__info">
					<a class="drawer__name" href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $p->get_title() ); ?></a>
					<?php if ( $meta ) : ?><div class="drawer__meta"><?php echo nl2br( esc_html( $meta ) ); ?></div><?php endif; ?>
					<div class="drawer__row">
						<div class="qty" data-qty>
							<button type="button" data-step="-1" aria-label="Diminuir"><?php echo lorie_icon( 'minus', 16 ); // phpcs:ignore ?></button>
							<input type="number" min="0" <?php echo $max > 0 ? 'max="' . (int) $max . '"' : ''; ?> value="<?php echo (int) $item['quantity']; ?>" aria-label="Quantidade">
							<button type="button" data-step="1" aria-label="Aumentar"><?php echo lorie_icon( 'plus', 16 ); // phpcs:ignore ?></button>
						</div>
						<span class="drawer__price"><?php echo wp_kses_post( $cart->get_product_subtotal( $p, $item['quantity'] ) ); ?></span>
					</div>
				</div>
				<button type="button" class="drawer__rm" data-remove aria-label="Remover"><?php echo lorie_icon( 'trash', 18 ); // phpcs:ignore ?></button>
			</li>
		<?php endforeach; ?>
	</ul>
	<div class="drawer__foot">
		<div class="drawer__total"><span>Subtotal</span><strong><?php echo wp_kses_post( $cart->get_cart_subtotal() ); ?></strong></div>
		<p class="drawer__note">Frete e descontos calculados na finalização.</p>
		<a class="btn btn--primary btn--block" href="<?php echo esc_url( wc_get_checkout_url() ); ?>">Finalizar compra <?php echo lorie_icon( 'arrow-r', 18 ); // phpcs:ignore ?></a>
		<div class="drawer__links">
			<a href="<?php echo esc_url( wc_get_cart_url() ); ?>">Ver carrinho</a>
			<button type="button" data-drawer-close>Continuar comprando</button>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

function lorie_cart_response( $extra = array() ) {
	WC()->cart->calculate_totals();
	wp_send_json_success( array_merge( array(
		'html'  => lorie_cart_drawer_html(),
		'count' => lorie_cart_count(),
	), $extra ) );
}

function lorie_ajax_cart() {
	lorie_cart_response();
}

function lorie_ajax_add() {
	// phpcs:disable WordPress.Security.NonceVerification
	$product_id   = absint( $_POST['product_id'] ?? 0 );
	$variation_id = absint( $_POST['variation_id'] ?? 0 );
	$qty          = max( 1, wc_stock_amount( wp_unslash( $_POST['quantity'] ?? 1 ) ) );
	$product      = wc_get_product( $variation_id ? $variation_id : $product_id );
	if ( ! $product ) {
		wp_send_json_error( array( 'message' => 'Produto não encontrado.' ) );
	}
	if ( $product->is_type( 'variation' ) ) {
		$product_id = $product->get_parent_id();
	}
	$parent     = wc_get_product( $product_id );
	$variations = array();
	foreach ( $_POST as $k => $v ) {
		if ( 0 === strpos( $k, 'attribute_' ) ) {
			$variations[ sanitize_title( wp_unslash( $k ) ) ] = wc_clean( wp_unslash( $v ) );
		}
	}
	// phpcs:enable
	if ( $parent && $parent->is_type( 'variable' ) && ! $variation_id ) {
		$variation_id = ( new WC_Product_Data_Store_CPT() )->find_matching_product_variation( $parent, $variations );
		if ( ! $variation_id ) {
			wp_send_json_error( array( 'message' => 'Escolha as opções da peça (cor/tamanho).', 'needs_options' => true, 'url' => $parent->get_permalink() ) );
		}
	}

	$ok = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $qty, $variation_id, $variations );
	if ( $ok ) {
		$ok = WC()->cart->add_to_cart( $product_id, $qty, $variation_id, $variations );
	}
	if ( ! $ok ) {
		$errors = wc_get_notices( 'error' );
		wc_clear_notices();
		$msg = $errors ? wp_strip_all_tags( is_array( $errors[0] ) ? $errors[0]['notice'] : $errors[0] ) : 'Não foi possível adicionar ao carrinho.';
		wp_send_json_error( array( 'message' => $msg ) );
	}
	wc_clear_notices();
	do_action( 'woocommerce_ajax_added_to_cart', $product_id );
	lorie_cart_response( array( 'message' => 'Adicionado ao carrinho' ) );
}

function lorie_ajax_qty() {
	$key = sanitize_text_field( wp_unslash( $_POST['key'] ?? '' ) ); // phpcs:ignore
	$qty = wc_stock_amount( wp_unslash( $_POST['qty'] ?? 0 ) ); // phpcs:ignore
	if ( $key && WC()->cart->get_cart_item( $key ) ) {
		if ( $qty <= 0 ) {
			WC()->cart->remove_cart_item( $key );
		} else {
			WC()->cart->set_quantity( $key, $qty, true );
		}
	}
	wc_clear_notices();
	lorie_cart_response();
}

function lorie_ajax_quick() {
	$id      = absint( $_GET['id'] ?? 0 ); // phpcs:ignore
	$product = wc_get_product( $id );
	if ( ! $product || ! $product->is_visible() ) {
		wp_send_json_error();
	}
	global $post;
	$post = get_post( $id ); // phpcs:ignore
	setup_postdata( $post );
	$GLOBALS['product'] = $product;
	ob_start();
	get_template_part( 'template-parts/quick-view' );
	$html = ob_get_clean();
	wp_reset_postdata();
	wp_send_json_success( array( 'html' => $html ) );
}

function lorie_ajax_search() {
	$q = sanitize_text_field( wp_unslash( $_GET['q'] ?? '' ) ); // phpcs:ignore
	if ( mb_strlen( $q ) < 2 ) {
		wp_send_json_success( array( 'items' => array() ) );
	}
	$products = wc_get_products( array( 's' => $q, 'limit' => 6, 'status' => 'publish', 'visibility' => 'search' ) );
	$items    = array();
	foreach ( $products as $p ) {
		$items[] = array(
			'name'  => $p->get_name(),
			'url'   => $p->get_permalink(),
			'price' => $p->get_price_html(),
			'img'   => wp_get_attachment_image_url( $p->get_image_id(), 'woocommerce_gallery_thumbnail' ),
		);
	}
	wp_send_json_success( array(
		'items' => $items,
		'all'   => add_query_arg( array( 's' => rawurlencode( $q ), 'post_type' => 'product' ), home_url( '/' ) ),
	) );
}

function lorie_ajax_wishlist() {
	$ids = array_slice( array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_GET['ids'] ?? '' ) ) ) ) ), 0, 60 ); // phpcs:ignore
	ob_start();
	if ( $ids ) {
		echo '<ul class="products">';
		foreach ( $ids as $id ) {
			lorie_product_card( $id );
		}
		echo '</ul>';
	}
	wp_send_json_success( array( 'html' => ob_get_clean() ) );
}
