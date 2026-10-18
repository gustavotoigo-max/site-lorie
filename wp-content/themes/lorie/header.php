<?php
/**
 * Cabeçalho.
 */

defined( 'ABSPATH' ) || exit;
$count = function_exists( 'lorie_cart_count' ) ? lorie_cart_count() : 0;
$wc    = class_exists( 'WooCommerce' );
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#main">Pular para o conteúdo</a>

<?php if ( lorie_mod( 'lorie_announce_on' ) && lorie_mod( 'lorie_announce' ) ) : ?>
	<div class="announce"><p><?php echo esc_html( lorie_mod( 'lorie_announce' ) ); ?></p></div>
<?php endif; ?>

<header class="site-header" data-header>
	<div class="container site-header__inner">
		<button type="button" class="icon-btn site-header__burger" data-menu-open aria-label="Abrir menu" aria-controls="mobile-menu" aria-expanded="false"><?php echo lorie_icon( 'menu' ); // phpcs:ignore ?></button>

		<nav class="site-nav" aria-label="Menu principal">
			<?php lorie_menu( 'primary', 'site-nav__list' ); ?>
		</nav>

		<a class="site-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> — início">
			<?php if ( has_custom_logo() ) : ?>
				<?php echo wp_get_attachment_image( get_theme_mod( 'custom_logo' ), 'medium', false, array( 'class' => 'site-logo__img' ) ); ?>
			<?php else : ?>
				<img class="site-logo__img site-logo__img--dark" src="<?php echo esc_url( lorie_img( 'logo-wordmark.png' ) ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" width="260" height="70">
				<img class="site-logo__img site-logo__img--light" src="<?php echo esc_url( lorie_img( 'logo-wordmark-light.png' ) ); ?>" alt="" width="260" height="70" aria-hidden="true">
			<?php endif; ?>
		</a>

		<div class="site-header__icons">
			<button type="button" class="icon-btn" data-search-open aria-label="Buscar"><?php echo lorie_icon( 'search' ); // phpcs:ignore ?></button>
			<?php if ( $wc ) : ?>
				<a class="icon-btn icon-btn--hide-sm" href="<?php echo esc_url( home_url( '/favoritos/' ) ); ?>" aria-label="Favoritos"><?php echo lorie_icon( 'heart' ); // phpcs:ignore ?><span class="bubble" data-wish-count hidden>0</span></a>
				<a class="icon-btn icon-btn--hide-sm" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" aria-label="<?php echo is_user_logged_in() ? 'Minha conta' : 'Entrar'; ?>"><?php echo lorie_icon( 'user' ); // phpcs:ignore ?></a>
				<a class="icon-btn" href="<?php echo esc_url( wc_get_cart_url() ); ?>" data-cart-open aria-label="Carrinho"><?php echo lorie_icon( 'bag' ); // phpcs:ignore ?><span class="bubble" data-cart-count <?php echo $count ? '' : 'hidden'; ?>><?php echo (int) $count; ?></span></a>
			<?php endif; ?>
		</div>
	</div>
</header>
