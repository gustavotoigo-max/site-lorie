<?php
/**
 * Assistente de configuração: deixa a loja pronta com um clique
 * (páginas, categorias, cores/tamanhos, frete, pagamento de teste, menus e — opcionalmente — peças de exemplo).
 * Pode ser executado mais de uma vez sem duplicar nada.
 *
 * Também disponível pelo terminal:  wp lorie setup --demo
 */

defined( 'ABSPATH' ) || exit;

class Lorie_Installer {

	public static function init() {
		add_action( 'admin_post_lorie_setup', array( __CLASS__, 'handle' ) );
		add_action( 'admin_notices', array( __CLASS__, 'notice' ) );
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'lorie setup', function ( $args, $assoc ) {
				$log = self::run( ! empty( $assoc['demo'] ) );
				foreach ( $log as $line ) {
					WP_CLI::log( '• ' . $line );
				}
				WP_CLI::success( 'Loja Lorié configurada.' );
			} );
		}
	}

	public static function notice() {
		if ( get_option( 'lorie_setup_done' ) || ! current_user_can( 'manage_options' ) || Lorie_Admin::is_lorie_screen() ) {
			return;
		}
		echo '<div class="notice notice-info"><p><strong>Lorié:</strong> falta só um passo — <a href="' . esc_url( admin_url( 'admin.php?page=lorie-assistente' ) ) . '">abrir o assistente de configuração</a>.</p></div>';
	}

	public static function page() {
		Lorie_Admin::header( 'Assistente de configuração', 'Prepara a loja inteira com um clique. Pode rodar de novo sem duplicar nada.' );
		$done = get_option( 'lorie_setup_done' );
		$log  = get_transient( 'lorie_setup_log' );
		?>
		<div class="lorie-cols">
			<section class="lorie-card">
				<h2 class="lorie-h2">O que será feito</h2>
				<ul class="lorie-checklist">
					<li>Moeda em Real, formato brasileiro, apenas Brasil</li>
					<li>Páginas: Início, Catálogo, Quem somos, Carrinho, Finalizar compra, Minha conta, Favoritos, Trocas e devoluções, Privacidade</li>
					<li>Categorias: Bolsas, Saias, Blusinhas, Chapéus e Acessórios</li>
					<li>Cores (com amostra) e tamanhos</li>
					<li>Frete fixo, frete grátis acima de <?php echo esc_html( Lorie_Admin::money( lorie_get( 'free_shipping_min' ) ) ); ?> e retirada</li>
					<li>Pagamento de teste com o código <code><?php echo esc_html( Lorie_Payments::get()['test_code'] ); ?></code></li>
					<li>Menus do topo e do rodapé, tradução para português</li>
				</ul>
			</section>
			<section class="lorie-card">
				<h2 class="lorie-h2"><?php echo $done ? 'Loja já configurada ✓' : 'Vamos lá?'; ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="lorie_setup">
					<?php wp_nonce_field( 'lorie_setup' ); ?>
					<label class="lorie-switch"><input type="checkbox" name="demo" value="1" <?php checked( ! $done ); ?>><span></span> Incluir peças, slides e fotos de exemplo</label>
					<p class="lorie-muted">As fotos de exemplo são texturas de tricô geradas por computador — troque pelas fotos reais das peças assim que puder.</p>
					<button class="lorie-btn lorie-btn--primary" type="submit" data-busy="Configurando… pode levar 1 minuto"><?php echo $done ? 'Rodar novamente' : 'Configurar loja agora'; ?></button>
				</form>
				<?php if ( is_array( $log ) ) : ?>
					<h3>Resultado</h3>
					<ul class="lorie-checklist lorie-checklist--done">
						<?php foreach ( $log as $line ) : ?><li><?php echo esc_html( $line ); ?></li><?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</section>
		</div>
		<?php
		Lorie_Admin::footer();
	}

	public static function handle() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sem permissão.' );
		}
		check_admin_referer( 'lorie_setup' );
		$log = self::run( ! empty( $_POST['demo'] ) );
		set_transient( 'lorie_setup_log', $log, HOUR_IN_SECONDS );
		wp_safe_redirect( admin_url( 'admin.php?page=lorie-assistente&lorie_msg=setup' ) );
		exit;
	}

	/* ================================================================== */

	public static function run( $demo = false ) {
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 600 ); // phpcs:ignore
		}
		wp_raise_memory_limit( 'admin' );
		$log = array();

		self::options();
		$log[] = 'Opções do WooCommerce ajustadas para o Brasil';

		$cats  = self::categories();
		$log[] = 'Categorias prontas';

		self::attributes();
		$log[] = 'Cores e tamanhos criados';

		$pages = self::pages();
		$log[] = 'Páginas criadas';

		self::shipping();
		$log[] = 'Frete configurado';

		self::gateways();
		$log[] = 'Pagamento de teste ativado';

		self::menus( $pages, $cats );
		$log[] = 'Menus montados';

		if ( $demo ) {
			$n     = self::demo( $cats );
			$log[] = "Conteúdo de exemplo: {$n} peça(s), slides, fotos do Instagram";
		}

		$log[] = self::translations();

		update_option( 'lorie_setup_done', time() );
		flush_rewrite_rules();
		return $log;
	}

	private static function options() {
		$opts = array(
			'woocommerce_default_country'                    => 'BR:SP',
			'woocommerce_currency'                           => 'BRL',
			'woocommerce_currency_pos'                       => 'left_space',
			'woocommerce_price_thousand_sep'                 => '.',
			'woocommerce_price_decimal_sep'                  => ',',
			'woocommerce_price_num_decimals'                 => 2,
			'woocommerce_allowed_countries'                  => 'specific',
			'woocommerce_specific_allowed_countries'         => array( 'BR' ),
			'woocommerce_ship_to_countries'                  => '',
			'woocommerce_ship_to_destination'                => 'billing',
			'woocommerce_enable_guest_checkout'              => 'no',
			'woocommerce_enable_checkout_login_reminder'     => 'no',
			'woocommerce_enable_signup_and_login_from_checkout' => 'no',
			'woocommerce_enable_myaccount_registration'      => 'no',
			'woocommerce_calc_taxes'                         => 'no',
			'woocommerce_checkout_phone_field'               => 'required',
			'woocommerce_checkout_company_field'             => 'hidden',
			'woocommerce_checkout_address_2_field'           => 'optional',
			'woocommerce_enable_coupons'                     => 'yes',
			'woocommerce_coming_soon'                        => 'no',
			'woocommerce_store_pages_only'                   => 'no',
			'woocommerce_weight_unit'                        => 'kg',
			'woocommerce_dimension_unit'                     => 'cm',
			'woocommerce_manage_stock'                       => 'yes',
			'woocommerce_hold_stock_minutes'                 => '60',
			'woocommerce_cart_redirect_after_add'            => 'no',
			'woocommerce_enable_ajax_add_to_cart'            => 'yes',
			'woocommerce_review_rating_verification_required' => 'yes',
			'woocommerce_email_base_color'                   => '#B88A3E',
			'woocommerce_email_background_color'             => '#FBF6EF',
			'woocommerce_email_body_background_color'        => '#FFFFFF',
			'woocommerce_email_text_color'                   => '#3A2A21',
			'woocommerce_email_from_name'                    => lorie_get( 'store_name' ),
			'woocommerce_task_list_hidden'                   => 'yes',
			'woocommerce_onboarding_profile'                 => array( 'skipped' => true ),
			'woocommerce_permalinks'                         => array( 'product_base' => 'produto', 'category_base' => 'categoria', 'tag_base' => 'tag-produto', 'attribute_base' => '', 'use_verbose_page_rules' => false ),
			'timezone_string'                                => 'America/Sao_Paulo',
			'date_format'                                    => 'd/m/Y',
			'time_format'                                    => 'H:i',
			'start_of_week'                                  => 0,
			'blogname'                                       => lorie_get( 'store_name' ),
			'blogdescription'                                => lorie_get( 'tagline' ),
			'permalink_structure'                            => '/%postname%/',
		);
		foreach ( $opts as $k => $v ) {
			update_option( $k, $v );
		}
	}

	private static function categories() {
		$defs = array(
			'bolsas'     => array( 'Bolsas', 'Bolsas e clutches tecidas à mão, ponto a ponto.', 'bolsa-1.jpg' ),
			'saias'      => array( 'Saias', 'Saias leves que acompanham o movimento.', 'saia-1.jpg' ),
			'blusinhas'  => array( 'Blusinhas', 'Croppeds e blusas para todas as estações.', 'blusa-1.jpg' ),
			'chapeus'    => array( 'Chapéus', 'Chapéus e buckets para dias de sol.', 'chapeu-1.jpg' ),
			'acessorios' => array( 'Acessórios', 'Detalhes que completam o look.', 'acessorio-1.jpg' ),
		);
		$ids = array();
		$i   = 0;
		foreach ( $defs as $slug => $d ) {
			$term = get_term_by( 'slug', $slug, 'product_cat' );
			if ( ! $term ) {
				$res = wp_insert_term( $d[0], 'product_cat', array( 'slug' => $slug, 'description' => $d[1] ) );
				$tid = is_wp_error( $res ) ? 0 : $res['term_id'];
			} else {
				$tid = $term->term_id;
			}
			if ( $tid ) {
				update_term_meta( $tid, 'order', $i++ );
				if ( ! get_term_meta( $tid, 'thumbnail_id', true ) && file_exists( LORIE_DIR . 'demo/' . $d[2] ) ) {
					$img = self::import_image( $d[2], $d[0] );
					if ( $img ) {
						update_term_meta( $tid, 'thumbnail_id', $img );
					}
				}
				$ids[ $slug ] = $tid;
			}
		}
		// Renomeia a categoria padrão do WooCommerce.
		$uncat = (int) get_option( 'default_product_cat' );
		if ( $uncat ) {
			wp_update_term( $uncat, 'product_cat', array( 'name' => 'Outros', 'slug' => 'outros' ) );
		}
		return $ids;
	}

	public static function colors() {
		return array(
			'Caramelo'  => '#C48C52',
			'Cru'       => '#EFE4D2',
			'Terracota' => '#BE6E5A',
			'Rosé'      => '#D6AAA0',
			'Grafite'   => '#3C3A3E',
			'Sálvia'    => '#96A080',
			'Palha'     => '#DCBE8C',
			'Café'      => '#78523A',
		);
	}

	private static function attributes() {
		Lorie_Colors::ensure_attributes();
		delete_transient( 'wc_attribute_taxonomies' );
		foreach ( self::colors() as $name => $hex ) {
			Lorie_Colors::ensure_color( $name, $hex );
		}
		foreach ( array( 'PP', 'P', 'M', 'G', 'GG', 'Único' ) as $s ) {
			Lorie_Colors::ensure_size( $s );
		}
	}

	private static function make_page( $slug, $title, $content = '', $template = '' ) {
		$found = get_posts( array( 'post_type' => 'page', 'name' => $slug, 'post_status' => array( 'publish', 'draft', 'private', 'pending' ), 'numberposts' => 1 ) );
		$page  = $found ? $found[0] : null;
		if ( $page ) {
			$id = $page->ID;
			if ( 'publish' !== $page->post_status ) {
				wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
			}
		} else {
			$id = wp_insert_post( array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_name'    => $slug,
				'post_title'   => $title,
				'post_content' => $content,
			) );
		}
		if ( $template ) {
			update_post_meta( $id, '_wp_page_template', $template );
		}
		return (int) $id;
	}

	private static function pages() {
		$p = array();
		$p['home']    = self::make_page( 'inicio', 'Início' );
		$p['shop']    = self::make_page( 'catalogo', 'Catálogo' );
		$p['about']   = self::make_page( 'quem-somos', 'Quem somos', '', 'page-templates/quem-somos.php' );
		$p['cart']    = self::make_page( 'carrinho', 'Carrinho', '<!-- wp:shortcode -->[woocommerce_cart]<!-- /wp:shortcode -->' );
		$p['checkout']= self::make_page( 'finalizar-compra', 'Finalizar compra', '<!-- wp:shortcode -->[woocommerce_checkout]<!-- /wp:shortcode -->' );
		$p['account'] = self::make_page( 'minha-conta', 'Minha conta', '<!-- wp:shortcode -->[woocommerce_my_account]<!-- /wp:shortcode -->' );
		$p['wish']    = self::make_page( 'favoritos', 'Favoritos', '', 'page-templates/favoritos.php' );
		$p['returns'] = self::make_page( 'trocas-e-devolucoes', 'Trocas e devoluções', self::returns_text() );
		$p['privacy'] = self::make_page( 'politica-de-privacidade', 'Política de privacidade', self::privacy_text() );

		// Garante que carrinho/checkout usam o modo clássico (layout próprio do tema).
		foreach ( array( 'cart' => '[woocommerce_cart]', 'checkout' => '[woocommerce_checkout]', 'account' => '[woocommerce_my_account]' ) as $k => $sc ) {
			$post = get_post( $p[ $k ] );
			if ( $post && false === strpos( $post->post_content, $sc ) ) {
				wp_update_post( array( 'ID' => $p[ $k ], 'post_content' => '<!-- wp:shortcode -->' . $sc . '<!-- /wp:shortcode -->' ) );
			}
		}

		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $p['home'] );
		update_option( 'woocommerce_shop_page_id', $p['shop'] );
		update_option( 'woocommerce_cart_page_id', $p['cart'] );
		update_option( 'woocommerce_checkout_page_id', $p['checkout'] );
		update_option( 'woocommerce_myaccount_page_id', $p['account'] );
		update_option( 'woocommerce_terms_page_id', $p['returns'] );
		update_option( 'wp_page_for_privacy_policy', $p['privacy'] );
		update_option( 'woocommerce_checkout_privacy_policy_text', 'Seus dados pessoais serão usados para processar seu pedido e para outros fins descritos na nossa [privacy_policy].' );
		return $p;
	}

	private static function returns_text() {
		return "<!-- wp:paragraph --><p><strong>[Revise este texto antes de publicar o site.]</strong></p><!-- /wp:paragraph -->\n"
			. "<!-- wp:heading --><h2>Direito de arrependimento</h2><!-- /wp:heading -->\n"
			. "<!-- wp:paragraph --><p>Compras feitas pela internet podem ser canceladas em até 7 dias corridos após o recebimento, conforme o Código de Defesa do Consumidor (art. 49). Fale com a gente pelo WhatsApp ou e-mail informando o número do pedido.</p><!-- /wp:paragraph -->\n"
			. "<!-- wp:heading --><h2>Peças personalizadas</h2><!-- /wp:heading -->\n"
			. "<!-- wp:paragraph --><p>Pedidos de personalização são avaliados pela artesã depois do pagamento. Se a personalização não for possível, entramos em contato e solicitamos o reembolso integral pelo mesmo meio de pagamento. O prazo para o valor aparecer depende do meio de pagamento e do banco.</p><!-- /wp:paragraph -->\n"
			. "<!-- wp:heading --><h2>Trocas</h2><!-- /wp:heading -->\n"
			. "<!-- wp:paragraph --><p>Defina aqui as regras de troca (prazo, estado da peça, quem paga o frete).</p><!-- /wp:paragraph -->";
	}

	private static function privacy_text() {
		return "<!-- wp:paragraph --><p><strong>[Revise este texto antes de publicar o site.]</strong></p><!-- /wp:paragraph -->\n"
			. "<!-- wp:paragraph --><p>Esta política explica como a " . esc_html( lorie_get( 'store_name' ) ) . " trata seus dados pessoais, conforme a Lei Geral de Proteção de Dados (LGPD — Lei 13.709/2018).</p><!-- /wp:paragraph -->\n"
			. "<!-- wp:heading --><h2>Quais dados coletamos</h2><!-- /wp:heading -->\n"
			. "<!-- wp:paragraph --><p>Nome completo, e-mail, telefone, CPF e endereço de entrega, informados por você no cadastro; e os dados dos pedidos realizados. Não armazenamos dados de cartão.</p><!-- /wp:paragraph -->\n"
			. "<!-- wp:heading --><h2>Para que usamos</h2><!-- /wp:heading -->\n"
			. "<!-- wp:paragraph --><p>Para processar e entregar seus pedidos, emitir documentos, falar com você sobre a encomenda e, se você autorizar, enviar novidades.</p><!-- /wp:paragraph -->\n"
			. "<!-- wp:heading --><h2>Seus direitos</h2><!-- /wp:heading -->\n"
			. "<!-- wp:paragraph --><p>Você pode pedir acesso, correção ou exclusão dos seus dados a qualquer momento pelo e-mail " . esc_html( lorie_get( 'email_contact' ) ) . ".</p><!-- /wp:paragraph -->";
	}

	private static function shipping() {
		if ( get_option( 'lorie_shipping_done' ) ) {
			return;
		}
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'Brasil' );
		$zone->set_zone_order( 0 );
		$zone->add_location( 'BR', 'country' );
		$zone->save();

		$flat = $zone->add_shipping_method( 'flat_rate' );
		update_option( "woocommerce_flat_rate_{$flat}_settings", array( 'title' => 'Envio pelos Correios', 'tax_status' => 'none', 'cost' => '25' ) );

		$free = $zone->add_shipping_method( 'free_shipping' );
		update_option( "woocommerce_free_shipping_{$free}_settings", array( 'title' => 'Frete grátis', 'requires' => 'min_amount', 'min_amount' => (string) lorie_get( 'free_shipping_min' ), 'ignore_discounts' => 'no' ) );

		$pick = $zone->add_shipping_method( 'local_pickup' );
		update_option( "woocommerce_local_pickup_{$pick}_settings", array( 'title' => 'Retirar com a artesã (combinar pelo WhatsApp)', 'tax_status' => 'none', 'cost' => '' ) );

		update_option( 'lorie_shipping_done', 1 );
	}

	private static function gateways() {
		update_option( 'woocommerce_lorie_test_settings', array(
			'enabled'     => 'yes',
			'title'       => 'Código de pagamento',
			'description' => 'Loja em fase de testes: nenhum valor é cobrado. Digite o código de pagamento para concluir seu pedido.',
		) );
		foreach ( array( 'bacs', 'cheque', 'cod' ) as $g ) {
			$s            = get_option( "woocommerce_{$g}_settings", array() );
			$s            = is_array( $s ) ? $s : array();
			$s['enabled'] = 'no';
			update_option( "woocommerce_{$g}_settings", $s );
		}
		update_option( 'woocommerce_gateway_order', array( 'lorie_test' => 0 ) );
	}

	private static function menus( $pages, $cats ) {
		$make = function ( $name, $location, $items ) {
			$menu = wp_get_nav_menu_object( $name );
			if ( $menu ) {
				return $menu->term_id;
			}
			$id = wp_create_nav_menu( $name );
			foreach ( $items as $it ) {
				$args = array( 'menu-item-title' => $it[0], 'menu-item-status' => 'publish' );
				if ( 'page' === $it[1] ) {
					$args += array( 'menu-item-object' => 'page', 'menu-item-object-id' => $it[2], 'menu-item-type' => 'post_type' );
				} elseif ( 'cat' === $it[1] ) {
					$args += array( 'menu-item-object' => 'product_cat', 'menu-item-object-id' => $it[2], 'menu-item-type' => 'taxonomy' );
				} else {
					$args += array( 'menu-item-url' => $it[2], 'menu-item-type' => 'custom' );
				}
				wp_update_nav_menu_item( $id, 0, $args );
			}
			$locs              = get_theme_mod( 'nav_menu_locations', array() );
			$locs[ $location ] = $id;
			set_theme_mod( 'nav_menu_locations', $locs );
			return $id;
		};

		$make( 'Principal', 'primary', array(
			array( 'Início', 'page', $pages['home'] ),
			array( 'Catálogo', 'page', $pages['shop'] ),
			array( 'Bolsas', 'cat', $cats['bolsas'] ?? 0 ),
			array( 'Roupas', 'cat', $cats['blusinhas'] ?? 0 ),
			array( 'Quem somos', 'page', $pages['about'] ),
		) );
		$make( 'Rodapé — Loja', 'footer_shop', array_map( fn( $slug ) => array( get_term( $cats[ $slug ] )->name, 'cat', $cats[ $slug ] ), array_keys( $cats ) ) );
		$make( 'Rodapé — Ajuda', 'footer_help', array(
			array( 'Minha conta', 'page', $pages['account'] ),
			array( 'Favoritos', 'page', $pages['wish'] ),
			array( 'Trocas e devoluções', 'page', $pages['returns'] ),
			array( 'Política de privacidade', 'page', $pages['privacy'] ),
		) );
	}

	/** Baixa as traduções em português do WordPress e do WooCommerce (precisa de internet). */
	private static function translations() {
		update_option( 'WPLANG', 'pt_BR' );
		try {
			require_once ABSPATH . 'wp-admin/includes/translation-install.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			require_once ABSPATH . 'wp-admin/includes/update.php';
			$core = wp_download_language_pack( 'pt_BR' );
			wp_clean_update_cache();
			wp_update_plugins();
			wp_update_themes();
			$updates = wp_get_translation_updates();
			if ( $updates ) {
				$upgrader = new Language_Pack_Upgrader( new Automatic_Upgrader_Skin() );
				$upgrader->bulk_upgrade( $updates );
			}
			return $core ? 'Traduções em português instaladas' : 'Idioma definido como português (as traduções serão baixadas quando houver internet)';
		} catch ( Throwable $e ) {
			return 'Idioma definido como português';
		}
	}

	/* ================================================================== Demo */

	public static function import_image( $file, $title ) {
		$cache = get_option( 'lorie_demo_images', array() );
		$cache = is_array( $cache ) ? $cache : array();
		if ( isset( $cache[ $file ] ) && get_post( $cache[ $file ] ) ) {
			return (int) $cache[ $file ];
		}
		$src = LORIE_DIR . 'demo/' . $file;
		if ( ! file_exists( $src ) ) {
			return 0;
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$tmp = wp_tempnam( $file );
		copy( $src, $tmp );
		$id = media_handle_sideload( array( 'name' => 'lorie-' . $file, 'tmp_name' => $tmp ), 0, $title );
		if ( is_wp_error( $id ) ) {
			@unlink( $tmp ); // phpcs:ignore
			return 0;
		}
		update_post_meta( $id, '_wp_attachment_image_alt', $title );
		update_post_meta( $id, '_lorie_demo', 1 );
		$cache[ $file ] = $id;
		update_option( 'lorie_demo_images', $cache, false );
		return (int) $id;
	}

	private static function demo( $cats ) {
		$products = array(
			array( 'Bolsa Aurora', 'bolsas', 189.90, '', array( 'bolsa-1.jpg', 'bolsa-2.jpg' ), array( 'Caramelo', 'Cru', 'Terracota' ), array(), null, true, 'Bolsa estruturada em fio de algodão, com alça de mão e forro de tecido. Cabe celular, carteira e o essencial do dia.' ),
			array( 'Bolsa Mar de Trigo', 'bolsas', 219.90, '', array( 'bolsa-3.jpg', 'bolsa-1.jpg' ), array( 'Cru', 'Palha' ), array(), null, true, 'Ponto trançado que lembra um campo de trigo. Espaçosa, ideal para passeios de fim de semana.' ),
			array( 'Mini bolsa Florescer', 'bolsas', 129.90, 109.90, array( 'bolsa-4.jpg' ), array( 'Sálvia', 'Rosé' ), array(), 3, false, 'A pequena que acompanha tudo: alça curta e fecho em botão de madeira.' ),
			array( 'Saia Brisa', 'saias', 159.90, '', array( 'saia-1.jpg', 'saia-2.jpg' ), array( 'Rosé', 'Grafite' ), array( 'P', 'M', 'G' ), null, true, 'Saia midi em ponto leque, com elástico confortável na cintura e forro removível.' ),
			array( 'Cropped Lírio', 'blusinhas', 139.90, '', array( 'blusa-1.jpg', 'blusa-3.jpg' ), array( 'Cru', 'Sálvia' ), array( 'PP', 'P', 'M', 'G' ), null, true, 'Cropped de alças finas em ponto aberto. Fresquinho para os dias quentes.' ),
			array( 'Blusa Terracota', 'blusinhas', 149.90, 129.90, array( 'blusa-2.jpg' ), array(), array( 'P', 'M', 'G' ), 2, false, 'Regata de ponto fechado, caimento soltinho e barra levemente arredondada.' ),
			array( 'Chapéu Sol de Outono', 'chapeus', 119.90, '', array( 'chapeu-1.jpg' ), array( 'Palha', 'Café' ), array(), null, false, 'Aba média estruturada para proteger do sol com charme.' ),
			array( 'Bucket Café', 'chapeus', 99.90, '', array( 'chapeu-2.jpg' ), array(), array(), 4, false, 'Bucket de crochê firme, dobrável e fácil de levar na bolsa.' ),
			array( 'Scrunchie Nuvem', 'acessorios', 24.90, '', array( 'acessorio-1.jpg' ), array( 'Rosé', 'Caramelo' ), array(), 10, false, 'Prendedor de cabelo macio que não marca os fios.' ),
			array( 'Tiara Trança', 'acessorios', 39.90, '', array( 'acessorio-2.jpg' ), array(), array(), 5, false, 'Tiara forrada em crochê trançado. Acabamento delicado.' ),
		);
		$count = 0;
		foreach ( $products as $d ) {
			list( $name, $cat, $price, $sale, $imgs, $colors, $sizes, $stock, $featured, $desc ) = $d;
			$existing = get_posts( array( 'post_type' => 'product', 'name' => sanitize_title( $name ), 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ) );
			if ( $existing ) {
				continue;
			}
			$img_ids = array_filter( array_map( fn( $f ) => self::import_image( $f, $name ), $imgs ) );
			$cids    = array_map( fn( $c ) => Lorie_Colors::ensure_color( $c ), $colors );
			$sids    = array_map( fn( $s ) => Lorie_Colors::ensure_size( $s ), $sizes );
			$is_var  = $cids || $sids;
			$p       = $is_var ? new WC_Product_Variable() : new WC_Product_Simple();
			$p->set_name( $name );
			$p->set_status( 'publish' );
			$p->set_featured( $featured );
			$p->set_short_description( '<p>' . $desc . '</p>' );
			$p->set_description( '<p>' . $desc . '</p><h4>Materiais</h4><p>Fio 100% algodão, feito à mão.</p><h4>Cuidados</h4><p>Lavar à mão em água fria, secar à sombra na horizontal.</p>' );
			$p->set_category_ids( array( $cats[ $cat ] ) );
			$p->set_image_id( $img_ids ? reset( $img_ids ) : 0 );
			$p->set_gallery_image_ids( array_slice( array_values( $img_ids ), 1 ) );
			$p->update_meta_data( '_lorie_allow_custom', 'yes' );
			$p->update_meta_data( '_lorie_demo', 1 );
			if ( ! $is_var ) {
				$p->set_regular_price( $price );
				if ( $sale ) {
					$p->set_sale_price( $sale );
				}
				if ( null !== $stock ) {
					$p->set_manage_stock( true );
					$p->set_stock_quantity( $stock );
				}
				$p->save();
			} else {
				$attrs = array();
				foreach ( array( 'pa_cor' => $cids, 'pa_tamanho' => $sids ) as $tax => $ids ) {
					if ( ! $ids ) {
						continue;
					}
					$a = new WC_Product_Attribute();
					$a->set_id( wc_attribute_taxonomy_id_by_name( $tax ) );
					$a->set_name( $tax );
					$a->set_options( $ids );
					$a->set_visible( true );
					$a->set_variation( true );
					$attrs[] = $a;
				}
				$p->set_attributes( $attrs );
				$p->save();
				Lorie_Products_Admin::sync_variations( $p, $cids, $sids, wc_format_decimal( $price, 2 ), $sale ? wc_format_decimal( $sale, 2 ) : '', $stock );
			}
			$count++;
		}

		// Slides do topo.
		if ( ! get_posts( array( 'post_type' => 'lorie_slide', 'numberposts' => 1, 'post_status' => 'any' ) ) ) {
			$shop   = wc_get_page_permalink( 'shop' );
			$slides = array(
				array( 'Feito à mão, feito para durar', 'Nova coleção', 'Ver coleção', $shop, 'slide-1.jpg', 'left' ),
				array( 'Bolsas que contam histórias', 'Bolsas', 'Conhecer bolsas', get_term_link( (int) $cats['bolsas'] ), 'slide-2.jpg', 'left' ),
				array( 'Leveza para os dias de sol', 'Verão', 'Ver blusinhas', get_term_link( (int) $cats['blusinhas'] ), 'slide-3.jpg', 'center' ),
				array( 'Sua peça, do seu jeito', 'Encomendas', 'Personalizar', lorie_whatsapp_url( 'Olá! Quero encomendar uma peça personalizada.' ), 'slide-4.jpg', 'right' ),
			);
			foreach ( $slides as $i => $s ) {
				$sid = wp_insert_post( array( 'post_type' => 'lorie_slide', 'post_status' => 'publish', 'post_title' => $s[0], 'menu_order' => $i ) );
				update_post_meta( $sid, '_lorie_eyebrow', $s[1] );
				update_post_meta( $sid, '_lorie_btn_text', $s[2] );
				update_post_meta( $sid, '_lorie_btn_link', is_wp_error( $s[3] ) ? $shop : $s[3] );
				update_post_meta( $sid, '_lorie_align', $s[5] );
				$img = self::import_image( $s[4], $s[0] );
				if ( $img ) {
					set_post_thumbnail( $sid, $img );
				}
			}
		}

		// Instagram (modo manual com fotos de exemplo).
		$ig = Lorie_Instagram::get();
		if ( ! $ig['posts'] ) {
			$posts = array();
			for ( $i = 1; $i <= 6; $i++ ) {
				$img = self::import_image( "insta-{$i}.jpg", 'Instagram ' . $i );
				if ( $img ) {
					$posts[] = array( 'image' => $img, 'link' => lorie_instagram_url() );
				}
			}
			update_option( Lorie_Instagram::OPTION, array( 'mode' => 'manual', 'posts' => $posts ) );
		}

		// Fotos da página inicial e do "Quem somos".
		if ( ! get_theme_mod( 'lorie_story_image' ) ) {
			set_theme_mod( 'lorie_story_image', self::import_image( 'sobre.jpg', 'Ateliê' ) );
		}
		if ( ! get_theme_mod( 'lorie_about_photo' ) ) {
			set_theme_mod( 'lorie_about_photo', self::import_image( 'sobre.jpg', 'Foto da artesã' ) );
		}
		return $count;
	}
}
