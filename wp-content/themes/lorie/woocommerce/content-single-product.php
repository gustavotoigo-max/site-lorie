<?php
/**
 * Página do produto (layout Lorié): galeria à esquerda, compra à direita.
 *
 * @package WooCommerce\Templates
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

do_action( 'woocommerce_before_single_product' );
if ( post_password_required() ) {
	echo get_the_password_form(); // phpcs:ignore
	return;
}

$images = array_values( array_filter( array_merge( array( $product->get_image_id() ), $product->get_gallery_image_ids() ) ) );
$cats   = wc_get_product_terms( $product->get_id(), 'product_cat', array( 'fields' => 'all' ) );
$days   = lorie_production_days( $product );
$attrs  = array_filter( $product->get_attributes(), fn( $a ) => $a->get_visible() );
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'pdp', $product ); ?>>
	<div class="container">
		<?php woocommerce_breadcrumb(); ?>
		<div class="pdp__grid">
			<div class="pdp__gallery">
				<div class="swiper pdp__main" data-pdp-main>
					<div class="swiper-wrapper">
						<?php foreach ( $images ? $images : array( 0 ) as $img ) : ?>
							<div class="swiper-slide pdp__slide">
								<?php if ( $img ) : ?>
									<a href="<?php echo esc_url( wp_get_attachment_image_url( $img, 'full' ) ); ?>" data-zoom>
										<?php echo wp_get_attachment_image( $img, 'woocommerce_single', false, array( 'class' => 'pdp__img' ) ); ?>
									</a>
								<?php else : ?>
									<?php echo lorie_image( 0 ); // phpcs:ignore ?>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
					<?php echo lorie_product_badges( $product ); // phpcs:ignore ?>
					<button type="button" class="wish-btn wish-btn--lg" data-wish="<?php echo (int) $product->get_id(); ?>" aria-label="Salvar nos favoritos" aria-pressed="false"><?php echo lorie_icon( 'heart', 22 ); // phpcs:ignore ?></button>
					<?php if ( count( $images ) > 1 ) : ?>
						<button type="button" class="pdp__arrow pdp__arrow--prev" aria-label="Foto anterior"><?php echo lorie_icon( 'arrow-l' ); // phpcs:ignore ?></button>
						<button type="button" class="pdp__arrow pdp__arrow--next" aria-label="Próxima foto"><?php echo lorie_icon( 'arrow-r' ); // phpcs:ignore ?></button>
					<?php endif; ?>
				</div>
				<?php if ( count( $images ) > 1 ) : ?>
					<div class="swiper pdp__thumbs" data-pdp-thumbs>
						<div class="swiper-wrapper">
							<?php foreach ( $images as $img ) : ?>
								<div class="swiper-slide"><?php echo wp_get_attachment_image( $img, 'woocommerce_gallery_thumbnail' ); ?></div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>
			</div>

			<div class="pdp__summary">
				<?php if ( $cats ) : ?>
					<a class="eyebrow" href="<?php echo esc_url( get_term_link( $cats[0] ) ); ?>"><?php echo esc_html( $cats[0]->name ); ?></a>
				<?php endif; ?>
				<h1 class="pdp__title"><?php the_title(); ?></h1>
				<div class="pdp__price price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
				<?php if ( $product->get_short_description() ) : ?>
					<div class="pdp__short"><?php echo wp_kses_post( apply_filters( 'woocommerce_short_description', $product->get_short_description() ) ); ?></div>
				<?php endif; ?>

				<div class="pdp-form">
					<?php woocommerce_template_single_add_to_cart(); ?>
				</div>

				<a class="pdp__ask" href="<?php echo esc_url( lorie_wa( 'Olá! Tenho uma dúvida sobre a peça "' . $product->get_name() . '": ' . $product->get_permalink() ) ); ?>" target="_blank" rel="noopener">
					<?php echo lorie_icon( 'whatsapp', 20 ); // phpcs:ignore ?> Tirar dúvida pelo WhatsApp
				</a>

				<ul class="pdp__trust">
					<li><?php echo lorie_icon( 'hand', 20 ); // phpcs:ignore ?><span><strong>Feito à mão</strong> por uma artesã</span></li>
					<li><?php echo lorie_icon( 'clock', 20 ); // phpcs:ignore ?><span><strong><?php echo $product->managing_stock() && $product->get_stock_quantity() > 0 ? 'Pronta entrega' : 'Produção em até ' . (int) $days . ' dias'; ?></strong></span></li>
					<li><?php echo lorie_icon( 'truck', 20 ); // phpcs:ignore ?><span><strong>Envio rastreado</strong> para todo o Brasil</span></li>
				</ul>

				<div class="accordion">
					<?php if ( $product->get_description() ) : ?>
						<details open>
							<summary>Descrição <?php echo lorie_icon( 'chev-d', 18 ); // phpcs:ignore ?></summary>
							<div class="accordion__body prose"><?php echo wp_kses_post( apply_filters( 'the_content', $product->get_description() ) ); ?></div>
						</details>
					<?php endif; ?>
					<?php if ( $attrs ) : ?>
						<details>
							<summary>Cores e tamanhos <?php echo lorie_icon( 'chev-d', 18 ); // phpcs:ignore ?></summary>
							<div class="accordion__body"><?php wc_display_product_attributes( $product ); ?></div>
						</details>
					<?php endif; ?>
					<details>
						<summary>Prazo, envio e trocas <?php echo lorie_icon( 'chev-d', 18 ); // phpcs:ignore ?></summary>
						<div class="accordion__body prose">
							<p>Peças sob encomenda ficam prontas em até <strong><?php echo (int) $days; ?> dias</strong> após a confirmação do pagamento. Peças de pronta entrega são enviadas em até 2 dias úteis.</p>
							<p>O frete é calculado no carrinho. Você acompanha tudo em <em>Minha conta › Meus pedidos</em>.</p>
							<?php $terms = wc_get_page_permalink( 'terms' ); ?>
							<?php if ( $terms ) : ?><p><a href="<?php echo esc_url( $terms ); ?>">Ver política de trocas e devoluções</a></p><?php endif; ?>
						</div>
					</details>
					<?php if ( comments_open() || $product->get_review_count() ) : ?>
						<details>
							<summary>Avaliações (<?php echo (int) $product->get_review_count(); ?>) <?php echo lorie_icon( 'chev-d', 18 ); // phpcs:ignore ?></summary>
							<div class="accordion__body"><?php comments_template(); ?></div>
						</details>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>

	<section class="section section--tint pdp__related">
		<div class="container">
			<?php woocommerce_output_related_products(); ?>
		</div>
	</section>
</div>
<?php do_action( 'woocommerce_after_single_product' ); ?>
