<?php
/**
 * Compra rápida (abre num modal a partir do card do produto).
 */

defined( 'ABSPATH' ) || exit;
global $product;
$images = array_filter( array_merge( array( $product->get_image_id() ), $product->get_gallery_image_ids() ) );
$cats   = wc_get_product_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) );
?>
<div class="qv">
	<div class="qv__media swiper" data-qv-swiper>
		<div class="swiper-wrapper">
			<?php foreach ( $images ? $images : array( 0 ) as $img ) : ?>
				<div class="swiper-slide"><?php echo $img ? wp_get_attachment_image( $img, 'large' ) : lorie_image( 0 ); // phpcs:ignore ?></div>
			<?php endforeach; ?>
		</div>
		<?php if ( count( $images ) > 1 ) : ?><div class="swiper-pagination"></div><?php endif; ?>
	</div>
	<div class="qv__info">
		<?php if ( $cats ) : ?><span class="eyebrow"><?php echo esc_html( $cats[0] ); ?></span><?php endif; ?>
		<h2 class="qv__title"><?php echo esc_html( $product->get_name() ); ?></h2>
		<div class="qv__price price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
		<div class="qv__desc"><?php echo wp_kses_post( wpautop( wp_trim_words( wp_strip_all_tags( $product->get_short_description() ), 32 ) ) ); ?></div>
		<div class="qv__form pdp-form"><?php woocommerce_template_single_add_to_cart(); ?></div>
		<a class="link-arrow" href="<?php echo esc_url( $product->get_permalink() ); ?>">Ver todos os detalhes <?php echo lorie_icon( 'arrow-r', 16 ); // phpcs:ignore ?></a>
	</div>
</div>
