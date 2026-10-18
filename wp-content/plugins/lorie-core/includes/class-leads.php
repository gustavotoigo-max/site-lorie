<?php
/**
 * Newsletter "Receba novidades": guarda nome/e-mail com consentimento e permite exportar CSV.
 */

defined( 'ABSPATH' ) || exit;

class Lorie_Leads {

	const OPTION = 'lorie_newsletter';

	public static function init() {
		add_action( 'wp_ajax_nopriv_lorie_newsletter', array( __CLASS__, 'subscribe' ) );
		add_action( 'wp_ajax_lorie_newsletter', array( __CLASS__, 'subscribe' ) );
		add_action( 'admin_post_lorie_newsletter_csv', array( __CLASS__, 'csv' ) );
		add_action( 'admin_post_lorie_newsletter_delete', array( __CLASS__, 'delete' ) );
	}

	public static function all() {
		$l = get_option( self::OPTION, array() );
		return is_array( $l ) ? $l : array();
	}

	public static function subscribe() {
		check_ajax_referer( 'lorie_front', 'nonce' );
		$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => 'Digite um e-mail válido.' ) );
		}
		$list = self::all();
		$key  = strtolower( $email );
		if ( ! isset( $list[ $key ] ) ) {
			$list[ $key ] = array( 'email' => $email, 'date' => current_time( 'mysql' ) );
			update_option( self::OPTION, $list, false );
		}
		wp_send_json_success( array( 'message' => 'Pronto! Você vai receber nossas novidades. 💛' ) );
	}

	public static function csv() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( 'Sem permissão.' );
		}
		check_admin_referer( 'lorie_newsletter_csv' );
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=newsletter-lorie-' . gmdate( 'Y-m-d' ) . '.csv' );
		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( 'email', 'data' ) );
		foreach ( self::all() as $row ) {
			fputcsv( $out, array( $row['email'], $row['date'] ) );
		}
		fclose( $out ); // phpcs:ignore
		exit;
	}

	public static function delete() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( 'Sem permissão.' );
		}
		check_admin_referer( 'lorie_newsletter_delete' );
		$list = self::all();
		unset( $list[ strtolower( sanitize_email( wp_unslash( $_GET['email'] ?? '' ) ) ) ] );
		update_option( self::OPTION, $list, false );
		wp_safe_redirect( admin_url( 'admin.php?page=lorie-newsletter' ) );
		exit;
	}
}
