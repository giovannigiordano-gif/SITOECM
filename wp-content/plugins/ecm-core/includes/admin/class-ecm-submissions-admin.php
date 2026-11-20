<?php
/**
 * Admin delle richieste di contatto e degli iscritti newsletter (sola lettura + esportazione CSV).
 *
 * @package ECM_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Richieste e iscritti.
 */
final class ECM_Submissions_Admin {

	private const TYPES = array( 'ecm_richiesta', 'ecm_iscritto' );

	/**
	 * Aggancia gli hook.
	 */
	public static function init(): void {
		add_filter( 'manage_ecm_richiesta_posts_columns', array( __CLASS__, 'richiesta_columns' ) );
		add_action( 'manage_ecm_richiesta_posts_custom_column', array( __CLASS__, 'richiesta_cell' ), 10, 2 );
		add_filter( 'manage_ecm_iscritto_posts_columns', array( __CLASS__, 'iscritto_columns' ) );
		add_action( 'manage_ecm_iscritto_posts_custom_column', array( __CLASS__, 'iscritto_cell' ), 10, 2 );

		add_filter( 'post_row_actions', array( __CLASS__, 'row_actions' ), 10, 2 );
		add_filter( 'bulk_actions-edit-ecm_richiesta', array( __CLASS__, 'bulk_actions' ) );
		add_filter( 'bulk_actions-edit-ecm_iscritto', array( __CLASS__, 'bulk_actions' ) );
		add_action( 'manage_posts_extra_tablenav', array( __CLASS__, 'export_button' ) );
		add_action( 'admin_post_ecm_export_csv', array( __CLASS__, 'export_csv' ) );

		add_action( 'add_meta_boxes', array( __CLASS__, 'meta_boxes' ), 10, 2 );
	}

	/* ------------------------------------------------------------------
	 * Elenchi.
	 * ------------------------------------------------------------------ */

	/**
	 * Colonne richieste.
	 *
	 * @param array $columns Colonne.
	 * @return array
	 */
	public static function richiesta_columns( $columns ): array {
		return array(
			'cb'              => $columns['cb'] ?? '<input type="checkbox" />',
			'title'           => __( 'Nome', 'ecm' ),
			'ecm_email'       => __( 'Email', 'ecm' ),
			'ecm_telefono'    => __( 'Telefono', 'ecm' ),
			'ecm_professione' => __( 'Professione', 'ecm' ),
			'ecm_corso'       => __( 'Corso', 'ecm' ),
			'ecm_messaggio'   => __( 'Messaggio', 'ecm' ),
			'ecm_mail'        => __( 'Notifica', 'ecm' ),
			'date'            => __( 'Ricevuta', 'ecm' ),
		);
	}

	/**
	 * Celle richieste.
	 *
	 * @param string $column  Colonna.
	 * @param int    $post_id ID.
	 */
	public static function richiesta_cell( $column, $post_id ): void {
		switch ( $column ) {
			case 'ecm_email':
				$email = (string) get_post_meta( $post_id, '_ecm_email', true );
				if ( '' !== $email ) {
					printf( '<a href="mailto:%1$s">%2$s</a>', esc_attr( $email ), esc_html( $email ) );
				}
				break;
			case 'ecm_telefono':
				$tel = (string) get_post_meta( $post_id, '_ecm_telefono', true );
				if ( '' !== $tel ) {
					printf( '<a href="tel:%1$s">%2$s</a>', esc_attr( preg_replace( '/[^0-9+]/', '', $tel ) ), esc_html( $tel ) );
				}
				break;
			case 'ecm_professione':
				echo esc_html( (string) get_post_meta( $post_id, '_ecm_professione', true ) );
				break;
			case 'ecm_corso':
				echo esc_html( (string) get_post_meta( $post_id, '_ecm_corso', true ) );
				break;
			case 'ecm_messaggio':
				echo esc_html( wp_trim_words( (string) get_post_meta( $post_id, '_ecm_messaggio', true ), 16, '…' ) );
				break;
			case 'ecm_mail':
				$sent = get_post_meta( $post_id, '_ecm_mail_sent', true );
				if ( '1' === $sent ) {
					echo '<span class="dashicons dashicons-yes" title="' . esc_attr__( 'Email di notifica inviata', 'ecm' ) . '"></span>';
				} elseif ( '0' === $sent ) {
					echo '<span class="dashicons dashicons-warning ecm-warn" title="' . esc_attr__( 'Email di notifica NON inviata: controlla la configurazione email del sito', 'ecm' ) . '"></span>';
				}
				break;
		}
	}

	/**
	 * Colonne iscritti.
	 *
	 * @param array $columns Colonne.
	 * @return array
	 */
	public static function iscritto_columns( $columns ): array {
		return array(
			'cb'           => $columns['cb'] ?? '<input type="checkbox" />',
			'title'        => __( 'Email', 'ecm' ),
			'ecm_consenso' => __( 'Consenso', 'ecm' ),
			'ecm_servizio' => __( 'Servizio newsletter', 'ecm' ),
			'date'         => __( 'Iscrizione', 'ecm' ),
		);
	}

	/**
	 * Celle iscritti.
	 *
	 * @param string $column  Colonna.
	 * @param int    $post_id ID.
	 */
	public static function iscritto_cell( $column, $post_id ): void {
		switch ( $column ) {
			case 'ecm_consenso':
				$date = (string) get_post_meta( $post_id, '_ecm_consenso_data', true );
				echo '' !== $date ? esc_html( mysql2date( 'd/m/Y H:i', $date ) ) : '—';
				break;
			case 'ecm_servizio':
				$provider = (string) get_post_meta( $post_id, '_ecm_provider', true );
				$status   = (string) get_post_meta( $post_id, '_ecm_provider_status', true );
				$labels   = array(
					'ok'      => __( 'Inviato', 'ecm' ),
					'error'   => __( 'Errore di invio', 'ecm' ),
					'skipped' => __( 'Solo nel sito', 'ecm' ),
				);
				$name     = 'brevo' === $provider ? 'Brevo' : ( 'mailchimp' === $provider ? 'Mailchimp' : '' );
				printf(
					'<span class="ecm-badge ecm-badge--%1$s">%2$s</span>%3$s',
					esc_attr( '' !== $status ? $status : 'skipped' ),
					esc_html( $labels[ $status ] ?? $labels['skipped'] ),
					'' !== $name ? ' <small>' . esc_html( $name ) . '</small>' : ''
				);
				break;
		}
	}

	/**
	 * Azioni di riga: "Visualizza" e cestino.
	 *
	 * @param array   $actions Azioni.
	 * @param WP_Post $post    Post.
	 * @return array
	 */
	public static function row_actions( $actions, $post ) {
		if ( ! $post instanceof WP_Post || ! in_array( $post->post_type, self::TYPES, true ) ) {
			return $actions;
		}
		unset( $actions['inline hide-if-no-js'], $actions['view'] );
		if ( isset( $actions['edit'] ) ) {
			$actions['edit'] = sprintf( '<a href="%1$s">%2$s</a>', esc_url( (string) get_edit_post_link( $post->ID ) ), esc_html__( 'Visualizza', 'ecm' ) );
		}
		return $actions;
	}

	/**
	 * Azioni di gruppo: solo cestino.
	 *
	 * @param array $actions Azioni.
	 * @return array
	 */
	public static function bulk_actions( $actions ) {
		unset( $actions['edit'] );
		return $actions;
	}

	/**
	 * Pulsante "Esporta CSV" sopra la tabella.
	 *
	 * @param string $which top|bottom.
	 */
	public static function export_button( $which ): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( 'top' !== $which || ! $screen || 'edit' !== $screen->base || ! in_array( $screen->post_type, self::TYPES, true ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$url = wp_nonce_url(
			add_query_arg(
				array(
					'action' => 'ecm_export_csv',
					'type'   => $screen->post_type,
				),
				admin_url( 'admin-post.php' )
			),
			'ecm_export_csv'
		);
		printf( '<div class="alignleft actions"><a href="%1$s" class="button"><span class="dashicons dashicons-download" aria-hidden="true"></span> %2$s</a></div>', esc_url( $url ), esc_html__( 'Esporta CSV', 'ecm' ) );
	}

	/**
	 * Esportazione CSV (separatore ";" e BOM UTF-8 per Excel in italiano).
	 */
	public static function export_csv(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Non hai i permessi per esportare questi dati.', 'ecm' ), 403 );
		}
		check_admin_referer( 'ecm_export_csv' );
		$type = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : '';
		if ( ! in_array( $type, self::TYPES, true ) ) {
			wp_die( esc_html__( 'Tipo di esportazione non valido.', 'ecm' ), 400 );
		}

		if ( 'ecm_iscritto' === $type ) {
			$filename = 'iscritti-newsletter-' . wp_date( 'Y-m-d' ) . '.csv';
			$header   = array( 'Email', 'Data consenso', 'Testo consenso', 'Servizio', 'Esito invio', 'Iscrizione' );
		} else {
			$filename = 'richieste-contatto-' . wp_date( 'Y-m-d' ) . '.csv';
			$header   = array( 'Data', 'Nome', 'Email', 'Telefono', 'Professione', 'Corso', 'Messaggio', 'Notifica inviata' );
		}

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		$out = fopen( 'php://output', 'w' );
		if ( false === $out ) {
			wp_die( esc_html__( 'Impossibile generare il file.', 'ecm' ) );
		}
		fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		fputcsv( $out, $header, ';', '"', '\\' );

		$page = 1;
		do {
			$query = new WP_Query(
				array(
					'post_type'      => $type,
					'post_status'    => array( 'publish', 'private', 'draft' ),
					'posts_per_page' => 500,
					'paged'          => $page,
					'orderby'        => 'date',
					'order'          => 'DESC',
					'no_found_rows'  => true,
				)
			);
			foreach ( $query->posts as $post ) {
				if ( 'ecm_iscritto' === $type ) {
					$row = array(
						(string) get_post_meta( $post->ID, '_ecm_email', true ),
						(string) get_post_meta( $post->ID, '_ecm_consenso_data', true ),
						(string) get_post_meta( $post->ID, '_ecm_consenso_testo', true ),
						(string) get_post_meta( $post->ID, '_ecm_provider', true ),
						(string) get_post_meta( $post->ID, '_ecm_provider_status', true ),
						$post->post_date,
					);
				} else {
					$row = array(
						$post->post_date,
						(string) get_post_meta( $post->ID, '_ecm_nome', true ),
						(string) get_post_meta( $post->ID, '_ecm_email', true ),
						(string) get_post_meta( $post->ID, '_ecm_telefono', true ),
						(string) get_post_meta( $post->ID, '_ecm_professione', true ),
						(string) get_post_meta( $post->ID, '_ecm_corso', true ),
						(string) get_post_meta( $post->ID, '_ecm_messaggio', true ),
						'1' === get_post_meta( $post->ID, '_ecm_mail_sent', true ) ? 'sì' : 'no',
					);
				}
				fputcsv( $out, array_map( array( __CLASS__, 'csv_safe' ), $row ), ';', '"', '\\' );
			}
			++$page;
			$count = count( $query->posts );
			wp_reset_postdata();
		} while ( 500 === $count );

		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}

	/**
	 * Evita l'interpretazione di formule nei fogli di calcolo.
	 *
	 * @param string $value Valore.
	 * @return string
	 */
	public static function csv_safe( $value ): string {
		$value = (string) $value;
		if ( '' !== $value && in_array( $value[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
			$value = "'" . $value;
		}
		return $value;
	}

	/* ------------------------------------------------------------------
	 * Schermata di dettaglio (sola lettura).
	 * ------------------------------------------------------------------ */

	/**
	 * Box di dettaglio al posto del box "Pubblica".
	 *
	 * @param string  $post_type Tipo.
	 * @param WP_Post $post      Post.
	 */
	public static function meta_boxes( $post_type, $post = null ): void {
		if ( ! in_array( $post_type, self::TYPES, true ) ) {
			return;
		}
		remove_meta_box( 'submitdiv', $post_type, 'side' );
		remove_meta_box( 'slugdiv', $post_type, 'normal' );
		add_meta_box( 'ecm_submission', 'ecm_richiesta' === $post_type ? __( 'Richiesta di contatto', 'ecm' ) : __( 'Iscritto newsletter', 'ecm' ), array( __CLASS__, 'box_details' ), $post_type, 'normal', 'high' );
		add_meta_box( 'ecm_submission_actions', __( 'Azioni', 'ecm' ), array( __CLASS__, 'box_actions' ), $post_type, 'side', 'high' );
	}

	/**
	 * Dettagli.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function box_details( $post ): void {
		if ( 'ecm_richiesta' === $post->post_type ) {
			$rows = array(
				__( 'Nome', 'ecm' )             => get_post_meta( $post->ID, '_ecm_nome', true ),
				__( 'Email', 'ecm' )            => get_post_meta( $post->ID, '_ecm_email', true ),
				__( 'Telefono', 'ecm' )         => get_post_meta( $post->ID, '_ecm_telefono', true ),
				__( 'Professione', 'ecm' )      => get_post_meta( $post->ID, '_ecm_professione', true ),
				__( 'Corso', 'ecm' )            => get_post_meta( $post->ID, '_ecm_corso', true ),
				__( 'Ricevuta il', 'ecm' )      => mysql2date( 'd/m/Y H:i', $post->post_date ),
				__( 'Consenso privacy', 'ecm' ) => get_post_meta( $post->ID, '_ecm_privacy', true ) ? mysql2date( 'd/m/Y H:i', (string) get_post_meta( $post->ID, '_ecm_privacy', true ) ) : '',
				__( 'Testo del consenso', 'ecm' ) => get_post_meta( $post->ID, '_ecm_consenso', true ),
				__( 'Pagina di invio', 'ecm' )  => get_post_meta( $post->ID, '_ecm_pagina', true ),
			);
		} else {
			$rows = array(
				__( 'Email', 'ecm' )              => get_post_meta( $post->ID, '_ecm_email', true ),
				__( 'Data del consenso', 'ecm' )  => get_post_meta( $post->ID, '_ecm_consenso_data', true ) ? mysql2date( 'd/m/Y H:i', (string) get_post_meta( $post->ID, '_ecm_consenso_data', true ) ) : '',
				__( 'Testo del consenso', 'ecm' ) => get_post_meta( $post->ID, '_ecm_consenso_testo', true ),
				__( 'Servizio newsletter', 'ecm' ) => get_post_meta( $post->ID, '_ecm_provider', true ),
				__( 'Esito invio', 'ecm' )        => get_post_meta( $post->ID, '_ecm_provider_status', true ),
				__( 'Dettaglio errore', 'ecm' )   => get_post_meta( $post->ID, '_ecm_provider_error', true ),
				__( 'Pagina di invio', 'ecm' )    => get_post_meta( $post->ID, '_ecm_pagina', true ),
			);
		}
		echo '<table class="widefat striped ecm-details"><tbody>';
		foreach ( $rows as $label => $value ) {
			if ( '' === (string) $value ) {
				continue;
			}
			printf( '<tr><th scope="row">%1$s</th><td>%2$s</td></tr>', esc_html( $label ), esc_html( (string) $value ) );
		}
		echo '</tbody></table>';

		if ( 'ecm_richiesta' === $post->post_type ) {
			$message = (string) get_post_meta( $post->ID, '_ecm_messaggio', true );
			printf( '<h3>%s</h3><div class="ecm-message">%s</div>', esc_html__( 'Messaggio', 'ecm' ), nl2br( esc_html( $message ) ) );
		}
		echo '<p class="description">' . esc_html__( 'I dati inviati dal modulo non sono modificabili. L\'indirizzo IP non viene conservato in chiaro.', 'ecm' ) . '</p>';
	}

	/**
	 * Azioni.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function box_actions( $post ): void {
		$email = (string) get_post_meta( $post->ID, '_ecm_email', true );
		echo '<div class="ecm-actions">';
		if ( 'ecm_richiesta' === $post->post_type && is_email( $email ) ) {
			$subject = rawurlencode( 'Re: richiesta informazioni' );
			printf( '<p><a class="button button-primary" href="mailto:%1$s?subject=%2$s"><span class="dashicons dashicons-email" aria-hidden="true"></span> %3$s</a></p>', esc_attr( $email ), esc_attr( $subject ), esc_html__( 'Rispondi via email', 'ecm' ) );
		}
		$back = admin_url( 'edit.php?post_type=' . $post->post_type );
		printf( '<p><a href="%1$s">← %2$s</a></p>', esc_url( $back ), esc_html__( 'Torna all\'elenco', 'ecm' ) );
		if ( current_user_can( 'delete_post', $post->ID ) ) {
			printf( '<p><a class="submitdelete deletion" href="%1$s">%2$s</a></p>', esc_url( (string) get_delete_post_link( $post->ID ) ), esc_html__( 'Sposta nel cestino', 'ecm' ) );
		}
		echo '</div>';
	}
}
