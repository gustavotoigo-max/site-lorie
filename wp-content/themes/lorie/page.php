<?php
/**
 * Páginas comuns (carrinho, checkout, minha conta, políticas…).
 */

defined( 'ABSPATH' ) || exit;
get_header();

$is_wc_page = function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() );
$compact    = function_exists( 'is_checkout' ) && ( is_checkout() || is_account_page() );
?>
<main id="main" class="site-main site-main--page">
	<?php while ( have_posts() ) : the_post(); ?>
		<header class="page-hero <?php echo $compact ? 'page-hero--compact' : ''; ?>">
			<div class="container">
				<?php if ( function_exists( 'woocommerce_breadcrumb' ) && ! is_front_page() ) { woocommerce_breadcrumb(); } ?>
				<h1 class="page-hero__title"><?php echo function_exists( 'is_order_received_page' ) && is_order_received_page() ? 'Pedido recebido' : esc_html( get_the_title() ); ?></h1>
			</div>
		</header>
		<div class="container <?php echo $is_wc_page ? 'wc-page' : 'prose'; ?>">
			<?php the_content(); ?>
		</div>
	<?php endwhile; ?>
</main>
<?php
get_footer();
