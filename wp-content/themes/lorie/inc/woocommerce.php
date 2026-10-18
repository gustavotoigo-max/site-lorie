<?php
/**
 * Ajustes do WooCommerce para o visual da Lorié.
 */

defined( 'ABSPATH' ) || exit;

// Estilos próprios no lugar dos estilos genéricos do WooCommerce.
add_filter( 'woocommerce_enqueue_styles', function ( $styles ) {
	unset( $styles['woocommerce-general'], $styles['woocommerce-layout'] );
	return $styles;
} );

// Remove wrappers e barra lateral padrão.
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
add_action( 'woocommerce_before_main_content', fn() => print( '<main id="main" class="site-main site-main--shop">' ), 10 );
add_action( 'woocommerce_after_main_content', fn() => print( '</main>' ), 10 );

// Texto dos botões.
add_filter( 'woocommerce_order_button_text', fn() => 'Confirmar pedido' );
add_filter( 'woocommerce_product_single_add_to_cart_text', fn() => 'Adicionar ao carrinho' );
add_filter( 'woocommerce_product_add_to_cart_text', function ( $text, $product ) {
	return $product && $product->is_purchasable() && $product->is_in_stock() ? 'Comprar' : 'Ver peça';
}, 10, 2 );

// Breadcrumb discreto.
add_filter( 'woocommerce_breadcrumb_defaults', function ( $args ) {
	$args['delimiter']   = '<span class="crumbs__sep">/</span>';
	$args['wrap_before'] = '<nav class="crumbs" aria-label="Você está em">';
	$args['wrap_after']  = '</nav>';
	$args['home']        = 'Início';
	return $args;
} );

// Produtos relacionados: 4 em linha.
add_filter( 'woocommerce_output_related_products_args', function ( $args ) {
	$args['posts_per_page'] = 4;
	$args['columns']        = 4;
	return $args;
} );
add_filter( 'woocommerce_product_related_products_heading', fn() => 'Você também vai amar' );
add_filter( 'loop_shop_per_page', fn() => 16 );

// "Continuar comprando" sempre leva ao catálogo.
add_filter( 'woocommerce_continue_shopping_redirect', fn() => wc_get_page_permalink( 'shop' ) );
add_filter( 'woocommerce_return_to_shop_redirect', fn() => wc_get_page_permalink( 'shop' ) );
add_filter( 'woocommerce_return_to_shop_text', fn() => 'Ver o catálogo' );

// Carrinho clássico: botão de continuar comprando e textos.
add_action( 'woocommerce_cart_actions', function () {
	echo '<a class="btn btn--ghost" href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '">' . lorie_icon( 'arrow-l', 18 ) . ' Continuar comprando</a>'; // phpcs:ignore
} );
remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20 );
add_action( 'woocommerce_proceed_to_checkout', function () {
	echo '<a href="' . esc_url( wc_get_checkout_url() ) . '" class="checkout-button btn btn--primary btn--block">Finalizar compra ' . lorie_icon( 'arrow-r', 18 ) . '</a>'; // phpcs:ignore
	echo '<a href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '" class="btn btn--ghost btn--block">Continuar comprando</a>';
	echo '<ul class="trust-mini"><li>' . lorie_icon( 'shield', 16 ) . ' Compra segura</li><li>' . lorie_icon( 'hand', 16 ) . ' Feito à mão</li><li>' . lorie_icon( 'truck', 16 ) . ' Envio rastreado</li></ul>'; // phpcs:ignore
}, 20 );
add_action( 'woocommerce_before_cart_totals', 'lorie_free_shipping_bar' );
add_action( 'woocommerce_review_order_before_payment', 'lorie_free_shipping_bar' );

function lorie_free_shipping_bar() {
	$min = (float) lorie_opt( 'free_shipping_min', 0 );
	if ( $min <= 0 || ! WC()->cart ) {
		return;
	}
	$sub  = (float) WC()->cart->get_displayed_subtotal();
	$left = max( 0, $min - $sub );
	$pct  = min( 100, $sub / $min * 100 );
	echo '<div class="ship-bar">';
	echo $left > 0
		? '<p>Faltam <strong>' . wp_kses_post( wc_price( $left ) ) . '</strong> para o <strong>frete grátis</strong></p>'
		: '<p>' . lorie_icon( 'check', 16 ) . ' Você ganhou <strong>frete grátis</strong>!</p>'; // phpcs:ignore
	echo '<div class="ship-bar__track"><span style="width:' . esc_attr( round( $pct, 1 ) ) . '%"></span></div></div>';
}

// Selos no card do produto.
function lorie_product_badges( $product ) {
	$out = array();
	if ( ! $product->is_in_stock() ) {
		$out[] = '<span class="badge badge--out">Esgotado</span>';
	} elseif ( $product->is_on_sale() ) {
		$reg  = (float) $product->get_regular_price();
		$sale = (float) $product->get_sale_price();
		if ( $product->is_type( 'variable' ) ) {
			$reg  = (float) $product->get_variation_regular_price( 'max' );
			$sale = (float) $product->get_variation_sale_price( 'min' );
		}
		$pct   = $reg > 0 ? round( ( $reg - $sale ) / $reg * 100 ) : 0;
		$out[] = '<span class="badge badge--sale">' . ( $pct ? '−' . $pct . '%' : 'Oferta' ) . '</span>';
	}
	$created = $product->get_date_created();
	if ( $created && ( time() - $created->getTimestamp() ) < 30 * DAY_IN_SECONDS && count( $out ) < 2 ) {
		$out[] = '<span class="badge badge--new">Novo</span>';
	}
	if ( $product->managing_stock() && $product->get_stock_quantity() > 0 && $product->get_stock_quantity() <= 2 ) {
		$out[] = '<span class="badge badge--low">Últimas unidades</span>';
	}
	return $out ? '<div class="badges">' . implode( '', $out ) . '</div>' : '';
}

/** Bolinhas de cor de um produto. */
function lorie_product_color_dots( $product, $max = 5 ) {
	if ( ! $product->is_type( 'variable' ) || ! taxonomy_exists( 'pa_cor' ) ) {
		return '';
	}
	$terms = wc_get_product_terms( $product->get_id(), 'pa_cor', array( 'fields' => 'all' ) );
	if ( ! $terms ) {
		return '';
	}
	$html = '<span class="dots" aria-label="Cores disponíveis">';
	foreach ( array_slice( $terms, 0, $max ) as $t ) {
		$hex   = class_exists( 'Lorie_Colors' ) ? Lorie_Colors::hex( $t ) : '#ddd';
		$html .= '<i style="--c:' . esc_attr( $hex ) . '" title="' . esc_attr( $t->name ) . '"></i>';
	}
	if ( count( $terms ) > $max ) {
		$html .= '<em>+' . ( count( $terms ) - $max ) . '</em>';
	}
	return $html . '</span>';
}

/**
 * Card de produto — usado no catálogo, na página inicial, nos relacionados e nos favoritos.
 */
function lorie_product_card( $product, $tag = 'li', $extra_class = '' ) {
	if ( is_numeric( $product ) ) {
		$product = wc_get_product( $product );
	}
	if ( ! $product || ! $product->is_visible() ) {
		return;
	}
	$id       = $product->get_id();
	$link     = $product->get_permalink();
	$gallery  = $product->get_gallery_image_ids();
	$alt_img  = $gallery ? $gallery[0] : 0;
	$cats     = wc_get_product_terms( $id, 'product_cat', array( 'fields' => 'names' ) );
	$simple   = $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock();
	$can_buy  = $product->is_purchasable() && $product->is_in_stock();
	$tag      = tag_escape( $tag );
	?>
	<<?php echo $tag; // phpcs:ignore ?> class="product-card <?php echo esc_attr( $extra_class ); ?>" data-product="<?php echo (int) $id; ?>">
		<div class="product-card__media">
			<a href="<?php echo esc_url( $link ); ?>" class="product-card__link" tabindex="-1" aria-hidden="true">
				<?php echo $product->get_image_id() ? wp_get_attachment_image( $product->get_image_id(), 'woocommerce_thumbnail', false, array( 'class' => 'product-card__img', 'loading' => 'lazy' ) ) : lorie_image( 0 ); // phpcs:ignore ?>
				<?php if ( $alt_img ) : ?>
					<?php echo wp_get_attachment_image( $alt_img, 'woocommerce_thumbnail', false, array( 'class' => 'product-card__img product-card__img--alt', 'loading' => 'lazy', 'alt' => '' ) ); ?>
				<?php endif; ?>
			</a>
			<?php echo lorie_product_badges( $product ); // phpcs:ignore ?>
			<button type="button" class="wish-btn" data-wish="<?php echo (int) $id; ?>" aria-label="Salvar nos favoritos" aria-pressed="false"><?php echo lorie_icon( 'heart', 20 ); // phpcs:ignore ?></button>
			<?php if ( $can_buy ) : ?>
				<button type="button" class="product-card__quick" data-quick="<?php echo (int) $id; ?>">
					<?php echo $simple ? 'Adicionar ao carrinho' : 'Escolher opções'; ?>
				</button>
			<?php endif; ?>
		</div>
		<div class="product-card__body">
			<?php if ( $cats ) : ?><span class="product-card__cat"><?php echo esc_html( $cats[0] ); ?></span><?php endif; ?>
			<h3 class="product-card__title"><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $product->get_name() ); ?></a></h3>
			<div class="product-card__row">
				<span class="price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
				<?php echo lorie_product_color_dots( $product ); // phpcs:ignore ?>
			</div>
			<?php if ( $can_buy ) : ?>
				<button type="button" class="btn btn--buy" <?php echo $simple ? 'data-add="' . (int) $id . '"' : 'data-quick="' . (int) $id . '"'; ?>>
					<?php echo lorie_icon( 'bag', 18 ); // phpcs:ignore ?> Comprar
				</button>
			<?php else : ?>
				<a class="btn btn--ghost btn--buy" href="<?php echo esc_url( $link ); ?>">Ver peça</a>
			<?php endif; ?>
		</div>
	</<?php echo $tag; // phpcs:ignore ?>>
	<?php
}

// Seletores de cor/tamanho em forma de amostras clicáveis (o select original continua, escondido).
add_filter( 'woocommerce_dropdown_variation_attribute_options_html', function ( $html, $args ) {
	$attr    = $args['attribute'];
	$product = $args['product'];
	if ( ! $product || ! taxonomy_exists( $attr ) ) {
		return $html;
	}
	$terms = wc_get_product_terms( $product->get_id(), $attr, array( 'fields' => 'all' ) );
	$terms = array_filter( $terms, fn( $t ) => in_array( $t->slug, (array) $args['options'], true ) );
	if ( ! $terms ) {
		return $html;
	}
	$is_color = 'pa_cor' === $attr;
	$out      = '<div class="swatches ' . ( $is_color ? 'swatches--color' : 'swatches--text' ) . '" data-for="' . esc_attr( 'attribute_' . sanitize_title( $attr ) ) . '" role="radiogroup">';
	foreach ( $terms as $t ) {
		if ( $is_color ) {
			$hex  = class_exists( 'Lorie_Colors' ) ? Lorie_Colors::hex( $t ) : '#ddd';
			$out .= '<button type="button" class="sw sw--color" role="radio" aria-checked="false" data-value="' . esc_attr( $t->slug ) . '" data-label="' . esc_attr( $t->name ) . '" title="' . esc_attr( $t->name ) . '" style="--c:' . esc_attr( $hex ) . '"><span class="screen-reader-text">' . esc_html( $t->name ) . '</span></button>';
		} else {
			$out .= '<button type="button" class="sw sw--text" role="radio" aria-checked="false" data-value="' . esc_attr( $t->slug ) . '" data-label="' . esc_attr( $t->name ) . '">' . esc_html( $t->name ) . '</button>';
		}
	}
	$out .= '</div>';
	return '<div class="swatch-select">' . $html . '</div>' . $out;
}, 10, 2 );

add_filter( 'woocommerce_reset_variations_link', fn() => '<a class="reset_variations" href="#">Limpar escolha</a>' );

// Página do produto: prazo de produção.
function lorie_production_days( $product ) {
	$d = (int) $product->get_meta( '_lorie_production_days' );
	return $d ? $d : (int) lorie_opt( 'production_days', 10 );
}

// Minha conta: textos do menu.
add_filter( 'woocommerce_account_menu_items', function ( $items ) {
	$rename = array( 'dashboard' => 'Início', 'orders' => 'Meus pedidos', 'edit-address' => 'Endereço', 'edit-account' => 'Meus dados', 'customer-logout' => 'Sair' );
	foreach ( $rename as $k => $v ) {
		if ( isset( $items[ $k ] ) ) {
			$items[ $k ] = $v;
		}
	}
	return $items;
}, 20 );

// Remove o campo de senha da edição de conta (o acesso é por link/Google).
add_filter( 'woocommerce_save_account_details_required_fields', function ( $f ) {
	return $f;
} );

// Checkout: um título por bloco.
add_filter( 'woocommerce_checkout_fields', function ( $fields ) {
	foreach ( array( 'billing_first_name', 'billing_last_name' ) as $k ) {
		if ( isset( $fields['billing'][ $k ] ) ) {
			$fields['billing'][ $k ]['priority'] = 'billing_first_name' === $k ? 10 : 11;
		}
	}
	return $fields;
}, 30 );
