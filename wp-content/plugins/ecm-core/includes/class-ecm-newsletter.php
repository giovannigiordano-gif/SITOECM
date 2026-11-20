<?php
/**
 * Integrazione con i servizi newsletter (Brevo API v3, Mailchimp Marketing API v3).
 *
 * Inattiva finché non viene scelto un servizio e inserite chiave e lista.
 * Gli errori vengono registrati in ECM › Impostazioni › Registro e mai mostrati ai visitatori.
 *
 * @package ECM_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Newsletter.
 */
final class ECM_Newsletter {

	/**
	 * Servizio configurato o 'none'.
	 *
	 * @return string none|brevo|mailchimp
	 */
	public static function provider(): string {
		$provider = (string) ecm_opt( 'newsletter_provider', 'none' );
		if ( 'brevo' === $provider && ecm_opt( 'brevo_api_key' ) && ecm_opt( 'brevo_list_id' ) ) {
			return 'brevo';
		}
		if ( 'mailchimp' === $provider && ecm_opt( 'mailchimp_api_key' ) && ecm_opt( 'mailchimp_list_id' ) && self::mailchimp_dc() ) {
			return 'mailchimp';
		}
		return 'none';
	}

	/**
	 * Invia un contatto al servizio configurato.
	 *
	 * @param string $email Email.
	 * @return array{status:string,error:string} status: ok|error|skipped.
	 */
	public static function subscribe( string $email ): array {
		$provider = self::provider();
		if ( 'none' === $provider ) {
			return array(
				'status' => 'skipped',
				'error'  => '',
			);
		}
		$result = 'brevo' === $provider ? self::brevo( $email ) : self::mailchimp( $email );
		if ( 'ok' !== $result['status'] ) {
			ECM_Logger::error(
				'newsletter',
				sprintf( 'Invio a %s non riuscito: %s', $provider, $result['error'] ),
				array( 'email_hash' => substr( md5( strtolower( $email ) ), 0, 10 ) )
			);
		}
		return $result;
	}

	/**
	 * Brevo: POST /v3/contacts con updateEnabled.
	 *
	 * @param string $email Email.
	 * @return array{status:string,error:string}
	 */
	private static function brevo( string $email ): array {
		$response = wp_remote_post(
			'https://api.brevo.com/v3/contacts',
			array(
				'timeout' => 8,
				'headers' => array(
					'api-key'      => (string) ecm_opt( 'brevo_api_key' ),
					'accept'       => 'application/json',
					'content-type' => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'email'         => $email,
						'listIds'       => array( (int) ecm_opt( 'brevo_list_id' ) ),
						'updateEnabled' => true,
					)
				),
			)
		);
		return self::check( $response, array( 200, 201, 204 ) );
	}

	/**
	 * Mailchimp: PUT /3.0/lists/{list}/members/{hash} (crea o aggiorna).
	 *
	 * @param string $email Email.
	 * @return array{status:string,error:string}
	 */
	private static function mailchimp( string $email ): array {
		$dc   = self::mailchimp_dc();
		$list = rawurlencode( (string) ecm_opt( 'mailchimp_list_id' ) );
		$hash = md5( strtolower( $email ) );
		$url  = sprintf( 'https://%s.api.mailchimp.com/3.0/lists/%s/members/%s', $dc, $list, $hash );

		$response = wp_remote_post(
			$url,
			array(
				'method'  => 'PUT',
				'timeout' => 8,
				'headers' => array(
					// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
					'authorization' => 'Basic ' . base64_encode( 'ecm:' . (string) ecm_opt( 'mailchimp_api_key' ) ),
					'content-type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'email_address' => $email,
						'status_if_new' => (string) apply_filters( 'ecm_mailchimp_status', 'subscribed' ),
					)
				),
			)
		);
		return self::check( $response, array( 200 ) );
	}

	/**
	 * Data center Mailchimp dal suffisso della chiave (es. -us21).
	 *
	 * @return string
	 */
	private static function mailchimp_dc(): string {
		$key = (string) ecm_opt( 'mailchimp_api_key' );
		if ( preg_match( '/-([a-z]{2,4}\d{1,3})$/i', $key, $m ) ) {
			return strtolower( $m[1] );
		}
		return '';
	}

	/**
	 * Interpreta la risposta HTTP.
	 *
	 * @param array|WP_Error $response Risposta.
	 * @param int[]          $ok_codes Codici di successo.
	 * @return array{status:string,error:string}
	 */
	private static function check( $response, array $ok_codes ): array {
		if ( is_wp_error( $response ) ) {
			return array(
				'status' => 'error',
				'error'  => $response->get_error_message(),
			);
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( in_array( $code, $ok_codes, true ) ) {
			return array(
				'status' => 'ok',
				'error'  => '',
			);
		}
		$body    = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		$message = is_array( $body ) ? (string) ( $body['message'] ?? ( $body['detail'] ?? ( $body['title'] ?? '' ) ) ) : '';
		return array(
			'status' => 'error',
			'error'  => trim( sprintf( 'HTTP %d %s', $code, mb_substr( $message, 0, 200 ) ) ),
		);
	}
}
