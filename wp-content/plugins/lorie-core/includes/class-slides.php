<?php
/**
 * Slides do topo da página inicial: cada slide é uma foto em alta resolução
 * com um texto curto e um botão. A ordem segue o campo "Ordem".
 */

defined( 'ABSPATH' ) || exit;

class Lorie_Slides {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'meta_box' ) );
		add_action( 'save_post_lorie_slide', array( __CLASS__, 'save' ) );
		add_filter( 'manage_lorie_slide_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_lorie_slide_posts_custom_column', array( __CLASS__, 'column' ), 10, 2 );
		add_filter( 'enter_title_here', function ( $t, $post ) {
			return 'lorie_slide' === $post->post_type ? 'Frase principal do slide (curta!)' : $t;
		}, 10, 2 );
	}

	public static function register() {
		register_post_type( 'lorie_slide', array(
			'labels'        => array(
				'name'               => 'Slides do topo',
				'singular_name'      => 'Slide',
				'add_new'            => 'Novo slide',
				'add_new_item'       => 'Novo slide',
				'edit_item'          => 'Editar slide',
				'featured_image'     => 'Foto do slide (mín. 2000 px de largura)',
				'set_featured_image' => 'Escolher foto do slide',
			),
			'public'        => false,
			'show_ui'       => true,
			'show_in_menu'  => false,
			'supports'      => array( 'title', 'thumbnail', 'page-attributes' ),
			'menu_icon'     => 'dashicons-images-alt2',
		) );
	}

	public static function meta_box() {
		add_meta_box( 'lorie_slide', 'Texto e botão', array( __CLASS__, 'render_box' ), 'lorie_slide', 'normal', 'high' );
	}

	public static function render_box( $post ) {
		wp_nonce_field( 'lorie_slide', 'lorie_slide_nonce' );
		$f = fn( $k ) => esc_attr( get_post_meta( $post->ID, $k, true ) );
		?>
		<p><label><strong>Texto pequeno acima da frase</strong> (opcional)<br>
			<input type="text" class="widefat" name="lorie_eyebrow" value="<?php echo $f( '_lorie_eyebrow' ); // phpcs:ignore ?>" placeholder="Ex.: Nova coleção"></label></p>
		<p><label><strong>Texto do botão</strong><br>
			<input type="text" class="widefat" name="lorie_btn_text" value="<?php echo $f( '_lorie_btn_text' ); // phpcs:ignore ?>" placeholder="Ex.: Ver bolsas"></label></p>
		<p><label><strong>Link do botão</strong><br>
			<input type="url" class="widefat" name="lorie_btn_link" value="<?php echo $f( '_lorie_btn_link' ); // phpcs:ignore ?>" placeholder="https://..."></label></p>
		<p><label><strong>Posição do texto</strong><br>
			<select name="lorie_align">
				<?php foreach ( array( 'left' => 'Esquerda', 'center' => 'Centro', 'right' => 'Direita' ) as $k => $l ) : ?>
					<option value="<?php echo esc_attr( $k ); ?>" <?php selected( get_post_meta( $post->ID, '_lorie_align', true ), $k ); ?>><?php echo esc_html( $l ); ?></option>
				<?php endforeach; ?>
			</select></label></p>
		<p class="description">Dica: use fotos horizontais grandes (2400 × 1200 px). A foto preenche a tela inteira; mantenha o texto curto para não poluir.</p>
		<?php
	}

	public static function save( $post_id ) {
		if ( ! isset( $_POST['lorie_slide_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['lorie_slide_nonce'] ), 'lorie_slide' ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		update_post_meta( $post_id, '_lorie_eyebrow', sanitize_text_field( wp_unslash( $_POST['lorie_eyebrow'] ?? '' ) ) );
		update_post_meta( $post_id, '_lorie_btn_text', sanitize_text_field( wp_unslash( $_POST['lorie_btn_text'] ?? '' ) ) );
		update_post_meta( $post_id, '_lorie_btn_link', esc_url_raw( wp_unslash( $_POST['lorie_btn_link'] ?? '' ) ) );
		update_post_meta( $post_id, '_lorie_align', sanitize_key( $_POST['lorie_align'] ?? 'left' ) );
	}

	public static function columns( $cols ) {
		return array( 'cb' => $cols['cb'], 'lorie_thumb' => 'Foto', 'title' => 'Frase', 'lorie_order' => 'Ordem', 'date' => $cols['date'] );
	}

	public static function column( $col, $post_id ) {
		if ( 'lorie_thumb' === $col ) {
			echo get_the_post_thumbnail( $post_id, array( 120, 60 ), array( 'style' => 'border-radius:6px;object-fit:cover;width:120px;height:60px' ) );
		}
		if ( 'lorie_order' === $col ) {
			echo (int) get_post_field( 'menu_order', $post_id );
		}
	}

	/** Slides publicados, prontos para o tema. */
	public static function get_slides() {
		$posts = get_posts( array(
			'post_type'   => 'lorie_slide',
			'numberposts' => 8,
			'orderby'     => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
		) );
		$out = array();
		foreach ( $posts as $p ) {
			$img = get_post_thumbnail_id( $p );
			if ( ! $img ) {
				continue;
			}
			$out[] = array(
				'title'    => get_the_title( $p ),
				'eyebrow'  => get_post_meta( $p->ID, '_lorie_eyebrow', true ),
				'btn_text' => get_post_meta( $p->ID, '_lorie_btn_text', true ),
				'btn_link' => get_post_meta( $p->ID, '_lorie_btn_link', true ),
				'align'    => get_post_meta( $p->ID, '_lorie_align', true ) ?: 'left',
				'image'    => $img,
			);
		}
		return $out;
	}
}
