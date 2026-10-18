<?php
/**
 * Ícones em SVG (traço fino, combinam com a tipografia).
 */

defined( 'ABSPATH' ) || exit;

function lorie_icon( $name, $size = 22 ) {
	$p = array(
		'search'   => '<circle cx="11" cy="11" r="6.5"/><path d="m20 20-4.2-4.2"/>',
		'heart'    => '<path d="M12 20s-7-4.35-7-9.5A4.1 4.1 0 0 1 12 8a4.1 4.1 0 0 1 7 2.5c0 5.15-7 9.5-7 9.5z"/>',
		'user'     => '<circle cx="12" cy="8.5" r="3.8"/><path d="M4.5 20c1.2-3.6 4-5.4 7.5-5.4s6.3 1.8 7.5 5.4"/>',
		'bag'      => '<path d="M5.5 8.5h13l-1 11.5h-11z"/><path d="M9 8.5V7a3 3 0 0 1 6 0v1.5"/>',
		'menu'     => '<path d="M4 7h16M4 12h16M4 17h10"/>',
		'close'    => '<path d="M6 6l12 12M18 6 6 18"/>',
		'arrow-r'  => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'arrow-l'  => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
		'chev-d'   => '<path d="m6 9 6 6 6-6"/>',
		'plus'     => '<path d="M12 5v14M5 12h14"/>',
		'minus'    => '<path d="M5 12h14"/>',
		'trash'    => '<path d="M5 7h14M10 11v6M14 11v6M6.5 7l1 12.5h9l1-12.5M9.5 7V4.5h5V7"/>',
		'truck'    => '<path d="M3 6.5h11v9H3zM14 9.5h3.8l3.2 3.3v2.7h-7"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17.5" cy="17.5" r="1.8"/>',
		'hand'     => '<path d="M8 13V6.5a1.5 1.5 0 0 1 3 0V12M11 11V5a1.5 1.5 0 0 1 3 0v6M14 11V6.5a1.5 1.5 0 0 1 3 0V14c0 3.6-2.4 6.5-6 6.5-2.6 0-4-1.3-5.4-3.4L3.8 14a1.5 1.5 0 0 1 2.4-1.8L8 14"/>',
		'shield'   => '<path d="M12 3.5 19 6v5.5c0 4.3-3 7.6-7 9-4-1.4-7-4.7-7-9V6z"/><path d="m9 12 2 2 4-4"/>',
		'refresh'  => '<path d="M19 8A7.5 7.5 0 0 0 5.6 7.4M5 16a7.5 7.5 0 0 0 13.4.6"/><path d="M19 4v4h-4M5 20v-4h4"/>',
		'sparkle'  => '<path d="M12 3.5c.6 4.4 2.5 6.4 7 7-4.5.6-6.4 2.6-7 7-.6-4.4-2.5-6.4-7-7 4.5-.6 6.4-2.6 7-7z"/>',
		'yarn'     => '<circle cx="12" cy="12" r="7.5"/><path d="M6 8.5c4 0 9 3 10.5 8M8.5 5.2c3.5 1.6 7 5.5 7.5 10.8M5 12.8c3 .2 6 2.2 7.5 6.6M17.5 6.5 21 3"/>',
		'check'    => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
		'mail'     => '<rect x="3.5" y="5.5" width="17" height="13" rx="2"/><path d="m4 7 8 6 8-6"/>',
		'pin'      => '<path d="M12 21s-6.5-6-6.5-11a6.5 6.5 0 0 1 13 0c0 5-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.3"/>',
		'clock'    => '<circle cx="12" cy="12" r="8"/><path d="M12 7.5V12l3 2"/>',
		'gift'     => '<rect x="4" y="9" width="16" height="11" rx="1"/><path d="M3.5 9h17V6.5h-17zM12 6.5V20M12 6.5C10.5 3 7 3.5 7.5 5.5c.3 1 2.3 1 4.5 1zM12 6.5c1.5-3.5 5-3 4.5-1-.3 1-2.3 1-4.5 1z"/>',
		'pencil'   => '<path d="M4 20l1-4.5L15.5 5l3.5 3.5L8.5 19z"/><path d="m13.5 7 3.5 3.5"/>',
		'instagram'=> '<rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.3" cy="6.7" r="1" fill="currentColor" stroke="none"/>',
		'whatsapp' => '<path d="M4.5 19.5 5.6 16A8 8 0 1 1 8.4 18.6z" /><path d="M9.2 8.6c.2-.5.5-.5.8-.5h.5c.2 0 .4 0 .6.5l.7 1.7c.1.2 0 .4-.1.6l-.5.6c-.1.2-.1.3 0 .5.5.8 1.3 1.6 2.2 2 .2.1.4.1.5 0l.6-.7c.2-.2.4-.2.6-.1l1.6.8c.3.1.4.3.3.6-.2.9-1 1.6-1.9 1.6-2.9-.2-5.6-2.9-5.9-5.6 0-.7.3-1.4.5-2z" fill="currentColor" stroke="none"/>',
	);
	if ( ! isset( $p[ $name ] ) ) {
		return '';
	}
	return '<svg class="icon icon--' . esc_attr( $name ) . '" width="' . (int) $size . '" height="' . (int) $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $p[ $name ] . '</svg>';
}
