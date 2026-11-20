<?php
/**
 * Pagina "Impostazioni ECM › Importazione": stato del bundle, step via AJAX, verifica.
 *
 * @package ECM_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Pagina importazione.
 */
final class ECM_Import_Page {

	public const SLUG = 'ecm-import';

	/**
	 * Aggancia gli hook.
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 20 );
		add_action( 'wp_ajax_ecm_import_step', array( __CLASS__, 'ajax_step' ) );
	}

	/**
	 * Sottovoce di menu.
	 */
	public static function menu(): void {
		add_submenu_page(
			ECM_Admin::MENU_SLUG,
			__( 'Importazione contenuti', 'ecm' ),
			__( 'Importazione', 'ecm' ),
			'manage_options',
			self::SLUG,
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Esegue uno step (AJAX).
	 */
	public static function ajax_step(): void {
		check_ajax_referer( 'ecm_import' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permessi insufficienti.', 'ecm' ) ), 403 );
		}
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- verificato sopra.
		$step = isset( $_POST['step'] ) ? sanitize_key( wp_unslash( $_POST['step'] ) ) : '';
		$args = array(
			'force'           => ! empty( $_POST['force'] ),
			'preserve_manual' => ! empty( $_POST['preserve_manual'] ),
		);
		if ( 'media' === $step ) {
			$args['offset'] = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
			$args['limit']  = isset( $_POST['limit'] ) ? max( 1, min( 20, absint( $_POST['limit'] ) ) ) : 4;
		}
		// phpcs:enable

		wp_send_json_success( ECM_Importer::run( $step, $args ) );
	}

	/**
	 * Stampa la pagina.
	 */
	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Non hai i permessi per accedere a questa pagina.', 'ecm' ) );
		}
		$status = ECM_Importer::status();
		$labels = ECM_Importer::step_labels();
		?>
		<div class="wrap ecm-import" id="ecm-import">
			<h1><?php esc_html_e( 'Importazione contenuti', 'ecm' ); ?></h1>
			<p class="ecm-settings__intro"><?php esc_html_e( 'Importa corsi, docenti, testimonianze, pagine e media dal pacchetto preparato dal vecchio sito. Ogni passaggio si può ripetere: i contenuti già importati vengono aggiornati, mai duplicati.', 'ecm' ); ?></p>

			<div class="ecm-card ecm-import__status">
				<h2><?php esc_html_e( 'Pacchetto di importazione', 'ecm' ); ?></h2>
				<p><code><?php echo esc_html( $status['dir'] ); ?>data.json</code></p>
				<?php if ( ! $status['exists'] || '' !== $status['error'] ) : ?>
					<div class="notice notice-warning inline"><p>
						<?php echo esc_html( '' !== $status['error'] ? $status['error'] : __( 'Pacchetto non trovato.', 'ecm' ) ); ?>
						<?php esc_html_e( 'Carica la cartella ecm-import (data.json + assets/) in wp-content/uploads/ e ricarica questa pagina.', 'ecm' ); ?>
					</p></div>
				<?php else : ?>
					<p>
						<?php
						/* translators: 1: versione, 2: data. */
						echo esc_html( sprintf( __( 'Versione %1$d, generato il %2$s.', 'ecm' ), $status['version'], '' !== $status['generated'] ? wp_date( 'd/m/Y H:i', (int) strtotime( $status['generated'] ) ) : '—' ) );
						?>
					</p>
					<table class="widefat striped ecm-import__counts">
						<thead><tr><th><?php esc_html_e( 'Contenuto', 'ecm' ); ?></th><th><?php esc_html_e( 'Nel pacchetto', 'ecm' ); ?></th></tr></thead>
						<tbody>
						<?php foreach ( $status['counts'] as $key => $count ) : ?>
							<tr>
								<td><?php echo esc_html( $labels[ $key ] ?? $key ); ?></td>
								<td>
									<?php echo (int) $count; ?>
									<?php if ( 'media' === $key ) : ?>
										<span class="ecm-muted">
											<?php
											/* translators: %d: numero di media già importati. */
											echo esc_html( sprintf( __( '(%d già nella Libreria media)', 'ecm' ), (int) ( $status['imported']['media'] ?? 0 ) ) );
											?>
										</span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>

			<?php if ( $status['exists'] && '' === $status['error'] ) : ?>
				<div class="ecm-card">
					<h2><?php esc_html_e( 'Esegui', 'ecm' ); ?></h2>
					<p class="ecm-import__options">
						<label><input type="checkbox" data-ecm-opt="preserve_manual"> <?php esc_html_e( 'Conserva le modifiche fatte a mano dopo l\'ultima importazione', 'ecm' ); ?></label><br>
						<label><input type="checkbox" data-ecm-opt="force"> <?php esc_html_e( 'Riscrivi anche i contenuti invariati', 'ecm' ); ?></label>
					</p>
					<p>
						<button type="button" class="button button-primary" data-ecm-run-all><?php esc_html_e( 'Esegui tutti i passaggi', 'ecm' ); ?></button>
						<button type="button" class="button" data-ecm-step="verify"><?php esc_html_e( 'Verifica', 'ecm' ); ?></button>
					</p>
					<ol class="ecm-import__steps">
						<?php foreach ( $labels as $step => $label ) : ?>
							<?php
							if ( 'verify' === $step ) {
								continue;
							}
							?>
							<li data-step-row="<?php echo esc_attr( $step ); ?>">
								<button type="button" class="button" data-ecm-step="<?php echo esc_attr( $step ); ?>"><?php echo esc_html( $label ); ?></button>
								<span class="ecm-import__state" aria-live="polite"></span>
								<?php if ( 'media' === $step ) : ?>
									<progress class="ecm-import__progress" max="100" value="0" hidden></progress>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ol>
				</div>
			<?php endif; ?>

			<div class="ecm-card">
				<h2><?php esc_html_e( 'Risultati', 'ecm' ); ?></h2>
				<div class="ecm-import__log" aria-live="polite"><p class="ecm-muted"><?php esc_html_e( 'I risultati dei passaggi compariranno qui.', 'ecm' ); ?></p></div>
			</div>

			<div class="ecm-card">
				<h2><?php esc_html_e( 'Da riga di comando (sviluppatori)', 'ecm' ); ?></h2>
				<p><code>ECM_Importer::run( 'terms' );</code> · <code>ECM_Importer::run( 'media', array( 'offset' => 0, 'limit' => 5 ) );</code> · <code>ECM_Importer::run( 'verify' );</code></p>
			</div>
		</div>
		<?php
	}
}
