<?php
/**
 * Registrazione dei meta (post e termini) e funzioni di sanitizzazione condivise.
 *
 * @package ECM_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Meta del plugin.
 */
final class ECM_Meta {

	/**
	 * Stati validi del corso.
	 */
	public const STATI = array( 'disponibile', 'preordine', 'in-arrivo', 'concluso' );

	/**
	 * Valori validi per la fonte delle testimonianze.
	 */
	public const FONTI = array( 'home', 'testimonianze', 'entrambe' );

	/**
	 * Aggancia gli hook.
	 */
	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register' ), 6 );
	}

	/**
	 * Registra i meta con tipo, valore predefinito e sanitizzazione.
	 */
	public static function register(): void {
		$auth = static function ( $allowed, $meta_key, $object_id ) {
			return current_user_can( 'edit_post', (int) $object_id );
		};

		$corso = array(
			'_ecm_brochure_id'         => array( 'integer', 0, 'absint' ),
			'_ecm_brochure_preview_id' => array( 'integer', 0, 'absint' ),
			'_ecm_patrocini'           => array( 'array', array(), array( __CLASS__, 'sanitize_patrocini' ) ),
			'_ecm_testimonianze'       => array( 'array', array(), array( __CLASS__, 'sanitize_testimonianze' ) ),
			'_ecm_per_chi'             => array( 'string', '', array( __CLASS__, 'sanitize_html' ) ),
			'_ecm_faq'                 => array( 'array', array(), array( __CLASS__, 'sanitize_faq' ) ),
			'_ecm_stato'               => array( 'string', 'disponibile', array( __CLASS__, 'sanitize_stato' ) ),
			'_ecm_crediti'             => array( 'string', '', array( __CLASS__, 'sanitize_crediti' ) ),
			'_ecm_link_iscrizione'     => array( 'string', '', 'esc_url_raw' ),
			'_ecm_in_evidenza'         => array( 'string', '', array( __CLASS__, 'sanitize_flag' ) ),
			'_ecm_old_paths'           => array( 'array', array(), array( __CLASS__, 'sanitize_paths' ) ),
			'_ecm_source_slug'         => array( 'string', '', 'sanitize_text_field' ),
		);
		foreach ( $corso as $key => $def ) {
			self::register_one( 'corso', $key, $def, $auth );
		}

		foreach ( array( 'corso', 'page' ) as $type ) {
			self::register_one( $type, '_ecm_seo_title', array( 'string', '', 'sanitize_text_field' ), $auth );
			self::register_one( $type, '_ecm_seo_desc', array( 'string', '', 'sanitize_textarea_field' ), $auth );
		}

		$docente = array(
			'_ecm_titolo'     => array( 'string', '', 'sanitize_text_field' ),
			'_ecm_cv_id'      => array( 'integer', 0, 'absint' ),
			'_ecm_cv_url'     => array( 'string', '', 'esc_url_raw' ),
			'_ecm_source_key' => array( 'string', '', 'sanitize_text_field' ),
		);
		foreach ( $docente as $key => $def ) {
			self::register_one( 'docente', $key, $def, $auth );
		}

		$testimonianza = array(
			'_ecm_corso_id'    => array( 'integer', 0, 'absint' ),
			'_ecm_corso_label' => array( 'string', '', 'sanitize_text_field' ),
			'_ecm_fonte'       => array( 'string', 'entrambe', array( __CLASS__, 'sanitize_fonte' ) ),
			'_ecm_rating'      => array( 'string', '', array( __CLASS__, 'sanitize_rating' ) ),
			'_ecm_source_key'  => array( 'string', '', 'sanitize_text_field' ),
		);
		foreach ( $testimonianza as $key => $def ) {
			self::register_one( 'testimonianza', $key, $def, $auth );
		}

		$term_auth = static function () {
			return current_user_can( 'manage_categories' );
		};
		foreach ( array( 'area_tematica', 'destinatario' ) as $tax ) {
			register_term_meta(
				$tax,
				'_ecm_order',
				array(
					'type'              => 'integer',
					'single'            => true,
					'default'           => 0,
					'sanitize_callback' => array( __CLASS__, 'sanitize_int' ),
					'auth_callback'     => $term_auth,
					'show_in_rest'      => false,
				)
			);
		}
		register_term_meta(
			'area_tematica',
			'_ecm_image_key',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => array( __CLASS__, 'sanitize_slug' ),
				'auth_callback'     => $term_auth,
			)
		);
		register_term_meta(
			'area_tematica',
			'_ecm_short',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => array( __CLASS__, 'sanitize_short' ),
				'auth_callback'     => $term_auth,
			)
		);
	}

	/**
	 * Registra un singolo meta.
	 *
	 * @param string   $post_type Tipo di contenuto.
	 * @param string   $key       Chiave.
	 * @param array    $def       [tipo, predefinito, sanitizzazione].
	 * @param callable $auth      Callback autorizzazione.
	 */
	private static function register_one( string $post_type, string $key, array $def, callable $auth ): void {
		register_post_meta(
			$post_type,
			$key,
			array(
				'type'              => $def[0],
				'single'            => true,
				'default'           => $def[1],
				'sanitize_callback' => $def[2],
				'auth_callback'     => $auth,
				'show_in_rest'      => false,
			)
		);
	}

	/* ------------------------------------------------------------------
	 * Sanitizzazione.
	 * ------------------------------------------------------------------ */

	/**
	 * HTML consentito nei contenuti (kses post).
	 *
	 * @param mixed $value Valore.
	 * @return string
	 */
	public static function sanitize_html( $value ): string {
		return is_scalar( $value ) ? wp_kses_post( trim( (string) $value ) ) : '';
	}

	/**
	 * Intero (anche negativo). Wrapper a un solo argomento: i filtri sanitize_*_meta passano 4 argomenti.
	 *
	 * @param mixed $value Valore.
	 * @return int
	 */
	public static function sanitize_int( $value ): int {
		return is_numeric( $value ) ? (int) $value : 0;
	}

	/**
	 * Slug (chiave immagine).
	 *
	 * @param mixed $value Valore.
	 * @return string
	 */
	public static function sanitize_slug( $value ): string {
		return is_scalar( $value ) ? sanitize_title( (string) $value ) : '';
	}

	/**
	 * Stato del corso.
	 *
	 * @param mixed $value Valore.
	 * @return string
	 */
	public static function sanitize_stato( $value ): string {
		$value = sanitize_key( (string) $value );
		$value = 'pre-ordine' === $value ? 'preordine' : $value;
		return in_array( $value, self::STATI, true ) ? $value : 'disponibile';
	}

	/**
	 * Crediti ECM: testo breve (di solito un numero).
	 *
	 * @param mixed $value Valore.
	 * @return string
	 */
	public static function sanitize_crediti( $value ): string {
		$value = is_scalar( $value ) ? trim( sanitize_text_field( (string) $value ) ) : '';
		return mb_substr( $value, 0, 20 );
	}

	/**
	 * Flag '1' o ''.
	 *
	 * @param mixed $value Valore.
	 * @return string
	 */
	public static function sanitize_flag( $value ): string {
		return ( ! empty( $value ) && 'false' !== $value && '0' !== $value ) ? '1' : '';
	}

	/**
	 * Fonte testimonianza.
	 *
	 * @param mixed $value Valore.
	 * @return string
	 */
	public static function sanitize_fonte( $value ): string {
		$value = sanitize_key( (string) $value );
		return in_array( $value, self::FONTI, true ) ? $value : 'entrambe';
	}

	/**
	 * Valutazione 1-5 o vuota.
	 *
	 * @param mixed $value Valore.
	 * @return string
	 */
	public static function sanitize_rating( $value ): string {
		if ( '' === $value || null === $value || false === $value ) {
			return '';
		}
		$n = (int) $value;
		return ( $n >= 1 && $n <= 5 ) ? (string) $n : '';
	}

	/**
	 * Descrizione breve dell'area (max 160 caratteri).
	 *
	 * @param mixed $value Valore.
	 * @return string
	 */
	public static function sanitize_short( $value ): string {
		$value = is_scalar( $value ) ? sanitize_textarea_field( (string) $value ) : '';
		return mb_substr( trim( $value ), 0, 160 );
	}

	/**
	 * Patrocini: [{image_id, name}].
	 *
	 * @param mixed $rows Righe.
	 * @return array
	 */
	public static function sanitize_patrocini( $rows ): array {
		$out = array();
		foreach ( self::rows( $rows ) as $row ) {
			$image_id = absint( $row['image_id'] ?? 0 );
			$name     = sanitize_text_field( (string) ( $row['name'] ?? '' ) );
			if ( ! $image_id && '' === $name ) {
				continue;
			}
			$out[] = array(
				'image_id' => $image_id,
				'name'     => $name,
			);
		}
		return $out;
	}

	/**
	 * Testimonianze del corso: [{name, meta, text}].
	 *
	 * @param mixed $rows Righe.
	 * @return array
	 */
	public static function sanitize_testimonianze( $rows ): array {
		$out = array();
		foreach ( self::rows( $rows ) as $row ) {
			$name = sanitize_text_field( (string) ( $row['name'] ?? '' ) );
			$meta = sanitize_text_field( (string) ( $row['meta'] ?? '' ) );
			$text = sanitize_textarea_field( (string) ( $row['text'] ?? '' ) );
			if ( '' === $name && '' === trim( $text ) ) {
				continue;
			}
			$out[] = array(
				'name' => $name,
				'meta' => $meta,
				'text' => trim( $text ),
			);
		}
		return $out;
	}

	/**
	 * FAQ del corso: [{q, a}] con risposta HTML.
	 *
	 * @param mixed $rows Righe.
	 * @return array
	 */
	public static function sanitize_faq( $rows ): array {
		$out = array();
		foreach ( self::rows( $rows ) as $row ) {
			$q = sanitize_text_field( (string) ( $row['q'] ?? '' ) );
			$a = wp_kses_post( trim( (string) ( $row['a'] ?? ( $row['a_html'] ?? '' ) ) ) );
			if ( '' === $q ) {
				continue;
			}
			$out[] = array(
				'q' => $q,
				'a' => $a,
			);
		}
		return $out;
	}

	/**
	 * Path storici: stringhe che iniziano con "/", senza dominio né query.
	 *
	 * @param mixed $paths Elenco o testo (uno per riga).
	 * @return array
	 */
	public static function sanitize_paths( $paths ): array {
		if ( is_string( $paths ) ) {
			$paths = preg_split( '/\r\n|\r|\n/', $paths );
		}
		if ( ! is_array( $paths ) ) {
			return array();
		}
		$out = array();
		foreach ( $paths as $path ) {
			if ( ! is_scalar( $path ) ) {
				continue;
			}
			$path = trim( wp_strip_all_tags( (string) $path ) );
			if ( '' === $path ) {
				continue;
			}
			if ( preg_match( '#^https?://#i', $path ) ) {
				$parsed = wp_parse_url( $path );
				$path   = $parsed['path'] ?? '';
			}
			$path = (string) preg_replace( '/[?#].*$/', '', $path );
			if ( '' === $path ) {
				continue;
			}
			if ( '/' !== $path[0] ) {
				$path = '/' . $path;
			}
			$path = mb_substr( $path, 0, 500 );
			if ( ! in_array( $path, $out, true ) ) {
				$out[] = $path;
			}
		}
		return $out;
	}

	/**
	 * Normalizza un insieme di righe di ripetitore.
	 *
	 * @param mixed $rows Righe.
	 * @return array<int,array>
	 */
	private static function rows( $rows ): array {
		if ( ! is_array( $rows ) ) {
			return array();
		}
		$out = array();
		foreach ( $rows as $row ) {
			if ( is_array( $row ) ) {
				$out[] = $row;
			}
		}
		return $out;
	}
}
