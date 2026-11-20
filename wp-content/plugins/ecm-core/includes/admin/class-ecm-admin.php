<?php
/**
 * Area admin: menu "Impostazioni ECM", risorse JS/CSS, avvisi e componenti UI condivisi.
 *
 * @package ECM_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin.
 */
final class ECM_Admin {

	public const MENU_SLUG = 'ecm-settings';

	/**
	 * Aggancia gli hook.
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 9 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_notices', array( __CLASS__, 'print_notices' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( ECM_CORE_FILE ), array( __CLASS__, 'action_links' ) );
	}

	/**
	 * Menu di primo livello (le sottovoci CPT vengono aggiunte da WordPress con priorità 10).
	 */
	public static function menu(): void {
		add_menu_page(
			__( 'Impostazioni ECM', 'ecm' ),
			__( 'Impostazioni ECM', 'ecm' ),
			'manage_options',
			self::MENU_SLUG,
			array( 'ECM_Settings_Page', 'render' ),
			'dashicons-welcome-learn-more',
			58
		);
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Impostazioni ECM', 'ecm' ),
			__( 'Impostazioni', 'ecm' ),
			'manage_options',
			self::MENU_SLUG,
			array( 'ECM_Settings_Page', 'render' )
		);
	}

	/**
	 * Link "Impostazioni" nella lista plugin.
	 *
	 * @param array $links Link.
	 * @return array
	 */
	public static function action_links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=' . self::MENU_SLUG ) ) . '">' . esc_html__( 'Impostazioni', 'ecm' ) . '</a>' );
		return $links;
	}

	/**
	 * Carica JS/CSS solo nelle schermate del plugin.
	 *
	 * @param string $hook Schermata.
	 */
	public static function assets( $hook ): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen ) {
			return;
		}

		$editor_types = array( 'corso', 'docente', 'testimonianza', 'page', 'ecm_richiesta', 'ecm_iscritto' );
		$is_editor    = 'post' === $screen->base && in_array( $screen->post_type, $editor_types, true );
		$is_list      = 'edit' === $screen->base && in_array( $screen->post_type, array( 'corso', 'docente', 'testimonianza', 'ecm_richiesta', 'ecm_iscritto' ), true );
		$is_term      = in_array( $screen->base, array( 'edit-tags', 'term' ), true ) && in_array( $screen->taxonomy, array( 'area_tematica', 'destinatario' ), true );
		$is_settings  = false !== strpos( (string) $hook, self::MENU_SLUG ) || false !== strpos( (string) $hook, 'ecm-import' );

		if ( ! $is_editor && ! $is_list && ! $is_term && ! $is_settings ) {
			return;
		}

		wp_enqueue_style( 'ecm-admin', ECM_CORE_URL . 'assets/admin/admin.css', array(), ECM_CORE_VERSION );

		if ( $is_list ) {
			return;
		}

		if ( $is_editor || $is_settings ) {
			wp_enqueue_media();
		}
		wp_enqueue_script( 'ecm-admin', ECM_CORE_URL . 'assets/admin/admin.js', array( 'jquery', 'jquery-ui-sortable' ), ECM_CORE_VERSION, true );
		wp_localize_script(
			'ecm-admin',
			'ecmAdmin',
			array(
				'chooseFile'     => __( 'Scegli un file', 'ecm' ),
				'useFile'        => __( 'Usa questo file', 'ecm' ),
				'wrongType'      => __( 'Il file scelto non è del tipo richiesto. Scegli un altro file.', 'ecm' ),
				'confirmRemove'  => __( 'Vuoi davvero rimuovere questa riga? La modifica diventa definitiva quando aggiorni il corso.', 'ecm' ),
				'chars'          => __( 'caratteri', 'ecm' ),
				'open'           => __( 'Apri', 'ecm' ),
				'pages'          => __( 'pagine', 'ecm' ),
				'noFile'         => __( 'Nessun file selezionato.', 'ecm' ),
				'noImage'        => __( 'Nessuna immagine selezionata.', 'ecm' ),
				'brochureSwitch' => __( 'Hai cambiato la brochure: l\'anteprima attuale si riferisce al PDF precedente. Al salvataggio verrà rimossa e sarà usata l\'anteprima generata da WordPress, a meno che tu non scelga una nuova immagine.', 'ecm' ),
			)
		);

		if ( false !== strpos( (string) $hook, 'ecm-import' ) ) {
			wp_enqueue_script( 'ecm-import', ECM_CORE_URL . 'assets/admin/import.js', array(), ECM_CORE_VERSION, true );
			wp_localize_script(
				'ecm-import',
				'ecmImport',
				array(
					'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
					'nonce'      => wp_create_nonce( 'ecm_import' ),
					'mediaBatch' => 4,
					'steps'      => ECM_Importer::step_labels(),
					'i18n'       => array(
						'running'   => __( 'In corso…', 'ecm' ),
						'done'      => __( 'Completato', 'ecm' ),
						'failed'    => __( 'Errore', 'ecm' ),
						'created'   => __( 'creati', 'ecm' ),
						'updated'   => __( 'aggiornati', 'ecm' ),
						'skipped'   => __( 'invariati', 'ecm' ),
						'errors'    => __( 'errori', 'ecm' ),
						'details'   => __( 'Dettagli', 'ecm' ),
						'problems'  => __( 'Problemi trovati', 'ecm' ),
						'noProblem' => __( 'Nessun problema trovato.', 'ecm' ),
						'network'   => __( 'Errore di rete o timeout del server. Puoi rilanciare lo step: riprende da dove si era fermato.', 'ecm' ),
						'confirm'   => __( 'Eseguire tutti gli step in sequenza? I contenuti esistenti con la stessa chiave verranno aggiornati.', 'ecm' ),
						'media'     => __( 'Media', 'ecm' ),
					),
				)
			);
		}
	}

	/* ------------------------------------------------------------------
	 * Avvisi.
	 * ------------------------------------------------------------------ */

	/**
	 * Accoda un avviso per l'utente corrente (mostrato alla prossima schermata).
	 *
	 * @param string $message Testo.
	 * @param string $type    success|warning|error|info.
	 */
	public static function add_notice( string $message, string $type = 'warning' ): void {
		$key     = 'ecm_notices_' . get_current_user_id();
		$notices = get_transient( $key );
		$notices = is_array( $notices ) ? $notices : array();

		$notices[] = array(
			'message' => $message,
			'type'    => $type,
		);
		set_transient( $key, $notices, 120 );
	}

	/**
	 * Stampa gli avvisi accodati.
	 */
	public static function print_notices(): void {
		$key     = 'ecm_notices_' . get_current_user_id();
		$notices = get_transient( $key );
		if ( ! is_array( $notices ) || ! $notices ) {
			return;
		}
		delete_transient( $key );
		foreach ( $notices as $notice ) {
			$type = in_array( $notice['type'], array( 'success', 'warning', 'error', 'info' ), true ) ? $notice['type'] : 'info';
			printf( '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>', esc_attr( $type ), esc_html( (string) $notice['message'] ) );
		}
	}

	/* ------------------------------------------------------------------
	 * Componenti UI.
	 * ------------------------------------------------------------------ */

	/**
	 * Campo media (wp.media) con anteprima e pulsanti Carica/Sostituisci/Rimuovi.
	 *
	 * @param string $name  Nome dell'input.
	 * @param int    $id    ID allegato.
	 * @param array  $opts  library (image|application/pdf|''), frame_title, frame_button, select, replace, remove, class, linked.
	 */
	public static function media_field( string $name, int $id, array $opts = array() ): void {
		$opts = wp_parse_args(
			$opts,
			array(
				'library'      => 'image',
				'frame_title'  => __( 'Scegli un\'immagine', 'ecm' ),
				'frame_button' => __( 'Usa questa immagine', 'ecm' ),
				'select'       => __( 'Carica o scegli', 'ecm' ),
				'replace'      => __( 'Sostituisci', 'ecm' ),
				'remove'       => __( 'Rimuovi', 'ecm' ),
				'class'        => '',
				'role'         => '',
			)
		);
		$valid = $id && 'attachment' === get_post_type( $id );
		$id    = $valid ? $id : 0;

		printf(
			'<div class="ecm-media %1$s%2$s" data-library="%3$s" data-frame-title="%4$s" data-frame-button="%5$s" data-role="%6$s">',
			esc_attr( $opts['class'] ),
			$id ? ' has-value' : '',
			esc_attr( $opts['library'] ),
			esc_attr( $opts['frame_title'] ),
			esc_attr( $opts['frame_button'] ),
			esc_attr( $opts['role'] )
		);
		printf( '<input type="hidden" class="ecm-media__input" name="%1$s" value="%2$s">', esc_attr( $name ), $id ? (int) $id : '' );
		echo '<div class="ecm-media__preview">';
		self::media_preview( $id, $opts['library'] );
		echo '</div><div class="ecm-media__actions">';
		printf( '<button type="button" class="button ecm-media__select">%s</button>', esc_html( $opts['select'] ) );
		printf( '<button type="button" class="button ecm-media__replace">%s</button>', esc_html( $opts['replace'] ) );
		printf( '<button type="button" class="button-link button-link-delete ecm-media__remove">%s</button>', esc_html( $opts['remove'] ) );
		echo '</div></div>';
	}

	/**
	 * Anteprima di un allegato.
	 *
	 * @param int    $id      ID.
	 * @param string $library Tipo atteso.
	 */
	public static function media_preview( int $id, string $library ): void {
		if ( ! $id ) {
			printf( '<p class="ecm-media__empty">%s</p>', 'image' === $library ? esc_html__( 'Nessuna immagine selezionata.', 'ecm' ) : esc_html__( 'Nessun file selezionato.', 'ecm' ) );
			return;
		}

		if ( wp_attachment_is_image( $id ) ) {
			echo '<div class="ecm-media__thumb">' . wp_get_attachment_image( $id, 'medium', false, array( 'alt' => '' ) ) . '</div>';
			printf( '<p class="ecm-media__meta"><span class="ecm-media__name">%s</span></p>', esc_html( wp_basename( (string) get_attached_file( $id ) ) ) );
			return;
		}

		$file  = get_attached_file( $id );
		$meta  = wp_get_attachment_metadata( $id );
		$bytes = (int) ( is_array( $meta ) && ! empty( $meta['filesize'] ) ? $meta['filesize'] : ( $file && file_exists( $file ) ? filesize( $file ) : 0 ) );
		$pages = (int) get_post_meta( $id, '_ecm_pdf_pages', true );
		$thumb = wp_get_attachment_image_src( $id, 'medium' );

		echo '<div class="ecm-media__file">';
		if ( $thumb ) {
			printf( '<img class="ecm-media__pdfthumb" src="%s" alt="">', esc_url( $thumb[0] ) );
		} else {
			echo '<span class="dashicons dashicons-pdf ecm-media__icon" aria-hidden="true"></span>';
		}
		echo '<p class="ecm-media__meta">';
		printf( '<strong class="ecm-media__name">%s</strong>', esc_html( wp_basename( (string) $file ) ) );
		$details = array();
		if ( $bytes ) {
			$details[] = ecm_human_filesize( $bytes );
		}
		if ( $pages ) {
			/* translators: %d: numero di pagine. */
			$details[] = sprintf( _n( '%d pagina', '%d pagine', $pages, 'ecm' ), $pages );
		}
		if ( $details ) {
			printf( '<span class="ecm-media__details">%s</span>', esc_html( implode( ' · ', $details ) ) );
		}
		printf( '<a class="ecm-media__open" href="%s" target="_blank" rel="noopener">%s</a>', esc_url( (string) wp_get_attachment_url( $id ) ), esc_html__( 'Apri', 'ecm' ) );
		echo '</p></div>';
	}

	/**
	 * Testo di aiuto sotto un campo.
	 *
	 * @param string $text Testo.
	 */
	public static function hint( string $text ): void {
		printf( '<p class="description ecm-hint">%s</p>', esc_html( $text ) );
	}
}
