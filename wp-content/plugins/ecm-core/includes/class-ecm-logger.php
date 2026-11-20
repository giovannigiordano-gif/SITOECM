<?php
/**
 * Registro eventi minimale (errori newsletter, invii email, importazione).
 *
 * Le voci sono salvate nell'opzione non autocaricata `ecm_log` (buffer circolare).
 *
 * @package ECM_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Logger.
 */
final class ECM_Logger {

	public const OPTION = 'ecm_log';
	public const MAX    = 200;

	/**
	 * Aggiunge una voce al registro.
	 *
	 * @param string $level   info|warning|error.
	 * @param string $context Modulo (newsletter, mail, import...).
	 * @param string $message Messaggio leggibile.
	 * @param array  $data    Dati aggiuntivi (mai segreti).
	 */
	public static function log( string $level, string $context, string $message, array $data = array() ): void {
		$entries = get_option( self::OPTION, array() );
		if ( ! is_array( $entries ) ) {
			$entries = array();
		}
		$entries[] = array(
			'time'    => time(),
			'level'   => sanitize_key( $level ),
			'context' => sanitize_key( $context ),
			'message' => wp_strip_all_tags( $message ),
			'data'    => self::scrub( $data ),
		);
		if ( count( $entries ) > self::MAX ) {
			$entries = array_slice( $entries, -1 * self::MAX );
		}
		update_option( self::OPTION, $entries, false );

		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( sprintf( '[ecm-core][%s][%s] %s', $level, $context, $message ) );
		}
	}

	/**
	 * Scorciatoia per gli errori.
	 *
	 * @param string $context Modulo.
	 * @param string $message Messaggio.
	 * @param array  $data    Dati.
	 */
	public static function error( string $context, string $message, array $data = array() ): void {
		self::log( 'error', $context, $message, $data );
	}

	/**
	 * Restituisce le voci più recenti, dalla più nuova.
	 *
	 * @param int $limit Numero massimo.
	 * @return array
	 */
	public static function entries( int $limit = 100 ): array {
		$entries = get_option( self::OPTION, array() );
		if ( ! is_array( $entries ) ) {
			return array();
		}
		return array_slice( array_reverse( $entries ), 0, max( 1, $limit ) );
	}

	/**
	 * Svuota il registro.
	 */
	public static function clear(): void {
		update_option( self::OPTION, array(), false );
	}

	/**
	 * Rimuove chiavi sensibili e limita la dimensione dei dati.
	 *
	 * @param array $data Dati.
	 * @return array
	 */
	private static function scrub( array $data ): array {
		$out = array();
		foreach ( $data as $key => $value ) {
			$key = (string) $key;
			if ( preg_match( '/key|token|secret|password|authorization/i', $key ) ) {
				continue;
			}
			if ( is_scalar( $value ) || null === $value ) {
				$out[ $key ] = mb_substr( (string) $value, 0, 500 );
			} else {
				$out[ $key ] = mb_substr( (string) wp_json_encode( $value ), 0, 500 );
			}
		}
		return $out;
	}
}
