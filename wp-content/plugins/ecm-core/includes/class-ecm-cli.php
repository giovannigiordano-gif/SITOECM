<?php
/**
 * Comandi WP-CLI di ECM Core.
 *
 * `wp ecm import` esegue gli step dell'importazione in un solo processo, senza il limite di tempo delle
 * richieste web: utile per la prima installazione su un sito nuovo (143 media in un colpo solo).
 * L'avanzamento viene salvato nell'opzione `ecm_cli_import`, leggibile anche mentre il comando gira.
 *
 * @package ECM_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Gestisce i contenuti di Corsi di Formazione ECM.
 */
final class ECM_CLI {

	private const PROGRESS = 'ecm_cli_import';

	/**
	 * Importa il bundle dei contenuti (wp-content/uploads/ecm-import/).
	 *
	 * ## OPTIONS
	 *
	 * [<step>...]
	 * : Step da eseguire, nell'ordine dato. Senza step: terms, media, courses, docenti, testimonianze, pages,
	 * menus, options, redirects, verify.
	 *
	 * [--force]
	 * : Riscrive anche i contenuti invariati.
	 *
	 * [--preserve-manual]
	 * : Non sovrascrive i campi modificati a mano dopo l'ultima importazione.
	 *
	 * [--dir=<dir>]
	 * : Cartella del bundle, se diversa da quella predefinita.
	 *
	 * [--batch=<n>]
	 * : Media per blocco (predefinito 10).
	 * ---
	 * default: 10
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp ecm import --user=admin
	 *     wp ecm import media verify --user=admin
	 *
	 * @param array $args  Step.
	 * @param array $assoc Opzioni.
	 */
	public function import( array $args, array $assoc ): void {
		$steps   = $args ? array_values( $args ) : ECM_Importer::STEPS;
		$unknown = array_diff( $steps, ECM_Importer::STEPS );
		if ( $unknown ) {
			WP_CLI::error( sprintf( 'Step sconosciuti: %s. Step validi: %s', implode( ', ', $unknown ), implode( ', ', ECM_Importer::STEPS ) ) );
		}

		$common = array();
		if ( ! empty( $assoc['force'] ) ) {
			$common['force'] = true;
		}
		if ( ! empty( $assoc['preserve-manual'] ) ) {
			$common['preserve_manual'] = true;
		}
		if ( ! empty( $assoc['dir'] ) ) {
			$common['dir'] = (string) $assoc['dir'];
		}
		$batch = max( 1, (int) ( $assoc['batch'] ?? 10 ) );

		$progress = array(
			'state'   => 'running',
			'steps'   => $steps,
			'current' => '',
			'started' => gmdate( 'c' ),
			'updated' => gmdate( 'c' ),
			'results' => array(),
		);
		$this->save( $progress );
		$failed = false;

		foreach ( $steps as $step ) {
			$progress['current'] = $step;
			$this->save( $progress );

			if ( 'media' === $step ) {
				$total  = array( 'created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => array() );
				$offset = 0;
				do {
					$s = ECM_Importer::run( 'media', $common + array( 'offset' => $offset, 'limit' => $batch, 'time_budget' => 600 ) );
					foreach ( array( 'created', 'updated', 'skipped' ) as $k ) {
						$total[ $k ] += (int) ( $s[ $k ] ?? 0 );
					}
					$total['errors'] = array_merge( $total['errors'], (array) ( $s['errors'] ?? array() ) );
					$next            = (int) ( $s['next_offset'] ?? $offset );
					$done            = ! empty( $s['done'] ) || $next <= $offset;
					WP_CLI::log( sprintf( 'media %d/%d', $next, (int) ( $s['total'] ?? 0 ) ) );
					$progress['results']['media'] = array( 'done' => $next, 'total' => (int) ( $s['total'] ?? 0 ) ) + $this->compact( $total );
					$this->save( $progress );
					$offset = $next;
				} while ( ! $done );
				$summary = $total;
			} else {
				$summary = ECM_Importer::run( $step, $common );
				$progress['results'][ $step ] = $this->compact( $summary );
			}

			$errors = (array) ( $summary['errors'] ?? array() );
			WP_CLI::log( sprintf( '%s: %d creati, %d aggiornati, %d invariati, %d errori', $step, (int) $summary['created'], (int) $summary['updated'], (int) $summary['skipped'], count( $errors ) ) );
			foreach ( array_slice( $errors, 0, 20 ) as $error ) {
				WP_CLI::warning( $error );
			}
			if ( $errors ) {
				$failed = true;
			}
			$this->save( $progress );
		}

		$progress['state']   = $failed ? 'done_with_errors' : 'done';
		$progress['current'] = '';
		$this->save( $progress );

		if ( $failed ) {
			WP_CLI::warning( 'Importazione terminata con errori.' );
			return;
		}
		WP_CLI::success( 'Importazione completata.' );
	}

	/**
	 * Mostra l'avanzamento dell'ultima importazione da riga di comando.
	 *
	 * ## EXAMPLES
	 *
	 *     wp ecm status
	 *
	 * @param array $args  Non usati.
	 * @param array $assoc Non usati.
	 */
	public function status( array $args, array $assoc ): void {
		WP_CLI::log( (string) wp_json_encode( get_option( self::PROGRESS, array() ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
	}

	/**
	 * Riepilogo breve di uno step.
	 *
	 * @param array $summary Riepilogo completo.
	 * @return array
	 */
	private function compact( array $summary ): array {
		$errors = (array) ( $summary['errors'] ?? array() );
		return array(
			'created' => (int) ( $summary['created'] ?? 0 ),
			'updated' => (int) ( $summary['updated'] ?? 0 ),
			'skipped' => (int) ( $summary['skipped'] ?? 0 ),
			'errors'  => count( $errors ),
			'first'   => array_slice( $errors, 0, 10 ),
		);
	}

	/**
	 * Salva l'avanzamento.
	 *
	 * @param array $progress Avanzamento.
	 */
	private function save( array $progress ): void {
		$progress['updated'] = gmdate( 'c' );
		update_option( self::PROGRESS, $progress, false );
	}
}

WP_CLI::add_command( 'ecm', 'ECM_CLI' );
