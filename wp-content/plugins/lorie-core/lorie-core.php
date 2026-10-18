<?php
/**
 * Plugin Name:       Lorié — Núcleo da Loja
 * Plugin URI:        https://github.com/gustavotoigo-max/site-lorie
 * Description:       Funções da loja Lorié: cadastro com CPF, login com Google e link por e-mail, pagamento de teste (código COMPREAQUI), personalização de peças, painel da artesã (encomendas, envios com etiqueta, produtos, pagamentos), Instagram, WhatsApp e newsletter.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.0
 * Requires Plugins:  woocommerce
 * Author:            Lorié
 * Text Domain:       lorie
 * License:           GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

define( 'LORIE_VERSION', '1.0.0' );
define( 'LORIE_FILE', __FILE__ );
define( 'LORIE_DIR', plugin_dir_path( __FILE__ ) );
define( 'LORIE_URL', plugin_dir_url( __FILE__ ) );

require_once LORIE_DIR . 'includes/helpers.php';

// Compatibilidade declarada com o armazenamento moderno de pedidos (HPOS) do WooCommerce.
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', LORIE_FILE, true );
		// O site usa o carrinho/checkout clássicos (shortcodes), com layout próprio.
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', LORIE_FILE, false );
	}
} );

add_action( 'plugins_loaded', function () {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error"><p><strong>Lorié:</strong> instale e ative o plugin <strong>WooCommerce</strong> para a loja funcionar.</p></div>';
		} );
		return;
	}

	$files = array(
		'class-settings',
		'class-account',
		'class-google',
		'class-orders',
		'class-payments',
		'class-refunds',
		'class-slides',
		'class-colors',
		'class-instagram',
		'class-leads',
		'class-admin',
		'class-products-admin',
		'class-board',
		'class-shipping',
		'class-installer',
	);
	foreach ( $files as $f ) {
		require_once LORIE_DIR . "includes/{$f}.php";
	}

	Lorie_Settings::init();
	Lorie_Account::init();
	Lorie_Google::init();
	Lorie_Orders::init();
	Lorie_Payments::init();
	Lorie_Refunds::init();
	Lorie_Slides::init();
	Lorie_Colors::init();
	Lorie_Instagram::init();
	Lorie_Leads::init();
	Lorie_Admin::init();
	Lorie_Products_Admin::init();
	Lorie_Board::init();
	Lorie_Shipping::init();
	Lorie_Installer::init();
}, 20 );

// O gateway de pagamento precisa da classe base do WooCommerce.
add_action( 'woocommerce_init', function () {
	require_once LORIE_DIR . 'includes/class-gateway-test.php';
} );
add_filter( 'woocommerce_payment_gateways', function ( $gateways ) {
	$gateways[] = 'Lorie_Gateway_Test';
	return $gateways;
} );

register_activation_hook( __FILE__, function () {
	add_option( 'lorie_flush_rewrite', 1 );
} );

add_action( 'init', function () {
	if ( get_option( 'lorie_flush_rewrite' ) ) {
		delete_option( 'lorie_flush_rewrite' );
		flush_rewrite_rules();
	}
}, 99 );
