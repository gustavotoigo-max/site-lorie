<?php
/**
 * Template Name: Favoritos
 * Lista das peças salvas com o coração (ficam guardadas no navegador da cliente).
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="main" class="site-main site-main--page">
	<header class="page-hero">
		<div class="container">
			<span class="eyebrow">Sua lista</span>
			<h1 class="page-hero__title">Favoritos</h1>
		</div>
	</header>
	<div class="container">
		<div data-wishlist-page class="wishlist">
			<p class="wishlist__loading">Carregando…</p>
		</div>
		<div class="wishlist__empty" data-wishlist-empty hidden>
			<?php echo lorie_icon( 'heart', 40 ); // phpcs:ignore ?>
			<h2>Nenhuma peça salva ainda</h2>
			<p>Toque no coração das peças que você ama para guardá-las aqui.</p>
			<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
				<a class="btn btn--primary" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Explorar o catálogo</a>
			<?php endif; ?>
		</div>
	</div>
</main>
<?php
get_footer();
