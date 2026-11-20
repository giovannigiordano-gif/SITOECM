<?php
/**
 * API pubblica per il tema `ecm`.
 *
 * Il tema legge i dati esclusivamente tramite queste funzioni (con function_exists come guardia).
 * Nomi e forme dei valori restituiti sono fissati da docs/contract-ecm-core.md: non rinominare.
 *
 * Tutti i testi restituiti sono "grezzi" (non escapati): il tema applica esc_html/esc_url/esc_attr
 * in output. I campi *_html e `content` sono HTML già filtrato (kses/the_content).
 *
 * @package ECM_Core
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-ecm-data.php';

if ( ! function_exists( 'ecm_opt' ) ) {
	/**
	 * Legge un'opzione globale da `ecm_options`.
	 *
	 * @param string $key     Chiave (vedi contratto).
	 * @param mixed  $default Valore restituito se l'opzione è vuota.
	 * @return mixed
	 */
	function ecm_opt( string $key, $default = '' ) {
		return ECM_Options::get( $key, $default );
	}
}

if ( ! function_exists( 'ecm_whatsapp_url' ) ) {
	/**
	 * Link WhatsApp con testo precompilato.
	 *
	 * @param string $text Testo; se vuoto usa `whatsapp_generic`.
	 * @return string URL https://wa.me/{numero}?text=... (vuoto se il numero non è impostato).
	 */
	function ecm_whatsapp_url( string $text = '' ): string {
		$number = (string) preg_replace( '/\D+/', '', (string) ecm_opt( 'whatsapp_number' ) );
		if ( '' === $number ) {
			return '';
		}
		if ( '' === trim( $text ) ) {
			$text = (string) ecm_opt( 'whatsapp_generic' );
		}
		$url = 'https://wa.me/' . $number;
		if ( '' !== trim( $text ) ) {
			$url .= '?text=' . rawurlencode( trim( $text ) );
		}
		return $url;
	}
}

if ( ! function_exists( 'ecm_course_whatsapp_url' ) ) {
	/**
	 * Link WhatsApp per un corso (template `whatsapp_template` con il titolo del corso).
	 *
	 * @param int|WP_Post $post Corso.
	 * @return string
	 */
	function ecm_course_whatsapp_url( $post ): string {
		$post = get_post( $post );
		if ( ! $post instanceof WP_Post ) {
			return ecm_whatsapp_url();
		}
		$title    = ecm_plain_title( $post );
		$template = (string) ecm_opt( 'whatsapp_template', 'Salve, vorrei avere maggiori informazioni sul corso "%s"' );
		$text     = false !== strpos( $template, '%s' ) ? str_replace( '%s', $title, $template ) : trim( $template . ' ' . $title );
		return ecm_whatsapp_url( $text );
	}
}

if ( ! function_exists( 'ecm_stati' ) ) {
	/**
	 * Stati del corso.
	 *
	 * @return array<string,string>
	 */
	function ecm_stati(): array {
		return array(
			'disponibile' => __( 'Disponibile', 'ecm' ),
			'preordine'   => __( 'Pre-ordine', 'ecm' ),
			'in-arrivo'   => __( 'In arrivo', 'ecm' ),
			'concluso'    => __( 'Concluso', 'ecm' ),
		);
	}
}

if ( ! function_exists( 'ecm_course' ) ) {
	/**
	 * Dati completi di un corso (forma nel contratto).
	 *
	 * @param int|WP_Post|null $post Corso; null = post corrente.
	 * @return array Vuoto se il post non è un corso.
	 */
	function ecm_course( $post = null ): array {
		return ECM_Data::course( $post );
	}
}

if ( ! function_exists( 'ecm_courses' ) ) {
	/**
	 * Corsi pubblicati, ordinati per ordine manuale e titolo.
	 *
	 * @param array $args featured(bool), area(slug), exclude(int[]), limit(int).
	 *                    Extra: destinatario(slug), stato(chiave), include(int[]).
	 * @return array<int,array> Elenco di ecm_course().
	 */
	function ecm_courses( array $args = array() ): array {
		return ECM_Data::courses( $args );
	}
}

if ( ! function_exists( 'ecm_areas' ) ) {
	/**
	 * Aree tematiche ordinate per _ecm_order.
	 *
	 * @return array<int,array{id:int,slug:string,name:string,description:string,count:int,parent:int,order:int,url:string,image_key:string,short:string}>
	 */
	function ecm_areas(): array {
		return array_values( ECM_Data::terms( 'area_tematica' ) );
	}
}

if ( ! function_exists( 'ecm_destinatari' ) ) {
	/**
	 * Destinatari ordinati per _ecm_order.
	 *
	 * @return array<int,array{id:int,slug:string,name:string,description:string,count:int,parent:int,order:int,url:string}>
	 */
	function ecm_destinatari(): array {
		return array_values( ECM_Data::terms( 'destinatario' ) );
	}
}

if ( ! function_exists( 'ecm_docenti' ) ) {
	/**
	 * Docenti ordinati.
	 *
	 * @return array<int,array{id:int,name:string,titolo:string,bio_html:string,photo_id:int,cv_url:string,initials:string}>
	 */
	function ecm_docenti(): array {
		return ECM_Data::docenti();
	}
}

if ( ! function_exists( 'ecm_testimonianze' ) ) {
	/**
	 * Testimonianze.
	 *
	 * @param array $args fonte (home|testimonianze; include sempre "entrambe"), corso_id, limit.
	 * @return array<int,array{id:int,name:string,text:string,corso_id:int,corso_title:string,corso_url:string,fonte:string,rating:int}>
	 */
	function ecm_testimonianze( array $args = array() ): array {
		return ECM_Data::testimonianze( $args );
	}
}

if ( ! function_exists( 'ecm_stats' ) ) {
	/**
	 * Numeri del sito.
	 *
	 * @return array{corsi:int,docenti:int,patrocini_ordini:int,testimonianze:int}
	 */
	function ecm_stats(): array {
		return ECM_Data::stats();
	}
}

if ( ! function_exists( 'ecm_human_filesize' ) ) {
	/**
	 * Dimensione file leggibile in formato italiano (es. "1,8 MB").
	 *
	 * @param int $bytes Byte.
	 * @return string
	 */
	function ecm_human_filesize( int $bytes ): string {
		if ( $bytes < 1024 ) {
			return max( 0, $bytes ) . ' B';
		}
		$units = array( 'KB', 'MB', 'GB', 'TB' );
		$value = $bytes / 1024;
		$i     = 0;
		while ( $value >= 1024 && $i < count( $units ) - 1 ) {
			$value /= 1024;
			++$i;
		}
		$formatted = number_format( $value, $value < 10 ? 1 : 0, ',', '.' );
		$formatted = (string) preg_replace( '/,0$/', '', $formatted );
		return $formatted . ' ' . $units[ $i ];
	}
}

if ( ! function_exists( 'ecm_privacy_urls' ) ) {
	/**
	 * URL delle pagine legali.
	 *
	 * @return array{privacy:string,cookie:string,termini:string}
	 */
	function ecm_privacy_urls(): array {
		$privacy = (string) ecm_opt( 'privacy_url' );
		if ( '' === $privacy ) {
			$privacy = (string) get_privacy_policy_url();
		}
		return array(
			'privacy' => $privacy,
			'cookie'  => (string) ecm_opt( 'cookie_url' ),
			'termini' => (string) ecm_opt( 'termini_url' ),
		);
	}
}

if ( ! function_exists( 'ecm_form_hidden_fields' ) ) {
	/**
	 * Campi nascosti anti-spam e nonce per i moduli (honeypot `website`, `_ts`, `_wpnonce`).
	 *
	 * Extra rispetto al contratto: comodità per il tema.
	 *
	 * @return string HTML già escapato.
	 */
	function ecm_form_hidden_fields(): string {
		return ECM_Forms::hidden_fields();
	}
}

if ( ! function_exists( 'ecm_form_endpoint' ) ) {
	/**
	 * URL dell'endpoint REST di un modulo.
	 *
	 * @param string $form contact|newsletter.
	 * @return string
	 */
	function ecm_form_endpoint( string $form = 'contact' ): string {
		return rest_url( 'ecm/v1/' . ( 'newsletter' === $form ? 'newsletter' : 'contact' ) );
	}
}

if ( ! function_exists( 'ecm_plain_title' ) ) {
	/**
	 * Titolo in testo semplice (entità decodificate, tipografia di WordPress applicata).
	 *
	 * @param WP_Post|int|null $post Post.
	 * @return string
	 */
	function ecm_plain_title( $post ): string {
		$post = get_post( $post );
		if ( ! $post instanceof WP_Post ) {
			return '';
		}
		$title = get_the_title( $post );
		return trim( html_entity_decode( wp_strip_all_tags( (string) $title ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	}
}

if ( ! function_exists( 'ecm_core_initials' ) ) {
	/**
	 * Iniziali da un nome, ignorando i titoli (Prof., Dott.ssa, Dr...).
	 *
	 * @param string $name Nome completo.
	 * @return string Es. "MR".
	 */
	function ecm_core_initials( string $name ): string {
		$tokens = preg_split( '/\s+/u', trim( $name ) );
		$words  = array();
		foreach ( (array) $tokens as $token ) {
			$clean = trim( (string) $token, " \t.,;:()" );
			if ( '' === $clean ) {
				continue;
			}
			if ( preg_match( '/^(prof|dott|dr|dr\.?ssa|d\.?ssa|ing|avv|sig|sig\.?ra|prof\.?ssa|dott\.?ssa|rag|arch)$/iu', $clean ) ) {
				continue;
			}
			$words[] = $clean;
		}
		if ( ! $words ) {
			return '';
		}
		$first = mb_substr( $words[0], 0, 1 );
		$last  = count( $words ) > 1 ? mb_substr( (string) end( $words ), 0, 1 ) : '';
		return mb_strtoupper( $first . $last );
	}
}
