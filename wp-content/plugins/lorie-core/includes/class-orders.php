<?php
/**
 * Pedidos: status de atendimento próprios, personalização por peça, status financeiro,
 * linha do tempo para a cliente, envio do pedido pelo WhatsApp e aviso de envio com rastreio.
 */

defined( 'ABSPATH' ) || exit;

class Lorie_Orders {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_statuses' ) );
		add_filter( 'wc_order_statuses', array( __CLASS__, 'order_statuses' ) );
		add_filter( 'woocommerce_order_is_paid_statuses', array( __CLASS__, 'paid_statuses' ) );
		add_filter( 'woocommerce_reports_order_statuses', array( __CLASS__, 'paid_statuses' ) );
		add_filter( 'woocommerce_valid_order_statuses_for_order_again', array( __CLASS__, 'paid_statuses' ) );
		add_filter( 'woocommerce_order_is_download_permitted', '__return_false' );
		// Nome da variação limpo ("Bolsa Aurora") com cor/tamanho listados abaixo no carrinho e no pedido.
		add_filter( 'woocommerce_product_variation_title_include_attributes', '__return_false' );
		add_filter( 'woocommerce_analytics_actionable_order_statuses', array( __CLASS__, 'paid_statuses' ) );

		// Com frete grátis disponível, não mostra o frete pago (a retirada continua como opção).
		add_filter( 'woocommerce_package_rates', array( __CLASS__, 'free_shipping_only' ), 100 );

		// Personalização da peça (vem da página do produto até o pedido).
		add_action( 'woocommerce_before_add_to_cart_button', array( __CLASS__, 'personalization_field' ), 25 );
		add_filter( 'woocommerce_add_cart_item_data', array( __CLASS__, 'add_cart_item_data' ), 10, 3 );
		add_filter( 'woocommerce_get_item_data', array( __CLASS__, 'display_cart_item_data' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'save_line_item' ), 10, 4 );

		// Status financeiro separado do status de atendimento.
		add_action( 'woocommerce_checkout_order_created', array( __CLASS__, 'on_created' ) );
		add_action( 'woocommerce_payment_complete', array( __CLASS__, 'on_paid' ) );

		// Cliente: rastreio, linha do tempo e WhatsApp.
		add_action( 'woocommerce_thankyou', array( __CLASS__, 'thankyou_whatsapp' ), 5 );
		add_action( 'woocommerce_order_details_before_order_table', array( __CLASS__, 'customer_timeline' ) );
		add_action( 'woocommerce_order_status_lorie-shipped', array( __CLASS__, 'email_shipped' ), 10, 2 );
		add_action( 'woocommerce_order_status_lorie-production', array( __CLASS__, 'email_production' ), 10, 2 );
	}

	public static function register_statuses() {
		foreach ( lorie_custom_statuses() as $key => $label ) {
			register_post_status( $key, array(
				'label'                     => $label,
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: count */
				'label_count'               => _n_noop( $label . ' <span class="count">(%s)</span>', $label . ' <span class="count">(%s)</span>' ), // phpcs:ignore
			) );
		}
	}

	public static function order_statuses( $statuses ) {
		$out = array();
		foreach ( $statuses as $k => $v ) {
			$out[ $k ] = 'wc-processing' === $k ? 'Recebido (pago)' : $v;
			if ( 'wc-processing' === $k ) {
				$out = array_merge( $out, lorie_custom_statuses() );
			}
		}
		if ( isset( $out['wc-completed'] ) ) {
			$out['wc-completed'] = 'Entregue / concluído';
		}
		return $out;
	}

	public static function paid_statuses( $statuses ) {
		return array_unique( array_merge( (array) $statuses, array( 'lorie-review', 'lorie-production', 'lorie-ready', 'lorie-shipped' ) ) );
	}

	public static function free_shipping_only( $rates ) {
		$has_free = false;
		foreach ( $rates as $r ) {
			if ( 'free_shipping' === $r->get_method_id() ) {
				$has_free = true;
			}
		}
		if ( ! $has_free ) {
			return $rates;
		}
		return array_filter( $rates, fn( $r ) => in_array( $r->get_method_id(), array( 'free_shipping', 'local_pickup' ), true ) );
	}

	/* ------------------------------------------------------------ Personalização */

	public static function personalization_field() {
		global $product;
		if ( $product && 'no' === $product->get_meta( '_lorie_allow_custom' ) ) {
			return;
		}
		?>
		<div class="lorie-custom">
			<button type="button" class="lorie-custom__toggle" aria-expanded="false">
				<span class="lorie-custom__icon" aria-hidden="true">✎</span>
				Quero personalizar esta peça <small>(opcional)</small>
			</button>
			<div class="lorie-custom__body" hidden>
				<label for="lorie_personalizacao">Conte como você imagina (cor diferente, medidas, detalhes…)</label>
				<textarea id="lorie_personalizacao" name="lorie_personalizacao" rows="3" maxlength="500" placeholder="Ex.: alça um pouco mais longa e forro na cor creme"></textarea>
				<p class="lorie-custom__note">A artesã avalia cada pedido de personalização depois do pagamento. Se não for possível fazer, ela entra em contato e você recebe o reembolso.</p>
			</div>
		</div>
		<?php
	}

	public static function add_cart_item_data( $data, $product_id, $variation_id ) {
		if ( ! empty( $_POST['lorie_personalizacao'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$text = sanitize_textarea_field( wp_unslash( $_POST['lorie_personalizacao'] ) ); // phpcs:ignore
			$text = mb_substr( trim( $text ), 0, 500 );
			if ( $text ) {
				$data['lorie_personalizacao'] = $text;
				$data['lorie_key']            = md5( $text . microtime() ); // peças personalizadas ficam em linhas separadas
			}
		}
		return $data;
	}

	public static function display_cart_item_data( $item_data, $cart_item ) {
		if ( ! empty( $cart_item['lorie_personalizacao'] ) ) {
			$item_data[] = array(
				'key'   => 'Personalização',
				'value' => esc_html( $cart_item['lorie_personalizacao'] ),
			);
		}
		return $item_data;
	}

	public static function save_line_item( $item, $cart_item_key, $values, $order ) {
		if ( ! empty( $values['lorie_personalizacao'] ) ) {
			$item->add_meta_data( 'Personalização', $values['lorie_personalizacao'], true );
		}
	}

	public static function has_personalization( $order ) {
		foreach ( $order->get_items() as $item ) {
			if ( $item->get_meta( 'Personalização' ) ) {
				return true;
			}
		}
		return false;
	}

	/* ------------------------------------------------------------ Financeiro */

	public static function on_created( $order ) {
		$order->update_meta_data( '_lorie_pay_status', 'pending' );
		$order->save();
	}

	public static function on_paid( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		$order->update_meta_data( '_lorie_pay_status', 'approved' );
		$order->save();
		if ( self::has_personalization( $order ) ) {
			$order->update_status( 'lorie-review', 'Pedido com personalização: aguardando avaliação da artesã.' );
		}
	}

	/* ------------------------------------------------------------ Cliente */

	public static function timeline_steps() {
		return array(
			'paid'       => 'Pagamento confirmado',
			'production' => 'Em produção',
			'ready'      => 'Pronto para envio',
			'shipped'    => 'Enviado',
			'delivered'  => 'Entregue',
		);
	}

	public static function timeline_index( $order ) {
		$map = array(
			'processing'       => 0,
			'lorie-review'     => 0,
			'lorie-production' => 1,
			'lorie-ready'      => 2,
			'lorie-shipped'    => 3,
			'completed'        => 4,
		);
		$s = $order->get_status();
		return array_key_exists( $s, $map ) ? $map[ $s ] : -1;
	}

	public static function customer_timeline( $order ) {
		$idx = self::timeline_index( $order );
		if ( $idx < 0 ) {
			return;
		}
		$tracking = $order->get_meta( '_lorie_tracking' );
		echo '<div class="lorie-timeline" style="--progress:' . esc_attr( $idx / 4 ) . '">';
		$i = 0;
		foreach ( self::timeline_steps() as $label ) {
			$cls = $i < $idx ? 'is-done' : ( $i === $idx ? 'is-current' : '' );
			echo '<div class="lorie-timeline__step ' . esc_attr( $cls ) . '"><span class="lorie-timeline__dot"></span><span class="lorie-timeline__label">' . esc_html( $label ) . '</span></div>';
			$i++;
		}
		echo '</div>';
		if ( 'lorie-review' === $order->get_status() ) {
			echo '<p class="lorie-alert">Recebemos seu pedido! A artesã está avaliando a personalização que você pediu e entra em contato se precisar de algum ajuste.</p>';
		}
		if ( $tracking ) {
			echo '<p class="lorie-alert">Código de rastreio: <strong>' . esc_html( $tracking ) . '</strong> — <a href="https://rastreamento.correios.com.br/app/index.php" target="_blank" rel="noopener">rastrear nos Correios</a></p>';
		}
	}

	/** Texto do pedido para o WhatsApp da loja (botão iniciado pela própria cliente). */
	public static function whatsapp_text( $order ) {
		$lines   = array();
		$lines[] = 'Olá! Acabei de fazer o pedido nº ' . $order->get_order_number() . ' no site da ' . lorie_get( 'store_name' ) . '.';
		$lines[] = '';
		foreach ( lorie_order_items( $order ) as $it ) {
			$lines[] = '• ' . $it['qty'] . 'x ' . $it['name'] . ( $it['variant'] ? ' (' . $it['variant'] . ')' : '' );
			if ( $it['custom'] ) {
				$lines[] = '   Personalização: ' . $it['custom'];
			}
		}
		$lines[] = '';
		$lines[] = 'Total: ' . html_entity_decode( wp_strip_all_tags( wc_price( $order->get_total() ) ) );
		$lines[] = 'Nome: ' . $order->get_formatted_billing_full_name();
		return implode( "\n", $lines );
	}

	public static function thankyou_whatsapp( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		?>
		<div class="lorie-thanks">
			<div class="lorie-thanks__heart" aria-hidden="true">♥</div>
			<h2>Obrigada pela sua compra!</h2>
			<p>Cada peça é feita à mão, ponto a ponto. Você pode acompanhar o andamento em <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>">Minha conta › Pedidos</a>.</p>
			<?php if ( self::has_personalization( $order ) ) : ?>
				<p class="lorie-alert">Seu pedido tem personalização. A artesã vai avaliar e, se não for possível fazer, entra em contato e solicita o reembolso para você.</p>
			<?php endif; ?>
			<a class="lorie-btn lorie-btn--whatsapp" target="_blank" rel="noopener" href="<?php echo esc_url( lorie_whatsapp_url( self::whatsapp_text( $order ) ) ); ?>">
				Enviar resumo do pedido no WhatsApp
			</a>
		</div>
		<?php
	}

	private static function email_customer( $order, $heading, $html ) {
		$mailer = WC()->mailer();
		$body   = $mailer->wrap_message( $heading, $html );
		$mailer->send( $order->get_billing_email(), $heading . ' — pedido nº ' . $order->get_order_number(), $body, array( 'Content-Type: text/html; charset=UTF-8' ) );
	}

	public static function email_production( $order_id, $order ) {
		$name = $order->get_billing_first_name();
		self::email_customer( $order, 'Sua peça entrou em produção', '<p>Oi, ' . esc_html( $name ) . '!</p><p>Boa notícia: a artesã já começou a produzir o seu pedido nº ' . esc_html( $order->get_order_number() ) . '. Avisaremos quando ele for enviado.</p>' );
	}

	public static function email_shipped( $order_id, $order ) {
		$name     = $order->get_billing_first_name();
		$tracking = $order->get_meta( '_lorie_tracking' );
		$html     = '<p>Oi, ' . esc_html( $name ) . '!</p><p>Seu pedido nº ' . esc_html( $order->get_order_number() ) . ' foi enviado. 💛</p>';
		if ( $tracking ) {
			$html .= '<p>Código de rastreio: <strong>' . esc_html( $tracking ) . '</strong><br><a href="https://rastreamento.correios.com.br/app/index.php">Rastrear nos Correios</a></p>';
		}
		self::email_customer( $order, 'Seu pedido foi enviado', $html );
	}
}
