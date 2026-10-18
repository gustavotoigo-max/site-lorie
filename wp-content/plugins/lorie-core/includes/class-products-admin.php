<?php
/**
 * Cadastro simplificado de produtos para a artesã: nome, categoria, preço, fotos,
 * cores e tamanhos (gera as variações automaticamente), estoque ou sob encomenda.
 * O editor completo do WooCommerce continua disponível para casos avançados.
 */

defined( 'ABSPATH' ) || exit;

class Lorie_Products_Admin {

	public static function init() {
		add_action( 'admin_post_lorie_save_product', array( __CLASS__, 'save' ) );
		add_action( 'admin_post_lorie_trash_product', array( __CLASS__, 'trash' ) );
	}

	public static function parse_price( $v ) {
		$v = trim( (string) $v );
		if ( '' === $v ) {
			return '';
		}
		$v = preg_replace( '/[^\d,\.]/', '', $v );
		if ( false !== strpos( $v, ',' ) ) {
			$v = str_replace( '.', '', $v );
			$v = str_replace( ',', '.', $v );
		}
		return wc_format_decimal( $v, 2 );
	}

	public static function price_br( $v ) {
		return '' === $v || null === $v ? '' : number_format( (float) $v, 2, ',', '.' );
	}

	/* ------------------------------------------------------------------ Lista */

	public static function page_list() {
		$cat   = isset( $_GET['cat'] ) ? absint( $_GET['cat'] ) : 0; // phpcs:ignore
		$s     = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore
		$args  = array( 'limit' => 200, 'status' => array( 'publish', 'draft', 'private' ), 'orderby' => 'date', 'order' => 'DESC' );
		if ( $cat ) {
			$term = get_term( $cat, 'product_cat' );
			if ( $term && ! is_wp_error( $term ) ) {
				$args['category'] = array( $term->slug );
			}
		}
		if ( $s ) {
			$args['s'] = $s;
		}
		$products = wc_get_products( $args );
		$cats     = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'exclude' => array( (int) get_option( 'default_product_cat' ) ) ) );

		$actions = '<a class="lorie-btn lorie-btn--primary" href="' . esc_url( admin_url( 'admin.php?page=lorie-produto' ) ) . '">+ Novo produto</a>';
		Lorie_Admin::header( 'Produtos', count( $products ) . ' peça(s) no catálogo.', $actions );
		?>
		<div class="lorie-toolbar">
			<nav class="lorie-chips">
				<a class="<?php echo $cat ? '' : 'is-active'; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=lorie-produtos' ) ); ?>">Todos</a>
				<?php foreach ( $cats as $c ) : ?>
					<a class="<?php echo $cat === $c->term_id ? 'is-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=lorie-produtos&cat=' . $c->term_id ) ); ?>"><?php echo esc_html( $c->name ); ?> <small><?php echo (int) $c->count; ?></small></a>
				<?php endforeach; ?>
			</nav>
			<form method="get" class="lorie-search">
				<input type="hidden" name="page" value="lorie-produtos">
				<input type="search" name="s" value="<?php echo esc_attr( $s ); ?>" placeholder="Buscar peça…">
			</form>
		</div>
		<?php if ( ! $products ) : ?>
			<section class="lorie-card lorie-empty-state">
				<p>Nenhuma peça encontrada.</p>
				<a class="lorie-btn lorie-btn--primary" href="<?php echo esc_url( admin_url( 'admin.php?page=lorie-produto' ) ); ?>">Cadastrar a primeira peça</a>
			</section>
		<?php else : ?>
			<div class="lorie-products">
				<?php foreach ( $products as $p ) : ?>
					<?php
					$colors = array();
					if ( $p->is_type( 'variable' ) ) {
						foreach ( wc_get_product_terms( $p->get_id(), 'pa_cor', array( 'fields' => 'all' ) ) as $t ) {
							$colors[] = $t;
						}
					}
					$stock = $p->is_in_stock() ? ( $p->managing_stock() ? $p->get_stock_quantity() . ' em estoque' : ( $p->is_type( 'variable' ) ? 'Disponível' : 'Sob encomenda' ) ) : 'Esgotado';
					$trash = wp_nonce_url( admin_url( 'admin-post.php?action=lorie_trash_product&id=' . $p->get_id() ), 'lorie_trash_' . $p->get_id() );
					?>
					<article class="lorie-product">
						<a class="lorie-product__img" href="<?php echo esc_url( admin_url( 'admin.php?page=lorie-produto&id=' . $p->get_id() ) ); ?>">
							<?php echo $p->get_image( 'woocommerce_thumbnail' ); // phpcs:ignore ?>
							<?php if ( 'publish' !== $p->get_status() ) : ?><span class="lorie-pill lorie-pill--warn">Rascunho</span><?php endif; ?>
							<?php if ( $p->is_featured() ) : ?><span class="lorie-pill lorie-pill--ok lorie-product__star">★ Destaque</span><?php endif; ?>
						</a>
						<div class="lorie-product__body">
							<small class="lorie-muted"><?php echo wp_kses_post( wc_get_product_category_list( $p->get_id(), ', ' ) ); ?></small>
							<h3><?php echo esc_html( $p->get_name() ); ?></h3>
							<div class="lorie-product__price"><?php echo wp_kses_post( $p->get_price_html() ); ?></div>
							<div class="lorie-product__meta">
								<span class="lorie-dots"><?php foreach ( $colors as $t ) : ?><i style="background:<?php echo esc_attr( Lorie_Colors::hex( $t ) ); ?>" title="<?php echo esc_attr( $t->name ); ?>"></i><?php endforeach; ?></span>
								<span class="lorie-muted"><?php echo esc_html( $stock ); ?></span>
							</div>
							<div class="lorie-product__actions">
								<a class="lorie-btn lorie-btn--sm" href="<?php echo esc_url( admin_url( 'admin.php?page=lorie-produto&id=' . $p->get_id() ) ); ?>">Editar</a>
								<a class="lorie-link-btn" href="<?php echo esc_url( get_permalink( $p->get_id() ) ); ?>" target="_blank">Ver</a>
								<a class="lorie-link-btn lorie-link-btn--danger" href="<?php echo esc_url( $trash ); ?>" data-confirm="Mover &quot;<?php echo esc_attr( $p->get_name() ); ?>&quot; para a lixeira?">Excluir</a>
							</div>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<?php
		Lorie_Admin::footer();
	}

	public static function trash() {
		$id = absint( $_GET['id'] ?? 0 );
		if ( ! current_user_can( 'delete_post', $id ) ) {
			wp_die( 'Sem permissão.' );
		}
		check_admin_referer( 'lorie_trash_' . $id );
		wp_trash_post( $id );
		wp_safe_redirect( admin_url( 'admin.php?page=lorie-produtos' ) );
		exit;
	}

	/* ------------------------------------------------------------------ Editor */

	public static function page_edit() {
		Lorie_Colors::ensure_attributes();
		$id      = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore
		$product = $id ? wc_get_product( $id ) : null;
		$is_edit = (bool) $product;

		$sel_colors = array();
		$sel_sizes  = array();
		$stock_qty  = '';
		$price      = $product ? $product->get_regular_price() : '';
		$sale       = $product ? $product->get_sale_price() : '';
		if ( $product && $product->is_type( 'variable' ) ) {
			$sel_colors = wc_get_product_terms( $id, 'pa_cor', array( 'fields' => 'ids' ) );
			$sel_sizes  = wc_get_product_terms( $id, 'pa_tamanho', array( 'fields' => 'ids' ) );
			$children   = $product->get_children();
			if ( $children ) {
				$first = wc_get_product( $children[0] );
				if ( $first ) {
					$price     = $first->get_regular_price();
					$sale      = $first->get_sale_price();
					$stock_qty = $first->managing_stock() ? $first->get_stock_quantity() : '';
				}
			}
		} elseif ( $product ) {
			$stock_qty = $product->managing_stock() ? $product->get_stock_quantity() : '';
		}

		$images = array();
		if ( $product ) {
			$images = array_filter( array_merge( array( $product->get_image_id() ), $product->get_gallery_image_ids() ) );
		}
		$cat_ids   = $product ? $product->get_category_ids() : array();
		$cats      = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'exclude' => array( (int) get_option( 'default_product_cat' ) ) ) );
		$colors    = get_terms( array( 'taxonomy' => 'pa_cor', 'hide_empty' => false, 'orderby' => 'name' ) );
		$sizes     = get_terms( array( 'taxonomy' => 'pa_tamanho', 'hide_empty' => false, 'orderby' => 'term_id', 'order' => 'ASC' ) );
		$days      = $product ? $product->get_meta( '_lorie_production_days' ) : '';
		$custom_ok = $product ? 'no' !== $product->get_meta( '_lorie_allow_custom' ) : true;

		$actions = $is_edit ? '<a class="lorie-btn" href="' . esc_url( get_permalink( $id ) ) . '" target="_blank">Ver na loja ↗</a> <a class="lorie-link-btn" href="' . esc_url( get_edit_post_link( $id ) ) . '">Editor avançado</a>' : '';
		Lorie_Admin::header( $is_edit ? 'Editar: ' . $product->get_name() : 'Nova peça', 'Preencha o básico — as combinações de cor e tamanho são criadas automaticamente.', $actions );
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="lorie-editor" data-lorie-editor>
			<input type="hidden" name="action" value="lorie_save_product">
			<input type="hidden" name="id" value="<?php echo (int) $id; ?>">
			<?php wp_nonce_field( 'lorie_save_product' ); ?>

			<div class="lorie-editor__main">
				<section class="lorie-card">
					<div class="lorie-field">
						<label for="p_name">Nome da peça</label>
						<input type="text" id="p_name" name="name" required value="<?php echo esc_attr( $product ? $product->get_name() : '' ); ?>" placeholder="Ex.: Bolsa Aurora" class="lorie-input-lg">
					</div>
					<div class="lorie-field">
						<label for="p_short">Descrição curta <small>(aparece ao lado do preço)</small></label>
						<textarea id="p_short" name="short" rows="3" placeholder="Duas ou três linhas sobre a peça."><?php echo esc_textarea( $product ? wp_strip_all_tags( $product->get_short_description() ) : '' ); ?></textarea>
					</div>
					<div class="lorie-field">
						<label>Descrição completa <small>(materiais, medidas, cuidados)</small></label>
						<?php
						wp_editor( $product ? $product->get_description() : '', 'p_desc', array(
							'textarea_name' => 'description',
							'textarea_rows' => 8,
							'media_buttons' => false,
							'teeny'         => true,
						) );
						?>
					</div>
				</section>

				<section class="lorie-card">
					<h2 class="lorie-h2">Fotos <small class="lorie-muted">a primeira é a principal · arraste para reordenar</small></h2>
					<div class="lorie-gallery" data-lorie-gallery>
						<?php foreach ( $images as $img ) : ?>
							<figure class="lorie-gallery__item" draggable="true">
								<?php echo wp_get_attachment_image( $img, 'thumbnail' ); ?>
								<input type="hidden" name="images[]" value="<?php echo (int) $img; ?>">
								<button type="button" class="lorie-gallery__rm" aria-label="Remover foto">×</button>
							</figure>
						<?php endforeach; ?>
						<button type="button" class="lorie-gallery__add" data-add-images>+ Adicionar fotos</button>
					</div>
				</section>

				<section class="lorie-card">
					<h2 class="lorie-h2">Cores</h2>
					<p class="lorie-muted">Marque as cores disponíveis. Deixe tudo desmarcado se a peça tem uma cor só.</p>
					<div class="lorie-swatches" data-lorie-colors>
						<?php foreach ( $colors as $c ) : ?>
							<label class="lorie-swatch">
								<input type="checkbox" name="colors[]" value="<?php echo (int) $c->term_id; ?>" <?php checked( in_array( $c->term_id, $sel_colors, true ) ); ?>>
								<i style="background:<?php echo esc_attr( Lorie_Colors::hex( $c ) ); ?>"></i><span><?php echo esc_html( $c->name ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
					<div class="lorie-addrow">
						<input type="text" placeholder="Nova cor (ex.: Verde-oliva)" data-new-color-name>
						<input type="color" value="#a7b09a" data-new-color-hex>
						<button type="button" class="lorie-btn lorie-btn--sm" data-add-color>Adicionar cor</button>
					</div>
				</section>

				<section class="lorie-card">
					<h2 class="lorie-h2">Tamanhos</h2>
					<p class="lorie-muted">Para bolsas e acessórios, normalmente "Único" ou nenhum.</p>
					<div class="lorie-chips lorie-chips--check" data-lorie-sizes>
						<?php foreach ( $sizes as $sz ) : ?>
							<label><input type="checkbox" name="sizes[]" value="<?php echo (int) $sz->term_id; ?>" <?php checked( in_array( $sz->term_id, $sel_sizes, true ) ); ?>><span><?php echo esc_html( $sz->name ); ?></span></label>
						<?php endforeach; ?>
					</div>
					<div class="lorie-addrow">
						<input type="text" placeholder="Novo tamanho (ex.: 38)" data-new-size-name>
						<button type="button" class="lorie-btn lorie-btn--sm" data-add-size>Adicionar tamanho</button>
					</div>
				</section>
			</div>

			<aside class="lorie-editor__side">
				<section class="lorie-card">
					<h2 class="lorie-h2">Publicação</h2>
					<label class="lorie-switch"><input type="checkbox" name="publish" value="1" <?php checked( ! $product || 'publish' === $product->get_status() ); ?>><span></span> Visível na loja</label>
					<label class="lorie-switch"><input type="checkbox" name="featured" value="1" <?php checked( $product && $product->is_featured() ); ?>><span></span> Destaque na página inicial</label>
					<label class="lorie-switch"><input type="checkbox" name="allow_custom" value="1" <?php checked( $custom_ok ); ?>><span></span> Aceita pedido de personalização</label>
					<button class="lorie-btn lorie-btn--primary lorie-btn--block" type="submit"><?php echo $is_edit ? 'Salvar alterações' : 'Publicar peça'; ?></button>
				</section>
				<section class="lorie-card">
					<h2 class="lorie-h2">Preço</h2>
					<div class="lorie-field lorie-field--money">
						<label for="p_price">Preço (R$)</label>
						<input type="text" inputmode="decimal" id="p_price" name="price" required value="<?php echo esc_attr( self::price_br( $price ) ); ?>" placeholder="189,90">
					</div>
					<div class="lorie-field lorie-field--money">
						<label for="p_sale">Preço promocional (R$) <small>opcional</small></label>
						<input type="text" inputmode="decimal" id="p_sale" name="sale" value="<?php echo esc_attr( self::price_br( $sale ) ); ?>">
					</div>
				</section>
				<section class="lorie-card">
					<h2 class="lorie-h2">Categoria</h2>
					<div class="lorie-radios">
						<?php foreach ( $cats as $c ) : ?>
							<label class="lorie-radio"><input type="radio" name="category" value="<?php echo (int) $c->term_id; ?>" <?php checked( in_array( $c->term_id, $cat_ids, true ) ); ?> required> <?php echo esc_html( $c->name ); ?></label>
						<?php endforeach; ?>
					</div>
				</section>
				<section class="lorie-card">
					<h2 class="lorie-h2">Estoque e prazo</h2>
					<div class="lorie-field">
						<label for="p_stock">Peças prontas por opção <small>(vazio = feito sob encomenda)</small></label>
						<input type="number" min="0" id="p_stock" name="stock" value="<?php echo esc_attr( $stock_qty ); ?>">
					</div>
					<div class="lorie-field">
						<label for="p_days">Prazo de produção (dias)</label>
						<input type="number" min="0" id="p_days" name="days" value="<?php echo esc_attr( $days ); ?>" placeholder="<?php echo esc_attr( lorie_get( 'production_days' ) ); ?>">
					</div>
				</section>
			</aside>
		</form>
		<?php
		Lorie_Admin::footer();
	}

	public static function save() {
		if ( ! current_user_can( 'edit_products' ) ) {
			wp_die( 'Sem permissão.' );
		}
		check_admin_referer( 'lorie_save_product' );
		Lorie_Colors::ensure_attributes();
		$post = wp_unslash( $_POST );

		$id     = absint( $post['id'] ?? 0 );
		$name   = sanitize_text_field( $post['name'] ?? '' );
		$price  = self::parse_price( $post['price'] ?? '' );
		$sale   = self::parse_price( $post['sale'] ?? '' );
		$stock  = '' === trim( (string) ( $post['stock'] ?? '' ) ) ? null : absint( $post['stock'] );
		$colors = array_map( 'absint', (array) ( $post['colors'] ?? array() ) );
		$sizes  = array_map( 'absint', (array) ( $post['sizes'] ?? array() ) );

		// Cores e tamanhos novos digitados no próprio formulário.
		foreach ( (array) ( $post['new_colors'] ?? array() ) as $nc ) {
			list( $n, $hex ) = array_pad( explode( '|', $nc ), 2, '' );
			$tid = Lorie_Colors::ensure_color( sanitize_text_field( $n ), $hex );
			if ( $tid ) {
				$colors[] = $tid;
			}
		}
		foreach ( (array) ( $post['new_sizes'] ?? array() ) as $ns ) {
			$tid = Lorie_Colors::ensure_size( sanitize_text_field( $ns ) );
			if ( $tid ) {
				$sizes[] = $tid;
			}
		}
		$colors = array_values( array_unique( array_filter( $colors ) ) );
		$sizes  = array_values( array_unique( array_filter( $sizes ) ) );
		$is_var = $colors || $sizes;

		$product = self::product_object( $id, $is_var );
		$product->set_name( $name ? $name : 'Peça sem nome' );
		$product->set_status( ! empty( $post['publish'] ) ? 'publish' : 'draft' );
		$product->set_featured( ! empty( $post['featured'] ) );
		$product->set_short_description( wp_kses_post( wpautop( $post['short'] ?? '' ) ) );
		$product->set_description( wp_kses_post( $post['description'] ?? '' ) );
		$product->set_category_ids( array_filter( array( absint( $post['category'] ?? 0 ) ) ) );
		$imgs = array_values( array_filter( array_map( 'absint', (array) ( $post['images'] ?? array() ) ) ) );
		$product->set_image_id( $imgs[0] ?? 0 );
		$product->set_gallery_image_ids( array_slice( $imgs, 1 ) );
		$product->update_meta_data( '_lorie_production_days', absint( $post['days'] ?? 0 ) ?: '' );
		$product->update_meta_data( '_lorie_allow_custom', ! empty( $post['allow_custom'] ) ? 'yes' : 'no' );

		if ( ! $is_var ) {
			$product->set_regular_price( $price );
			$product->set_sale_price( $sale && (float) $sale < (float) $price ? $sale : '' );
			self::apply_stock( $product, $stock );
			$product->save();
		} else {
			$attrs = array();
			foreach ( array( 'pa_cor' => $colors, 'pa_tamanho' => $sizes ) as $tax => $ids ) {
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
			$product->set_attributes( $attrs );
			$product->set_stock_status( 'instock' );
			$product->set_manage_stock( false );
			$product->save();
			self::sync_variations( $product, $colors, $sizes, $price, $sale, $stock );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=lorie-produto&id=' . $product->get_id() . '&lorie_msg=created' ) );
		exit;
	}

	private static function product_object( $id, $is_var ) {
		$type = $is_var ? 'variable' : 'simple';
		if ( $id && ( $existing = wc_get_product( $id ) ) ) {
			if ( $existing->get_type() !== $type ) {
				if ( 'simple' === $type ) {
					foreach ( $existing->get_children() as $child ) {
						wp_delete_post( $child, true );
					}
				}
				wp_set_object_terms( $id, $type, 'product_type' );
				wc_delete_product_transients( $id );
				$class = WC_Product_Factory::get_classname_from_product_type( $type );
				return new $class( $id );
			}
			return $existing;
		}
		return $is_var ? new WC_Product_Variable() : new WC_Product_Simple();
	}

	private static function apply_stock( $p, $stock ) {
		if ( null === $stock ) {
			$p->set_manage_stock( false );
			$p->set_stock_status( 'instock' );
		} else {
			$p->set_manage_stock( true );
			$p->set_stock_quantity( $stock );
			$p->set_backorders( 'no' );
		}
	}

	/** Cria/atualiza uma variação para cada combinação cor × tamanho e remove as que saíram. */
	public static function sync_variations( $product, $colors, $sizes, $price, $sale, $stock ) {
		$color_slugs = array_map( fn( $id ) => get_term( $id, 'pa_cor' )->slug, $colors );
		$size_slugs  = array_map( fn( $id ) => get_term( $id, 'pa_tamanho' )->slug, $sizes );
		$combos      = array();
		foreach ( $color_slugs ? $color_slugs : array( null ) as $c ) {
			foreach ( $size_slugs ? $size_slugs : array( null ) as $s ) {
				$attrs = array();
				if ( $c ) {
					$attrs['pa_cor'] = $c;
				}
				if ( $s ) {
					$attrs['pa_tamanho'] = $s;
				}
				ksort( $attrs );
				$combos[ wp_json_encode( $attrs ) ] = $attrs;
			}
		}

		$existing = array();
		foreach ( $product->get_children() as $child_id ) {
			$v = wc_get_product( $child_id );
			if ( ! $v ) {
				continue;
			}
			$a = array_filter( $v->get_attributes() );
			ksort( $a );
			$existing[ wp_json_encode( $a ) ] = $v;
		}

		$menu = 0;
		foreach ( $combos as $key => $attrs ) {
			$v = $existing[ $key ] ?? new WC_Product_Variation();
			unset( $existing[ $key ] );
			$v->set_parent_id( $product->get_id() );
			$v->set_attributes( $attrs );
			$v->set_regular_price( $price );
			$v->set_sale_price( $sale && (float) $sale < (float) $price ? $sale : '' );
			$v->set_status( 'publish' );
			$v->set_menu_order( $menu++ );
			self::apply_stock( $v, $stock );
			$v->save();
		}
		foreach ( $existing as $old ) {
			$old->delete( true );
		}
		WC_Product_Variable::sync( $product->get_id() );
		wc_delete_product_transients( $product->get_id() );
	}
}
