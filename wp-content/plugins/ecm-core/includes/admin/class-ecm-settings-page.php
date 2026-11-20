<?php
/**
 * Pagina "Impostazioni ECM" a schede (Settings API) + Redirect + Registro.
 *
 * @package ECM_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Pagina impostazioni.
 */
final class ECM_Settings_Page {

	public const GROUP          = 'ecm_settings';
	public const REDIRECT_GROUP = 'ecm_redirects_group';

	/**
	 * Aggancia gli hook.
	 */
	public static function init(): void {
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_post_ecm_clear_log', array( __CLASS__, 'clear_log' ) );
	}

	/**
	 * Registra le impostazioni (gruppi separati: ogni form aggiorna solo la propria opzione).
	 */
	public static function register(): void {
		register_setting(
			self::GROUP,
			ECM_Options::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'ECM_Options', 'sanitize' ),
				'default'           => ECM_Options::defaults(),
				'show_in_rest'      => false,
			)
		);
		register_setting(
			self::REDIRECT_GROUP,
			ECM_Redirects::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'ECM_Redirects', 'sanitize_option' ),
				'default'           => array(),
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * Schede disponibili.
	 *
	 * @return array<string,string>
	 */
	private static function tabs(): array {
		return array_merge(
			ECM_Options::tabs(),
			array(
				'redirect' => __( 'Redirect', 'ecm' ),
				'registro' => __( 'Registro', 'ecm' ),
			)
		);
	}

	/**
	 * Scheda corrente.
	 *
	 * @return string
	 */
	private static function current_tab(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'contatti';
		return array_key_exists( $tab, self::tabs() ) ? $tab : 'contatti';
	}

	/**
	 * Stampa la pagina.
	 */
	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Non hai i permessi per accedere a questa pagina.', 'ecm' ) );
		}
		$current = self::current_tab();
		$base    = admin_url( 'admin.php?page=' . ECM_Admin::MENU_SLUG );
		?>
		<div class="wrap ecm-settings">
			<h1><?php esc_html_e( 'Impostazioni ECM', 'ecm' ); ?></h1>
			<?php settings_errors(); ?>
			<nav class="nav-tab-wrapper wp-clearfix" aria-label="<?php esc_attr_e( 'Sezioni delle impostazioni', 'ecm' ); ?>">
				<?php foreach ( self::tabs() as $slug => $label ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'tab', $slug, $base ) ); ?>" class="nav-tab<?php echo $slug === $current ? ' nav-tab-active' : ''; ?>" <?php echo $slug === $current ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>
			<div class="ecm-settings__body">
				<?php
				if ( 'redirect' === $current ) {
					self::render_redirects();
				} elseif ( 'registro' === $current ) {
					self::render_log();
				} else {
					self::render_options_tab( $current );
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Scheda di opzioni.
	 *
	 * @param string $tab Scheda.
	 */
	private static function render_options_tab( string $tab ): void {
		$intro = array(
			'contatti'   => __( 'Recapiti mostrati nel sito, link alla piattaforma dei corsi e messaggi WhatsApp precompilati.', 'ecm' ),
			'faq'        => __( 'Domande che compaiono in tutte le schede corso, dopo "Per chi è adatto". Le domande specifiche si aggiungono dentro ogni corso.', 'ecm' ),
			'social'     => __( 'Profili social, dati societari e pagine legali (usati nel footer, nel banner cookie e nei dati per i motori di ricerca).', 'ecm' ),
			'home'       => __( 'Testi principali della home page.', 'ecm' ),
			'newsletter' => __( 'Testi del box newsletter e dei consensi, servizio di invio e codice statistiche caricato solo dopo il consenso ai cookie.', 'ecm' ),
		);
		$values = ECM_Options::all();
		$stored = get_option( ECM_Options::OPTION, array() );
		$stored = is_array( $stored ) ? $stored : array();
		?>
		<?php if ( isset( $intro[ $tab ] ) ) : ?>
			<p class="ecm-settings__intro"><?php echo esc_html( $intro[ $tab ] ); ?></p>
		<?php endif; ?>
		<?php if ( 'newsletter' === $tab ) : ?>
			<?php self::provider_status(); ?>
		<?php endif; ?>
		<form method="post" action="options.php" class="ecm-settings__form">
			<?php settings_fields( self::GROUP ); ?>
			<table class="form-table" role="presentation">
				<tbody>
				<?php foreach ( ECM_Options::fields() as $key => $field ) : ?>
					<?php
					if ( $field['tab'] !== $tab ) {
						continue;
					}
					$id    = 'ecm_opt_' . $key;
					$value = 'secret' === $field['type'] ? (string) ( $stored[ $key ] ?? '' ) : ( $stored[ $key ] ?? $values[ $key ] ?? '' );
					?>
					<tr class="ecm-field ecm-field--<?php echo esc_attr( $field['type'] ); ?>">
						<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
						<td>
							<?php self::render_field( $key, $field, $value, $id ); ?>
							<?php if ( ! empty( $field['desc'] ) ) : ?>
								<p class="description"><?php echo esc_html( $field['desc'] ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php submit_button( __( 'Salva le modifiche', 'ecm' ) ); ?>
		</form>
		<?php
	}

	/**
	 * Campo singolo.
	 *
	 * @param string $key   Chiave.
	 * @param array  $field Definizione.
	 * @param mixed  $value Valore.
	 * @param string $id    ID HTML.
	 */
	private static function render_field( string $key, array $field, $value, string $id ): void {
		$name = ECM_Options::OPTION . '[' . $key . ']';
		switch ( $field['type'] ) {
			case 'textarea':
				printf( '<textarea id="%1$s" name="%2$s" rows="4" class="large-text">%3$s</textarea>', esc_attr( $id ), esc_attr( $name ), esc_textarea( (string) $value ) );
				break;

			case 'lines':
				printf( '<textarea id="%1$s" name="%2$s" rows="5" class="large-text code" placeholder="10k+|Corsisti">%3$s</textarea>', esc_attr( $id ), esc_attr( $name ), esc_textarea( (string) $value ) );
				break;

			case 'code':
				printf( '<textarea id="%1$s" name="%2$s" rows="8" class="large-text code" spellcheck="false" %4$s>%3$s</textarea>', esc_attr( $id ), esc_attr( $name ), esc_textarea( (string) $value ), current_user_can( 'unfiltered_html' ) ? '' : 'readonly' );
				break;

			case 'html':
				wp_editor(
					(string) $value,
					$id,
					array(
						'textarea_name' => $name,
						'textarea_rows' => 4,
						'media_buttons' => false,
						'teeny'         => true,
						'quicktags'     => array( 'buttons' => 'strong,em,link' ),
					)
				);
				break;

			case 'select':
				printf( '<select id="%1$s" name="%2$s">', esc_attr( $id ), esc_attr( $name ) );
				foreach ( (array) ( $field['choices'] ?? array() ) as $choice => $label ) {
					printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( (string) $choice ), selected( (string) $value, (string) $choice, false ), esc_html( (string) $label ) );
				}
				echo '</select>';
				break;

			case 'secret':
				$saved = '' !== (string) $value;
				printf(
					'<input type="password" id="%1$s" name="%2$s" value="" class="regular-text" autocomplete="new-password" placeholder="%3$s">',
					esc_attr( $id ),
					esc_attr( $name ),
					$saved
						/* translators: %s: ultime cifre della chiave. */
						? esc_attr( sprintf( __( 'Chiave salvata (…%s). Lascia vuoto per non modificarla.', 'ecm' ), substr( (string) $value, -4 ) ) )
						: esc_attr__( 'Incolla qui la chiave API', 'ecm' )
				);
				if ( $saved ) {
					printf( '<label class="ecm-clear-secret"><input type="checkbox" name="%1$s" value="1"> %2$s</label>', esc_attr( ECM_Options::OPTION . '[__clear][' . $key . ']' ), esc_html__( 'Elimina la chiave salvata', 'ecm' ) );
				}
				break;

			case 'media':
				ECM_Admin::media_field(
					$name,
					(int) $value,
					array(
						'library'      => 'image',
						'frame_title'  => $field['label'],
						'frame_button' => __( 'Usa questa immagine', 'ecm' ),
						'select'       => __( 'Scegli immagine', 'ecm' ),
						'class'        => 'ecm-media--small',
					)
				);
				break;

			case 'email':
			case 'url':
				printf( '<input type="%4$s" id="%1$s" name="%2$s" value="%3$s" class="regular-text">', esc_attr( $id ), esc_attr( $name ), esc_attr( (string) $value ), esc_attr( $field['type'] ) );
				break;

			case 'digits':
				printf( '<input type="text" inputmode="numeric" pattern="[0-9 +]*" id="%1$s" name="%2$s" value="%3$s" class="regular-text code">', esc_attr( $id ), esc_attr( $name ), esc_attr( (string) $value ) );
				break;

			case 'template':
			case 'text':
			default:
				printf( '<input type="text" id="%1$s" name="%2$s" value="%3$s" class="large-text">', esc_attr( $id ), esc_attr( $name ), esc_attr( (string) $value ) );
				if ( 'whatsapp_template' === $key ) {
					$preview = ecm_whatsapp_url( str_replace( '%s', __( 'Titolo del corso', 'ecm' ), (string) $value ) );
					if ( '' !== $preview ) {
						printf( '<p class="description"><a href="%1$s" target="_blank" rel="noopener">%2$s</a></p>', esc_url( $preview ), esc_html__( 'Prova il link WhatsApp', 'ecm' ) );
					}
				}
				break;
		}
	}

	/**
	 * Stato dell'integrazione newsletter.
	 */
	private static function provider_status(): void {
		$chosen = (string) ecm_opt( 'newsletter_provider', 'none' );
		$active = ECM_Newsletter::provider();
		if ( 'none' === $chosen ) {
			$text  = __( 'Nessun servizio esterno: gli iscritti vengono salvati solo nel sito (ECM › Iscritti newsletter, esportabili in CSV).', 'ecm' );
			$class = 'notice-info';
		} elseif ( 'none' === $active ) {
			$text  = __( 'Servizio scelto ma non configurato: inserisci chiave API e ID lista. Fino ad allora gli iscritti vengono salvati solo nel sito.', 'ecm' );
			$class = 'notice-warning';
		} else {
			/* translators: %s: nome del servizio. */
			$text  = sprintf( __( 'Integrazione attiva con %s. Eventuali errori di invio sono nella scheda Registro.', 'ecm' ), 'brevo' === $active ? 'Brevo' : 'Mailchimp' );
			$class = 'notice-success';
		}
		printf( '<div class="notice inline %1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $text ) );
	}

	/**
	 * Scheda Redirect.
	 */
	private static function render_redirects(): void {
		$table = get_option( ECM_Redirects::OPTION, array() );
		$table = is_array( $table ) ? $table : array();
		$map   = ECM_Redirects::old_paths_map();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- strumento di sola lettura.
		$test = isset( $_GET['ecm_test'] ) ? trim( (string) wp_unslash( $_GET['ecm_test'] ) ) : '';
		?>
		<p class="ecm-settings__intro"><?php esc_html_e( 'Indirizzi del vecchio sito da reindirizzare in modo permanente (301). I vecchi indirizzi dei corsi si gestiscono dentro ogni corso (box "SEO e vecchi indirizzi").', 'ecm' ); ?></p>
		<div class="ecm-cards">
			<div class="ecm-card">
				<h2><?php esc_html_e( 'Regole automatiche', 'ecm' ); ?></h2>
				<ul class="ecm-list">
					<li><code>/collections</code>, <code>/collections?page=N</code>, <code>/collections/*</code> → <code><?php echo esc_html( wp_make_link_relative( (string) get_post_type_archive_link( 'corso' ) ) ); ?></code></li>
					<?php foreach ( ECM_Redirects::LEGACY_PAGES as $slug ) : ?>
						<li><code>/pages/<?php echo esc_html( $slug ); ?></code> → <code>/<?php echo esc_html( $slug ); ?>/</code></li>
					<?php endforeach; ?>
					<li>
						<?php
						/* translators: %d: numero di indirizzi. */
						echo esc_html( sprintf( _n( '%d vecchio indirizzo di corso', '%d vecchi indirizzi di corsi', count( $map ), 'ecm' ), count( $map ) ) );
						?>
					</li>
				</ul>
			</div>
			<div class="ecm-card">
				<h2><?php esc_html_e( 'Prova un indirizzo', 'ecm' ); ?></h2>
				<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
					<input type="hidden" name="page" value="<?php echo esc_attr( ECM_Admin::MENU_SLUG ); ?>">
					<input type="hidden" name="tab" value="redirect">
					<input type="text" name="ecm_test" value="<?php echo esc_attr( $test ); ?>" class="regular-text code" placeholder="/courses/vecchio-indirizzo">
					<?php submit_button( __( 'Prova', 'ecm' ), 'secondary', '', false ); ?>
				</form>
				<?php if ( '' !== $test ) : ?>
					<?php $target = ECM_Redirects::resolve( $test ); ?>
					<p class="ecm-test-result">
						<?php if ( $target ) : ?>
							<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
							<?php esc_html_e( 'Reindirizza a:', 'ecm' ); ?> <a href="<?php echo esc_url( $target ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $target ); ?></a>
						<?php else : ?>
							<span class="dashicons dashicons-warning" aria-hidden="true"></span>
							<?php esc_html_e( 'Nessuna regola: se l\'indirizzo non esiste nel nuovo sito mostrerà la pagina 404.', 'ecm' ); ?>
						<?php endif; ?>
					</p>
				<?php endif; ?>
			</div>
		</div>
		<form method="post" action="options.php">
			<?php settings_fields( self::REDIRECT_GROUP ); ?>
			<h2><label for="ecm_redirects"><?php esc_html_e( 'Redirect personalizzati', 'ecm' ); ?></label></h2>
			<p class="description"><?php esc_html_e( 'Uno per riga nel formato: /vecchio-indirizzo => /nuovo-indirizzo/ . Puoi usare un indirizzo completo come destinazione e un asterisco finale come jolly (es. /blog/* => /). Le righe che iniziano con # sono commenti.', 'ecm' ); ?></p>
			<textarea id="ecm_redirects" name="<?php echo esc_attr( ECM_Redirects::OPTION ); ?>" rows="14" class="large-text code" spellcheck="false" placeholder="/pages/faq => /contatti/"><?php echo esc_textarea( ECM_Redirects::to_lines( $table ) ); ?></textarea>
			<?php submit_button( __( 'Salva i redirect', 'ecm' ) ); ?>
		</form>
		<?php
	}

	/**
	 * Scheda Registro.
	 */
	private static function render_log(): void {
		$entries = ECM_Logger::entries( 100 );
		$levels  = array(
			'info'    => __( 'Info', 'ecm' ),
			'warning' => __( 'Avviso', 'ecm' ),
			'error'   => __( 'Errore', 'ecm' ),
		);
		?>
		<p class="ecm-settings__intro"><?php esc_html_e( 'Ultimi eventi tecnici: errori di invio email e newsletter, esiti delle importazioni. I visitatori non vedono mai questi messaggi.', 'ecm' ); ?></p>
		<?php if ( ! $entries ) : ?>
			<p><?php esc_html_e( 'Il registro è vuoto.', 'ecm' ); ?></p>
			<?php return; ?>
		<?php endif; ?>
		<table class="widefat striped ecm-log">
			<thead><tr><th><?php esc_html_e( 'Data', 'ecm' ); ?></th><th><?php esc_html_e( 'Livello', 'ecm' ); ?></th><th><?php esc_html_e( 'Modulo', 'ecm' ); ?></th><th><?php esc_html_e( 'Messaggio', 'ecm' ); ?></th></tr></thead>
			<tbody>
			<?php foreach ( $entries as $entry ) : ?>
				<tr class="ecm-log--<?php echo esc_attr( $entry['level'] ); ?>">
					<td><?php echo esc_html( wp_date( 'd/m/Y H:i:s', (int) $entry['time'] ) ); ?></td>
					<td><span class="ecm-badge ecm-badge--<?php echo esc_attr( $entry['level'] ); ?>"><?php echo esc_html( $levels[ $entry['level'] ] ?? $entry['level'] ); ?></span></td>
					<td><code><?php echo esc_html( $entry['context'] ); ?></code></td>
					<td>
						<?php echo esc_html( $entry['message'] ); ?>
						<?php if ( ! empty( $entry['data'] ) ) : ?>
							<br><small class="ecm-muted"><?php echo esc_html( (string) wp_json_encode( $entry['data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ); ?></small>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ecm-log__clear">
			<input type="hidden" name="action" value="ecm_clear_log">
			<?php wp_nonce_field( 'ecm_clear_log' ); ?>
			<?php submit_button( __( 'Svuota il registro', 'ecm' ), 'delete', 'submit', false ); ?>
		</form>
		<?php
	}

	/**
	 * Svuota il registro.
	 */
	public static function clear_log(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Non hai i permessi per questa operazione.', 'ecm' ), 403 );
		}
		check_admin_referer( 'ecm_clear_log' );
		ECM_Logger::clear();
		wp_safe_redirect( add_query_arg( 'tab', 'registro', admin_url( 'admin.php?page=' . ECM_Admin::MENU_SLUG ) ) );
		exit;
	}
}
