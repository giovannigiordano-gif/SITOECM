<?php
/**
 * Inoltro alla piattaforma corsi (Thinkific).
 *
 * Quando il dominio principale passa a WordPress, i link salvati dai corsisti verso la piattaforma
 * (lezioni, login, iscrizioni, carrello...) arrivano su WordPress. Questi percorsi vengono inoltrati,
 * con lo stesso path e la stessa query, all'host dell'opzione "URL login piattaforma".
 * L'inoltro è attivo solo se quell'host è diverso da quello del sito WordPress.
 *
 * @package ecm-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Inoltro dei percorsi della piattaforma.
 */
class ECM_Platform_Redirects {

	/**
	 * Prefissi di percorso che appartengono alla piattaforma Thinkific.
	 *
	 * @return string[]
	 */
	public static function prefixes(): array {
		$defaults = array(
			'/courses/take/',
			'/users/',
			'/enrollments',
			'/account',
			'/cart',
			'/checkout',
			'/orders',
			'/bundles/',
			'/order',
			'/purchases',
			'/manage/',
			'/communities',
			'/api/',
		);
		return (array) apply_filters( 'ecm_platform_prefixes', $defaults );
	}

	/**
	 * Hook.
	 */
	public static function init(): void {
		add_action( 'template_redirect', array( __CLASS__, 'maybe_forward' ), 0 );
	}

	/**
	 * Inoltra la richiesta se il percorso è della piattaforma.
	 */
	public static function maybe_forward(): void {
		if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}
		$login = (string) ecm_opt( 'login_url', '' );
		$host  = (string) wp_parse_url( $login, PHP_URL_HOST );
		if ( '' === $host || strtolower( $host ) === strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) ) {
			return;
		}
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
		foreach ( self::prefixes() as $prefix ) {
			if ( 0 === stripos( $path, $prefix ) ) {
				$scheme = (string) ( wp_parse_url( $login, PHP_URL_SCHEME ) ?: 'https' );
				$query  = (string) wp_parse_url( $uri, PHP_URL_QUERY );
				$target = $scheme . '://' . $host . $path . ( '' !== $query ? '?' . $query : '' );
				wp_redirect( esc_url_raw( $target ), 301, 'ECM Core' ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- host dell'opzione gestita dall'admin.
				exit;
			}
		}
	}
}
