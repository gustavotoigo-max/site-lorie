<?php
defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="main" class="site-main site-main--page">
	<section class="not-found container">
		<img src="<?php echo esc_url( lorie_img( 'logo-monogram.png' ) ); ?>" alt="" width="90">
		<h1 class="section-title section-title--xl">Ops, perdemos o fio da meada</h1>
		<p>A página que você procura não existe ou mudou de lugar.</p>
		<div class="not-found__actions">
			<a class="btn btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>">Voltar ao início</a>
			<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
				<a class="btn btn--ghost" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Ver catálogo</a>
			<?php endif; ?>
		</div>
	</section>
</main>
<?php
get_footer();
