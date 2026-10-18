<?php
/**
 * Funções utilitárias usadas pelo plugin e pelo tema.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Configurações gerais da loja (editáveis em Lorié › Configurações).
 * Nada aqui é secreto: segredos (ex.: Google Client Secret) ficam em opções separadas
 * ou em constantes no wp-config.php — nunca no repositório.
 */
function lorie_settings() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$defaults = array(
		'store_name'        => 'Lorié',
		'tagline'           => 'De uma geração para outra.',
		// Número PROVISÓRIO — trocar em Lorié › Configurações (formato: 55 + DDD + número).
		'whatsapp'          => '5500000000000',
		'whatsapp_message'  => 'Olá! Vim pelo site da Lorié e gostaria de mais informações.',
		'instagram_handle'  => 'lorie.croche',
		'email_contact'     => get_option( 'admin_email' ),
		'free_shipping_min' => 300,
		'production_days'   => 10,
		'sender_name'       => 'Lorié Crochê',
		'sender_street'     => 'Rua Exemplo, 123',
		'sender_district'   => 'Centro',
		'sender_city'       => 'Sua cidade',
		'sender_state'      => 'SP',
		'sender_cep'        => '00000-000',
		'sender_phone'      => '',
	);
	$saved = get_option( 'lorie_settings', array() );
	$cache = wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );
	return $cache;
}

function lorie_get( $key, $fallback = '' ) {
	$s = lorie_settings();
	return isset( $s[ $key ] ) && '' !== $s[ $key ] ? $s[ $key ] : $fallback;
}

function lorie_digits( $value ) {
	return preg_replace( '/\D+/', '', (string) $value );
}

/** Link do WhatsApp da loja com mensagem pré-preenchida. */
function lorie_whatsapp_url( $text = '' ) {
	$number = lorie_digits( lorie_get( 'whatsapp' ) );
	$text   = $text ? $text : lorie_get( 'whatsapp_message' );
	return 'https://wa.me/' . $number . ( $text ? '?text=' . rawurlencode( $text ) : '' );
}

function lorie_instagram_url() {
	return 'https://www.instagram.com/' . ltrim( lorie_get( 'instagram_handle' ), '@' ) . '/';
}

/** Validação oficial de CPF (dígitos verificadores). */
function lorie_valid_cpf( $cpf ) {
	$cpf = lorie_digits( $cpf );
	if ( strlen( $cpf ) !== 11 || preg_match( '/^(\d)\1{10}$/', $cpf ) ) {
		return false;
	}
	for ( $t = 9; $t < 11; $t++ ) {
		$sum = 0;
		for ( $i = 0; $i < $t; $i++ ) {
			$sum += (int) $cpf[ $i ] * ( ( $t + 1 ) - $i );
		}
		$digit = ( ( 10 * $sum ) % 11 ) % 10;
		if ( (int) $cpf[ $t ] !== $digit ) {
			return false;
		}
	}
	return true;
}

function lorie_format_cpf( $cpf ) {
	$d = lorie_digits( $cpf );
	return strlen( $d ) === 11 ? substr( $d, 0, 3 ) . '.' . substr( $d, 3, 3 ) . '.' . substr( $d, 6, 3 ) . '-' . substr( $d, 9, 2 ) : $cpf;
}

function lorie_format_cep( $cep ) {
	$d = lorie_digits( $cep );
	return strlen( $d ) === 8 ? substr( $d, 0, 5 ) . '-' . substr( $d, 5 ) : $cep;
}

function lorie_format_phone( $phone ) {
	$d = lorie_digits( $phone );
	if ( strlen( $d ) === 11 ) {
		return '(' . substr( $d, 0, 2 ) . ') ' . substr( $d, 2, 5 ) . '-' . substr( $d, 7 );
	}
	if ( strlen( $d ) === 10 ) {
		return '(' . substr( $d, 0, 2 ) . ') ' . substr( $d, 2, 4 ) . '-' . substr( $d, 6 );
	}
	return $phone;
}

function lorie_logo_url( $variant = 'circle' ) {
	return LORIE_URL . 'assets/img/logo-' . $variant . '.png';
}

/** Estados brasileiros (UF => nome). */
function lorie_states() {
	return array(
		'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas', 'BA' => 'Bahia', 'CE' => 'Ceará',
		'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo', 'GO' => 'Goiás', 'MA' => 'Maranhão', 'MT' => 'Mato Grosso',
		'MS' => 'Mato Grosso do Sul', 'MG' => 'Minas Gerais', 'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná',
		'PE' => 'Pernambuco', 'PI' => 'Piauí', 'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte',
		'RS' => 'Rio Grande do Sul', 'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina',
		'SP' => 'São Paulo', 'SE' => 'Sergipe', 'TO' => 'Tocantins',
	);
}

/** Campos de cadastro do cliente (mesmas chaves usadas pelo WooCommerce / Brazilian Market). */
function lorie_profile_keys() {
	return array( 'billing_phone', 'billing_cpf', 'billing_postcode', 'billing_address_1', 'billing_number', 'billing_neighborhood', 'billing_city', 'billing_state' );
}

function lorie_profile_complete( $user_id ) {
	foreach ( lorie_profile_keys() as $k ) {
		if ( '' === trim( (string) get_user_meta( $user_id, $k, true ) ) ) {
			return false;
		}
	}
	return true;
}

/** Status financeiro (separado do status de atendimento, conforme a especificação). */
function lorie_pay_status_labels() {
	return array(
		'pending'       => 'Aguardando pagamento',
		'approved'      => 'Pago',
		'rejected'      => 'Recusado',
		'refunding'     => 'Reembolso em processamento',
		'refunded'      => 'Reembolsado',
		'refund_failed' => 'Reembolso falhou',
	);
}

function lorie_pay_status( $order ) {
	$s = $order->get_meta( '_lorie_pay_status' );
	if ( ! $s ) {
		$s = $order->is_paid() ? 'approved' : 'pending';
	}
	return $s;
}

/** Registra um evento no histórico auditável do pedido (meta + nota do pedido). */
function lorie_order_log( $order, $message, $user_id = null ) {
	if ( is_numeric( $order ) ) {
		$order = wc_get_order( $order );
	}
	if ( ! $order ) {
		return;
	}
	$user_id = null === $user_id ? get_current_user_id() : $user_id;
	$user    = $user_id ? get_userdata( $user_id ) : null;
	$history = $order->get_meta( '_lorie_history' );
	$history = is_array( $history ) ? $history : array();
	$history[] = array(
		't'    => time(),
		'user' => $user ? $user->display_name : 'Sistema',
		'msg'  => $message,
	);
	$order->update_meta_data( '_lorie_history', $history );
	$order->save();
	$order->add_order_note( $message . ( $user ? ' — ' . $user->display_name : '' ) );
}

/** Itens de um pedido em formato simples para o painel/etiqueta. */
function lorie_order_items( $order ) {
	$out = array();
	foreach ( $order->get_items() as $item_id => $item ) {
		$variation = array();
		foreach ( $item->get_formatted_meta_data( '_', true ) as $meta ) {
			if ( 'Personalização' === $meta->key ) {
				continue;
			}
			$variation[] = wp_strip_all_tags( $meta->display_key ) . ': ' . wp_strip_all_tags( $meta->display_value );
		}
		$product = $item->get_product();
		$out[]   = array(
			'id'       => $item_id,
			'name'     => $item->get_name(),
			'qty'      => $item->get_quantity(),
			'variant'  => implode( ' · ', $variation ),
			'custom'   => (string) $item->get_meta( 'Personalização' ),
			'done'     => (bool) $item->get_meta( '_lorie_done' ),
			'thumb'    => $product ? wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ) : '',
		);
	}
	return $out;
}

/** Status operacionais personalizados. */
function lorie_custom_statuses() {
	return array(
		'wc-lorie-review'     => 'Avaliando personalização',
		'wc-lorie-production' => 'Em produção',
		'wc-lorie-ready'      => 'Pronto para envio',
		'wc-lorie-shipped'    => 'Enviado',
	);
}
