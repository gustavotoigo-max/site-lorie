<?php
/**
 * Painel da artesã dentro do WordPress (menu "Lorié"): visão geral de vendas,
 * atalhos e páginas de configuração. Encomendas, Envios e Produtos têm classes próprias.
 */

defined( 'ABSPATH' ) || exit;

class Lorie_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 9 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_filter( 'admin_body_class', array( __CLASS__, 'body_class' ) );
		add_action( 'admin_bar_menu', array( __CLASS__, 'admin_bar' ), 80 );
		add_filter( 'parent_file', array( __CLASS__, 'slides_parent' ) );
	}

	public static function is_lorie_screen() {
		$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : ''; // phpcs:ignore
		return 0 === strpos( $page, 'lorie' );
	}

	public static function body_class( $classes ) {
		return self::is_lorie_screen() ? $classes . ' lorie-admin-screen' : $classes;
	}

	public static function count_status( $statuses ) {
		$n = 0;
		foreach ( (array) $statuses as $s ) {
			$n += (int) wc_orders_count( $s );
		}
		return $n;
	}

	public static function menu() {
		$cap      = 'manage_woocommerce';
		$active   = self::count_status( array( 'processing', 'lorie-review', 'lorie-production' ) );
		$to_ship  = self::count_status( array( 'lorie-ready' ) );
		$badge    = fn( $n ) => $n ? ' <span class="awaiting-mod"><span class="pending-count">' . (int) $n . '</span></span>' : '';
		$icon     = 'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="black" d="M10 17.5s-6.5-3.9-6.5-8.6A3.6 3.6 0 0 1 10 6.6a3.6 3.6 0 0 1 6.5 2.3c0 4.7-6.5 8.6-6.5 8.6z"/></svg>' );

		add_menu_page( 'Lorié', 'Lorié' . $badge( $active ), $cap, 'lorie', array( __CLASS__, 'page_dashboard' ), $icon, 2 );
		add_submenu_page( 'lorie', 'Painel', 'Painel', $cap, 'lorie', array( __CLASS__, 'page_dashboard' ) );
		add_submenu_page( 'lorie', 'Encomendas', 'Encomendas' . $badge( $active ), $cap, 'lorie-encomendas', array( 'Lorie_Board', 'page' ) );
		add_submenu_page( 'lorie', 'Envios', 'Envios' . $badge( $to_ship ), $cap, 'lorie-envios', array( 'Lorie_Shipping', 'page' ) );
		add_submenu_page( 'lorie', 'Produtos', 'Produtos', $cap, 'lorie-produtos', array( 'Lorie_Products_Admin', 'page_list' ) );
		add_submenu_page( 'lorie', 'Novo produto', 'Novo produto', $cap, 'lorie-produto', array( 'Lorie_Products_Admin', 'page_edit' ) );
		add_submenu_page( 'lorie', 'Slides do topo', 'Slides do topo', $cap, 'edit.php?post_type=lorie_slide' );
		add_submenu_page( 'lorie', 'Instagram', 'Instagram', $cap, 'lorie-instagram', array( __CLASS__, 'page_instagram' ) );
		add_submenu_page( 'lorie', 'Pagamentos', 'Pagamentos', $cap, 'lorie-pagamentos', array( __CLASS__, 'page_payments' ) );
		add_submenu_page( 'lorie', 'Newsletter', 'Newsletter', $cap, 'lorie-newsletter', array( __CLASS__, 'page_newsletter' ) );
		add_submenu_page( 'lorie', 'Configurações', 'Configurações', $cap, 'lorie-config', array( __CLASS__, 'page_settings' ) );
		add_submenu_page( 'lorie', 'Textos e fotos do site', 'Textos e fotos do site', 'edit_theme_options', 'customize.php?autofocus[panel]=lorie_panel' );
		add_submenu_page( 'lorie', 'Assistente de configuração', 'Assistente', 'manage_options', 'lorie-assistente', array( 'Lorie_Installer', 'page' ) );
	}

	public static function slides_parent( $parent ) {
		global $current_screen;
		if ( $current_screen && 'lorie_slide' === $current_screen->post_type ) {
			return 'lorie';
		}
		return $parent;
	}

	public static function admin_bar( $bar ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$bar->add_node( array( 'id' => 'lorie-panel', 'title' => '♥ Painel Lorié', 'href' => admin_url( 'admin.php?page=lorie' ) ) );
		$bar->add_node( array( 'parent' => 'lorie-panel', 'id' => 'lorie-enc', 'title' => 'Encomendas', 'href' => admin_url( 'admin.php?page=lorie-encomendas' ) ) );
		$bar->add_node( array( 'parent' => 'lorie-panel', 'id' => 'lorie-env', 'title' => 'Envios', 'href' => admin_url( 'admin.php?page=lorie-envios' ) ) );
		$bar->add_node( array( 'parent' => 'lorie-panel', 'id' => 'lorie-new', 'title' => 'Novo produto', 'href' => admin_url( 'admin.php?page=lorie-produto' ) ) );
	}

	public static function assets( $hook ) {
		if ( ! self::is_lorie_screen() ) {
			return;
		}
		wp_enqueue_style( 'lorie-admin', LORIE_URL . 'assets/css/admin.css', array(), LORIE_VERSION );
		wp_enqueue_media();
		wp_enqueue_script( 'lorie-qrcode', LORIE_URL . 'assets/vendor/qrcode.js', array(), '1.4.4', true );
		wp_enqueue_script( 'lorie-admin', LORIE_URL . 'assets/js/admin.js', array( 'lorie-qrcode' ), LORIE_VERSION, true );
		wp_localize_script( 'lorie-admin', 'LORIE_ADMIN', array(
			'ajax'   => admin_url( 'admin-ajax.php' ),
			'nonce'  => wp_create_nonce( 'lorie_admin' ),
			'logo'   => lorie_logo_url( 'circle' ),
			'store'  => lorie_get( 'store_name' ),
			'tagline'=> lorie_get( 'tagline' ),
			'sender' => array(
				'name'     => lorie_get( 'sender_name' ),
				'street'   => lorie_get( 'sender_street' ),
				'district' => lorie_get( 'sender_district' ),
				'city'     => lorie_get( 'sender_city' ),
				'state'    => lorie_get( 'sender_state' ),
				'cep'      => lorie_get( 'sender_cep' ),
				'phone'    => lorie_get( 'sender_phone' ),
			),
		) );
	}

	/* ------------------------------------------------------------------ Layout */

	public static function header( $title, $subtitle = '', $actions = '' ) {
		$page  = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : 'lorie'; // phpcs:ignore
		$tabs  = array(
			'lorie'            => 'Painel',
			'lorie-encomendas' => 'Encomendas',
			'lorie-envios'     => 'Envios',
			'lorie-produtos'   => 'Produtos',
			'lorie-pagamentos' => 'Pagamentos',
			'lorie-config'     => 'Configurações',
		);
		?>
		<div class="lorie-wrap">
			<header class="lorie-top">
				<a class="lorie-top__brand" href="<?php echo esc_url( admin_url( 'admin.php?page=lorie' ) ); ?>">
					<img src="<?php echo esc_url( lorie_logo_url( 'monogram' ) ); ?>" alt="" width="28" height="40">
					<span>Lorié <em>ateliê</em></span>
				</a>
				<nav class="lorie-top__nav">
					<?php foreach ( $tabs as $slug => $label ) : ?>
						<a class="<?php echo $slug === $page || ( 'lorie-produtos' === $slug && 'lorie-produto' === $page ) ? 'is-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=' . $slug ) ); ?>"><?php echo esc_html( $label ); ?></a>
					<?php endforeach; ?>
				</nav>
				<a class="lorie-top__site" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank">Ver loja ↗</a>
			</header>
			<div class="lorie-head">
				<div>
					<h1 class="lorie-h1"><?php echo esc_html( $title ); ?></h1>
					<?php if ( $subtitle ) : ?>
						<p class="lorie-sub"><?php echo wp_kses_post( $subtitle ); ?></p>
					<?php endif; ?>
				</div>
				<div class="lorie-head__actions"><?php echo $actions; // phpcs:ignore ?></div>
			</div>
			<?php self::flash(); ?>
		<?php
	}

	public static function footer() {
		echo '</div>';
	}

	public static function flash() {
		$m = isset( $_GET['lorie_msg'] ) ? sanitize_key( $_GET['lorie_msg'] ) : ''; // phpcs:ignore
		$map = array(
			'saved'   => array( 'ok', 'Alterações salvas.' ),
			'none'    => array( 'error', 'A loja precisa de pelo menos uma forma de pagamento ativa.' ),
			'created' => array( 'ok', 'Produto salvo e publicado no catálogo.' ),
			'setup'   => array( 'ok', 'Loja configurada! Confira o site.' ),
		);
		if ( isset( $map[ $m ] ) ) {
			echo '<div class="lorie-flash lorie-flash--' . esc_attr( $map[ $m ][0] ) . '">' . esc_html( $map[ $m ][1] ) . '</div>';
		}
	}

	public static function money( $v ) {
		return 'R$ ' . number_format( (float) $v, 2, ',', '.' );
	}

	/* ------------------------------------------------------------------ Painel */

	public static function page_dashboard() {
		$paid   = array( 'processing', 'lorie-review', 'lorie-production', 'lorie-ready', 'lorie-shipped', 'completed' );
		$since  = strtotime( '-29 days', strtotime( 'today', current_time( 'timestamp' ) ) );
		$orders = wc_get_orders( array(
			'status'       => $paid,
			'limit' => 500,
			'date_created' => '>=' . $since,
		) );

		$month_start = strtotime( gmdate( 'Y-m-01', current_time( 'timestamp' ) ) );
		$month_total = 0;
		$month_count = 0;
		$days        = array();
		for ( $i = 13; $i >= 0; $i-- ) {
			$days[ gmdate( 'Y-m-d', strtotime( "-{$i} days", current_time( 'timestamp' ) ) ) ] = 0;
		}
		foreach ( $orders as $o ) {
			$ts = $o->get_date_created() ? $o->get_date_created()->getOffsetTimestamp() : 0;
			$net = (float) $o->get_total() - (float) $o->get_total_refunded();
			if ( $ts >= $month_start ) {
				$month_total += $net;
				$month_count++;
			}
			$d = gmdate( 'Y-m-d', $ts );
			if ( isset( $days[ $d ] ) ) {
				$days[ $d ] += $net;
			}
		}
		$active  = self::count_status( array( 'processing', 'lorie-review', 'lorie-production' ) );
		$review  = self::count_status( array( 'lorie-review' ) );
		$to_ship = self::count_status( array( 'lorie-ready' ) );
		$ticket  = $month_count ? $month_total / $month_count : 0;

		$actions = '<a class="lorie-btn lorie-btn--primary" href="' . esc_url( admin_url( 'admin.php?page=lorie-produto' ) ) . '">+ Novo produto</a>';
		$hello   = wp_get_current_user()->first_name ? wp_get_current_user()->first_name : wp_get_current_user()->display_name;
		self::header( 'Olá, ' . $hello . ' ♥', 'Resumo do ateliê hoje, ' . date_i18n( 'j \d\e F' ) . '.', $actions );
		?>
		<section class="lorie-kpis">
			<div class="lorie-kpi"><span class="lorie-kpi__label">Vendas no mês</span><strong class="lorie-kpi__value"><?php echo esc_html( self::money( $month_total ) ); ?></strong><span class="lorie-kpi__hint"><?php echo (int) $month_count; ?> pedido(s)</span></div>
			<a class="lorie-kpi lorie-kpi--link" href="<?php echo esc_url( admin_url( 'admin.php?page=lorie-encomendas' ) ); ?>"><span class="lorie-kpi__label">Encomendas ativas</span><strong class="lorie-kpi__value"><?php echo (int) $active; ?></strong><span class="lorie-kpi__hint"><?php echo (int) $review; ?> aguardando avaliação</span></a>
			<a class="lorie-kpi lorie-kpi--link" href="<?php echo esc_url( admin_url( 'admin.php?page=lorie-envios' ) ); ?>"><span class="lorie-kpi__label">Prontas para enviar</span><strong class="lorie-kpi__value"><?php echo (int) $to_ship; ?></strong><span class="lorie-kpi__hint">gerar etiqueta</span></a>
			<div class="lorie-kpi"><span class="lorie-kpi__label">Ticket médio (mês)</span><strong class="lorie-kpi__value"><?php echo esc_html( self::money( $ticket ) ); ?></strong><span class="lorie-kpi__hint">por pedido</span></div>
		</section>

		<div class="lorie-cols">
			<section class="lorie-card lorie-card--chart">
				<h2 class="lorie-h2">Vendas — últimos 14 dias</h2>
				<?php echo self::bar_chart( $days ); // phpcs:ignore ?>
			</section>
			<section class="lorie-card">
				<h2 class="lorie-h2">Atalhos</h2>
				<div class="lorie-shortcuts">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=lorie-produto' ) ); ?>"><span>＋</span>Cadastrar peça</a>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=lorie_slide' ) ); ?>"><span>▣</span>Fotos do slider</a>
					<a href="<?php echo esc_url( admin_url( 'customize.php?autofocus[panel]=lorie_panel' ) ); ?>"><span>✎</span>Textos e fotos</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=lorie-instagram' ) ); ?>"><span>◎</span>Instagram</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=lorie-pagamentos' ) ); ?>"><span>$</span>Pagamentos</a>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=shop_coupon' ) ); ?>"><span>%</span>Cupons</a>
				</div>
			</section>
		</div>

		<div class="lorie-cols">
			<section class="lorie-card">
				<h2 class="lorie-h2">Últimos pedidos</h2>
				<?php
				$recent = wc_get_orders( array( 'limit' => 8, 'orderby' => 'date', 'order' => 'DESC', 'status' => array_merge( $paid, array( 'pending', 'cancelled', 'refunded' ) ) ) );
				if ( ! $recent ) {
					echo '<p class="lorie-empty">Nenhum pedido ainda. Assim que a primeira cliente comprar, ele aparece aqui.</p>';
				} else {
					echo '<table class="lorie-table"><thead><tr><th>Pedido</th><th>Cliente</th><th>Status</th><th>Pagamento</th><th class="num">Total</th></tr></thead><tbody>';
					foreach ( $recent as $o ) {
						$pay = lorie_pay_status( $o );
						echo '<tr><td><a href="' . esc_url( $o->get_edit_order_url() ) . '">#' . esc_html( $o->get_order_number() ) . '</a><br><small>' . esc_html( $o->get_date_created() ? $o->get_date_created()->date_i18n( 'd/m H:i' ) : '' ) . '</small></td>'
							. '<td>' . esc_html( $o->get_formatted_billing_full_name() ) . '</td>'
							. '<td><span class="lorie-pill lorie-pill--' . esc_attr( $o->get_status() ) . '">' . esc_html( wc_get_order_status_name( $o->get_status() ) ) . '</span></td>'
							. '<td><span class="lorie-pay lorie-pay--' . esc_attr( $pay ) . '">' . esc_html( lorie_pay_status_labels()[ $pay ] ?? $pay ) . '</span></td>'
							. '<td class="num">' . esc_html( self::money( $o->get_total() ) ) . '</td></tr>';
					}
					echo '</tbody></table>';
				}
				?>
			</section>
			<section class="lorie-card">
				<h2 class="lorie-h2">Estoque baixo</h2>
				<?php
				$low = wc_get_products( array( 'limit' => 200, 'manage_stock' => true, 'type' => array( 'simple', 'variation' ), 'return' => 'objects' ) );
				$low = array_slice( array_filter( $low, fn( $p ) => $p->get_manage_stock() && $p->get_stock_quantity() <= 2 ), 0, 8 );
				if ( ! $low ) {
					echo '<p class="lorie-empty">Tudo certo com o estoque. ✓</p>';
				} else {
					echo '<ul class="lorie-list">';
					foreach ( $low as $p ) {
						$parent = $p->get_parent_id() ? $p->get_parent_id() : $p->get_id();
						$variant = $p->is_type( 'variation' ) ? wc_get_formatted_variation( $p, true, false ) : '';
						echo '<li><span><a href="' . esc_url( admin_url( 'admin.php?page=lorie-produto&id=' . $parent ) ) . '">' . esc_html( $p->get_name() ) . '</a>' . ( $variant ? ' <small class="lorie-muted">' . esc_html( $variant ) . '</small>' : '' ) . '</span><span class="lorie-pill lorie-pill--warn">' . (int) $p->get_stock_quantity() . ' un.</span></li>';
					}
					echo '</ul>';
				}
				?>
			</section>
		</div>
		<?php
		self::footer();
	}

	private static function bar_chart( $days ) {
		$max = max( 1, max( $days ) );
		$w   = 640;
		$h   = 200;
		$n   = count( $days );
		$bw  = $w / $n;
		$svg = '<svg class="lorie-chart" viewBox="0 0 ' . $w . ' ' . ( $h + 28 ) . '" role="img" aria-label="Vendas por dia">';
		$i   = 0;
		foreach ( $days as $d => $v ) {
			$bh   = $v > 0 ? max( 4, ( $v / $max ) * ( $h - 20 ) ) : 2;
			$x    = $i * $bw + $bw * 0.18;
			$y    = $h - $bh;
			$svg .= '<g><title>' . esc_html( date_i18n( 'd/m', strtotime( $d ) ) . ' — ' . self::money( $v ) ) . '</title>';
			$svg .= '<rect x="' . round( $x, 1 ) . '" y="' . round( $y, 1 ) . '" width="' . round( $bw * 0.64, 1 ) . '" height="' . round( $bh, 1 ) . '" rx="4" class="' . ( $v > 0 ? 'bar' : 'bar bar--zero' ) . '"/></g>';
			if ( 0 === $i % 2 ) {
				$svg .= '<text x="' . round( $x + $bw * 0.32, 1 ) . '" y="' . ( $h + 18 ) . '" text-anchor="middle">' . esc_html( date_i18n( 'd/m', strtotime( $d ) ) ) . '</text>';
			}
			$i++;
		}
		return $svg . '</svg>';
	}

	/* ------------------------------------------------------------------ Pagamentos */

	public static function page_payments() {
		$s   = Lorie_Payments::get();
		$log = get_option( Lorie_Payments::LOG, array() );
		self::header( 'Pagamentos', 'Escolha como suas clientes podem pagar. As regras valem para <strong>novos</strong> pedidos.' );
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="lorie-cols">
			<input type="hidden" name="action" value="lorie_save_payments">
			<?php wp_nonce_field( 'lorie_save_payments' ); ?>
			<section class="lorie-card">
				<h2 class="lorie-h2">Mercado Pago <span class="lorie-pill">próxima versão</span></h2>
				<p class="lorie-muted">Estas opções já ficam guardadas e serão aplicadas pelo servidor quando o Mercado Pago (Checkout Pro) for conectado.</p>
				<label class="lorie-switch"><input type="checkbox" name="pix_enabled" value="1" <?php checked( $s['pix_enabled'] ); ?>><span></span> Aceitar <strong>Pix</strong></label>
				<label class="lorie-switch"><input type="checkbox" name="credit_card_enabled" value="1" <?php checked( $s['credit_card_enabled'] ); ?> data-toggle-target="#lorie-installments"><span></span> Aceitar <strong>cartão de crédito</strong></label>
				<div id="lorie-installments" class="lorie-field">
					<label for="max_installments">Máximo de parcelas no cartão</label>
					<select name="max_installments" id="max_installments">
						<?php for ( $i = 1; $i <= 12; $i++ ) : ?>
							<option value="<?php echo (int) $i; ?>" <?php selected( (int) $s['max_installments'], $i ); ?>><?php echo (int) $i; ?>x</option>
						<?php endfor; ?>
					</select>
				</div>
			</section>
			<section class="lorie-card">
				<h2 class="lorie-h2">Pagamento de teste <span class="lorie-pill lorie-pill--ok">ativo nesta versão</span></h2>
				<p class="lorie-muted">Enquanto o pagamento oficial não está ligado, o pedido é aprovado quando a cliente digita o código abaixo. Nenhum valor é cobrado.</p>
				<label class="lorie-switch"><input type="checkbox" name="test_mode" value="1" <?php checked( $s['test_mode'] ); ?>><span></span> Ativar pagamento por código</label>
				<div class="lorie-field">
					<label for="test_code">Código</label>
					<input type="text" id="test_code" name="test_code" value="<?php echo esc_attr( $s['test_code'] ); ?>" style="text-transform:uppercase;letter-spacing:.12em;font-weight:600">
				</div>
				<label class="lorie-switch"><input type="checkbox" name="show_hint" value="1" <?php checked( $s['show_hint'] ); ?>><span></span> Mostrar o código como dica no carrinho</label>
				<p><button class="lorie-btn lorie-btn--primary" type="submit">Salvar pagamentos</button></p>
			</section>
		</form>
		<section class="lorie-card">
			<h2 class="lorie-h2">Histórico de alterações</h2>
			<?php if ( ! $log ) : ?>
				<p class="lorie-empty">Nenhuma alteração registrada ainda.</p>
			<?php else : ?>
				<table class="lorie-table"><thead><tr><th>Quando</th><th>Quem</th><th>O que mudou</th></tr></thead><tbody>
				<?php foreach ( array_slice( $log, 0, 30 ) as $row ) : ?>
					<tr><td><?php echo esc_html( date_i18n( 'd/m/Y H:i', $row['t'] + (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) ); ?></td><td><?php echo esc_html( $row['user'] ); ?></td><td><?php echo esc_html( implode( ' · ', $row['changes'] ) ); ?></td></tr>
				<?php endforeach; ?>
				</tbody></table>
			<?php endif; ?>
		</section>
		<?php
		self::footer();
	}

	/* ------------------------------------------------------------------ Configurações */

	public static function page_settings() {
		$s = lorie_settings();
		self::header( 'Configurações da loja', 'Dados de contato, WhatsApp, remetente das etiquetas e login com Google.' );
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="lorie_save_settings">
			<?php wp_nonce_field( 'lorie_save_settings' ); ?>
			<div class="lorie-cols">
			<?php foreach ( Lorie_Settings::fields() as $group => $fields ) : ?>
				<section class="lorie-card">
					<h2 class="lorie-h2"><?php echo esc_html( $group ); ?></h2>
					<?php foreach ( $fields as $key => $def ) : ?>
						<div class="lorie-field">
							<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $def[0] ); ?></label>
							<input type="<?php echo 'number' === $def[1] ? 'number' : esc_attr( $def[1] ); ?>" step="any" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $s[ $key ] ); ?>" <?php echo isset( $def[2] ) ? 'placeholder="' . esc_attr( $def[2] ) . '"' : ''; ?>>
						</div>
					<?php endforeach; ?>
					<?php if ( 'Loja' === $group && '5500000000000' === $s['whatsapp'] ) : ?>
						<p class="lorie-flash lorie-flash--error">O WhatsApp ainda está com o número provisório. Coloque o número da loja.</p>
					<?php endif; ?>
				</section>
			<?php endforeach; ?>
			</div>
			<section class="lorie-card">
				<h2 class="lorie-h2">Login com Google</h2>
				<p class="lorie-muted">Crie as credenciais no Google Cloud (passo a passo em <code>docs/03-login-com-google.md</code>). URI de redirecionamento autorizada:<br><code class="lorie-copy"><?php echo esc_html( Lorie_Google::callback_url() ); ?></code></p>
				<div class="lorie-cols">
					<div class="lorie-field">
						<label for="google_client_id">Client ID</label>
						<input type="text" id="google_client_id" name="google_client_id" value="<?php echo esc_attr( Lorie_Google::client_id() ); ?>" <?php disabled( defined( 'LORIE_GOOGLE_CLIENT_ID' ) ); ?>>
					</div>
					<div class="lorie-field">
						<label for="google_client_secret">Client Secret <small>(guardado no banco, nunca exibido)</small></label>
						<input type="password" id="google_client_secret" name="google_client_secret" value="<?php echo Lorie_Google::client_secret() ? '••••••••••••' : ''; ?>" autocomplete="new-password" <?php disabled( defined( 'LORIE_GOOGLE_CLIENT_SECRET' ) ); ?>>
					</div>
				</div>
				<p>Status: <?php echo Lorie_Google::enabled() ? '<span class="lorie-pill lorie-pill--ok">configurado</span>' : '<span class="lorie-pill lorie-pill--warn">não configurado</span>'; ?></p>
			</section>
			<p><button class="lorie-btn lorie-btn--primary" type="submit">Salvar configurações</button></p>
		</form>
		<?php
		self::footer();
	}

	/* ------------------------------------------------------------------ Instagram */

	public static function page_instagram() {
		$o = Lorie_Instagram::get();
		self::header( 'Instagram', 'Escolha as fotos que aparecem na página inicial, ou conecte a conta para atualizar sozinho.' );
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="lorie_save_instagram">
			<?php wp_nonce_field( 'lorie_save_instagram' ); ?>
			<section class="lorie-card">
				<h2 class="lorie-h2">Como mostrar</h2>
				<label class="lorie-radio"><input type="radio" name="ig_mode" value="manual" <?php checked( $o['mode'], 'manual' ); ?>> <strong>Manual</strong> — eu escolho as fotos e o link de cada post</label>
				<label class="lorie-radio"><input type="radio" name="ig_mode" value="auto" <?php checked( $o['mode'], 'auto' ); ?>> <strong>Automático</strong> — últimos posts pela API do Instagram (precisa de token)</label>
				<div class="lorie-field" style="max-width:520px">
					<label for="ig_token">Token de acesso (modo automático)</label>
					<input type="password" id="ig_token" name="ig_token" value="<?php echo get_option( Lorie_Instagram::TOKEN ) ? '••••••••••••' : ''; ?>" autocomplete="new-password">
					<label class="lorie-check-sm"><input type="checkbox" name="ig_token_clear" value="1"> remover token salvo</label>
				</div>
			</section>
			<section class="lorie-card">
				<h2 class="lorie-h2">Fotos (modo manual) <small class="lorie-muted">até 9</small></h2>
				<div class="lorie-ig-grid" data-lorie-ig>
					<?php
					$posts = $o['posts'];
					for ( $i = 0; $i < 9; $i++ ) :
						$p   = $posts[ $i ] ?? array( 'image' => 0, 'link' => '' );
						$src = $p['image'] ? wp_get_attachment_image_url( $p['image'], 'thumbnail' ) : '';
						?>
						<div class="lorie-ig-slot">
							<button type="button" class="lorie-ig-slot__img" data-pick-image style="<?php echo $src ? 'background-image:url(' . esc_url( $src ) . ')' : ''; ?>"><?php echo $src ? '' : '+ foto'; ?></button>
							<input type="hidden" name="ig_image[]" value="<?php echo (int) $p['image']; ?>">
							<input type="url" name="ig_link[]" value="<?php echo esc_attr( $p['link'] ); ?>" placeholder="Link do post">
							<button type="button" class="lorie-link-btn" data-clear-image>remover</button>
						</div>
					<?php endfor; ?>
				</div>
				<p><button class="lorie-btn lorie-btn--primary" type="submit">Salvar Instagram</button></p>
			</section>
		</form>
		<?php
		self::footer();
	}

	/* ------------------------------------------------------------------ Newsletter */

	public static function page_newsletter() {
		$list    = Lorie_Leads::all();
		$csv     = wp_nonce_url( admin_url( 'admin-post.php?action=lorie_newsletter_csv' ), 'lorie_newsletter_csv' );
		$actions = $list ? '<a class="lorie-btn" href="' . esc_url( $csv ) . '">Baixar planilha (CSV)</a>' : '';
		self::header( 'Newsletter', count( $list ) . ' pessoa(s) pediram para receber novidades.', $actions );
		echo '<section class="lorie-card">';
		if ( ! $list ) {
			echo '<p class="lorie-empty">Ainda não há inscritos. O formulário fica no fim da página inicial.</p>';
		} else {
			echo '<table class="lorie-table"><thead><tr><th>E-mail</th><th>Data</th><th></th></tr></thead><tbody>';
			foreach ( array_reverse( $list ) as $row ) {
				$del = wp_nonce_url( admin_url( 'admin-post.php?action=lorie_newsletter_delete&email=' . rawurlencode( $row['email'] ) ), 'lorie_newsletter_delete' );
				echo '<tr><td>' . esc_html( $row['email'] ) . '</td><td>' . esc_html( mysql2date( 'd/m/Y H:i', $row['date'] ) ) . '</td><td class="num"><a class="lorie-link-btn" href="' . esc_url( $del ) . '">remover</a></td></tr>';
			}
			echo '</tbody></table>';
		}
		echo '</section>';
		self::footer();
	}
}
