<?php
/**
 * "Textos e fotos do site": tudo editável em Aparência › Personalizar › Lorié, com pré-visualização ao vivo.
 */

defined( 'ABSPATH' ) || exit;

function lorie_mod_defaults() {
	return array(
		'lorie_announce_on'    => true,
		'lorie_announce'       => 'Feito à mão, ponto a ponto · Frete grátis nas compras acima de R$ 300',
		'lorie_marquee'        => "Feito à mão\nPeças únicas\nEncomendas personalizadas\nFios de algodão\nEnviamos para todo o Brasil",
		'lorie_cats_eyebrow'   => 'Categorias',
		'lorie_cats_title'     => 'Encontre a sua peça',
		'lorie_new_eyebrow'    => 'Novidades',
		'lorie_new_title'      => 'Acabou de sair da agulha',
		'lorie_story_eyebrow'  => 'Nossa história',
		'lorie_story_title'    => 'De uma geração para outra',
		'lorie_story_text'     => "O crochê chegou até nós pelas mãos das avós — e cada peça da Lorié carrega esse mesmo carinho. Escolhemos fios macios, desenhamos modelos atemporais e produzimos tudo à mão, sem pressa.\n\nQuando você leva uma peça nossa, leva também um pedacinho dessa história.",
		'lorie_story_btn'      => 'Conheça a artesã',
		'lorie_story_image'    => 0,
		'lorie_featured_eyebrow' => 'Destaques',
		'lorie_featured_title' => 'Os mais amados',
		'lorie_custom_eyebrow' => 'Encomendas',
		'lorie_custom_title'   => 'Sua peça, do seu jeito',
		'lorie_custom_text'    => 'Quer outra cor, outro tamanho ou um detalhe especial? É só contar no pedido ou chamar no WhatsApp.',
		'lorie_custom_steps'   => "Escolha a peça|Encontre o modelo que mais combina com você.\nConte como imagina|Cores, medidas e detalhes — tudo no campo de personalização.\nFeito à mão|A artesã avalia e produz sua peça com carinho.\nChega até você|Enviamos com rastreio para todo o Brasil.",
		'lorie_benefits'       => "hand|Feito à mão|Cada ponto é único\ntruck|Envio rastreado|Para todo o Brasil\nshield|Compra segura|Seus dados protegidos\npencil|Personalize|Do seu jeito",
		'lorie_insta_eyebrow'  => 'Instagram',
		'lorie_insta_title'    => 'Bastidores do ateliê',
		'lorie_news_title'     => 'Receba novidades em primeira mão',
		'lorie_news_text'      => 'Lançamentos, peças únicas e encomendas abertas. Sem spam, prometemos.',
		'lorie_about_photo'    => 0,
		'lorie_about_eyebrow'  => 'Quem somos',
		'lorie_about_name'     => 'Lorié',
		'lorie_about_role'     => 'Artesã e fundadora',
		'lorie_about_quote'    => 'Cada peça que sai das minhas mãos leva um pouco da minha história.',
		'lorie_about_stats'    => "100%|feito à mão\n+300|peças entregues\n3|gerações de crocheteiras",
		'lorie_about_story'    => "[Escreva aqui a sua história — este texto é um exemplo.]\n\nAprendi crochê vendo minha avó tecer na varanda de casa. O que começou como passatempo virou paixão e, depois, a Lorié: uma marca de peças feitas à mão, com fios escolhidos a dedo e modelos pensados para durar.\n\nCada bolsa, saia ou blusinha é produzida por mim, ponto a ponto. Também faço encomendas personalizadas — porque acredito que roupa boa é aquela que tem a sua cara.",
		'lorie_footer_text'    => 'Peças de crochê feitas à mão, com fios naturais e muito carinho.',
		'lorie_legal'          => '',
	);
}

add_action( 'customize_register', function ( WP_Customize_Manager $wp ) {
	$d = lorie_mod_defaults();

	$wp->add_panel( 'lorie_panel', array( 'title' => 'Lorié — textos e fotos', 'priority' => 1 ) );

	$add = function ( $section, $key, $label, $type = 'text', $desc = '' ) use ( $wp, $d ) {
		$is_img = 'image' === $type;
		$wp->add_setting( $key, array(
			'default'           => $d[ $key ] ?? '',
			'sanitize_callback' => $is_img ? 'absint' : ( 'checkbox' === $type ? 'rest_sanitize_boolean' : ( 'textarea' === $type ? 'sanitize_textarea_field' : 'sanitize_text_field' ) ),
			'transport'         => 'refresh',
		) );
		if ( $is_img ) {
			$wp->add_control( new WP_Customize_Media_Control( $wp, $key, array( 'label' => $label, 'section' => $section, 'mime_type' => 'image', 'description' => $desc ) ) );
		} else {
			$wp->add_control( $key, array( 'label' => $label, 'section' => $section, 'type' => $type, 'description' => $desc ) );
		}
	};

	$sections = array(
		'lorie_top'     => 'Barra de aviso',
		'lorie_home'    => 'Página inicial',
		'lorie_story'   => 'Página inicial — história',
		'lorie_custom'  => 'Página inicial — encomendas',
		'lorie_about'   => 'Quem somos',
		'lorie_footer'  => 'Rodapé',
	);
	foreach ( $sections as $id => $title ) {
		$wp->add_section( $id, array( 'title' => $title, 'panel' => 'lorie_panel' ) );
	}

	$add( 'lorie_top', 'lorie_announce_on', 'Mostrar barra de aviso no topo', 'checkbox' );
	$add( 'lorie_top', 'lorie_announce', 'Texto da barra' );

	$add( 'lorie_home', 'lorie_marquee', 'Faixa animada (uma frase por linha)', 'textarea' );
	$add( 'lorie_home', 'lorie_cats_eyebrow', 'Categorias — texto pequeno' );
	$add( 'lorie_home', 'lorie_cats_title', 'Categorias — título' );
	$add( 'lorie_home', 'lorie_new_eyebrow', 'Novidades — texto pequeno' );
	$add( 'lorie_home', 'lorie_new_title', 'Novidades — título' );
	$add( 'lorie_home', 'lorie_featured_eyebrow', 'Destaques — texto pequeno' );
	$add( 'lorie_home', 'lorie_featured_title', 'Destaques — título' );
	$add( 'lorie_home', 'lorie_benefits', 'Benefícios (ícone|título|texto, um por linha)', 'textarea', 'Ícones: hand, truck, shield, pencil, refresh, gift, yarn, clock, sparkle' );
	$add( 'lorie_home', 'lorie_insta_eyebrow', 'Instagram — texto pequeno' );
	$add( 'lorie_home', 'lorie_insta_title', 'Instagram — título' );
	$add( 'lorie_home', 'lorie_news_title', 'Newsletter — título' );
	$add( 'lorie_home', 'lorie_news_text', 'Newsletter — texto', 'textarea' );

	$add( 'lorie_story', 'lorie_story_image', 'Foto', 'image', 'Formato retrato fica mais bonito.' );
	$add( 'lorie_story', 'lorie_story_eyebrow', 'Texto pequeno' );
	$add( 'lorie_story', 'lorie_story_title', 'Título' );
	$add( 'lorie_story', 'lorie_story_text', 'Texto', 'textarea' );
	$add( 'lorie_story', 'lorie_story_btn', 'Texto do botão' );

	$add( 'lorie_custom', 'lorie_custom_eyebrow', 'Texto pequeno' );
	$add( 'lorie_custom', 'lorie_custom_title', 'Título' );
	$add( 'lorie_custom', 'lorie_custom_text', 'Texto', 'textarea' );
	$add( 'lorie_custom', 'lorie_custom_steps', 'Passos (título|texto, um por linha)', 'textarea' );

	$add( 'lorie_about', 'lorie_about_photo', 'Foto da dona', 'image', 'Use uma foto em pé (retrato), de preferência com luz natural.' );
	$add( 'lorie_about', 'lorie_about_eyebrow', 'Texto pequeno' );
	$add( 'lorie_about', 'lorie_about_name', 'Nome' );
	$add( 'lorie_about', 'lorie_about_role', 'Função' );
	$add( 'lorie_about', 'lorie_about_quote', 'Frase em destaque', 'textarea' );
	$add( 'lorie_about', 'lorie_about_stats', 'Números (valor|legenda, um por linha)', 'textarea' );
	$add( 'lorie_about', 'lorie_about_story', 'História (caixa de texto)', 'textarea', 'Separe os parágrafos com uma linha em branco.' );

	$add( 'lorie_footer', 'lorie_footer_text', 'Frase do rodapé', 'textarea' );
	$add( 'lorie_footer', 'lorie_legal', 'Dados legais (CNPJ/CPF e endereço)', 'textarea', 'Obrigatório por lei para lojas virtuais (Decreto 7.962/2013).' );
} );

/** Divide um campo "a|b" por linha em arrays. */
function lorie_lines( $key, $parts = 2 ) {
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) lorie_mod( $key ) ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		$out[] = array_pad( array_map( 'trim', explode( '|', $line, $parts ) ), $parts, '' );
	}
	return $out;
}
