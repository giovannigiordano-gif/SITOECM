<?php
/**
 * Redirect 301 dagli indirizzi del vecchio sito Thinkific.
 *
 * Ordine di risoluzione: tabella `ecm_redirects` (admin/import) → regole fisse
 * (/collections*, /pages/*) → `_ecm_old_paths` dei corsi.
 * Il confronto avviene su path decodificato, minuscolo, senza slash finale e senza query string.
 *
 * @package ECM_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Redirect.
 */
final class ECM_Redirects {

	public const OPTION        = 'ecm_redirects';
	public const MAP_TRANSIENT = 'ecm_old_paths_map';

	/**
	 * Pagine del vecchio sito con equivalente diretto.
	 */
	public const LEGACY_PAGES = array( 'chi-siamo', 'docenti', 'contatti', 'testimonianze' );

	/**
	 * Mappa path storico normalizzato => ID corso.
	 *
	 * @var array<string,int>|null
	 */
	private static ?array $map = null;

	/**
	 * Tabella normalizzata da => a.
	 *
	 * @var array<string,string>|null
	 */
	private static ?array $table = null;

	/**
	 * Aggancia gli hook.
	 */
	public static function init(): void {
		add_action( 'parse_request', array( __CLASS__, 'early' ), 1 );
		add_action( 'template_redirect', array( __CLASS__, 'on_404' ), 1 );

		add_action( 'save_post_corso', array( __CLASS__, 'flush_map' ) );
		add_action( 'deleted_post', array( __CLASS__, 'flush_map_for_post' ) );
		add_action( 'trashed_post', array( __CLASS__, 'flush_map_for_post' ) );
		add_action( 'untrashed_post', array( __CLASS__, 'flush_map_for_post' ) );
		add_action( 'updated_post_meta', array( __CLASS__, 'meta_changed' ), 10, 3 );
		add_action( 'added_post_meta', array( __CLASS__, 'meta_changed' ), 10, 3 );
		add_action( 'deleted_post_meta', array( __CLASS__, 'meta_changed' ), 10, 3 );
		add_action(
			'update_option_' . self::OPTION,
			static function () {
				self::$table = null;
			}
		);
	}

	/* ------------------------------------------------------------------
	 * Hook di runtime.
	 * ------------------------------------------------------------------ */

	/**
	 * Prima del routing: /collections* e /pages/* non esistono nel nuovo sito.
	 */
	public static function early(): void {
		if ( ! self::should_handle() ) {
			return;
		}
		$raw  = self::request_path();
		$path = self::normalize( $raw );
		if ( '/collections' !== $path && ! str_starts_with( $path, '/collections/' ) && ! str_starts_with( $path, '/pages/' ) ) {
			return;
		}
		$target = self::resolve( $raw );
		if ( $target ) {
			self::redirect( $target );
		}
	}

	/**
	 * Sulle richieste che finirebbero in 404 (prima di redirect_canonical e del "guess" di WordPress).
	 */
	public static function on_404(): void {
		if ( ! is_404() || ! self::should_handle() ) {
			return;
		}
		$target = self::resolve( self::request_path() );
		if ( $target ) {
			self::redirect( $target );
		}
	}

	/**
	 * Esegue il redirect 301 e termina la richiesta.
	 *
	 * @param string $url Destinazione assoluta.
	 */
	private static function redirect( string $url ): void {
		nocache_headers();
		// phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- destinazioni definite da admin.
		if ( wp_redirect( $url, 301, 'ECM Core' ) ) {
			exit;
		}
	}

	/**
	 * Solo richieste pubbliche GET/HEAD.
	 *
	 * @return bool
	 */
	private static function should_handle(): bool {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return false;
		}
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_key( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET';
		if ( ! in_array( $method, array( 'GET', 'HEAD' ), true ) ) {
			return false;
		}
		$path = self::normalize( self::request_path() );
		$rest = '/' . strtolower( trim( rest_get_url_prefix(), '/' ) );
		return ! ( $path === $rest || str_starts_with( $path, $rest . '/' ) || str_starts_with( $path, '/wp-' ) );
	}

	/**
	 * Path della richiesta corrente, relativo alla home.
	 *
	 * @return string
	 */
	public static function request_path(): string {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- usato solo per confronto, mai stampato.
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
		$path = (string) preg_replace( '/[?#].*$/s', '', $uri );

		$home_path = rtrim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
		if ( '' !== $home_path && 0 === stripos( $path, $home_path . '/' ) ) {
			$path = substr( $path, strlen( $home_path ) );
		}
		return '' !== $path ? $path : '/';
	}

	/* ------------------------------------------------------------------
	 * Risoluzione (pura: testabile senza redirect).
	 * ------------------------------------------------------------------ */

	/**
	 * Normalizza un path: niente query, decodificato, minuscolo, spazi compattati, senza slash finale.
	 *
	 * @param string $path Path o URI (anche con dominio).
	 * @return string Es. "/courses/metabolismo, nutrizione e performance".
	 */
	public static function normalize( string $path ): string {
		$path = trim( $path );
		if ( preg_match( '#^https?://#i', $path ) ) {
			$parsed = wp_parse_url( $path );
			$path   = (string) ( $parsed['path'] ?? '/' );
		}
		$path = (string) preg_replace( '/[?#].*$/s', '', $path );

		for ( $i = 0; $i < 3; $i++ ) {
			$decoded = rawurldecode( $path );
			if ( $decoded === $path ) {
				break;
			}
			$path = $decoded;
		}

		if ( ! mb_check_encoding( $path, 'UTF-8' ) ) {
			$path = mb_convert_encoding( $path, 'UTF-8', 'ISO-8859-1' );
		}

		$path = str_replace( array( '+', "\u{00A0}" ), ' ', $path );
		$path = str_replace( array( "\u{2019}", "\u{2018}", '`' ), "'", $path );
		$path = (string) preg_replace( '/\s+/u', ' ', $path );
		$path = (string) preg_replace( '#\s*/\s*#u', '/', $path );
		$path = (string) preg_replace( '#/+#', '/', $path );
		$path = mb_strtolower( trim( $path ), 'UTF-8' );
		$path = trim( $path, '/ ' );

		return '/' . $path;
	}

	/**
	 * Trova la destinazione di un path del vecchio sito.
	 *
	 * @param string $uri Path richiesto (anche percent-encoded, con query string).
	 * @return string|null URL assoluto di destinazione, null se nessuna regola.
	 */
	public static function resolve( string $uri ): ?string {
		$path = self::normalize( $uri );
		if ( '/' === $path ) {
			return null;
		}

		// 1. Tabella personalizzata (esatta, poi con jolly finale "*").
		$table = self::table();
		if ( isset( $table[ $path ] ) ) {
			return self::guard( $path, self::absolute( $table[ $path ] ) );
		}
		foreach ( $table as $from => $to ) {
			if ( ! str_ends_with( $from, '*' ) ) {
				continue;
			}
			$prefix = substr( $from, 0, -1 );
			$base   = rtrim( $prefix, '/' );
			if ( ( '' !== $base && $path === $base ) || ( '' !== $prefix && str_starts_with( $path, $prefix ) ) ) {
				return self::guard( $path, self::absolute( $to ) );
			}
		}

		// 2. Regole fisse del vecchio sito.
		if ( '/collections' === $path || str_starts_with( $path, '/collections/' ) ) {
			return self::archive_url();
		}
		if ( preg_match( '#^/pages/([^/]+)$#u', $path, $m ) ) {
			$slug = sanitize_title( $m[1] );
			$page = '' !== $slug ? get_page_by_path( $slug, OBJECT, 'page' ) : null;
			if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
				return (string) get_permalink( $page );
			}
			if ( in_array( $slug, self::LEGACY_PAGES, true ) ) {
				return home_url( '/' . $slug . '/' );
			}
		}

		// 3. Path storici dei corsi.
		$map = self::old_paths_map();
		if ( isset( $map[ $path ] ) ) {
			$post = get_post( $map[ $path ] );
			if ( $post instanceof WP_Post && 'publish' === $post->post_status ) {
				return self::guard( $path, (string) get_permalink( $post ) );
			}
		}

		return null;
	}

	/**
	 * Evita redirect verso lo stesso indirizzo.
	 *
	 * @param string $path   Path normalizzato richiesto.
	 * @param string $target Destinazione.
	 * @return string|null
	 */
	private static function guard( string $path, string $target ): ?string {
		if ( '' === $target ) {
			return null;
		}
		$target_host = wp_parse_url( $target, PHP_URL_HOST );
		$home_host   = wp_parse_url( home_url(), PHP_URL_HOST );
		if ( ( ! $target_host || $target_host === $home_host ) && self::normalize( $target ) === $path ) {
			return null;
		}
		return $target;
	}

	/**
	 * URL assoluto da un valore "a" (path relativo o URL completo).
	 *
	 * @param string $to Destinazione.
	 * @return string
	 */
	private static function absolute( string $to ): string {
		$to = trim( $to );
		if ( '' === $to ) {
			return '';
		}
		if ( preg_match( '#^https?://#i', $to ) ) {
			return esc_url_raw( $to );
		}
		return home_url( '/' . ltrim( $to, '/' ) );
	}

	/**
	 * URL del catalogo.
	 *
	 * @return string
	 */
	private static function archive_url(): string {
		$url = get_post_type_archive_link( 'corso' );
		return $url ? (string) $url : home_url( '/corsi/' );
	}

	/**
	 * Tabella personalizzata con chiavi normalizzate.
	 *
	 * @return array<string,string>
	 */
	public static function table(): array {
		if ( null === self::$table ) {
			self::$table = array();
			$stored      = get_option( self::OPTION, array() );
			foreach ( is_array( $stored ) ? $stored : array() as $from => $to ) {
				$from = self::normalize( (string) $from );
				if ( '/' !== $from && '' !== trim( (string) $to ) ) {
					self::$table[ $from ] = (string) $to;
				}
			}
		}
		return self::$table;
	}

	/**
	 * Mappa dei path storici dei corsi pubblicati (cache transient, invalidata a ogni modifica).
	 *
	 * @return array<string,int>
	 */
	public static function old_paths_map(): array {
		if ( null !== self::$map ) {
			return self::$map;
		}
		$map = get_transient( self::MAP_TRANSIENT );
		if ( ! is_array( $map ) ) {
			$map = array();
			$ids = get_posts(
				array(
					'post_type'        => 'corso',
					'post_status'      => 'publish',
					'posts_per_page'   => -1,
					'fields'           => 'ids',
					'no_found_rows'    => true,
					'suppress_filters' => true,
				)
			);
			if ( $ids ) {
				update_meta_cache( 'post', $ids );
			}
			foreach ( $ids as $id ) {
				$paths = get_post_meta( $id, '_ecm_old_paths', true );
				foreach ( is_array( $paths ) ? $paths : array() as $old ) {
					$key = self::normalize( (string) $old );
					if ( '/' !== $key && ! isset( $map[ $key ] ) ) {
						$map[ $key ] = (int) $id;
					}
				}
			}
			set_transient( self::MAP_TRANSIENT, $map, DAY_IN_SECONDS );
		}
		self::$map = $map;
		return $map;
	}

	/**
	 * Invalida la mappa dei path storici.
	 */
	public static function flush_map(): void {
		self::$map = null;
		delete_transient( self::MAP_TRANSIENT );
	}

	/**
	 * Invalida la mappa se il post è un corso.
	 *
	 * @param int $post_id ID.
	 */
	public static function flush_map_for_post( $post_id ): void {
		if ( 'corso' === get_post_type( $post_id ) ) {
			self::flush_map();
		}
	}

	/**
	 * Invalida la mappa quando cambiano i path storici.
	 *
	 * @param int|array $meta_id  ID meta.
	 * @param int       $post_id  ID post.
	 * @param string    $meta_key Chiave.
	 */
	public static function meta_changed( $meta_id, $post_id, $meta_key ): void {
		if ( '_ecm_old_paths' === $meta_key ) {
			self::flush_map();
		}
	}

	/* ------------------------------------------------------------------
	 * Gestione della tabella (admin e importer).
	 * ------------------------------------------------------------------ */

	/**
	 * Converte il testo dell'area admin ("da => a" per riga) in array.
	 *
	 * @param string $text   Testo.
	 * @param array  $errors Righe non valide (per riferimento).
	 * @return array<string,string>
	 */
	public static function parse_lines( string $text, array &$errors = array() ): array {
		$out   = array();
		$lines = preg_split( '/\r\n|\r|\n/', $text );
		foreach ( (array) $lines as $n => $line ) {
			$line = trim( (string) $line );
			if ( '' === $line || str_starts_with( $line, '#' ) ) {
				continue;
			}
			$parts = preg_split( '/\s*(?:=>|→|->)\s*/u', $line, 2 );
			if ( ! is_array( $parts ) || 2 !== count( $parts ) || '' === trim( $parts[0] ) || '' === trim( $parts[1] ) ) {
				$errors[] = $n + 1;
				continue;
			}
			$from = self::clean_from( $parts[0] );
			$to   = self::clean_to( $parts[1] );
			if ( '' === $from || '' === $to ) {
				$errors[] = $n + 1;
				continue;
			}
			$out[ $from ] = $to;
		}
		return $out;
	}

	/**
	 * Converte la tabella in testo per la textarea.
	 *
	 * @param array $table Tabella.
	 * @return string
	 */
	public static function to_lines( array $table ): string {
		$lines = array();
		foreach ( $table as $from => $to ) {
			$lines[] = $from . ' => ' . $to;
		}
		return implode( "\n", $lines );
	}

	/**
	 * Sanitizzazione dell'opzione (textarea o array).
	 *
	 * @param mixed $input Valore.
	 * @return array<string,string>
	 */
	public static function sanitize_option( $input ): array {
		if ( is_string( $input ) ) {
			$errors = array();
			$table  = self::parse_lines( $input, $errors );
			if ( $errors && function_exists( 'add_settings_error' ) ) {
				add_settings_error(
					self::OPTION,
					'ecm_redirect_lines',
					/* translators: %s: numeri di riga. */
					sprintf( __( 'Alcune righe non sono state capite e sono state ignorate (righe: %s). Usa il formato: /vecchio-indirizzo => /nuovo-indirizzo/', 'ecm' ), implode( ', ', $errors ) ),
					'warning'
				);
			}
			return $table;
		}
		$out = array();
		foreach ( is_array( $input ) ? $input : array() as $from => $to ) {
			$from = self::clean_from( (string) $from );
			$to   = self::clean_to( (string) $to );
			if ( '' !== $from && '' !== $to ) {
				$out[ $from ] = $to;
			}
		}
		return $out;
	}

	/**
	 * Unisce coppie da => a alla tabella esistente (importer).
	 *
	 * @param array $pairs Elenco di {from,to} o mappa da => a.
	 * @return array{created:int,updated:int,skipped:int,errors:array}
	 */
	public static function merge( array $pairs ): array {
		$stored = get_option( self::OPTION, array() );
		$stored = is_array( $stored ) ? $stored : array();
		$index  = array();
		foreach ( $stored as $from => $to ) {
			$index[ self::normalize( (string) $from ) ] = (string) $from;
		}

		$result = array(
			'created' => 0,
			'updated' => 0,
			'skipped' => 0,
			'errors'  => array(),
		);

		foreach ( $pairs as $key => $pair ) {
			if ( is_array( $pair ) ) {
				$from = (string) ( $pair['from'] ?? '' );
				$to   = (string) ( $pair['to'] ?? '' );
			} else {
				$from = (string) $key;
				$to   = (string) $pair;
			}
			$from = self::clean_from( $from );
			$to   = self::clean_to( $to );
			if ( '' === $from || '' === $to ) {
				$result['errors'][] = sprintf( 'Redirect non valido: %s', wp_json_encode( $pair ) );
				continue;
			}
			$norm = self::normalize( $from );
			if ( isset( $index[ $norm ] ) ) {
				$existing_key = $index[ $norm ];
				if ( $stored[ $existing_key ] === $to ) {
					++$result['skipped'];
					continue;
				}
				$stored[ $existing_key ] = $to;
				++$result['updated'];
				continue;
			}
			$stored[ $from ] = $to;
			$index[ $norm ]  = $from;
			++$result['created'];
		}

		if ( $result['created'] || $result['updated'] ) {
			update_option( self::OPTION, $stored, false );
		}
		self::$table = null;
		return $result;
	}

	/**
	 * Pulisce un valore "da".
	 *
	 * @param string $from Valore.
	 * @return string
	 */
	private static function clean_from( string $from ): string {
		$from = trim( wp_strip_all_tags( $from ) );
		if ( preg_match( '#^https?://#i', $from ) ) {
			$from = (string) ( wp_parse_url( $from, PHP_URL_PATH ) ?? '' );
		}
		$from = (string) preg_replace( '/[?#].*$/s', '', $from );
		if ( '' === $from ) {
			return '';
		}
		return '/' === $from[0] ? $from : '/' . $from;
	}

	/**
	 * Pulisce un valore "a".
	 *
	 * @param string $to Valore.
	 * @return string
	 */
	private static function clean_to( string $to ): string {
		$to = trim( wp_strip_all_tags( $to ) );
		if ( '' === $to ) {
			return '';
		}
		if ( preg_match( '#^https?://#i', $to ) ) {
			return esc_url_raw( $to );
		}
		$to = (string) preg_replace( '/[\s"\'<>]/', '', $to );
		return '/' === ( $to[0] ?? '' ) ? $to : '/' . $to;
	}
}
