<?php
/**
 * Card de produto no catálogo (layout Lorié).
 *
 * @package WooCommerce\Templates
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

global $product;
if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}
lorie_product_card( $product, 'li' );
