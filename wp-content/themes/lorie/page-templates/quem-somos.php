<?php
/**
 * Template Name: Quem somos (perfil da artesã)
 *
 * Foto da dona à esquerda, perfil ao lado e, abaixo, a caixa de texto com a história.
 * Tudo editável em Aparência › Personalizar › Lorié › Quem somos.
 */

defined( 'ABSPATH' ) || exit;
get_header();
$stats = lorie_lines( 'lorie_about_stats' );
$story = trim( (string) lorie_mod( 'lorie_about_story' ) );
?>
<main id="main" class="site-main site-main--about">
	<section class="about">
		<div class="container">
			<div class="about__profile">
				<figure class="about__photo" data-reveal>
					<div class="about__arch"><?php echo lorie_image( (int) lorie_mod( 'lorie_about_photo' ), 'lorie-portrait', array( 'alt' => esc_attr( lorie_mod( 'lorie_about_name' ) ) ) ); // phpcs:ignore ?></div>
					<img class="about__seal" src="<?php echo esc_url( lorie_img( 'logo-circle.png' ) ); ?>" alt="" width="130" height="130">
				</figure>
				<div class="about__card" data-reveal>
					<span class="eyebrow"><?php echo esc_html( lorie_mod( 'lorie_about_eyebrow' ) ); ?></span>
					<h1 class="about__name"><?php echo esc_html( lorie_mod( 'lorie_about_name' ) ); ?></h1>
					<p class="about__role"><?php echo esc_html( lorie_mod( 'lorie_about_role' ) ); ?></p>
					<?php if ( lorie_mod( 'lorie_about_quote' ) ) : ?>
						<blockquote class="about__quote">“<?php echo esc_html( lorie_mod( 'lorie_about_quote' ) ); ?>”</blockquote>
					<?php endif; ?>
					<?php if ( $stats ) : ?>
						<ul class="about__stats">
							<?php foreach ( $stats as $s ) : ?>
								<li><strong><?php echo esc_html( $s[0] ); ?></strong><span><?php echo esc_html( $s[1] ); ?></span></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<div class="about__actions">
						<a class="btn btn--whatsapp" href="<?php echo esc_url( lorie_wa() ); ?>" target="_blank" rel="noopener"><?php echo lorie_icon( 'whatsapp', 20 ); // phpcs:ignore ?> Conversar</a>
						<a class="btn btn--ghost" href="<?php echo esc_url( function_exists( 'lorie_instagram_url' ) ? lorie_instagram_url() : '#' ); ?>" target="_blank" rel="noopener"><?php echo lorie_icon( 'instagram', 18 ); // phpcs:ignore ?> Instagram</a>
					</div>
				</div>
			</div>

			<article class="about__story" data-reveal>
				<h2 class="about__story-title">Minha história</h2>
				<div class="about__story-body">
					<?php
					echo wp_kses_post( wpautop( esc_html( $story ) ) );
					while ( have_posts() ) {
						the_post();
						the_content(); // conteúdo extra opcional escrito no editor da página
					}
					?>
				</div>
				<img class="about__signature" src="<?php echo esc_url( lorie_img( 'logo-monogram.png' ) ); ?>" alt="" width="54">
			</article>
		</div>
	</section>

	<section class="benefits benefits--about">
		<div class="container benefits__grid">
			<?php foreach ( lorie_lines( 'lorie_benefits', 3 ) as $b ) : ?>
				<div class="benefit" data-reveal>
					<span class="benefit__icon"><?php echo lorie_icon( $b[0] ? $b[0] : 'sparkle', 26 ); // phpcs:ignore ?></span>
					<div><strong><?php echo esc_html( $b[1] ); ?></strong><span><?php echo esc_html( $b[2] ); ?></span></div>
				</div>
			<?php endforeach; ?>
		</div>
	</section>

	<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
	<section class="section center">
		<div class="container" data-reveal>
			<h2 class="section-title">Que tal conhecer as peças?</h2>
			<p><a class="btn btn--primary" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Ver o catálogo <?php echo lorie_icon( 'arrow-r', 18 ); // phpcs:ignore ?></a></p>
		</div>
	</section>
	<?php endif; ?>
</main>
<?php
get_footer();
