<?php
/**
 * Cores com amostra: cada termo do atributo "Cor" (pa_cor) guarda um código hexadecimal,
 * usado para mostrar bolinhas de cor no site e no painel.
 */

defined( 'ABSPATH' ) || exit;

class Lorie_Colors {

	const TAX = 'pa_cor';

	public static function init() {
		add_action( self::TAX . '_add_form_fields', array( __CLASS__, 'add_field' ) );
		add_action( self::TAX . '_edit_form_fields', array( __CLASS__, 'edit_field' ) );
		add_action( 'created_' . self::TAX, array( __CLASS__, 'save' ) );
		add_action( 'edited_' . self::TAX, array( __CLASS__, 'save' ) );
	}

	public static function hex( $term ) {
		$term = is_object( $term ) ? $term : get_term_by( 'slug', $term, self::TAX );
		if ( ! $term ) {
			return '';
		}
		$hex = get_term_meta( $term->term_id, 'lorie_hex', true );
		return $hex ? $hex : '#d9cbb8';
	}

	public static function add_field() {
		echo '<div class="form-field"><label for="lorie_hex">Cor da amostra</label><input type="color" id="lorie_hex" name="lorie_hex" value="#c49a5a"><p>Usada para a bolinha de cor no site.</p></div>';
	}

	public static function edit_field( $term ) {
		echo '<tr class="form-field"><th><label for="lorie_hex">Cor da amostra</label></th><td><input type="color" id="lorie_hex" name="lorie_hex" value="' . esc_attr( self::hex( $term ) ) . '"></td></tr>';
	}

	public static function save( $term_id ) {
		if ( isset( $_POST['lorie_hex'] ) && current_user_can( 'manage_product_terms' ) ) { // phpcs:ignore
			update_term_meta( $term_id, 'lorie_hex', sanitize_hex_color( wp_unslash( $_POST['lorie_hex'] ) ) ); // phpcs:ignore
		}
	}

	/** Garante que os atributos globais Cor e Tamanho existam. */
	public static function ensure_attributes() {
		$wanted = array( 'cor' => 'Cor', 'tamanho' => 'Tamanho' );
		foreach ( $wanted as $slug => $name ) {
			if ( ! wc_attribute_taxonomy_id_by_name( $slug ) ) {
				wc_create_attribute( array( 'name' => $name, 'slug' => $slug, 'type' => 'select', 'order_by' => 'menu_order', 'has_archives' => false ) );
			}
			$tax = wc_attribute_taxonomy_name( $slug );
			if ( ! taxonomy_exists( $tax ) ) {
				register_taxonomy( $tax, array( 'product', 'product_variation' ), array( 'hierarchical' => false, 'show_ui' => false, 'query_var' => true, 'rewrite' => false ) );
			}
		}
	}

	/** Cria (ou reaproveita) um termo de cor e grava o hex. */
	public static function ensure_color( $name, $hex = '' ) {
		self::ensure_attributes();
		$term = get_term_by( 'name', $name, self::TAX );
		if ( ! $term ) {
			$res = wp_insert_term( $name, self::TAX );
			if ( is_wp_error( $res ) ) {
				return 0;
			}
			$term_id = $res['term_id'];
		} else {
			$term_id = $term->term_id;
		}
		if ( $hex ) {
			update_term_meta( $term_id, 'lorie_hex', sanitize_hex_color( $hex ) );
		}
		return (int) $term_id;
	}

	public static function ensure_size( $name ) {
		self::ensure_attributes();
		$tax  = wc_attribute_taxonomy_name( 'tamanho' );
		$term = get_term_by( 'name', $name, $tax );
		if ( $term ) {
			return (int) $term->term_id;
		}
		$res = wp_insert_term( $name, $tax );
		return is_wp_error( $res ) ? 0 : (int) $res['term_id'];
	}
}
