<?php
/**
 * Meta box di corsi, docenti, testimonianze e SEO delle pagine.
 *
 * @package ECM_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Meta box.
 */
final class ECM_Meta_Boxes {

	private const NONCE        = 'ecm_meta_nonce';
	private const NONCE_ACTION = 'ecm_save_meta';

	/**
	 * Aggancia gli hook.
	 */
	public static function init(): void {
		add_action( 'add_meta_boxes', array( __CLASS__, 'register' ), 10, 2 );
		add_action( 'edit_form_after_title', array( __CLASS__, 'nonce_and_intro' ) );
		add_action( 'save_post', array( __CLASS__, 'save' ), 10, 2 );
	}

	/**
	 * Registra i box.
	 *
	 * @param string  $post_type Tipo.
	 * @param WP_Post $post      Post.
	 */
	public static function register( $post_type, $post = null ): void {
		switch ( $post_type ) {
			case 'corso':
				add_meta_box( 'ecm_course_status', __( 'Stato, crediti e iscrizione', 'ecm' ), array( __CLASS__, 'box_course_status' ), 'corso', 'side', 'high' );
				add_meta_box( 'ecm_course_brochure', __( 'Brochure PDF', 'ecm' ), array( __CLASS__, 'box_course_brochure' ), 'corso', 'normal', 'high' );
				add_meta_box( 'ecm_course_per_chi', __( 'Per chi è adatto', 'ecm' ), array( __CLASS__, 'box_course_per_chi' ), 'corso', 'normal', 'high' );
				add_meta_box( 'ecm_course_patrocini', __( 'Patrocini', 'ecm' ), array( __CLASS__, 'box_course_patrocini' ), 'corso', 'normal', 'default' );
				add_meta_box( 'ecm_course_testimonianze', __( 'Testimonianze del corso', 'ecm' ), array( __CLASS__, 'box_course_testimonianze' ), 'corso', 'normal', 'default' );
				add_meta_box( 'ecm_course_faq', __( 'Domande frequenti del corso', 'ecm' ), array( __CLASS__, 'box_course_faq' ), 'corso', 'normal', 'default' );
				add_meta_box( 'ecm_seo', __( 'SEO e vecchi indirizzi', 'ecm' ), array( __CLASS__, 'box_seo' ), 'corso', 'normal', 'low' );
				break;

			case 'docente':
				add_meta_box( 'ecm_docente', __( 'Dati del docente', 'ecm' ), array( __CLASS__, 'box_docente' ), 'docente', 'normal', 'high' );
				break;

			case 'testimonianza':
				add_meta_box( 'ecm_testimonianza', __( 'Dettagli testimonianza', 'ecm' ), array( __CLASS__, 'box_testimonianza' ), 'testimonianza', 'normal', 'high' );
				break;

			case 'page':
				add_meta_box( 'ecm_seo', __( 'SEO', 'ecm' ), array( __CLASS__, 'box_seo' ), 'page', 'normal', 'low' );
				break;
		}
	}

	/**
	 * Nonce (sempre presente, anche con box nascosti) e testo introduttivo.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function nonce_and_intro( $post ): void {
		if ( ! $post instanceof WP_Post || ! in_array( $post->post_type, array( 'corso', 'docente', 'testimonianza', 'page' ), true ) ) {
			return;
		}
		wp_nonce_field( self::NONCE_ACTION, self::NONCE, false );

		$intro = array(
			'corso'         => __( 'Sotto il titolo scrivi la descrizione completa del corso. La copertina si imposta nel riquadro "Copertina del corso" a destra; l\'ordine nel catalogo in "Ordine nel catalogo" (numeri più bassi compaiono prima).', 'ecm' ),
			'docente'       => __( 'Il titolo è il nome del docente; il testo sotto è la biografia. La foto si imposta nel riquadro "Foto del docente" a destra.', 'ecm' ),
			'testimonianza' => __( 'Il titolo è il nome di chi lascia la testimonianza; il testo sotto è la testimonianza stessa.', 'ecm' ),
		);
		if ( isset( $intro[ $post->post_type ] ) ) {
			printf( '<div class="ecm-intro"><span class="dashicons dashicons-info-outline" aria-hidden="true"></span><p>%s</p></div>', esc_html( $intro[ $post->post_type ] ) );
		}
	}

	/* ------------------------------------------------------------------
	 * Box corso.
	 * ------------------------------------------------------------------ */

	/**
	 * Stato, crediti, in evidenza, link iscrizione.
	 *
	 * @param WP_Post $post Corso.
	 */
	public static function box_course_status( $post ): void {
		$stato    = ECM_Meta::sanitize_stato( get_post_meta( $post->ID, '_ecm_stato', true ) );
		$crediti  = (string) get_post_meta( $post->ID, '_ecm_crediti', true );
		$evidenza = '1' === get_post_meta( $post->ID, '_ecm_in_evidenza', true );
		$link     = (string) get_post_meta( $post->ID, '_ecm_link_iscrizione', true );
		?>
		<div class="ecm-fields ecm-fields--side">
			<p>
				<label for="ecm_stato"><strong><?php esc_html_e( 'Stato', 'ecm' ); ?></strong></label>
				<select id="ecm_stato" name="ecm[stato]" class="widefat">
					<?php foreach ( ecm_stati() as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $stato, $key ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php ECM_Admin::hint( __( '"Pre-ordine" e "In arrivo" mostrano un\'etichetta sulla scheda; "Concluso" indica che il corso non è più acquistabile.', 'ecm' ) ); ?>
			</p>
			<p>
				<label for="ecm_crediti"><strong><?php esc_html_e( 'Crediti ECM', 'ecm' ); ?></strong></label>
				<input type="text" id="ecm_crediti" name="ecm[crediti]" value="<?php echo esc_attr( $crediti ); ?>" class="widefat" inputmode="decimal" placeholder="<?php esc_attr_e( 'es. 50', 'ecm' ); ?>" maxlength="20">
				<?php ECM_Admin::hint( __( 'Lascia vuoto se il corso non rilascia crediti ECM.', 'ecm' ) ); ?>
			</p>
			<p class="ecm-check">
				<label>
					<input type="checkbox" name="ecm[in_evidenza]" value="1" <?php checked( $evidenza ); ?>>
					<strong><?php esc_html_e( 'Corso in evidenza', 'ecm' ); ?></strong>
				</label>
				<?php ECM_Admin::hint( __( 'I corsi in evidenza compaiono nella home page.', 'ecm' ) ); ?>
			</p>
			<p>
				<label for="ecm_link"><strong><?php esc_html_e( 'Link di iscrizione', 'ecm' ); ?></strong></label>
				<input type="url" id="ecm_link" name="ecm[link_iscrizione]" value="<?php echo esc_attr( $link ); ?>" class="widefat" placeholder="https://">
				<?php ECM_Admin::hint( __( 'Indirizzo della pagina di acquisto/iscrizione sulla piattaforma (facoltativo). Se vuoto, il sito propone WhatsApp e brochure.', 'ecm' ) ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Brochure PDF e anteprima.
	 *
	 * @param WP_Post $post Corso.
	 */
	public static function box_course_brochure( $post ): void {
		$brochure = (int) get_post_meta( $post->ID, '_ecm_brochure_id', true );
		$preview  = (int) get_post_meta( $post->ID, '_ecm_brochure_preview_id', true );
		?>
		<div class="ecm-grid ecm-grid--brochure">
			<div class="ecm-grid__col">
				<h4 class="ecm-label"><?php esc_html_e( 'File PDF della brochure', 'ecm' ); ?></h4>
				<?php
				ECM_Admin::media_field(
					'ecm[brochure_id]',
					$brochure,
					array(
						'library'      => 'application/pdf',
						'frame_title'  => __( 'Scegli o carica la brochure (PDF)', 'ecm' ),
						'frame_button' => __( 'Usa questo PDF', 'ecm' ),
						'select'       => __( 'Carica brochure PDF', 'ecm' ),
						'role'         => 'brochure',
					)
				);
				ECM_Admin::hint( __( 'Per sostituire la brochure clicca "Sostituisci" e carica il nuovo PDF, poi premi "Aggiorna". Il vecchio file resta nella Libreria media.', 'ecm' ) );
				?>
			</div>
			<div class="ecm-grid__col">
				<h4 class="ecm-label"><?php esc_html_e( 'Anteprima (facoltativa)', 'ecm' ); ?></h4>
				<?php
				ECM_Admin::media_field(
					'ecm[brochure_preview_id]',
					$preview,
					array(
						'library'      => 'image',
						'frame_title'  => __( 'Scegli l\'immagine di anteprima della brochure', 'ecm' ),
						'frame_button' => __( 'Usa come anteprima', 'ecm' ),
						'select'       => __( 'Scegli immagine', 'ecm' ),
						'role'         => 'brochure-preview',
						'class'        => 'ecm-media--small',
					)
				);
				ECM_Admin::hint( __( 'Immagine della prima pagina mostrata accanto al pulsante di download. Se la lasci vuota, il sito usa l\'anteprima generata automaticamente da WordPress dal PDF.', 'ecm' ) );
				?>
				<p class="ecm-inline-notice" data-ecm-brochure-notice hidden></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Per chi è adatto (editor ridotto).
	 *
	 * @param WP_Post $post Corso.
	 */
	public static function box_course_per_chi( $post ): void {
		ECM_Admin::hint( __( 'Professioni e profili a cui si rivolge il corso. Viene mostrato come prima domanda nelle FAQ della scheda corso.', 'ecm' ) );
		wp_editor(
			(string) get_post_meta( $post->ID, '_ecm_per_chi', true ),
			'ecm_per_chi',
			array(
				'textarea_name' => 'ecm[per_chi]',
				'textarea_rows' => 6,
				'media_buttons' => false,
				'teeny'         => true,
				'quicktags'     => array( 'buttons' => 'strong,em,link,ul,ol,li' ),
			)
		);
	}

	/**
	 * Patrocini (logo + nome, ordinabili).
	 *
	 * @param WP_Post $post Corso.
	 */
	public static function box_course_patrocini( $post ): void {
		$rows = get_post_meta( $post->ID, '_ecm_patrocini', true );
		ECM_Admin::hint( __( 'Loghi degli enti che patrocinano il corso. Trascina le righe dalla maniglia per cambiare l\'ordine. Il nome è facoltativo ma consigliato (serve anche per l\'accessibilità).', 'ecm' ) );
		self::repeater(
			'patrocini',
			is_array( $rows ) ? $rows : array(),
			array( __CLASS__, 'row_patrocinio' ),
			__( 'Aggiungi patrocinio', 'ecm' ),
			__( 'Nessun patrocinio. Clicca "Aggiungi patrocinio" per inserire un logo.', 'ecm' )
		);
	}

	/**
	 * Riga patrocinio.
	 *
	 * @param string $base Nome base degli input.
	 * @param array  $row  Valori.
	 */
	public static function row_patrocinio( string $base, array $row ): void {
		?>
		<div class="ecm-row ecm-row--patrocinio">
			<div class="ecm-row__media">
				<?php
				ECM_Admin::media_field(
					$base . '[image_id]',
					(int) ( $row['image_id'] ?? 0 ),
					array(
						'library'      => 'image',
						'frame_title'  => __( 'Scegli il logo dell\'ente', 'ecm' ),
						'frame_button' => __( 'Usa questo logo', 'ecm' ),
						'select'       => __( 'Scegli logo', 'ecm' ),
						'class'        => 'ecm-media--logo',
					)
				);
				?>
			</div>
			<div class="ecm-row__fields">
				<label>
					<span><?php esc_html_e( 'Nome dell\'ente', 'ecm' ); ?></span>
					<input type="text" class="widefat" name="<?php echo esc_attr( $base . '[name]' ); ?>" value="<?php echo esc_attr( (string) ( $row['name'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'es. Ordine dei Biologi', 'ecm' ); ?>">
				</label>
			</div>
		</div>
		<?php
	}

	/**
	 * Testimonianze del corso.
	 *
	 * @param WP_Post $post Corso.
	 */
	public static function box_course_testimonianze( $post ): void {
		$rows = get_post_meta( $post->ID, '_ecm_testimonianze', true );
		ECM_Admin::hint( __( 'Testimonianze mostrate solo in questa scheda corso. Per quelle della home e della pagina Testimonianze usa il menu "Testimonianze".', 'ecm' ) );
		self::repeater(
			'testimonianze',
			is_array( $rows ) ? $rows : array(),
			array( __CLASS__, 'row_testimonianza' ),
			__( 'Aggiungi testimonianza', 'ecm' ),
			__( 'Nessuna testimonianza per questo corso.', 'ecm' )
		);
	}

	/**
	 * Riga testimonianza.
	 *
	 * @param string $base Nome base.
	 * @param array  $row  Valori.
	 */
	public static function row_testimonianza( string $base, array $row ): void {
		?>
		<div class="ecm-row ecm-row--stack">
			<div class="ecm-row__pair">
				<label>
					<span><?php esc_html_e( 'Nome', 'ecm' ); ?></span>
					<input type="text" class="widefat" name="<?php echo esc_attr( $base . '[name]' ); ?>" value="<?php echo esc_attr( (string) ( $row['name'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'es. Dott.ssa Maria Rossi', 'ecm' ); ?>">
				</label>
				<label>
					<span><?php esc_html_e( 'Qualifica o titolo (facoltativo)', 'ecm' ); ?></span>
					<input type="text" class="widefat" name="<?php echo esc_attr( $base . '[meta]' ); ?>" value="<?php echo esc_attr( (string) ( $row['meta'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'es. Biologa nutrizionista', 'ecm' ); ?>">
				</label>
			</div>
			<label>
				<span><?php esc_html_e( 'Testo', 'ecm' ); ?></span>
				<textarea class="widefat" rows="3" name="<?php echo esc_attr( $base . '[text]' ); ?>"><?php echo esc_textarea( (string) ( $row['text'] ?? '' ) ); ?></textarea>
			</label>
		</div>
		<?php
	}

	/**
	 * FAQ del corso.
	 *
	 * @param WP_Post $post Corso.
	 */
	public static function box_course_faq( $post ): void {
		$rows = get_post_meta( $post->ID, '_ecm_faq', true );
		ECM_Admin::hint( __( 'Solo le domande specifiche di questo corso. Le domande comuni a tutti i corsi (email, bonifico) si modificano una volta sola in Impostazioni ECM › FAQ comuni.', 'ecm' ) );
		self::repeater(
			'faq',
			is_array( $rows ) ? $rows : array(),
			array( __CLASS__, 'row_faq' ),
			__( 'Aggiungi domanda', 'ecm' ),
			__( 'Nessuna domanda specifica per questo corso.', 'ecm' )
		);
	}

	/**
	 * Riga FAQ.
	 *
	 * @param string $base Nome base.
	 * @param array  $row  Valori.
	 */
	public static function row_faq( string $base, array $row ): void {
		?>
		<div class="ecm-row ecm-row--stack">
			<label>
				<span><?php esc_html_e( 'Domanda', 'ecm' ); ?></span>
				<input type="text" class="widefat" name="<?php echo esc_attr( $base . '[q]' ); ?>" value="<?php echo esc_attr( (string) ( $row['q'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'es. È possibile pagare a rate?', 'ecm' ); ?>">
			</label>
			<label>
				<span><?php esc_html_e( 'Risposta', 'ecm' ); ?></span>
				<textarea class="widefat" rows="4" name="<?php echo esc_attr( $base . '[a]' ); ?>"><?php echo esc_textarea( (string) ( $row['a'] ?? '' ) ); ?></textarea>
			</label>
			<p class="description"><?php esc_html_e( 'Puoi andare a capo liberamente e usare HTML semplice come <strong>, <em>, <a href="…"> ed elenchi.', 'ecm' ); ?></p>
		</div>
		<?php
	}

	/**
	 * SEO (corsi e pagine) + vecchi indirizzi (solo corsi).
	 *
	 * @param WP_Post $post Post.
	 */
	public static function box_seo( $post ): void {
		$title = (string) get_post_meta( $post->ID, '_ecm_seo_title', true );
		$desc  = (string) get_post_meta( $post->ID, '_ecm_seo_desc', true );
		if ( ! ECM_SEO::enabled() ) {
			echo '<div class="notice notice-info inline"><p>' . esc_html__( 'È attivo un plugin SEO dedicato: questi campi non vengono usati, gestisci titolo e descrizione da quel plugin.', 'ecm' ) . '</p></div>';
		}
		?>
		<div class="ecm-fields">
			<p>
				<label for="ecm_seo_title"><strong><?php esc_html_e( 'Titolo per Google', 'ecm' ); ?></strong></label>
				<input type="text" id="ecm_seo_title" name="ecm[seo_title]" class="widefat" value="<?php echo esc_attr( $title ); ?>" data-ecm-counter="60" placeholder="<?php echo esc_attr( ecm_plain_title( $post ) ); ?>">
				<?php ECM_Admin::hint( __( 'Facoltativo. Se vuoto viene usato il titolo della pagina. Consigliati al massimo 60 caratteri.', 'ecm' ) ); ?>
			</p>
			<p>
				<label for="ecm_seo_desc"><strong><?php esc_html_e( 'Descrizione per Google', 'ecm' ); ?></strong></label>
				<textarea id="ecm_seo_desc" name="ecm[seo_desc]" class="widefat" rows="3" data-ecm-counter="155"><?php echo esc_textarea( $desc ); ?></textarea>
				<?php ECM_Admin::hint( __( 'Facoltativa. Se vuota viene usato l\'inizio della descrizione. Consigliati 120–155 caratteri.', 'ecm' ) ); ?>
			</p>
			<?php if ( 'corso' === $post->post_type ) : ?>
				<?php
				$paths  = get_post_meta( $post->ID, '_ecm_old_paths', true );
				$source = (string) get_post_meta( $post->ID, '_ecm_source_slug', true );
				?>
				<p>
					<label for="ecm_old_paths"><strong><?php esc_html_e( 'Vecchi indirizzi (redirect 301)', 'ecm' ); ?></strong></label>
					<textarea id="ecm_old_paths" name="ecm[old_paths]" class="widefat code" rows="3" placeholder="/courses/nome-vecchio-corso"><?php echo esc_textarea( implode( "\n", is_array( $paths ) ? $paths : array() ) ); ?></textarea>
					<?php ECM_Admin::hint( __( 'Un indirizzo per riga, a partire da "/". Chi visita questi indirizzi del vecchio sito viene portato automaticamente a questo corso.', 'ecm' ) ); ?>
				</p>
				<?php if ( '' !== $source ) : ?>
					<p class="description">
						<?php
						/* translators: %s: chiave di importazione. */
						printf( esc_html__( 'Chiave di importazione: %s', 'ecm' ), '<code>' . esc_html( $source ) . '</code>' );
						?>
					</p>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/* ------------------------------------------------------------------
	 * Box docente e testimonianza.
	 * ------------------------------------------------------------------ */

	/**
	 * Docente.
	 *
	 * @param WP_Post $post Docente.
	 */
	public static function box_docente( $post ): void {
		$titolo = (string) get_post_meta( $post->ID, '_ecm_titolo', true );
		$cv_id  = (int) get_post_meta( $post->ID, '_ecm_cv_id', true );
		$cv_url = (string) get_post_meta( $post->ID, '_ecm_cv_url', true );
		?>
		<div class="ecm-fields">
			<p>
				<label for="ecm_titolo"><strong><?php esc_html_e( 'Ruolo o qualifica', 'ecm' ); ?></strong></label>
				<input type="text" id="ecm_titolo" name="ecm[titolo]" class="widefat" value="<?php echo esc_attr( $titolo ); ?>" placeholder="<?php esc_attr_e( 'es. Biologo nutrizionista, docente universitario', 'ecm' ); ?>">
			</p>
			<div class="ecm-grid">
				<div class="ecm-grid__col">
					<h4 class="ecm-label"><?php esc_html_e( 'Curriculum (file)', 'ecm' ); ?></h4>
					<?php
					ECM_Admin::media_field(
						'ecm[cv_id]',
						$cv_id,
						array(
							'library'      => '',
							'frame_title'  => __( 'Scegli o carica il curriculum', 'ecm' ),
							'frame_button' => __( 'Usa questo file', 'ecm' ),
							'select'       => __( 'Carica curriculum', 'ecm' ),
						)
					);
					?>
				</div>
				<div class="ecm-grid__col">
					<label for="ecm_cv_url"><strong><?php esc_html_e( 'Oppure link al curriculum', 'ecm' ); ?></strong></label>
					<input type="url" id="ecm_cv_url" name="ecm[cv_url]" class="widefat" value="<?php echo esc_attr( $cv_url ); ?>" placeholder="https://">
					<?php ECM_Admin::hint( __( 'Usato solo se non è caricato un file.', 'ecm' ) ); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Testimonianza.
	 *
	 * @param WP_Post $post Testimonianza.
	 */
	public static function box_testimonianza( $post ): void {
		$corso_id = (int) get_post_meta( $post->ID, '_ecm_corso_id', true );
		$label    = (string) get_post_meta( $post->ID, '_ecm_corso_label', true );
		$fonte    = ECM_Meta::sanitize_fonte( get_post_meta( $post->ID, '_ecm_fonte', true ) );
		$rating   = (string) get_post_meta( $post->ID, '_ecm_rating', true );
		$ruolo    = (string) get_post_meta( $post->ID, '_ecm_ruolo', true );
		$video    = (string) get_post_meta( $post->ID, '_ecm_video', true );
		$courses  = get_posts(
			array(
				'post_type'      => 'corso',
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);
		$fonti    = array(
			'home'          => __( 'Solo home page', 'ecm' ),
			'testimonianze' => __( 'Solo pagina Testimonianze', 'ecm' ),
			'entrambe'      => __( 'Home e pagina Testimonianze', 'ecm' ),
		);
		?>
		<div class="ecm-fields">
			<div class="ecm-grid">
				<div class="ecm-grid__col">
					<label for="ecm_ruolo"><strong><?php esc_html_e( 'Professione', 'ecm' ); ?></strong></label>
					<input type="text" id="ecm_ruolo" name="ecm[ruolo]" class="widefat" value="<?php echo esc_attr( $ruolo ); ?>" placeholder="<?php esc_attr_e( 'Es. Biologa nutrizionista', 'ecm' ); ?>">
					<?php ECM_Admin::hint( __( 'Mostrata sotto il nome, come sul sito originale.', 'ecm' ) ); ?>
				</div>
				<div class="ecm-grid__col">
					<label for="ecm_video"><strong><?php esc_html_e( 'Video (facoltativo)', 'ecm' ); ?></strong></label>
					<input type="url" id="ecm_video" name="ecm[video]" class="widefat" value="<?php echo esc_attr( $video ); ?>" placeholder="https://vimeo.com/123456789">
					<?php ECM_Admin::hint( __( 'Indirizzo Vimeo o YouTube. Una testimonianza con video viene messa in evidenza, con la sua immagine in evidenza come anteprima.', 'ecm' ) ); ?>
				</div>
			</div>
			<div class="ecm-grid">
				<div class="ecm-grid__col">
					<label for="ecm_corso_id"><strong><?php esc_html_e( 'Corso frequentato', 'ecm' ); ?></strong></label>
					<select id="ecm_corso_id" name="ecm[corso_id]" class="widefat">
						<option value="0"><?php esc_html_e( '— Nessun corso collegato —', 'ecm' ); ?></option>
						<?php foreach ( $courses as $course ) : ?>
							<option value="<?php echo (int) $course->ID; ?>" <?php selected( $corso_id, $course->ID ); ?>><?php echo esc_html( ecm_plain_title( $course ) ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php ECM_Admin::hint( __( 'Se colleghi un corso, il nome diventa un link alla sua scheda.', 'ecm' ) ); ?>
				</div>
				<div class="ecm-grid__col">
					<label for="ecm_corso_label"><strong><?php esc_html_e( 'Nome del corso da mostrare', 'ecm' ); ?></strong></label>
					<input type="text" id="ecm_corso_label" name="ecm[corso_label]" class="widefat" value="<?php echo esc_attr( $label ); ?>">
					<?php ECM_Admin::hint( __( 'Usato quando il corso non è collegato (ad esempio un corso non più presente nel sito).', 'ecm' ) ); ?>
				</div>
			</div>
			<fieldset class="ecm-fieldset">
				<legend><strong><?php esc_html_e( 'Dove mostrarla', 'ecm' ); ?></strong></legend>
				<?php foreach ( $fonti as $key => $text ) : ?>
					<label class="ecm-radio"><input type="radio" name="ecm[fonte]" value="<?php echo esc_attr( $key ); ?>" <?php checked( $fonte, $key ); ?>> <?php echo esc_html( $text ); ?></label>
				<?php endforeach; ?>
			</fieldset>
			<p>
				<label for="ecm_rating"><strong><?php esc_html_e( 'Valutazione (facoltativa)', 'ecm' ); ?></strong></label>
				<select id="ecm_rating" name="ecm[rating]">
					<option value=""><?php esc_html_e( '— Nessuna —', 'ecm' ); ?></option>
					<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
						<option value="<?php echo (int) $i; ?>" <?php selected( $rating, (string) $i ); ?>><?php echo esc_html( str_repeat( '★', $i ) . str_repeat( '☆', 5 - $i ) ); ?></option>
					<?php endfor; ?>
				</select>
			</p>
		</div>
		<?php
	}

	/* ------------------------------------------------------------------
	 * Ripetitore.
	 * ------------------------------------------------------------------ */

	/**
	 * Ripetitore ordinabile.
	 *
	 * @param string   $key   Chiave (ecm[key][i][...]).
	 * @param array    $rows  Righe.
	 * @param callable $row   Callback di una riga ($base, $row).
	 * @param string   $add   Etichetta pulsante.
	 * @param string   $empty Testo stato vuoto.
	 */
	private static function repeater( string $key, array $rows, callable $row, string $add, string $empty ): void {
		printf( '<div class="ecm-repeater" data-key="%s">', esc_attr( $key ) );
		echo '<ol class="ecm-repeater__list">';
		foreach ( array_values( $rows ) as $i => $values ) {
			self::repeater_row( 'ecm[' . $key . '][' . $i . ']', is_array( $values ) ? $values : array(), $row );
		}
		echo '</ol>';
		printf( '<p class="ecm-repeater__empty">%s</p>', esc_html( $empty ) );
		echo '<script type="text/html" class="ecm-repeater__tpl">';
		self::repeater_row( 'ecm[' . $key . '][__i__]', array(), $row );
		echo '</script>';
		printf( '<p><button type="button" class="button ecm-repeater__add"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span> %s</button></p>', esc_html( $add ) );
		echo '</div>';
	}

	/**
	 * Riga del ripetitore con maniglia e pulsante rimuovi.
	 *
	 * @param string   $base   Nome base.
	 * @param array    $values Valori.
	 * @param callable $row    Callback.
	 */
	private static function repeater_row( string $base, array $values, callable $row ): void {
		echo '<li class="ecm-repeater__row">';
		printf( '<span class="ecm-repeater__handle" title="%1$s" aria-label="%1$s"><span class="ecm-repeater__num"></span><span class="dashicons dashicons-move" aria-hidden="true"></span></span>', esc_attr__( 'Trascina per riordinare', 'ecm' ) );
		echo '<div class="ecm-repeater__body">';
		call_user_func( $row, $base, $values );
		echo '</div>';
		printf( '<button type="button" class="button-link button-link-delete ecm-repeater__remove"><span class="dashicons dashicons-trash" aria-hidden="true"></span><span class="screen-reader-text">%s</span></button>', esc_html__( 'Rimuovi riga', 'ecm' ) );
		echo '</li>';
	}

	/* ------------------------------------------------------------------
	 * Salvataggio.
	 * ------------------------------------------------------------------ */

	/**
	 * Salva i meta inviati dai box.
	 *
	 * @param int     $post_id ID.
	 * @param WP_Post $post    Post.
	 */
	public static function save( $post_id, $post ): void {
		if ( ! $post instanceof WP_Post || ! in_array( $post->post_type, array( 'corso', 'docente', 'testimonianza', 'page' ), true ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ self::NONCE ] ) ), self::NONCE_ACTION ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitizzato campo per campo sotto.
		$in = isset( $_POST['ecm'] ) && is_array( $_POST['ecm'] ) ? wp_unslash( $_POST['ecm'] ) : array();

		switch ( $post->post_type ) {
			case 'corso':
				self::save_course( (int) $post_id, $in );
				self::save_seo( (int) $post_id, $in );
				break;
			case 'docente':
				self::update( $post_id, '_ecm_titolo', sanitize_text_field( (string) ( $in['titolo'] ?? '' ) ) );
				$cv_id = absint( $in['cv_id'] ?? 0 );
				self::update( $post_id, '_ecm_cv_id', ( $cv_id && 'attachment' === get_post_type( $cv_id ) ) ? $cv_id : 0 );
				self::update( $post_id, '_ecm_cv_url', esc_url_raw( (string) ( $in['cv_url'] ?? '' ) ) );
				break;
			case 'testimonianza':
				$corso_id = absint( $in['corso_id'] ?? 0 );
				self::update( $post_id, '_ecm_corso_id', ( $corso_id && 'corso' === get_post_type( $corso_id ) ) ? $corso_id : 0 );
				self::update( $post_id, '_ecm_corso_label', sanitize_text_field( (string) ( $in['corso_label'] ?? '' ) ) );
				self::update( $post_id, '_ecm_fonte', ECM_Meta::sanitize_fonte( $in['fonte'] ?? 'entrambe' ) );
				self::update( $post_id, '_ecm_rating', ECM_Meta::sanitize_rating( $in['rating'] ?? '' ) );
				self::update( $post_id, '_ecm_ruolo', sanitize_text_field( (string) ( $in['ruolo'] ?? '' ) ) );
				self::update( $post_id, '_ecm_video', esc_url_raw( (string) ( $in['video'] ?? '' ) ) );
				break;
			case 'page':
				self::save_seo( (int) $post_id, $in );
				break;
		}
	}

	/**
	 * Salvataggio dei campi corso.
	 *
	 * @param int   $post_id ID.
	 * @param array $in      Dati inviati (senza slash).
	 */
	private static function save_course( int $post_id, array $in ): void {
		self::update( $post_id, '_ecm_stato', ECM_Meta::sanitize_stato( $in['stato'] ?? 'disponibile' ) );
		self::update( $post_id, '_ecm_crediti', ECM_Meta::sanitize_crediti( $in['crediti'] ?? '' ) );
		self::update( $post_id, '_ecm_in_evidenza', ECM_Meta::sanitize_flag( $in['in_evidenza'] ?? '' ) );
		self::update( $post_id, '_ecm_link_iscrizione', esc_url_raw( (string) ( $in['link_iscrizione'] ?? '' ) ) );

		// Brochure: solo PDF.
		$old_brochure = (int) get_post_meta( $post_id, '_ecm_brochure_id', true );
		$old_preview  = (int) get_post_meta( $post_id, '_ecm_brochure_preview_id', true );
		$brochure     = absint( $in['brochure_id'] ?? 0 );
		$preview      = absint( $in['brochure_preview_id'] ?? 0 );

		if ( $brochure && 'application/pdf' !== get_post_mime_type( $brochure ) ) {
			ECM_Admin::add_notice( __( 'La brochure deve essere un file PDF: il file scelto non è stato salvato.', 'ecm' ), 'error' );
			$brochure = $old_brochure;
		}
		if ( $preview && ! wp_attachment_is_image( $preview ) ) {
			ECM_Admin::add_notice( __( 'L\'anteprima della brochure deve essere un\'immagine: il file scelto non è stato salvato.', 'ecm' ), 'error' );
			$preview = $old_preview;
		}
		if ( $brochure !== $old_brochure && $old_brochure && $preview && $preview === $old_preview ) {
			$preview = 0;
			ECM_Admin::add_notice( __( 'Brochure sostituita: la vecchia anteprima è stata rimossa e verrà usata quella generata automaticamente dal nuovo PDF. Puoi sempre scegliere un\'immagine di anteprima diversa.', 'ecm' ), 'info' );
		}
		if ( $brochure && '' === (string) get_post_meta( $brochure, '_ecm_pdf_pages', true ) ) {
			$file  = get_attached_file( $brochure );
			$pages = $file ? ECM_Media::pdf_page_count( $file ) : null;
			if ( $pages ) {
				update_post_meta( $brochure, '_ecm_pdf_pages', $pages );
			}
		}
		self::update( $post_id, '_ecm_brochure_id', $brochure );
		self::update( $post_id, '_ecm_brochure_preview_id', $preview );
		self::update( $post_id, '_ecm_brochure_preview_for', $brochure );

		// Patrocini: solo immagini.
		$patrocini = ECM_Meta::sanitize_patrocini( $in['patrocini'] ?? array() );
		foreach ( $patrocini as $i => $row ) {
			if ( $row['image_id'] && ! wp_attachment_is_image( $row['image_id'] ) ) {
				$patrocini[ $i ]['image_id'] = 0;
			}
		}
		self::update( $post_id, '_ecm_patrocini', array_values( $patrocini ) );
		self::update( $post_id, '_ecm_testimonianze', ECM_Meta::sanitize_testimonianze( $in['testimonianze'] ?? array() ) );
		self::update( $post_id, '_ecm_faq', ECM_Meta::sanitize_faq( $in['faq'] ?? array() ) );
		self::update( $post_id, '_ecm_per_chi', ECM_Meta::sanitize_html( $in['per_chi'] ?? '' ) );
		self::update( $post_id, '_ecm_old_paths', ECM_Meta::sanitize_paths( (string) ( $in['old_paths'] ?? '' ) ) );
	}

	/**
	 * Salvataggio SEO.
	 *
	 * @param int   $post_id ID.
	 * @param array $in      Dati.
	 */
	private static function save_seo( int $post_id, array $in ): void {
		if ( array_key_exists( 'seo_title', $in ) ) {
			self::update( $post_id, '_ecm_seo_title', sanitize_text_field( (string) $in['seo_title'] ) );
		}
		if ( array_key_exists( 'seo_desc', $in ) ) {
			self::update( $post_id, '_ecm_seo_desc', sanitize_textarea_field( (string) $in['seo_desc'] ) );
		}
	}

	/**
	 * update_post_meta con slash: update_metadata rimuove gli slash, i dati qui sono già senza.
	 *
	 * @param int    $post_id ID.
	 * @param string $key     Chiave.
	 * @param mixed  $value   Valore sanitizzato.
	 */
	private static function update( int $post_id, string $key, $value ): void {
		update_post_meta( $post_id, $key, wp_slash( $value ) );
	}
}
