<?php
/**
 * Entrar / criar cadastro (Google ou e-mail, sem senha).
 *
 * @package WooCommerce\Templates
 * @version 9.9.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_customer_login_form' );
?>
<div class="auth-layout">
	<aside class="auth-layout__side">
		<img src="<?php echo esc_url( lorie_img( 'logo-stack-light.png' ) ); ?>" alt="" width="220">
		<ul>
			<li><?php echo lorie_icon( 'check', 18 ); // phpcs:ignore ?> Acompanhe seus pedidos</li>
			<li><?php echo lorie_icon( 'check', 18 ); // phpcs:ignore ?> Compre mais rápido</li>
			<li><?php echo lorie_icon( 'check', 18 ); // phpcs:ignore ?> Sem senha para lembrar</li>
		</ul>
	</aside>
	<div class="auth-layout__main">
		<?php
		if ( class_exists( 'Lorie_Account' ) ) {
			echo Lorie_Account::shortcode_auth(); // phpcs:ignore
		} else {
			echo '<p>Ative o plugin "Lorié — Núcleo da Loja" para habilitar o login.</p>';
		}
		?>
	</div>
</div>
<?php do_action( 'woocommerce_after_customer_login_form' ); ?>
