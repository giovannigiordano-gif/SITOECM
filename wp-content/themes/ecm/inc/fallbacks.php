<?php
/**
 * Fallback minimi se il plugin ECM Core non è attivo: il sito resta navigabile
 * (senza dati) invece di andare in errore, e l'admin vede un avviso.
 *
 * @package ecm
 */

defined( 'ABSPATH' ) || exit;

/**
 * True se il plugin ECM Core è caricato.
 */
function ecm_core_ready(): bool {
	return function_exists( 'ecm_course' ) && function_exists( 'ecm_opt' );
}

if ( ! function_exists( 'ecm_opt' ) ) {
	function ecm_opt( string $key, $default = '' ) {
		$opts = get_option( 'ecm_options', array() );
		return ( is_array( $opts ) && isset( $opts[ $key ] ) && '' !== $opts[ $key ] ) ? $opts[ $key ] : $default;
	}
}
if ( ! function_exists( 'ecm_whatsapp_url' ) ) {
	function ecm_whatsapp_url( string $text = '' ): string {
		$n = preg_replace( '/\D+/', '', (string) ecm_opt( 'whatsapp_number', '' ) );
		return $n ? 'https://wa.me/' . $n . ( $text ? '?text=' . rawurlencode( $text ) : '' ) : '#';
	}
}
if ( ! function_exists( 'ecm_stati' ) ) {
	function ecm_stati(): array {
		return array(
			'disponibile' => 'Disponibile',
			'preordine'   => 'Pre-ordine',
			'in-arrivo'   => 'In arrivo',
			'concluso'    => 'Concluso',
		);
	}
}
if ( ! function_exists( 'ecm_courses' ) ) {
	function ecm_courses( array $args = array() ): array {
		return array();
	}
}
if ( ! function_exists( 'ecm_areas' ) ) {
	function ecm_areas(): array {
		return array();
	}
}
if ( ! function_exists( 'ecm_destinatari' ) ) {
	function ecm_destinatari(): array {
		return array();
	}
}
if ( ! function_exists( 'ecm_docenti' ) ) {
	function ecm_docenti(): array {
		return array();
	}
}
if ( ! function_exists( 'ecm_testimonianze' ) ) {
	function ecm_testimonianze( array $args = array() ): array {
		return array();
	}
}
if ( ! function_exists( 'ecm_stats' ) ) {
	function ecm_stats(): array {
		return array( 'corsi' => 0, 'docenti' => 0, 'patrocini_ordini' => 0, 'testimonianze' => 0 );
	}
}
if ( ! function_exists( 'ecm_human_filesize' ) ) {
	function ecm_human_filesize( int $bytes ): string {
		if ( $bytes <= 0 ) {
			return '';
		}
		$units = array( 'B', 'KB', 'MB', 'GB' );
		$i     = (int) min( 3, floor( log( $bytes, 1024 ) ) );
		return number_format_i18n( $bytes / pow( 1024, $i ), $i > 1 ? 1 : 0 ) . ' ' . $units[ $i ];
	}
}
if ( ! function_exists( 'ecm_privacy_urls' ) ) {
	function ecm_privacy_urls(): array {
		return array(
			'privacy' => (string) ecm_opt( 'privacy_url', get_privacy_policy_url() ),
			'cookie'  => (string) ecm_opt( 'cookie_url', ecm_opt( 'privacy_url', get_privacy_policy_url() ) ),
			'termini' => (string) ecm_opt( 'termini_url', '' ),
		);
	}
}

add_action(
	'admin_notices',
	static function () {
		if ( ! ecm_core_ready() && current_user_can( 'activate_plugins' ) ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'Il tema ECM richiede il plugin "ECM Core" attivo per mostrare corsi, docenti e testimonianze.', 'ecm' ) . '</p></div>';
		}
	}
);
