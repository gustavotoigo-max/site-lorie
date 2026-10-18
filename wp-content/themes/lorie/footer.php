<?php
/**
 * Rodapé + camadas (carrinho lateral, busca, menu mobile, compra rápida, WhatsApp).
 */

defined( 'ABSPATH' ) || exit;
$wc    = class_exists( 'WooCommerce' );
$insta = function_exists( 'lorie_instagram_url' ) ? lorie_instagram_url() : '#';
$pay   = class_exists( 'Lorie_Payments' ) ? Lorie_Payments::get() : array( 'test_mode' => true );
?>

<section class="newsletter" data-reveal>
	<div class="container newsletter__inner">
		<div class="newsletter__text">
			<span class="newsletter__icon"><?php echo lorie_icon( 'mail', 26 ); // phpcs:ignore ?></span>
			<div>
				<h2 class="newsletter__title"><?php echo esc_html( lorie_mod( 'lorie_news_title' ) ); ?></h2>
				<p><?php echo esc_html( lorie_mod( 'lorie_news_text' ) ); ?></p>
			</div>
		</div>
		<form class="newsletter__form" data-newsletter>
			<label class="screen-reader-text" for="nl-email">Seu e-mail</label>
			<input id="nl-email" type="email" name="email" required placeholder="Seu melhor e-mail" autocomplete="email">
			<button class="btn btn--primary" type="submit">Quero receber</button>
			<p class="newsletter__msg" aria-live="polite"></p>
		</form>
	</div>
</section>

<footer class="site-footer">
	<div class="container site-footer__grid">
		<div class="site-footer__brand">
			<img src="<?php echo esc_url( lorie_img( 'logo-stack-light.png' ) ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" width="180" height="155" loading="lazy">
			<p><?php echo esc_html( lorie_mod( 'lorie_footer_text' ) ); ?></p>
			<div class="social">
				<a href="<?php echo esc_url( $insta ); ?>" target="_blank" rel="noopener" aria-label="Instagram"><?php echo lorie_icon( 'instagram' ); // phpcs:ignore ?></a>
				<a href="<?php echo esc_url( lorie_wa() ); ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><?php echo lorie_icon( 'whatsapp' ); // phpcs:ignore ?></a>
				<a href="mailto:<?php echo esc_attr( lorie_opt( 'email_contact' ) ); ?>" aria-label="E-mail"><?php echo lorie_icon( 'mail' ); // phpcs:ignore ?></a>
			</div>
		</div>
		<div class="site-footer__col">
			<h3>Loja</h3>
			<?php
			if ( has_nav_menu( 'footer_shop' ) ) {
				wp_nav_menu( array( 'theme_location' => 'footer_shop', 'container' => false, 'depth' => 1 ) );
			} else {
				echo '<ul>';
				foreach ( lorie_product_categories() as $t ) {
					echo '<li><a href="' . esc_url( get_term_link( $t ) ) . '">' . esc_html( $t->name ) . '</a></li>';
				}
				echo '</ul>';
			}
			?>
		</div>
		<div class="site-footer__col">
			<h3>Ajuda</h3>
			<?php
			if ( has_nav_menu( 'footer_help' ) ) {
				wp_nav_menu( array( 'theme_location' => 'footer_help', 'container' => false, 'depth' => 1 ) );
			}
			?>
		</div>
		<div class="site-footer__col">
			<h3>Atendimento</h3>
			<ul class="contact-list">
				<li><a href="<?php echo esc_url( lorie_wa() ); ?>" target="_blank" rel="noopener"><?php echo lorie_icon( 'whatsapp', 18 ); // phpcs:ignore ?> WhatsApp</a></li>
				<li><a href="mailto:<?php echo esc_attr( lorie_opt( 'email_contact' ) ); ?>"><?php echo lorie_icon( 'mail', 18 ); // phpcs:ignore ?> <?php echo esc_html( lorie_opt( 'email_contact' ) ); ?></a></li>
				<li><a href="<?php echo esc_url( $insta ); ?>" target="_blank" rel="noopener"><?php echo lorie_icon( 'instagram', 18 ); // phpcs:ignore ?> @<?php echo esc_html( ltrim( lorie_opt( 'instagram_handle' ), '@' ) ); ?></a></li>
			</ul>
			<div class="pay-info">
				<?php if ( ! empty( $pay['test_mode'] ) ) : ?>
					<span class="chip">Loja em fase de testes</span>
				<?php else : ?>
					<?php if ( ! empty( $pay['pix_enabled'] ) ) : ?><span class="chip">Pix</span><?php endif; ?>
					<?php if ( ! empty( $pay['credit_card_enabled'] ) ) : ?><span class="chip">Cartão em até <?php echo (int) $pay['max_installments']; ?>x</span><?php endif; ?>
				<?php endif; ?>
				<span class="chip"><?php echo lorie_icon( 'shield', 14 ); // phpcs:ignore ?> Site seguro</span>
			</div>
		</div>
	</div>
	<div class="container site-footer__bottom">
		<p>© <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?> · <?php echo esc_html( lorie_opt( 'tagline', get_bloginfo( 'description' ) ) ); ?></p>
		<?php if ( lorie_mod( 'lorie_legal' ) ) : ?><p class="site-footer__legal"><?php echo esc_html( lorie_mod( 'lorie_legal' ) ); ?></p><?php endif; ?>
	</div>
</footer>

<!-- WhatsApp flutuante -->
<a class="wa-float" href="<?php echo esc_url( lorie_wa() ); ?>" target="_blank" rel="noopener" aria-label="Falar no WhatsApp">
	<?php echo lorie_icon( 'whatsapp', 30 ); // phpcs:ignore ?>
	<span class="wa-float__label">Fale com a gente</span>
</a>

<!-- Menu mobile -->
<div class="mobile-menu" id="mobile-menu" data-mobile-menu hidden>
	<div class="mobile-menu__head">
		<img src="<?php echo esc_url( lorie_img( 'logo-wordmark.png' ) ); ?>" alt="" height="40">
		<button type="button" class="icon-btn" data-menu-close aria-label="Fechar menu"><?php echo lorie_icon( 'close' ); // phpcs:ignore ?></button>
	</div>
	<nav aria-label="Menu"><?php lorie_menu( 'primary', 'mobile-menu__list' ); ?></nav>
	<?php if ( $wc ) : ?>
		<div class="mobile-menu__cats">
			<?php foreach ( lorie_product_categories() as $t ) : ?>
				<a href="<?php echo esc_url( get_term_link( $t ) ); ?>"><?php echo esc_html( $t->name ); ?></a>
			<?php endforeach; ?>
		</div>
		<div class="mobile-menu__links">
			<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php echo lorie_icon( 'user', 18 ); // phpcs:ignore ?> Minha conta</a>
			<a href="<?php echo esc_url( home_url( '/favoritos/' ) ); ?>"><?php echo lorie_icon( 'heart', 18 ); // phpcs:ignore ?> Favoritos</a>
			<a href="<?php echo esc_url( lorie_wa() ); ?>" target="_blank" rel="noopener"><?php echo lorie_icon( 'whatsapp', 18 ); // phpcs:ignore ?> WhatsApp</a>
		</div>
	<?php endif; ?>
</div>

<!-- Busca -->
<div class="search-layer" data-search hidden>
	<div class="search-layer__box">
		<form role="search" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="search-layer__form">
			<?php echo lorie_icon( 'search', 24 ); // phpcs:ignore ?>
			<input type="search" name="s" placeholder="O que você procura? Ex.: bolsa, cropped…" autocomplete="off" data-search-input aria-label="Buscar peças">
			<input type="hidden" name="post_type" value="product">
			<button type="button" class="icon-btn" data-search-close aria-label="Fechar busca"><?php echo lorie_icon( 'close' ); // phpcs:ignore ?></button>
		</form>
		<div class="search-layer__results" data-search-results></div>
		<div class="search-layer__suggest">
			<span>Sugestões:</span>
			<?php foreach ( array_slice( lorie_product_categories(), 0, 5 ) as $t ) : ?>
				<a href="<?php echo esc_url( get_term_link( $t ) ); ?>"><?php echo esc_html( $t->name ); ?></a>
			<?php endforeach; ?>
		</div>
	</div>
</div>

<?php if ( $wc ) : ?>
<!-- Carrinho lateral -->
<aside class="drawer" data-drawer aria-label="Carrinho" aria-hidden="true">
	<div class="drawer__overlay" data-drawer-close></div>
	<div class="drawer__panel" role="dialog" aria-modal="true" aria-labelledby="drawer-title">
		<header class="drawer__head">
			<h2 id="drawer-title">Seu carrinho <span data-cart-count-text>(<?php echo (int) lorie_cart_count(); ?>)</span></h2>
			<button type="button" class="icon-btn" data-drawer-close aria-label="Fechar carrinho"><?php echo lorie_icon( 'close' ); // phpcs:ignore ?></button>
		</header>
		<div class="drawer__body" data-drawer-body><?php echo lorie_cart_drawer_html(); // phpcs:ignore ?></div>
	</div>
</aside>

<!-- Compra rápida -->
<div class="modal" data-modal hidden>
	<div class="modal__overlay" data-modal-close></div>
	<div class="modal__panel" role="dialog" aria-modal="true" aria-label="Compra rápida">
		<button type="button" class="icon-btn modal__close" data-modal-close aria-label="Fechar"><?php echo lorie_icon( 'close' ); // phpcs:ignore ?></button>
		<div class="modal__body" data-modal-body></div>
	</div>
</div>
<?php endif; ?>

<div class="toast" data-toast role="status" aria-live="polite"></div>

<div class="cookie" data-cookie hidden>
	<p>Usamos cookies essenciais para o carrinho e o login funcionarem. Saiba mais na <a href="<?php echo esc_url( get_privacy_policy_url() ? get_privacy_policy_url() : home_url( '/politica-de-privacidade/' ) ); ?>">Política de privacidade</a>.</p>
	<button type="button" class="btn btn--primary btn--sm" data-cookie-ok>Entendi</button>
</div>

<?php wp_footer(); ?>
</body>
</html>
