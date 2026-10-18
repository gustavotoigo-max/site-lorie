<?php
/**
 * Catálogo. Na página principal do catálogo, mostra uma seção para cada categorias
 * (Bolsas, Saias, Blusinhas, Chapéus, Acessórios…). Em categorias e buscas, mostra a grade filtrada.
 *
 * @package WooCommerce\Templates
 * @version 8.6.0
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

$cats      = lorie_product_categories();
$current   = is_product_category() ? get_queried_object() : null;
$is_main   = is_shop() && ! is_search() && empty( $_GET['orderby'] ) && ! is_paged(); // phpcs:ignore
$shop_url  = wc_get_page_permalink( 'shop' );
$per_cat   = 8;

do_action( 'woocommerce_before_main_content' );
?>
<header class="shop-hero">
	<div class="container">
		<?php woocommerce_breadcrumb(); ?>
		<h1 class="shop-hero__title"><?php echo $is_main ? 'Catálogo' : esc_html( woocommerce_page_title( false ) ); ?></h1>
		<?php if ( $current && $current->description ) : ?>
			<p class="shop-hero__desc"><?php echo esc_html( $current->description ); ?></p>
		<?php elseif ( $is_main ) : ?>
			<p class="shop-hero__desc">Peças feitas à mão, uma a uma. Escolha a categoria ou role para ver tudo.</p>
		<?php endif; ?>
	</div>
</header>

<nav class="cat-nav" data-cat-nav aria-label="Categorias">
	<div class="container cat-nav__inner">
		<a class="cat-nav__chip <?php echo $is_main ? 'is-active' : ''; ?>" href="<?php echo esc_url( $shop_url ); ?>" <?php echo $is_main ? 'data-spy="top"' : ''; ?>>Todos</a>
		<?php foreach ( $cats as $t ) : ?>
			<?php if ( ! $t->count && $is_main ) { continue; } ?>
			<a class="cat-nav__chip <?php echo $current && $current->term_id === $t->term_id ? 'is-active' : ''; ?>"
				href="<?php echo $is_main ? '#cat-' . esc_attr( $t->slug ) : esc_url( get_term_link( $t ) ); ?>"
				<?php echo $is_main ? 'data-spy="cat-' . esc_attr( $t->slug ) . '"' : ''; ?>><?php echo esc_html( $t->name ); ?></a>
		<?php endforeach; ?>
	</div>
</nav>

<div class="container shop-body">
	<?php if ( $is_main ) : ?>
		<?php woocommerce_output_all_notices(); ?>
		<?php foreach ( $cats as $t ) : ?>
			<?php
			$ids = wc_get_products( array(
				'category'   => array( $t->slug ),
				'limit'      => $per_cat,
				'status'     => 'publish',
				'visibility' => 'catalog',
				'orderby'    => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
				'return'     => 'ids',
			) );
			if ( ! $ids ) {
				continue;
			}
			?>
			<section class="cat-section" id="cat-<?php echo esc_attr( $t->slug ); ?>">
				<header class="cat-section__head" data-reveal>
					<div>
						<h2 class="section-title"><?php echo esc_html( $t->name ); ?></h2>
						<?php if ( $t->description ) : ?><p class="cat-section__desc"><?php echo esc_html( $t->description ); ?></p><?php endif; ?>
					</div>
					<a class="link-arrow" href="<?php echo esc_url( get_term_link( $t ) ); ?>">Ver <?php echo (int) $t->count > $per_cat ? 'todas as ' . (int) $t->count : 'categoria'; ?> <?php echo lorie_icon( 'arrow-r', 16 ); // phpcs:ignore ?></a>
				</header>
				<ul class="products">
					<?php foreach ( $ids as $id ) { lorie_product_card( $id ); } ?>
				</ul>
			</section>
		<?php endforeach; ?>
	<?php else : ?>
		<?php if ( woocommerce_product_loop() ) : ?>
			<div class="shop-toolbar">
				<?php
				woocommerce_output_all_notices();
				woocommerce_result_count();
				woocommerce_catalog_ordering();
				?>
			</div>
			<?php
			woocommerce_product_loop_start();
			while ( have_posts() ) {
				the_post();
				do_action( 'woocommerce_shop_loop' );
				wc_get_template_part( 'content', 'product' );
			}
			woocommerce_product_loop_end();
			woocommerce_pagination();
			?>
		<?php else : ?>
			<div class="empty-state">
				<?php echo lorie_icon( 'yarn', 44 ); // phpcs:ignore ?>
				<h2>Nenhuma peça por aqui ainda</h2>
				<p>Estamos tecendo novidades. Enquanto isso, veja as outras categorias.</p>
				<a class="btn btn--primary" href="<?php echo esc_url( $shop_url ); ?>">Ver catálogo</a>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</div>
<?php
do_action( 'woocommerce_after_main_content' );
get_footer( 'shop' );
