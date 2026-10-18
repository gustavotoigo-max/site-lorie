<?php
/**
 * Modelo genérico (blog/arquivos/busca de páginas).
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="main" class="site-main site-main--page">
	<header class="page-hero">
		<div class="container">
			<h1 class="page-hero__title">
				<?php
				if ( is_search() ) {
					printf( 'Resultados para “%s”', esc_html( get_search_query() ) );
				} elseif ( is_archive() ) {
					the_archive_title();
				} else {
					echo esc_html( get_bloginfo( 'name' ) );
				}
				?>
			</h1>
		</div>
	</header>
	<div class="container prose">
		<?php if ( have_posts() ) : ?>
			<?php while ( have_posts() ) : the_post(); ?>
				<article <?php post_class( 'entry' ); ?>>
					<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<?php is_singular() ? the_content() : the_excerpt(); ?>
				</article>
			<?php endwhile; ?>
			<?php the_posts_pagination(); ?>
		<?php else : ?>
			<p>Nada encontrado por aqui.</p>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
