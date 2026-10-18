<?php
/**
 * Tema Lorié.
 */

defined( 'ABSPATH' ) || exit;

define( 'LORIE_THEME_VERSION', '1.0.0' );
define( 'LORIE_THEME_URI', get_template_directory_uri() );
define( 'LORIE_THEME_DIR', get_template_directory() );

require LORIE_THEME_DIR . '/inc/setup.php';
require LORIE_THEME_DIR . '/inc/icons.php';
require LORIE_THEME_DIR . '/inc/customizer.php';

if ( class_exists( 'WooCommerce' ) ) {
	require LORIE_THEME_DIR . '/inc/woocommerce.php';
	require LORIE_THEME_DIR . '/inc/ajax.php';
}
