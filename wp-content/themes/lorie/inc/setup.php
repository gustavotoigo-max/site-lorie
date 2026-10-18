<?php
/**
 * Configuração do tema, scripts e funções auxiliares.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', function () {
	load_theme_textdomain( 'lorie', LORIE_THEME_DIR . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support( 'custom-logo', array( 'height' => 120, 'width' => 400, 'flex-height' => true, 'flex-width' => true ) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'customize-selective-refresh-widgets' );
	add_theme_support( 'woocommerce', array(
		'thumbnail_image_width' => 600,
		'single_image_width'    => 1100,
		'product_grid'          => array( 'default_columns' => 4, 'default_rows' => 3, 'min_columns' => 2, 'max_columns' => 4 ),
	) );

	register_nav_menus( array(
		'primary'     => 'Menu principal',
		'footer_shop' => 'Rodapé — Loja',
		'footer_help' => 'Rodapé — Ajuda',
	) );

	add_image_size( 'lorie-hero', 2400, 1400, false );
	add_image_size( 'lorie-portrait', 900, 1125, true );
} );

// Miniaturas de produto no formato retrato 4:5.
add_filter( 'woocommerce_get_image_size_thumbnail', function ( $size ) {
	return array( 'width' => 600, 'height' => 750, 'crop' => 1 );
} );
add_filter( 'woocommerce_get_image_size_gallery_thumbnail', fn() => array( 'width' => 200, 'height' => 250, 'crop' => 1 ) );

/** Valor de uma configuração do Personalizador com padrão. */
function lorie_mod( $key ) {
	$defaults = lorie_mod_defaults();
	return get_theme_mod( $key, $defaults[ $key ] ?? '' );
}

/** Configuração da loja (plugin) com fallback se o plugin estiver desativado. */
function lorie_opt( $key, $fallback = '' ) {
	return function_exists( 'lorie_get' ) ? lorie_get( $key, $fallback ) : $fallback;
}

function lorie_wa( $text = '' ) {
	return function_exists( 'lorie_whatsapp_url' ) ? lorie_whatsapp_url( $text ) : '#';
}

function lorie_img( $file ) {
	return LORIE_THEME_URI . '/assets/img/' . $file;
}

/** Imagem por ID com fallback elegante. */
function lorie_image( $id, $size = 'large', $attr = array() ) {
	if ( $id && wp_attachment_is_image( $id ) ) {
		return wp_get_attachment_image( $id, $size, false, $attr );
	}
	return '<span class="lorie-ph" aria-hidden="true"><img src="' . esc_url( lorie_img( 'logo-monogram.png' ) ) . '" alt=""></span>';
}

add_action( 'wp_enqueue_scripts', function () {
	$v   = LORIE_THEME_VERSION;
	$uri = LORIE_THEME_URI . '/assets';

	wp_enqueue_style( 'swiper', $uri . '/vendor/swiper-bundle.min.css', array(), '11.2.10' );
	wp_enqueue_style( 'lorie', $uri . '/css/main.css', array( 'swiper' ), $v );

	wp_enqueue_script( 'swiper', $uri . '/vendor/swiper-bundle.min.js', array(), '11.2.10', true );
	wp_enqueue_script( 'gsap', $uri . '/vendor/gsap.min.js', array(), '3.15.0', true );
	wp_enqueue_script( 'gsap-st', $uri . '/vendor/ScrollTrigger.min.js', array( 'gsap' ), '3.15.0', true );
	wp_enqueue_script( 'lenis', $uri . '/vendor/lenis.min.js', array(), '1.3.27', true );

	$deps = array( 'jquery', 'swiper', 'gsap', 'gsap-st', 'lenis' );
	if ( class_exists( 'WooCommerce' ) ) {
		wp_enqueue_script( 'wc-add-to-cart-variation' );
		$deps[] = 'wc-add-to-cart-variation';
	}
	wp_enqueue_script( 'lorie', $uri . '/js/main.js', $deps, $v, true );

	$wc = class_exists( 'WooCommerce' );
	wp_localize_script( 'lorie', 'LORIE', array(
		'ajax'      => admin_url( 'admin-ajax.php' ),
		'wcAjax'    => $wc ? WC_AJAX::get_endpoint( '%%endpoint%%' ) : '',
		'nonce'     => wp_create_nonce( 'lorie_front' ),
		'cartUrl'   => $wc ? wc_get_cart_url() : '',
		'checkout'  => $wc ? wc_get_checkout_url() : '',
		'freeMin'   => (float) lorie_opt( 'free_shipping_min', 0 ),
		'loggedIn'  => is_user_logged_in(),
		'i18n'      => array(
			'added'   => 'Adicionado ao carrinho',
			'error'   => 'Não foi possível concluir. Tente novamente.',
			'choose'  => 'Escolha as opções',
			'wishAdd' => 'Salvo nos favoritos',
			'wishRm'  => 'Removido dos favoritos',
		),
	) );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
} );

// Pré-carrega as fontes principais (melhora a primeira pintura).
add_action( 'wp_head', function () {
	foreach ( array( 'jost-latin-400-normal', 'cormorant-garamond-latin-500-normal' ) as $f ) {
		echo '<link rel="preload" href="' . esc_url( LORIE_THEME_URI . '/assets/fonts/' . $f . '.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
	}
	echo '<meta name="theme-color" content="#FBF6EF">' . "\n";
}, 1 );

// Metadados para compartilhamento (WhatsApp, Instagram, Facebook).
add_action( 'wp_head', function () {
	if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) ) {
		return; // um plugin de SEO já cuida disso
	}
	$title = wp_get_document_title();
	$desc  = get_bloginfo( 'description' );
	$image = lorie_img( 'logo-circle.png' );
	if ( is_singular( 'product' ) && function_exists( 'wc_get_product' ) ) {
		$p = wc_get_product( get_the_ID() );
		if ( $p ) {
			$desc  = wp_strip_all_tags( $p->get_short_description() ) ?: $desc;
			$image = wp_get_attachment_image_url( $p->get_image_id(), 'large' ) ?: $image;
		}
	}
	printf( '<meta name="description" content="%s">' . "\n", esc_attr( wp_trim_words( $desc, 30 ) ) );
	printf( '<meta property="og:title" content="%s"><meta property="og:description" content="%s"><meta property="og:image" content="%s"><meta property="og:type" content="website"><meta name="twitter:card" content="summary_large_image">' . "\n", esc_attr( $title ), esc_attr( wp_trim_words( $desc, 30 ) ), esc_url( $image ) );
}, 5 );

add_filter( 'body_class', function ( $classes ) {
	if ( is_front_page() ) {
		$classes[] = 'has-hero';
	}
	return $classes;
} );

// Ícone do site padrão (o monograma) se nenhum foi escolhido.
add_action( 'wp_head', function () {
	if ( ! has_site_icon() ) {
		echo '<link rel="icon" href="' . esc_url( lorie_img( 'logo-monogram.png' ) ) . '">' . "\n";
	}
}, 2 );

/** Menu com fallback para quando ainda não foi configurado. */
function lorie_menu( $location, $class = '' ) {
	if ( has_nav_menu( $location ) ) {
		wp_nav_menu( array(
			'theme_location' => $location,
			'container'      => false,
			'menu_class'     => $class,
			'depth'          => 2,
			'fallback_cb'    => false,
		) );
		return;
	}
	$links = array( home_url( '/' ) => 'Início' );
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$links[ wc_get_page_permalink( 'shop' ) ] = 'Catálogo';
	}
	$about = get_page_by_path( 'quem-somos' );
	if ( $about ) {
		$links[ get_permalink( $about ) ] = 'Quem somos';
	}
	echo '<ul class="' . esc_attr( $class ) . '">';
	foreach ( $links as $url => $label ) {
		echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></li>';
	}
	echo '</ul>';
}

/** Categorias de produto na ordem definida (sem "Outros"). */
function lorie_product_categories() {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return array();
	}
	$terms = get_terms( array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
		'parent'     => 0,
		'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
		'orderby'    => 'meta_value_num',
		'meta_key'   => 'order', // phpcs:ignore
	) );
	return is_wp_error( $terms ) ? array() : $terms;
}
