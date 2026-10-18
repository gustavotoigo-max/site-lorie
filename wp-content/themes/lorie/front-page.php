<?php
/**
 * Página inicial.
 */

defined( 'ABSPATH' ) || exit;
get_header();

$wc     = class_exists( 'WooCommerce' );
$slides = class_exists( 'Lorie_Slides' ) ? Lorie_Slides::get_slides() : array();
?>
<main id="main" class="site-main site-main--home">

	<!-- Slider em tela cheia -->
	<section class="hero" aria-label="Destaques">
		<?php if ( $slides ) : ?>
			<div class="hero__swiper swiper" data-hero>
				<div class="swiper-wrapper">
					<?php foreach ( $slides as $i => $s ) : ?>
						<div class="swiper-slide hero__slide hero__slide--<?php echo esc_attr( $s['align'] ); ?>">
							<div class="hero__media">
								<?php echo wp_get_attachment_image( $s['image'], 'lorie-hero', false, array( 'class' => 'hero__img', 'loading' => 0 === $i ? 'eager' : 'lazy', 'fetchpriority' => 0 === $i ? 'high' : 'auto', 'sizes' => '100vw' ) ); ?>
							</div>
							<div class="container hero__content">
								<?php if ( $s['eyebrow'] ) : ?><span class="hero__eyebrow"><?php echo esc_html( $s['eyebrow'] ); ?></span><?php endif; ?>
								<h2 class="hero__title"><?php echo esc_html( $s['title'] ); ?></h2>
								<?php if ( $s['btn_text'] && $s['btn_link'] ) : ?>
									<a class="btn btn--light" href="<?php echo esc_url( $s['btn_link'] ); ?>"><?php echo esc_html( $s['btn_text'] ); ?> <?php echo lorie_icon( 'arrow-r', 18 ); // phpcs:ignore ?></a>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<?php if ( count( $slides ) > 1 ) : ?>
					<div class="hero__ui container">
						<div class="hero__count"><span data-hero-current>01</span><i></i><span><?php echo esc_html( str_pad( (string) count( $slides ), 2, '0', STR_PAD_LEFT ) ); ?></span></div>
						<div class="hero__progress"><span data-hero-progress></span></div>
						<div class="hero__arrows">
							<button type="button" class="hero__arrow" data-hero-prev aria-label="Slide anterior"><?php echo lorie_icon( 'arrow-l' ); // phpcs:ignore ?></button>
							<button type="button" class="hero__arrow" data-hero-next aria-label="Próximo slide"><?php echo lorie_icon( 'arrow-r' ); // phpcs:ignore ?></button>
						</div>
					</div>
				<?php endif; ?>
			</div>
		<?php else : ?>
			<div class="hero__empty">
				<img src="<?php echo esc_url( lorie_img( 'logo-stack.png' ) ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" width="360">
				<?php if ( current_user_can( 'edit_posts' ) ) : ?>
					<p><a class="btn btn--primary" href="<?php echo esc_url( admin_url( 'edit.php?post_type=lorie_slide' ) ); ?>">Adicionar fotos ao slider</a></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<a href="#home-start" class="hero__scroll" aria-label="Rolar para baixo"><span></span></a>
	</section>

	<!-- Faixa animada -->
	<?php $marquee = array_filter( array_map( 'trim', preg_split( '/\r\n|\n/', (string) lorie_mod( 'lorie_marquee' ) ) ) ); ?>
	<?php if ( $marquee ) : ?>
	<div class="marquee" id="home-start" aria-hidden="true">
		<div class="marquee__track">
			<?php for ( $r = 0; $r < 2; $r++ ) : ?>
				<?php foreach ( $marquee as $m ) : ?><span><?php echo esc_html( $m ); ?></span><i><?php echo lorie_icon( 'sparkle', 16 ); // phpcs:ignore ?></i><?php endforeach; ?>
			<?php endfor; ?>
		</div>
	</div>
	<?php endif; ?>

	<?php if ( $wc ) : ?>
	<!-- Categorias -->
	<?php $cats = lorie_product_categories(); ?>
	<?php if ( $cats ) : ?>
	<section class="section cats">
		<div class="container">
			<header class="section-head" data-reveal>
				<span class="eyebrow"><?php echo esc_html( lorie_mod( 'lorie_cats_eyebrow' ) ); ?></span>
				<h2 class="section-title"><?php echo esc_html( lorie_mod( 'lorie_cats_title' ) ); ?></h2>
			</header>
			<div class="cats__grid">
				<?php foreach ( $cats as $t ) : ?>
					<a class="cat-card" href="<?php echo esc_url( get_term_link( $t ) ); ?>" data-reveal>
						<span class="cat-card__img"><?php echo lorie_image( (int) get_term_meta( $t->term_id, 'thumbnail_id', true ), 'woocommerce_thumbnail' ); // phpcs:ignore ?></span>
						<span class="cat-card__name"><?php echo esc_html( $t->name ); ?></span>
						<span class="cat-card__count"><?php echo (int) $t->count; ?> <?php echo 1 === (int) $t->count ? 'peça' : 'peças'; ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<!-- Novidades -->
	<?php $new = wc_get_products( array( 'limit' => 10, 'status' => 'publish', 'visibility' => 'catalog', 'orderby' => 'date', 'order' => 'DESC' ) ); ?>
	<?php if ( $new ) : ?>
	<section class="section section--tint">
		<div class="container">
			<header class="section-head section-head--row" data-reveal>
				<div>
					<span class="eyebrow"><?php echo esc_html( lorie_mod( 'lorie_new_eyebrow' ) ); ?></span>
					<h2 class="section-title"><?php echo esc_html( lorie_mod( 'lorie_new_title' ) ); ?></h2>
				</div>
				<div class="carousel-nav">
					<button type="button" class="round-btn" data-car-prev aria-label="Anteriores"><?php echo lorie_icon( 'arrow-l' ); // phpcs:ignore ?></button>
					<button type="button" class="round-btn" data-car-next aria-label="Próximos"><?php echo lorie_icon( 'arrow-r' ); // phpcs:ignore ?></button>
					<a class="link-arrow" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Ver tudo <?php echo lorie_icon( 'arrow-r', 16 ); // phpcs:ignore ?></a>
				</div>
			</header>
			<div class="swiper product-carousel" data-carousel>
				<div class="swiper-wrapper">
					<?php foreach ( $new as $p ) { lorie_product_card( $p, 'div', 'swiper-slide' ); } ?>
				</div>
			</div>
		</div>
	</section>
	<?php endif; ?>
	<?php endif; ?>

	<!-- História -->
	<section class="section story">
		<div class="container story__grid">
			<div class="story__media" data-reveal>
				<div class="story__frame"><?php echo lorie_image( (int) lorie_mod( 'lorie_story_image' ), 'lorie-portrait' ); // phpcs:ignore ?></div>
				<img class="story__seal" src="<?php echo esc_url( lorie_img( 'logo-circle.png' ) ); ?>" alt="" width="150" height="150" loading="lazy">
			</div>
			<div class="story__text" data-reveal>
				<span class="eyebrow"><?php echo esc_html( lorie_mod( 'lorie_story_eyebrow' ) ); ?></span>
				<h2 class="section-title section-title--xl"><?php echo esc_html( lorie_mod( 'lorie_story_title' ) ); ?></h2>
				<div class="story__body"><?php echo wp_kses_post( wpautop( esc_html( lorie_mod( 'lorie_story_text' ) ) ) ); ?></div>
				<?php $about = get_page_by_path( 'quem-somos' ); ?>
				<?php if ( $about ) : ?>
					<a class="btn btn--primary" href="<?php echo esc_url( get_permalink( $about ) ); ?>"><?php echo esc_html( lorie_mod( 'lorie_story_btn' ) ); ?> <?php echo lorie_icon( 'arrow-r', 18 ); // phpcs:ignore ?></a>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<?php if ( $wc ) : ?>
	<!-- Destaques -->
	<?php
	$feat_ids = wc_get_featured_product_ids();
	$featured = $feat_ids ? wc_get_products( array( 'include' => $feat_ids, 'limit' => 8, 'status' => 'publish', 'visibility' => 'catalog' ) ) : array();
	if ( count( $featured ) < 4 ) {
		$featured = wc_get_products( array( 'limit' => 8, 'status' => 'publish', 'visibility' => 'catalog', 'orderby' => 'popularity' ) );
	}
	?>
	<?php if ( $featured ) : ?>
	<section class="section">
		<div class="container">
			<header class="section-head" data-reveal>
				<span class="eyebrow"><?php echo esc_html( lorie_mod( 'lorie_featured_eyebrow' ) ); ?></span>
				<h2 class="section-title"><?php echo esc_html( lorie_mod( 'lorie_featured_title' ) ); ?></h2>
			</header>
			<ul class="products products--home">
				<?php foreach ( array_slice( $featured, 0, 8 ) as $p ) { lorie_product_card( $p ); } ?>
			</ul>
			<div class="center"><a class="btn btn--ghost" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Ver catálogo completo</a></div>
		</div>
	</section>
	<?php endif; ?>
	<?php endif; ?>

	<!-- Encomendas personalizadas -->
	<section class="custom-band">
		<div class="container custom-band__grid">
			<div class="custom-band__intro" data-reveal>
				<span class="eyebrow eyebrow--light"><?php echo esc_html( lorie_mod( 'lorie_custom_eyebrow' ) ); ?></span>
				<h2 class="section-title section-title--light"><?php echo esc_html( lorie_mod( 'lorie_custom_title' ) ); ?></h2>
				<p><?php echo esc_html( lorie_mod( 'lorie_custom_text' ) ); ?></p>
				<a class="btn btn--whatsapp" href="<?php echo esc_url( lorie_wa( 'Olá! Quero encomendar uma peça personalizada.' ) ); ?>" target="_blank" rel="noopener"><?php echo lorie_icon( 'whatsapp', 20 ); // phpcs:ignore ?> Encomendar pelo WhatsApp</a>
			</div>
			<ol class="steps">
				<?php foreach ( lorie_lines( 'lorie_custom_steps' ) as $i => $st ) : ?>
					<li class="step" data-reveal>
						<span class="step__n"><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
						<h3><?php echo esc_html( $st[0] ); ?></h3>
						<p><?php echo esc_html( $st[1] ); ?></p>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</section>

	<!-- Benefícios -->
	<section class="benefits">
		<div class="container benefits__grid">
			<?php foreach ( lorie_lines( 'lorie_benefits', 3 ) as $b ) : ?>
				<div class="benefit" data-reveal>
					<span class="benefit__icon"><?php echo lorie_icon( $b[0] ? $b[0] : 'sparkle', 26 ); // phpcs:ignore ?></span>
					<div><strong><?php echo esc_html( $b[1] ); ?></strong><span><?php echo esc_html( $b[2] ); ?></span></div>
				</div>
			<?php endforeach; ?>
		</div>
	</section>

	<!-- Instagram -->
	<?php $feed = class_exists( 'Lorie_Instagram' ) ? Lorie_Instagram::feed( 6 ) : array(); ?>
	<?php if ( $feed ) : ?>
	<section class="section insta">
		<div class="container">
			<header class="section-head section-head--row" data-reveal>
				<div>
					<span class="eyebrow"><?php echo esc_html( lorie_mod( 'lorie_insta_eyebrow' ) ); ?></span>
					<h2 class="section-title"><?php echo esc_html( lorie_mod( 'lorie_insta_title' ) ); ?></h2>
				</div>
				<a class="btn btn--ghost" href="<?php echo esc_url( lorie_instagram_url() ); ?>" target="_blank" rel="noopener"><?php echo lorie_icon( 'instagram', 18 ); // phpcs:ignore ?> @<?php echo esc_html( ltrim( lorie_opt( 'instagram_handle' ), '@' ) ); ?></a>
			</header>
			<div class="insta__grid">
				<?php foreach ( $feed as $post_ig ) : ?>
					<a class="insta__item" href="<?php echo esc_url( $post_ig['link'] ); ?>" target="_blank" rel="noopener" data-reveal>
						<img src="<?php echo esc_url( $post_ig['src'] ); ?>" alt="<?php echo esc_attr( $post_ig['alt'] ); ?>" loading="lazy">
						<span class="insta__over"><?php echo lorie_icon( 'instagram', 30 ); // phpcs:ignore ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php endif; ?>

</main>
<?php
get_footer();
