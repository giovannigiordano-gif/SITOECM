<?php
/**
 * Moduli pubblici via REST (`ecm/v1/contact`, `ecm/v1/newsletter`).
 *
 * Protezioni: nonce `wp_rest`, honeypot `website`, trappola temporale `_ts` (< 3 s = scarto),
 * limite per IP (transient). Risposta JSON: { ok: bool, message: string, errors: { campo: messaggio } }.
 *
 * @package ECM_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Moduli.
 */
final class ECM_Forms {

	public const NS          = 'ecm/v1';
	public const MIN_SECONDS = 3;
	public const WINDOW      = 600;

	/**
	 * Ultimo errore di wp_mail.
	 *
	 * @var string
	 */
	private static string $mail_error = '';

	/**
	 * Aggancia gli hook.
	 */
	public static function init(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	/**
	 * Registra gli endpoint.
	 */
	public static function routes(): void {
		register_rest_route(
			self::NS,
			'/contact',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'contact' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			self::NS,
			'/newsletter',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'newsletter' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Campi nascosti per il tema.
	 *
	 * @return string
	 */
	public static function hidden_fields(): string {
		return sprintf(
			'<div class="ecm-hp" aria-hidden="true" style="position:absolute!important;left:-10000px!important;width:1px;height:1px;overflow:hidden;"><label>%1$s <input type="text" name="website" value="" tabindex="-1" autocomplete="off"></label></div><input type="hidden" name="_ts" value="%2$d"><input type="hidden" name="_wpnonce" value="%3$s">',
			esc_html__( 'Non compilare questo campo', 'ecm' ),
			time(),
			esc_attr( wp_create_nonce( 'wp_rest' ) )
		);
	}

	/* ------------------------------------------------------------------
	 * Endpoint.
	 * ------------------------------------------------------------------ */

	/**
	 * POST /ecm/v1/contact.
	 *
	 * @param WP_REST_Request $request Richiesta.
	 * @return WP_REST_Response
	 */
	public static function contact( WP_REST_Request $request ): WP_REST_Response {
		$blocked = self::guard( $request, 'contact' );
		if ( $blocked ) {
			return $blocked;
		}
		$success = __( 'Grazie! Abbiamo ricevuto il tuo messaggio e ti risponderemo al più presto.', 'ecm' );
		if ( self::is_bot( $request ) ) {
			return self::respond( true, $success );
		}

		$data = array(
			'nome'        => self::text( $request->get_param( 'nome' ), 100 ),
			'email'       => sanitize_email( self::text( $request->get_param( 'email' ), 190 ) ),
			'telefono'    => self::text( $request->get_param( 'telefono' ), 30 ),
			'professione' => self::text( $request->get_param( 'professione' ), 100 ),
			'corso'       => self::text( $request->get_param( 'corso' ), 200 ),
			'messaggio'   => self::textarea( $request->get_param( 'messaggio' ), 5000 ),
			'privacy'     => self::truthy( $request->get_param( 'privacy' ) ),
		);

		$errors = array();
		if ( mb_strlen( $data['nome'] ) < 2 ) {
			$errors['nome'] = __( 'Inserisci il tuo nome e cognome.', 'ecm' );
		}
		if ( '' === $data['email'] || ! is_email( $data['email'] ) ) {
			$errors['email'] = __( 'Inserisci un indirizzo email valido.', 'ecm' );
		}
		if ( '' !== $data['telefono'] && ! preg_match( '/^\+?[0-9 .\-\/()]{6,25}$/', $data['telefono'] ) ) {
			$errors['telefono'] = __( 'Il numero di telefono non sembra valido: usa solo cifre, spazi e l\'eventuale prefisso +.', 'ecm' );
		}
		if ( mb_strlen( trim( $data['messaggio'] ) ) < 10 ) {
			$errors['messaggio'] = __( 'Scrivi un messaggio di almeno 10 caratteri.', 'ecm' );
		}
		if ( ! $data['privacy'] ) {
			$errors['privacy'] = __( 'Per inviare la richiesta è necessario accettare l\'informativa privacy.', 'ecm' );
		}
		if ( $errors ) {
			return self::respond( false, __( 'Controlla i campi evidenziati e riprova.', 'ecm' ), $errors, 422 );
		}

		if ( ! self::hit( 'contact_ok', (int) apply_filters( 'ecm_rate_limit', 5, 'contact' ) ) ) {
			return self::respond( false, __( 'Hai inviato troppe richieste in poco tempo. Riprova tra qualche minuto oppure scrivici su WhatsApp.', 'ecm' ), array(), 429 );
		}

		$now  = current_time( 'mysql' );
		$meta = array(
			'_ecm_nome'        => $data['nome'],
			'_ecm_email'       => $data['email'],
			'_ecm_telefono'    => $data['telefono'],
			'_ecm_professione' => $data['professione'],
			'_ecm_corso'       => $data['corso'],
			'_ecm_messaggio'   => $data['messaggio'],
			'_ecm_privacy'     => $now,
			'_ecm_consenso'    => wp_strip_all_tags( (string) ecm_opt( 'contact_consent' ) ),
			'_ecm_ip_hash'     => self::ip_hash(),
			'_ecm_pagina'      => esc_url_raw( (string) $request->get_header( 'referer' ) ),
		);

		$post_id = wp_insert_post(
			array(
				'post_type'    => 'ecm_richiesta',
				'post_status'  => 'publish',
				'post_title'   => wp_slash( $data['nome'] . ' – ' . $data['email'] ),
				'post_content' => '',
				'meta_input'   => wp_slash( $meta ),
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			ECM_Logger::error( 'contact', 'Salvataggio richiesta non riuscito: ' . $post_id->get_error_message() );
			return self::respond( false, __( 'Si è verificato un problema temporaneo. Riprova tra poco oppure scrivici su WhatsApp.', 'ecm' ), array(), 500 );
		}

		$sent = self::send_contact_mail( $data, (int) $post_id );
		update_post_meta( (int) $post_id, '_ecm_mail_sent', $sent ? '1' : '0' );

		/**
		 * Dopo il salvataggio di una richiesta di contatto.
		 *
		 * @param int   $post_id ID richiesta.
		 * @param array $data    Dati sanitizzati.
		 */
		do_action( 'ecm_contact_received', (int) $post_id, $data );

		return self::respond( true, $success );
	}

	/**
	 * POST /ecm/v1/newsletter.
	 *
	 * @param WP_REST_Request $request Richiesta.
	 * @return WP_REST_Response
	 */
	public static function newsletter( WP_REST_Request $request ): WP_REST_Response {
		$blocked = self::guard( $request, 'newsletter' );
		if ( $blocked ) {
			return $blocked;
		}
		$success = __( 'Grazie! La tua iscrizione alla newsletter è stata registrata.', 'ecm' );
		if ( self::is_bot( $request ) ) {
			return self::respond( true, $success );
		}

		$email   = strtolower( sanitize_email( self::text( $request->get_param( 'email' ), 190 ) ) );
		$privacy = self::truthy( $request->get_param( 'privacy' ) );

		$errors = array();
		if ( '' === $email || ! is_email( $email ) ) {
			$errors['email'] = __( 'Inserisci un indirizzo email valido.', 'ecm' );
		}
		if ( ! $privacy ) {
			$errors['privacy'] = __( 'Per iscriverti è necessario accettare l\'informativa privacy.', 'ecm' );
		}
		if ( $errors ) {
			return self::respond( false, __( 'Controlla i campi evidenziati e riprova.', 'ecm' ), $errors, 422 );
		}

		if ( ! self::hit( 'newsletter_ok', (int) apply_filters( 'ecm_rate_limit', 5, 'newsletter' ) ) ) {
			return self::respond( false, __( 'Hai inviato troppe richieste in poco tempo. Riprova tra qualche minuto.', 'ecm' ), array(), 429 );
		}

		$existing = get_posts(
			array(
				'post_type'        => 'ecm_iscritto',
				'post_status'      => array( 'publish', 'draft', 'private' ),
				'posts_per_page'   => 1,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => true,
				'meta_key'         => '_ecm_email', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'       => $email, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		$meta = array(
			'_ecm_email'          => $email,
			'_ecm_consenso_data'  => current_time( 'mysql' ),
			'_ecm_consenso_testo' => wp_strip_all_tags( (string) ecm_opt( 'newsletter_consent' ) ),
			'_ecm_ip_hash'        => self::ip_hash(),
			'_ecm_pagina'         => esc_url_raw( (string) $request->get_header( 'referer' ) ),
		);

		if ( $existing ) {
			$post_id = (int) $existing[0];
			foreach ( $meta as $key => $value ) {
				update_post_meta( $post_id, $key, wp_slash( $value ) );
			}
		} else {
			$post_id = wp_insert_post(
				array(
					'post_type'   => 'ecm_iscritto',
					'post_status' => 'publish',
					'post_title'  => wp_slash( $email ),
					'meta_input'  => wp_slash( $meta ),
				),
				true
			);
			if ( is_wp_error( $post_id ) ) {
				ECM_Logger::error( 'newsletter', 'Salvataggio iscritto non riuscito: ' . $post_id->get_error_message() );
				return self::respond( false, __( 'Si è verificato un problema temporaneo. Riprova tra poco.', 'ecm' ), array(), 500 );
			}
			$post_id = (int) $post_id;
		}

		$sync = ECM_Newsletter::subscribe( $email );
		update_post_meta( $post_id, '_ecm_provider', ECM_Newsletter::provider() );
		update_post_meta( $post_id, '_ecm_provider_status', $sync['status'] );
		update_post_meta( $post_id, '_ecm_provider_error', wp_slash( $sync['error'] ) );

		/**
		 * Dopo una iscrizione alla newsletter.
		 *
		 * @param int    $post_id ID iscritto.
		 * @param string $email   Email.
		 * @param array  $sync    Esito invio al servizio esterno.
		 */
		do_action( 'ecm_newsletter_subscribed', $post_id, $email, $sync );

		return self::respond( true, $success );
	}

	/* ------------------------------------------------------------------
	 * Protezioni.
	 * ------------------------------------------------------------------ */

	/**
	 * Controlli comuni: nonce, limite tentativi, timestamp.
	 *
	 * @param WP_REST_Request $request Richiesta.
	 * @param string          $form    Nome modulo.
	 * @return WP_REST_Response|null Risposta di blocco o null se si può proseguire.
	 */
	private static function guard( WP_REST_Request $request, string $form ): ?WP_REST_Response {
		if ( apply_filters( 'ecm_forms_require_nonce', true, $form ) ) {
			$nonce = (string) $request->get_header( 'x_wp_nonce' );
			if ( '' === $nonce ) {
				$nonce = (string) $request->get_param( '_wpnonce' );
			}
			if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
				return self::respond( false, __( 'La sessione della pagina è scaduta. Ricarica la pagina e invia di nuovo il modulo.', 'ecm' ), array(), 403 );
			}
		}

		if ( ! self::hit( $form . '_all', (int) apply_filters( 'ecm_rate_limit_attempts', 20, $form ) ) ) {
			return self::respond( false, __( 'Troppi tentativi in poco tempo. Riprova tra qualche minuto.', 'ecm' ), array(), 429 );
		}

		$ts = $request->get_param( '_ts' );
		if ( null === $ts || '' === $ts || ! is_numeric( $ts ) ) {
			return self::respond( false, __( 'Invio non valido. Ricarica la pagina e riprova.', 'ecm' ), array(), 400 );
		}
		return null;
	}

	/**
	 * Honeypot compilato o invio troppo rapido: scarto silenzioso.
	 *
	 * @param WP_REST_Request $request Richiesta.
	 * @return bool
	 */
	private static function is_bot( WP_REST_Request $request ): bool {
		$honeypot = $request->get_param( 'website' );
		if ( is_scalar( $honeypot ) && '' !== trim( (string) $honeypot ) ) {
			return true;
		}
		$ts = (float) $request->get_param( '_ts' );
		if ( $ts > 100000000000 ) {
			$ts = $ts / 1000; // Millisecondi (Date.now()).
		}
		$elapsed = time() - $ts;
		// Orologi sfasati nel futuro: nessuna penalità.
		return $elapsed >= 0 && $elapsed < self::MIN_SECONDS;
	}

	/**
	 * Conta un tentativo per IP; false se il limite è superato.
	 *
	 * @param string $bucket Contatore.
	 * @param int    $limit  Limite nella finestra.
	 * @return bool
	 */
	private static function hit( string $bucket, int $limit ): bool {
		if ( $limit <= 0 ) {
			return true;
		}
		$key  = 'ecm_rl_' . md5( $bucket . '|' . self::ip_hash() );
		$data = get_transient( $key );
		$now  = time();
		if ( ! is_array( $data ) || ( $now - (int) ( $data['start'] ?? 0 ) ) > self::WINDOW ) {
			$data = array(
				'count' => 0,
				'start' => $now,
			);
		}
		if ( (int) $data['count'] >= $limit ) {
			return false;
		}
		++$data['count'];
		set_transient( $key, $data, self::WINDOW );
		return true;
	}

	/**
	 * Hash dell'IP (non reversibile, con salt del sito).
	 *
	 * @return string
	 */
	public static function ip_hash(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$ip = (string) apply_filters( 'ecm_client_ip', $ip );
		return substr( hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) ), 0, 32 );
	}

	/* ------------------------------------------------------------------
	 * Email.
	 * ------------------------------------------------------------------ */

	/**
	 * Notifica alla segreteria (HTML, valori escapati, reply-to al mittente).
	 *
	 * @param array $data    Dati.
	 * @param int   $post_id ID richiesta.
	 * @return bool
	 */
	private static function send_contact_mail( array $data, int $post_id ): bool {
		$to = (string) ecm_opt( 'contact_recipient' );
		if ( ! is_email( $to ) ) {
			$to = (string) get_option( 'admin_email' );
		}
		$site    = wp_specialchars_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES );
		$subject = self::header_safe( sprintf( '[%s] Nuova richiesta da %s', $site, $data['nome'] ) );

		$rows = array(
			__( 'Nome', 'ecm' )        => $data['nome'],
			__( 'Email', 'ecm' )       => $data['email'],
			__( 'Telefono', 'ecm' )    => $data['telefono'],
			__( 'Professione', 'ecm' ) => $data['professione'],
			__( 'Corso', 'ecm' )       => $data['corso'],
		);
		$html  = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.5;color:#1c1937">';
		$html .= '<h2 style="font-size:18px;margin:0 0 12px">' . esc_html__( 'Nuova richiesta dal sito', 'ecm' ) . '</h2>';
		$html .= '<table cellpadding="6" cellspacing="0" style="border-collapse:collapse">';
		foreach ( $rows as $label => $value ) {
			if ( '' === (string) $value ) {
				continue;
			}
			$html .= '<tr><th align="left" style="color:#585871;font-weight:normal;padding-right:16px">' . esc_html( $label ) . '</th><td>' . esc_html( (string) $value ) . '</td></tr>';
		}
		$html .= '</table>';
		$html .= '<p style="margin:16px 0 4px;color:#585871">' . esc_html__( 'Messaggio', 'ecm' ) . '</p>';
		$html .= '<div style="white-space:normal;border-left:3px solid #be1522;padding-left:12px">' . nl2br( esc_html( $data['messaggio'] ) ) . '</div>';
		$html .= '<p style="margin-top:20px;font-size:13px;color:#585871">' . sprintf(
			/* translators: %s: link all'area admin. */
			esc_html__( 'Rispondi direttamente a questa email per scrivere a chi ha inviato la richiesta. Archivio: %s', 'ecm' ),
			'<a href="' . esc_url( admin_url( 'post.php?post=' . $post_id . '&action=edit' ) ) . '">' . esc_html__( 'apri nel pannello', 'ecm' ) . '</a>'
		) . '</p></div>';

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			sprintf( 'Reply-To: "%s" <%s>', self::header_safe( str_replace( array( '"', '<', '>', ',', ';', '\\' ), '', $data['nome'] ) ), $data['email'] ),
		);

		self::$mail_error = '';
		$catch            = static function ( $error ) {
			if ( $error instanceof WP_Error ) {
				self::$mail_error = $error->get_error_message();
			}
		};
		add_action( 'wp_mail_failed', $catch );
		$sent = wp_mail( $to, $subject, $html, $headers );
		remove_action( 'wp_mail_failed', $catch );

		if ( ! $sent ) {
			ECM_Logger::error( 'mail', 'Notifica richiesta contatto non inviata: ' . ( '' !== self::$mail_error ? self::$mail_error : 'wp_mail ha restituito false' ), array( 'richiesta' => $post_id ) );
		}
		return (bool) $sent;
	}

	/* ------------------------------------------------------------------
	 * Utilità.
	 * ------------------------------------------------------------------ */

	/**
	 * Risposta JSON standard.
	 *
	 * @param bool   $ok      Esito.
	 * @param string $message Messaggio per l'utente.
	 * @param array  $errors  Errori per campo.
	 * @param int    $status  Codice HTTP.
	 * @return WP_REST_Response
	 */
	private static function respond( bool $ok, string $message, array $errors = array(), int $status = 200 ): WP_REST_Response {
		$response = new WP_REST_Response(
			array(
				'ok'      => $ok,
				'message' => $message,
				'errors'  => (object) $errors,
			),
			$status
		);
		$response->header( 'Cache-Control', 'no-store, max-age=0' );
		return $response;
	}

	/**
	 * Testo di una riga.
	 *
	 * @param mixed $value Valore.
	 * @param int   $max   Lunghezza massima.
	 * @return string
	 */
	private static function text( $value, int $max ): string {
		return is_scalar( $value ) ? mb_substr( trim( sanitize_text_field( (string) $value ) ), 0, $max ) : '';
	}

	/**
	 * Testo multiriga.
	 *
	 * @param mixed $value Valore.
	 * @param int   $max   Lunghezza massima.
	 * @return string
	 */
	private static function textarea( $value, int $max ): string {
		return is_scalar( $value ) ? mb_substr( trim( sanitize_textarea_field( (string) $value ) ), 0, $max ) : '';
	}

	/**
	 * Casella di consenso spuntata.
	 *
	 * @param mixed $value Valore.
	 * @return bool
	 */
	private static function truthy( $value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}
		return is_scalar( $value ) && in_array( strtolower( trim( (string) $value ) ), array( '1', 'true', 'on', 'yes', 'si', 'sì' ), true );
	}

	/**
	 * Rimuove a capo e caratteri di controllo dalle intestazioni.
	 *
	 * @param string $value Valore.
	 * @return string
	 */
	private static function header_safe( string $value ): string {
		return trim( (string) preg_replace( '/[\r\n\t\x00-\x1F\x7F]+/', ' ', $value ) );
	}
}
