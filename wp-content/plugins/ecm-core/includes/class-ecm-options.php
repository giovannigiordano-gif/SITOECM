<?php
/**
 * Opzioni globali `ecm_options`: schema, valori predefiniti, lettura e sanitizzazione.
 *
 * @package ECM_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Gestione dell'opzione `ecm_options`.
 */
final class ECM_Options {

	public const OPTION = 'ecm_options';

	/**
	 * Cache per richiesta dei valori uniti ai predefiniti.
	 *
	 * @var array|null
	 */
	private static ?array $cache = null;

	/**
	 * Schede della pagina impostazioni.
	 *
	 * @return array<string,string>
	 */
	public static function tabs(): array {
		return array(
			'contatti'   => __( 'Contatti & piattaforma', 'ecm' ),
			'faq'        => __( 'FAQ comuni', 'ecm' ),
			'social'     => __( 'Social & legale', 'ecm' ),
			'home'       => __( 'Home', 'ecm' ),
			'newsletter' => __( 'Newsletter & cookie', 'ecm' ),
		);
	}

	/**
	 * Schema dei campi: chiave => definizione.
	 *
	 * Tipi: text, textarea, html, email, url, digits, select, secret, code, media, template.
	 *
	 * @return array<string,array>
	 */
	public static function fields(): array {
		static $fields = null;
		if ( null !== $fields ) {
			return $fields;
		}

		$fields = array(
			// Contatti & piattaforma.
			'login_url'           => array(
				'tab'   => 'contatti',
				'label' => __( 'Link "Accedi" (piattaforma corsi)', 'ecm' ),
				'type'  => 'url',
				'desc'  => __( 'Indirizzo della pagina di accesso agli studenti su Thinkific. Viene usato dal pulsante "Accedi" del sito.', 'ecm' ),
			),
			'email_segreteria'    => array(
				'tab'   => 'contatti',
				'label' => __( 'Email segreteria', 'ecm' ),
				'type'  => 'email',
				'desc'  => __( 'Indirizzo mostrato nei contatti e usato come destinatario predefinito del modulo contatti.', 'ecm' ),
			),
			'email_supporto'      => array(
				'tab'   => 'contatti',
				'label' => __( 'Email supporto', 'ecm' ),
				'type'  => 'email',
			),
			'contact_recipient'   => array(
				'tab'   => 'contatti',
				'label' => __( 'Destinatario modulo contatti', 'ecm' ),
				'type'  => 'email',
				'desc'  => __( 'Se vuoto, le richieste arrivano all\'email della segreteria.', 'ecm' ),
			),
			'whatsapp_number'     => array(
				'tab'     => 'contatti',
				'label'   => __( 'Numero WhatsApp', 'ecm' ),
				'type'    => 'digits',
				'default' => '393394846020',
				'desc'    => __( 'Formato internazionale, solo cifre, con prefisso del paese e senza "+" (es. 393394846020).', 'ecm' ),
			),
			'whatsapp_display'    => array(
				'tab'     => 'contatti',
				'label'   => __( 'Numero WhatsApp da mostrare', 'ecm' ),
				'type'    => 'text',
				'default' => '339 484 6020',
				'desc'    => __( 'Come appare sul sito (es. 339 484 6020).', 'ecm' ),
			),
			'whatsapp_template'   => array(
				'tab'     => 'contatti',
				'label'   => __( 'Messaggio WhatsApp per un corso', 'ecm' ),
				'type'    => 'template',
				'default' => 'Salve, vorrei avere maggiori informazioni sul corso "%s"',
				'desc'    => __( 'Testo precompilato dal pulsante WhatsApp di ogni corso. %s viene sostituito dal titolo del corso.', 'ecm' ),
			),
			'whatsapp_generic'    => array(
				'tab'     => 'contatti',
				'label'   => __( 'Messaggio WhatsApp generico', 'ecm' ),
				'type'    => 'text',
				'default' => 'Salve, vorrei avere maggiori informazioni sui vostri corsi ECM.',
				'desc'    => __( 'Testo precompilato quando il pulsante WhatsApp non riguarda un corso specifico.', 'ecm' ),
			),

			// FAQ comuni.
			'faq_email_q'         => array(
				'tab'     => 'faq',
				'label'   => __( 'FAQ email: domanda', 'ecm' ),
				'type'    => 'text',
				'default' => 'Come posso ricevere maggiori informazioni?',
			),
			'faq_email_a'         => array(
				'tab'   => 'faq',
				'label' => __( 'FAQ email: risposta', 'ecm' ),
				'type'  => 'html',
				'desc'  => __( 'Mostrata in tutte le schede corso, dopo "Per chi è adatto".', 'ecm' ),
			),
			'faq_bonifico_q'      => array(
				'tab'     => 'faq',
				'label'   => __( 'FAQ bonifico: domanda', 'ecm' ),
				'type'    => 'text',
				'default' => 'È possibile pagare con bonifico bancario?',
			),
			'faq_bonifico_a'      => array(
				'tab'   => 'faq',
				'label' => __( 'FAQ bonifico: risposta', 'ecm' ),
				'type'  => 'html',
				'desc'  => __( 'Mostrata in tutte le schede corso. Lascia vuota la risposta per nascondere la domanda.', 'ecm' ),
			),

			// Social & legale.
			'instagram_url'       => array(
				'tab'   => 'social',
				'label' => __( 'Instagram', 'ecm' ),
				'type'  => 'url',
			),
			'facebook_url'        => array(
				'tab'   => 'social',
				'label' => __( 'Facebook', 'ecm' ),
				'type'  => 'url',
			),
			'youtube_url'         => array(
				'tab'   => 'social',
				'label' => __( 'YouTube', 'ecm' ),
				'type'  => 'url',
			),
			'ragione_sociale'     => array(
				'tab'   => 'social',
				'label' => __( 'Ragione sociale', 'ecm' ),
				'type'  => 'text',
			),
			'piva'                => array(
				'tab'   => 'social',
				'label' => __( 'Partita IVA', 'ecm' ),
				'type'  => 'text',
			),
			'indirizzo'           => array(
				'tab'   => 'social',
				'label' => __( 'Indirizzo', 'ecm' ),
				'type'  => 'textarea',
			),
			'termini_url'         => array(
				'tab'   => 'social',
				'label' => __( 'Termini e condizioni (URL)', 'ecm' ),
				'type'  => 'url',
			),
			'privacy_url'         => array(
				'tab'   => 'social',
				'label' => __( 'Privacy policy (URL)', 'ecm' ),
				'type'  => 'url',
				'desc'  => __( 'Se vuoto viene usata la pagina privacy impostata in Impostazioni › Privacy.', 'ecm' ),
			),
			'cookie_url'          => array(
				'tab'   => 'social',
				'label' => __( 'Cookie policy (URL)', 'ecm' ),
				'type'  => 'url',
			),
			'logo_id'             => array(
				'tab'   => 'social',
				'label' => __( 'Logo per i motori di ricerca', 'ecm' ),
				'type'  => 'media',
				'desc'  => __( 'Usato nei dati strutturati (Organization). Preferibilmente PNG quadrato, almeno 112×112 px.', 'ecm' ),
			),
			'og_default_image_id' => array(
				'tab'   => 'social',
				'label' => __( 'Immagine di condivisione predefinita', 'ecm' ),
				'type'  => 'media',
				'desc'  => __( 'Mostrata quando una pagina viene condivisa sui social e non ha una propria immagine. Ideale 1200×630 px.', 'ecm' ),
			),

			// Home.
			'hero_title'          => array(
				'tab'   => 'home',
				'label' => __( 'Titolo principale', 'ecm' ),
				'type'  => 'text',
			),
			'hero_text'           => array(
				'tab'   => 'home',
				'label' => __( 'Testo introduttivo', 'ecm' ),
				'type'  => 'textarea',
			),
			'cta_title'           => array(
				'tab'   => 'home',
				'label' => __( 'Invito finale: titolo', 'ecm' ),
				'type'  => 'text',
			),
			'cta_text'            => array(
				'tab'   => 'home',
				'label' => __( 'Invito finale: testo', 'ecm' ),
				'type'  => 'textarea',
			),
			'stats_extra'         => array(
				'tab'   => 'home',
				'label' => __( 'Numeri dichiarati (una riga per numero: valore|etichetta)', 'ecm' ),
				'type'  => 'lines',
				'desc'  => __( 'Es. 10k+|Corsisti. Mostrati in home e in Chi siamo.', 'ecm' ),
			),

			// Newsletter & cookie.
			'newsletter_title'    => array(
				'tab'     => 'newsletter',
				'label'   => __( 'Titolo box newsletter', 'ecm' ),
				'type'    => 'text',
				'default' => 'Resta aggiornato sui nuovi corsi',
			),
			'newsletter_text'     => array(
				'tab'   => 'newsletter',
				'label' => __( 'Testo box newsletter', 'ecm' ),
				'type'  => 'textarea',
			),
			'newsletter_consent'  => array(
				'tab'     => 'newsletter',
				'label'   => __( 'Testo consenso newsletter', 'ecm' ),
				'type'    => 'html',
				'default' => 'Ho letto l\'informativa privacy e acconsento a ricevere la newsletter.',
				'desc'    => __( 'Testo della casella da spuntare. Viene salvato insieme a ogni iscrizione come prova del consenso.', 'ecm' ),
			),
			'contact_consent'     => array(
				'tab'     => 'newsletter',
				'label'   => __( 'Testo consenso modulo contatti', 'ecm' ),
				'type'    => 'html',
				'default' => 'Ho letto l\'informativa privacy e acconsento al trattamento dei miei dati per ricevere una risposta.',
			),
			'newsletter_provider' => array(
				'tab'     => 'newsletter',
				'label'   => __( 'Servizio newsletter', 'ecm' ),
				'type'    => 'select',
				'default' => 'none',
				'choices' => array(
					'none'      => __( 'Nessuno (solo elenco nel sito)', 'ecm' ),
					'brevo'     => 'Brevo',
					'mailchimp' => 'Mailchimp',
				),
				'desc'    => __( 'Gli iscritti vengono sempre salvati nel sito (ECM › Iscritti newsletter). Se scegli un servizio, vengono inviati anche lì.', 'ecm' ),
			),
			'brevo_api_key'       => array(
				'tab'   => 'newsletter',
				'label' => __( 'Brevo: chiave API', 'ecm' ),
				'type'  => 'secret',
			),
			'brevo_list_id'       => array(
				'tab'   => 'newsletter',
				'label' => __( 'Brevo: ID lista', 'ecm' ),
				'type'  => 'digits',
			),
			'mailchimp_api_key'   => array(
				'tab'   => 'newsletter',
				'label' => __( 'Mailchimp: chiave API', 'ecm' ),
				'type'  => 'secret',
				'desc'  => __( 'Formato chiave-dc (es. abc123…-us21).', 'ecm' ),
			),
			'mailchimp_list_id'   => array(
				'tab'   => 'newsletter',
				'label' => __( 'Mailchimp: ID audience', 'ecm' ),
				'type'  => 'text',
			),
			'analytics_head'      => array(
				'tab'   => 'newsletter',
				'label' => __( 'Codice statistiche (dopo consenso)', 'ecm' ),
				'type'  => 'code',
				'desc'  => __( 'Incolla qui lo snippet di Google Analytics o simili. Viene caricato solo dopo che il visitatore accetta i cookie statistici dal banner.', 'ecm' ),
			),
		);

		return $fields;
	}

	/**
	 * Valori predefiniti.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults(): array {
		$defaults = array();
		foreach ( self::fields() as $key => $field ) {
			$defaults[ $key ] = $field['default'] ?? ( 'media' === $field['type'] ? 0 : '' );
		}
		return $defaults;
	}

	/**
	 * Tutte le opzioni unite ai predefiniti (cache per richiesta).
	 *
	 * @return array<string,mixed>
	 */
	public static function all(): array {
		if ( null === self::$cache ) {
			$stored = get_option( self::OPTION, array() );
			$stored = is_array( $stored ) ? $stored : array();
			$all    = array_merge( self::defaults(), $stored );

			foreach ( self::defaults() as $key => $default ) {
				if ( '' === $all[ $key ] && '' !== $default ) {
					$all[ $key ] = $default;
				}
			}
			if ( '' === (string) $all['contact_recipient'] ) {
				$all['contact_recipient'] = $all['email_segreteria'];
			}
			self::$cache = $all;
		}
		return self::$cache;
	}

	/**
	 * Legge una singola opzione.
	 *
	 * @param string $key     Chiave.
	 * @param mixed  $default Valore se vuota.
	 * @return mixed
	 */
	public static function get( string $key, $default = '' ) {
		$all   = self::all();
		$value = $all[ $key ] ?? null;
		if ( null === $value || '' === $value ) {
			return $default;
		}
		return $value;
	}

	/**
	 * Svuota la cache per richiesta (dopo un aggiornamento).
	 */
	public static function flush(): void {
		self::$cache = null;
	}

	/**
	 * Unisce valori nell'opzione senza toccare le altre chiavi (usato dall'importer).
	 *
	 * Le chiavi non presenti nello schema vengono salvate comunque (testo sanitizzato) e
	 * segnalate in `unknown`: restano leggibili con ecm_opt().
	 *
	 * @param array $values Chiave => valore grezzo.
	 * @return array{updated:string[],skipped:string[],unknown:string[]}
	 */
	public static function merge( array $values ): array {
		$fields  = self::fields();
		$stored  = get_option( self::OPTION, array() );
		$stored  = is_array( $stored ) ? $stored : array();
		$result  = array(
			'updated' => array(),
			'skipped' => array(),
			'unknown' => array(),
		);
		$changed = false;

		foreach ( $values as $key => $raw ) {
			$key = sanitize_key( (string) $key );
			if ( '' === $key || '__clear' === $key ) {
				continue;
			}
			if ( isset( $fields[ $key ] ) ) {
				$clean = self::sanitize_field( $key, $raw, $stored[ $key ] ?? '' );
			} else {
				$result['unknown'][] = $key;
				$clean               = is_array( $raw ) ? map_deep( $raw, 'sanitize_textarea_field' ) : sanitize_textarea_field( is_scalar( $raw ) ? (string) $raw : '' );
			}
			if ( array_key_exists( $key, $stored ) && $stored[ $key ] === $clean ) {
				$result['skipped'][] = $key;
				continue;
			}
			$stored[ $key ]      = $clean;
			$result['updated'][] = $key;
			$changed             = true;
		}

		if ( $changed ) {
			update_option( self::OPTION, $stored );
		}
		self::flush();
		return $result;
	}

	/**
	 * Callback di sanitizzazione per register_setting.
	 *
	 * Ogni scheda invia solo i propri campi: gli altri restano invariati.
	 *
	 * @param mixed $input Valori inviati.
	 * @return array
	 */
	public static function sanitize( $input ): array {
		$stored = get_option( self::OPTION, array() );
		$stored = is_array( $stored ) ? $stored : array();
		if ( ! is_array( $input ) ) {
			return $stored;
		}

		$fields = self::fields();
		$clear  = isset( $input['__clear'] ) && is_array( $input['__clear'] ) ? $input['__clear'] : array();
		$out    = $stored;

		foreach ( $fields as $key => $field ) {
			if ( 'secret' === $field['type'] && ! empty( $clear[ $key ] ) ) {
				$out[ $key ] = '';
				continue;
			}
			if ( ! array_key_exists( $key, $input ) ) {
				continue;
			}
			$out[ $key ] = self::sanitize_field( $key, $input[ $key ], $stored[ $key ] ?? '', true );
		}

		self::flush();
		return $out;
	}

	/**
	 * Sanitizza un valore secondo il tipo del campo.
	 *
	 * @param string $key       Chiave.
	 * @param mixed  $raw       Valore grezzo.
	 * @param mixed  $previous  Valore precedente.
	 * @param bool   $from_form true se arriva dal modulo admin (mostra avvisi).
	 * @return mixed
	 */
	public static function sanitize_field( string $key, $raw, $previous = '', bool $from_form = false ) {
		$fields = self::fields();
		$field  = $fields[ $key ] ?? array( 'type' => 'text' );
		$raw    = is_scalar( $raw ) ? (string) $raw : '';
		$label  = $field['label'] ?? $key;

		switch ( $field['type'] ) {
			case 'email':
				$raw = trim( $raw );
				if ( '' === $raw ) {
					return '';
				}
				$email = sanitize_email( $raw );
				if ( ! is_email( $email ) ) {
					if ( $from_form ) {
						/* translators: %s: nome del campo. */
						add_settings_error( self::OPTION, 'ecm_' . $key, sprintf( __( '%s: indirizzo email non valido, valore non salvato.', 'ecm' ), $label ) );
					}
					return (string) $previous;
				}
				return $email;

			case 'url':
				return esc_url_raw( trim( $raw ) );

			case 'digits':
				return preg_replace( '/\D+/', '', $raw );

			case 'textarea':
				return sanitize_textarea_field( $raw );

			case 'lines':
				$lines = array();
				foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
					$line = sanitize_text_field( $line );
					if ( '' !== $line ) {
						$lines[] = $line;
					}
				}
				return implode( "\n", $lines );

			case 'html':
				return wp_kses_post( trim( $raw ) );

			case 'select':
				$choices = $field['choices'] ?? array();
				return isset( $choices[ $raw ] ) ? $raw : (string) ( $field['default'] ?? '' );

			case 'secret':
				$raw = trim( sanitize_text_field( $raw ) );
				if ( '' === $raw && $from_form ) {
					return (string) $previous;
				}
				return $raw;

			case 'code':
				if ( current_user_can( 'unfiltered_html' ) || ! $from_form ) {
					return trim( $raw );
				}
				if ( $from_form ) {
					add_settings_error( self::OPTION, 'ecm_' . $key, __( 'Non hai i permessi per inserire codice di tracciamento: il campo non è stato modificato.', 'ecm' ) );
				}
				return (string) $previous;

			case 'media':
				$id = absint( $raw );
				return ( $id && 'attachment' === get_post_type( $id ) ) ? $id : 0;

			case 'template':
				$value = sanitize_text_field( $raw );
				if ( '' !== $value && false === strpos( $value, '%s' ) ) {
					$value .= ' "%s"';
					if ( $from_form ) {
						add_settings_error( self::OPTION, 'ecm_' . $key, __( 'Al messaggio WhatsApp è stato aggiunto %s, che viene sostituito dal titolo del corso.', 'ecm' ), 'warning' );
					}
				}
				return $value;

			case 'text':
			default:
				return sanitize_text_field( $raw );
		}
	}
}

add_action(
	'update_option_' . ECM_Options::OPTION,
	static function () {
		ECM_Options::flush();
	}
);
add_action(
	'add_option_' . ECM_Options::OPTION,
	static function () {
		ECM_Options::flush();
	}
);
