<?php
/**
 * Importazione ripetibile dal bundle `wp-content/uploads/ecm-import/` (data.json + assets/).
 *
 * Ogni step è idempotente e restituisce un riepilogo:
 * [ step, created, updated, skipped, errors[], log[], time, ... ].
 *
 * Uso: ECM_Importer::run( 'terms' ); ECM_Importer::run( 'media', [ 'offset' => 0, 'limit' => 5 ] ); ...
 * Argomenti comuni: dir (percorso bundle alternativo), force (riscrive anche se invariato),
 * preserve_manual (non sovrascrive i campi modificati a mano dopo l'ultima importazione).
 *
 * @package ECM_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Importer.
 */
final class ECM_Importer {

	public const STEPS = array( 'terms', 'media', 'courses', 'docenti', 'testimonianze', 'pages', 'menus', 'options', 'redirects', 'verify' );

	public const MENU_NAMES = array(
		'primary' => 'Principale',
		'footer'  => 'Footer',
	);

	/**
	 * Bundle già letti, per percorso.
	 *
	 * @var array<string,array>
	 */
	private static array $bundles = array();

	/**
	 * Etichette degli step.
	 *
	 * @return array<string,string>
	 */
	public static function step_labels(): array {
		return array(
			'terms'         => __( 'Aree e destinatari', 'ecm' ),
			'media'         => __( 'Media (copertine, brochure, loghi)', 'ecm' ),
			'courses'       => __( 'Corsi', 'ecm' ),
			'docenti'       => __( 'Docenti', 'ecm' ),
			'testimonianze' => __( 'Testimonianze', 'ecm' ),
			'pages'         => __( 'Pagine', 'ecm' ),
			'menus'         => __( 'Menu', 'ecm' ),
			'options'       => __( 'Impostazioni', 'ecm' ),
			'redirects'     => __( 'Redirect', 'ecm' ),
			'verify'        => __( 'Verifica', 'ecm' ),
		);
	}

	/* ------------------------------------------------------------------
	 * Entrata.
	 * ------------------------------------------------------------------ */

	/**
	 * Esegue uno step.
	 *
	 * @param string $step Nome dello step.
	 * @param array  $args Argomenti.
	 * @return array Riepilogo.
	 */
	public static function run( string $step, array $args = array() ): array {
		$start   = microtime( true );
		$summary = array(
			'step'    => $step,
			'created' => 0,
			'updated' => 0,
			'skipped' => 0,
			'errors'  => array(),
			'log'     => array(),
		);

		if ( ! in_array( $step, self::STEPS, true ) ) {
			$summary['errors'][] = sprintf( 'Step sconosciuto: %s. Step validi: %s', $step, implode( ', ', self::STEPS ) );
			return $summary;
		}

		$data = self::load( $args );
		if ( is_wp_error( $data ) ) {
			$summary['errors'][] = $data->get_error_message();
			return $summary;
		}

		wp_raise_memory_limit( 'admin' );
		self::require_admin_includes();
		$deferred = in_array( $step, array( 'courses' ), true );
		if ( $deferred ) {
			wp_defer_term_counting( true );
		}

		try {
			$method  = 'step_' . $step;
			$summary = self::$method( $data, $args, $summary );
		} catch ( Throwable $e ) {
			$summary['errors'][] = sprintf( '%s: %s (%s:%d)', get_class( $e ), $e->getMessage(), wp_basename( $e->getFile() ), $e->getLine() );
		} finally {
			if ( $deferred ) {
				wp_defer_term_counting( false );
			}
		}

		ECM_Data::flush();
		ECM_Redirects::flush_map();

		$summary['time'] = round( microtime( true ) - $start, 2 );
		if ( count( $summary['log'] ) > 300 ) {
			$summary['log'] = array_merge( array_slice( $summary['log'], 0, 300 ), array( '…' ) );
		}

		ECM_Logger::log(
			$summary['errors'] ? 'warning' : 'info',
			'import',
			sprintf( 'Step %s: %d creati, %d aggiornati, %d invariati, %d errori (%ss).', $step, $summary['created'], $summary['updated'], $summary['skipped'], count( $summary['errors'] ), $summary['time'] )
		);

		return $summary;
	}

	/**
	 * Percorso del bundle.
	 *
	 * @param array $args Argomenti (dir opzionale).
	 * @return string Con slash finale.
	 */
	public static function dir( array $args = array() ): string {
		$dir = ! empty( $args['dir'] ) ? (string) $args['dir'] : wp_upload_dir( null, false )['basedir'] . '/ecm-import';
		return trailingslashit( (string) apply_filters( 'ecm_import_dir', $dir ) );
	}

	/**
	 * Legge e valida data.json.
	 *
	 * @param array $args Argomenti.
	 * @return array|WP_Error
	 */
	public static function load( array $args = array() ) {
		$dir = self::dir( $args );
		if ( isset( self::$bundles[ $dir ] ) && empty( $args['reload'] ) ) {
			return self::$bundles[ $dir ];
		}
		$file = $dir . 'data.json';
		if ( ! is_readable( $file ) ) {
			return new WP_Error( 'ecm_import_missing', sprintf( 'Bundle non trovato: %s', $file ) );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$data = json_decode( (string) file_get_contents( $file ), true );
		if ( ! is_array( $data ) ) {
			return new WP_Error( 'ecm_import_json', sprintf( 'data.json non valido: %s', json_last_error_msg() ) );
		}
		if ( 1 !== (int) ( $data['version'] ?? 0 ) ) {
			return new WP_Error( 'ecm_import_version', sprintf( 'Versione del bundle non supportata: %s (attesa 1).', (string) ( $data['version'] ?? '?' ) ) );
		}
		$data['__dir']         = $dir;
		self::$bundles[ $dir ] = $data;
		return $data;
	}

	/**
	 * Stato del bundle per la pagina admin.
	 *
	 * @param array $args Argomenti.
	 * @return array
	 */
	public static function status( array $args = array() ): array {
		$dir    = self::dir( $args );
		$status = array(
			'dir'       => $dir,
			'exists'    => is_readable( $dir . 'data.json' ),
			'error'     => '',
			'version'   => null,
			'generated' => '',
			'counts'    => array(),
			'imported'  => array(),
		);
		$data = self::load( array_merge( $args, array( 'reload' => true ) ) );
		if ( is_wp_error( $data ) ) {
			$status['error'] = $data->get_error_message();
			return $status;
		}
		$status['version']   = (int) $data['version'];
		$status['generated'] = (string) ( $data['generated'] ?? '' );
		$status['counts']    = array(
			'terms'         => count( (array) ( $data['terms']['area_tematica'] ?? array() ) ) + count( (array) ( $data['terms']['destinatario'] ?? array() ) ),
			'media'         => count( (array) ( $data['media'] ?? array() ) ),
			'courses'       => count( (array) ( $data['courses'] ?? array() ) ),
			'docenti'       => count( (array) ( $data['docenti'] ?? array() ) ),
			'testimonianze' => count( (array) ( $data['testimonianze'] ?? array() ) ),
			'pages'         => count( (array) ( $data['pages'] ?? array() ) ),
			'menus'         => count( (array) ( $data['menus'] ?? array() ) ),
			'options'       => count( (array) ( $data['options'] ?? array() ) ),
			'redirects'     => count( (array) ( $data['redirects'] ?? array() ) ),
		);

		$media_keys = array();
		foreach ( (array) ( $data['media'] ?? array() ) as $item ) {
			$media_keys[] = (string) ( $item['key'] ?? '' );
		}
		$status['imported']['media'] = count( array_intersect( $media_keys, array_keys( self::media_map() ) ) );
		return $status;
	}

	/* ------------------------------------------------------------------
	 * Step: termini.
	 * ------------------------------------------------------------------ */

	/**
	 * Aree tematiche e destinatari.
	 *
	 * @param array $data    Bundle.
	 * @param array $args    Argomenti.
	 * @param array $summary Riepilogo.
	 * @return array
	 */
	private static function step_terms( array $data, array $args, array $summary ): array {
		$force = ! empty( $args['force'] );

		foreach ( array( 'area_tematica', 'destinatario' ) as $taxonomy ) {
			$items = (array) ( $data['terms'][ $taxonomy ] ?? array() );

			foreach ( $items as $item ) {
				$slug = sanitize_title( (string) ( $item['slug'] ?? '' ) );
				$name = sanitize_text_field( (string) ( $item['name'] ?? '' ) );
				if ( '' === $slug || '' === $name ) {
					$summary['errors'][] = sprintf( '%s: termine senza slug o nome (%s).', $taxonomy, wp_json_encode( $item ) );
					continue;
				}

				$parent_id = 0;
				if ( ! empty( $item['parent'] ) ) {
					$parent    = get_term_by( 'slug', sanitize_title( (string) $item['parent'] ), $taxonomy );
					$parent_id = $parent instanceof WP_Term ? (int) $parent->term_id : 0;
					if ( ! $parent_id ) {
						$summary['errors'][] = sprintf( '%s/%s: termine superiore "%s" non trovato.', $taxonomy, $slug, (string) $item['parent'] );
					}
				}

				$fields = array(
					'name'        => $name,
					'description' => wp_kses_post( (string) ( $item['description'] ?? '' ) ),
					'parent'      => $parent_id,
				);
				$meta   = array( '_ecm_order' => (int) ( $item['order'] ?? 0 ) );
				if ( 'area_tematica' === $taxonomy ) {
					$meta['_ecm_image_key'] = sanitize_title( (string) ( $item['image_key'] ?? '' ) );
					$meta['_ecm_short']     = ECM_Meta::sanitize_short( (string) ( $item['short'] ?? '' ) );
				}
				$sig = self::sig( array( $fields, $meta ) );

				$term = get_term_by( 'slug', $slug, $taxonomy );
				if ( $term instanceof WP_Term ) {
					if ( ! $force && get_term_meta( $term->term_id, '_ecm_import_sig', true ) === $sig ) {
						++$summary['skipped'];
						continue;
					}
					$result = wp_update_term( $term->term_id, $taxonomy, wp_slash( $fields ) );
					if ( is_wp_error( $result ) ) {
						$summary['errors'][] = sprintf( '%s/%s: %s', $taxonomy, $slug, $result->get_error_message() );
						continue;
					}
					$term_id = (int) $term->term_id;
					++$summary['updated'];
					$summary['log'][] = sprintf( 'Aggiornato %s "%s"', $taxonomy, $name );
				} else {
					$result = wp_insert_term( wp_slash( $name ), $taxonomy, wp_slash( array_merge( $fields, array( 'slug' => $slug ) ) ) );
					if ( is_wp_error( $result ) ) {
						$summary['errors'][] = sprintf( '%s/%s: %s', $taxonomy, $slug, $result->get_error_message() );
						continue;
					}
					$term_id = (int) $result['term_id'];
					++$summary['created'];
					$summary['log'][] = sprintf( 'Creato %s "%s"', $taxonomy, $name );
				}

				foreach ( $meta as $key => $value ) {
					update_term_meta( $term_id, $key, $value );
				}
				update_term_meta( $term_id, '_ecm_import_sig', $sig );
			}
		}
		return $summary;
	}

	/* ------------------------------------------------------------------
	 * Step: media.
	 * ------------------------------------------------------------------ */

	/**
	 * Carica i file nella Media Library (a blocchi: offset/limit).
	 *
	 * @param array $data    Bundle.
	 * @param array $args    offset, limit (predefinito 5), time_budget (secondi, predefinito 20).
	 * @param array $summary Riepilogo.
	 * @return array
	 */
	private static function step_media( array $data, array $args, array $summary ): array {
		$items    = array_values( (array) ( $data['media'] ?? array() ) );
		$total    = count( $items );
		$offset   = max( 0, (int) ( $args['offset'] ?? 0 ) );
		$limit    = (int) ( $args['limit'] ?? 5 );
		$limit    = $limit > 0 ? $limit : $total;
		$deadline = microtime( true ) + (float) ( $args['time_budget'] ?? 20 );
		$map      = self::media_map();
		$dir      = (string) $data['__dir'];
		$end      = min( $total, $offset + $limit );
		$i        = $offset;

		for ( ; $i < $end; $i++ ) {
			if ( $i > $offset && microtime( true ) > $deadline ) {
				$summary['log'][] = 'Tempo disponibile esaurito: il prossimo blocco riprenderà da qui.';
				break;
			}
			$item  = (array) $items[ $i ];
			$key   = sanitize_text_field( (string) ( $item['key'] ?? '' ) );
			$kind  = sanitize_key( (string) ( $item['kind'] ?? '' ) );
			$title = sanitize_text_field( (string) ( $item['title'] ?? '' ) );
			$alt   = sanitize_text_field( (string) ( $item['alt'] ?? '' ) );
			$pages = isset( $item['pages'] ) ? absint( $item['pages'] ) : 0;

			if ( '' === $key ) {
				$summary['errors'][] = sprintf( 'Media #%d senza "key".', $i );
				continue;
			}

			// Già importato: aggiorna solo titolo, alt e pagine.
			if ( isset( $map[ $key ] ) ) {
				$id      = $map[ $key ];
				$changed = false;
				$post    = get_post( $id );
				if ( '' !== $title && $post && $post->post_title !== $title ) {
					wp_update_post(
						array(
							'ID'         => $id,
							'post_title' => wp_slash( $title ),
						)
					);
					$changed = true;
				}
				if ( '' !== $alt && get_post_meta( $id, '_wp_attachment_image_alt', true ) !== $alt ) {
					update_post_meta( $id, '_wp_attachment_image_alt', wp_slash( $alt ) );
					$changed = true;
				}
				if ( $pages && (int) get_post_meta( $id, '_ecm_pdf_pages', true ) !== $pages ) {
					update_post_meta( $id, '_ecm_pdf_pages', $pages );
					$changed = true;
				}
				if ( $changed ) {
					++$summary['updated'];
					$summary['log'][] = sprintf( 'Aggiornato media %s (ID %d)', $key, $id );
				} else {
					++$summary['skipped'];
				}
				continue;
			}

			$src = self::bundle_file( $dir, (string) ( $item['file'] ?? '' ) );
			if ( ! $src ) {
				$summary['errors'][] = sprintf( 'Media %s: file mancante nel bundle (%s).', $key, (string) ( $item['file'] ?? '' ) );
				continue;
			}

			// Copia temporanea: media_handle_sideload sposta il file e il bundle deve restare intatto.
			$filename = sanitize_file_name( wp_basename( $src ) );
			$tmp      = wp_tempnam( $filename );
			if ( ! $tmp || ! copy( $src, $tmp ) ) {
				$summary['errors'][] = sprintf( 'Media %s: impossibile creare la copia temporanea.', $key );
				continue;
			}

			if ( 'logo' === $kind && preg_match( '/\.(png|jpe?g|webp|gif)$/i', $filename ) ) {
				if ( ECM_Media::trim_logo( $tmp ) ) {
					$summary['log'][] = sprintf( 'Logo %s rifilato.', $key );
				}
			}

			$id = media_handle_sideload(
				array(
					'name'     => $filename,
					'tmp_name' => $tmp,
				),
				0,
				null,
				array( 'post_title' => '' !== $title ? $title : preg_replace( '/\.[^.]+$/', '', $filename ) )
			);

			if ( is_wp_error( $id ) ) {
				if ( file_exists( $tmp ) ) {
					wp_delete_file( $tmp );
				}
				$summary['errors'][] = sprintf( 'Media %s: %s', $key, $id->get_error_message() );
				continue;
			}

			$id = (int) $id;
			update_post_meta( $id, '_ecm_import_key', $key );
			update_post_meta( $id, '_ecm_import_kind', $kind );
			if ( '' !== $alt ) {
				update_post_meta( $id, '_wp_attachment_image_alt', wp_slash( $alt ) );
			}
			if ( 'application/pdf' === get_post_mime_type( $id ) ) {
				if ( ! $pages ) {
					$file  = get_attached_file( $id );
					$pages = $file ? (int) ECM_Media::pdf_page_count( $file ) : 0;
				}
				if ( $pages ) {
					update_post_meta( $id, '_ecm_pdf_pages', $pages );
				}
			}

			$map[ $key ] = $id;
			++$summary['created'];
			$summary['log'][] = sprintf( 'Caricato media %s (ID %d)', $key, $id );
		}

		$summary['offset']      = $offset;
		$summary['next_offset'] = $i;
		$summary['total']       = $total;
		$summary['done']        = $i >= $total;
		return $summary;
	}

	/* ------------------------------------------------------------------
	 * Step: corsi.
	 * ------------------------------------------------------------------ */

	/**
	 * Corsi (chiave di idempotenza: _ecm_source_slug).
	 *
	 * @param array $data    Bundle.
	 * @param array $args    force, preserve_manual.
	 * @param array $summary Riepilogo.
	 * @return array
	 */
	private static function step_courses( array $data, array $args, array $summary ): array {
		$media    = self::media_map();
		$existing = self::map_by_meta( 'corso', '_ecm_source_slug' );

		foreach ( (array) ( $data['courses'] ?? array() ) as $item ) {
			$item   = (array) $item;
			$source = sanitize_text_field( (string) ( $item['source_slug'] ?? ( $item['slug'] ?? '' ) ) );
			$title  = sanitize_text_field( (string) ( $item['title'] ?? '' ) );
			if ( '' === $source || '' === $title ) {
				$summary['errors'][] = sprintf( 'Corso senza source_slug o titolo: %s', mb_substr( (string) wp_json_encode( $item ), 0, 120 ) );
				continue;
			}
			$label  = sprintf( 'Corso "%s"', $title );
			$errors = array();

			$cover     = self::media_id( $media, (string) ( $item['cover'] ?? '' ), $label . ' copertina', $errors );
			$brochure  = self::media_id( $media, (string) ( $item['brochure'] ?? '' ), $label . ' brochure', $errors );
			$preview   = self::media_id( $media, (string) ( $item['brochure_preview'] ?? '' ), $label . ' anteprima brochure', $errors );
			$patrocini = array();
			foreach ( (array) ( $item['patrocini'] ?? array() ) as $row ) {
				$row      = (array) $row;
				$image_id = self::media_id( $media, (string) ( $row['media'] ?? '' ), $label . ' patrocinio', $errors );
				$name     = sanitize_text_field( (string) ( $row['name'] ?? '' ) );
				if ( $image_id || '' !== $name ) {
					$patrocini[] = array(
						'image_id' => (int) $image_id,
						'name'     => $name,
					);
				}
			}
			$faq = array();
			foreach ( (array) ( $item['faq'] ?? array() ) as $row ) {
				$row   = (array) $row;
				$faq[] = array(
					'q' => (string) ( $row['q'] ?? '' ),
					'a' => (string) ( $row['a_html'] ?? ( $row['a'] ?? '' ) ),
				);
			}

			$status = (string) ( $item['status'] ?? 'publish' );
			$status = in_array( $status, array( 'publish', 'draft', 'pending', 'private' ), true ) ? $status : 'publish';
			$post   = array(
				'post_title'   => $title,
				'post_name'    => sanitize_title( (string) ( $item['slug'] ?? $source ) ),
				'post_content' => (string) ( $item['content_html'] ?? '' ),
				'post_excerpt' => (string) ( $item['excerpt'] ?? '' ),
				'menu_order'   => (int) ( $item['menu_order'] ?? 0 ),
				'post_status'  => $status,
			);
			$meta   = array(
				'_ecm_source_slug'          => $source,
				'_ecm_brochure_id'          => (int) $brochure,
				'_ecm_brochure_preview_id'  => (int) $preview,
				'_ecm_brochure_preview_for' => (int) $brochure,
				'_ecm_patrocini'            => ECM_Meta::sanitize_patrocini( $patrocini ),
				'_ecm_testimonianze'        => ECM_Meta::sanitize_testimonianze( (array) ( $item['testimonianze'] ?? array() ) ),
				'_ecm_per_chi'              => ECM_Meta::sanitize_html( (string) ( $item['per_chi_html'] ?? '' ) ),
				'_ecm_faq'                  => ECM_Meta::sanitize_faq( $faq ),
				'_ecm_stato'                => ECM_Meta::sanitize_stato( (string) ( $item['stato'] ?? 'disponibile' ) ),
				'_ecm_crediti'              => ECM_Meta::sanitize_crediti( (string) ( $item['crediti'] ?? '' ) ),
				'_ecm_link_iscrizione'      => esc_url_raw( (string) ( $item['link_iscrizione'] ?? '' ) ),
				'_ecm_in_evidenza'          => ECM_Meta::sanitize_flag( $item['in_evidenza'] ?? '' ),
				'_ecm_old_paths'            => ECM_Meta::sanitize_paths( (array) ( $item['old_paths'] ?? array() ) ),
				'_ecm_seo_title'            => sanitize_text_field( (string) ( $item['seo_title'] ?? '' ) ),
				'_ecm_seo_desc'             => sanitize_textarea_field( (string) ( $item['seo_desc'] ?? '' ) ),
			);
			// Riferimento non risolto: non toccare il valore esistente.
			if ( null === $brochure && '' !== (string) ( $item['brochure'] ?? '' ) ) {
				unset( $meta['_ecm_brochure_id'], $meta['_ecm_brochure_preview_for'] );
			}
			if ( null === $preview && '' !== (string) ( $item['brochure_preview'] ?? '' ) ) {
				unset( $meta['_ecm_brochure_preview_id'] );
			}

			$terms = array();
			foreach ( array(
				'area_tematica' => 'areas',
				'destinatario'  => 'destinatari',
			) as $taxonomy => $field ) {
				$terms[ $taxonomy ] = self::term_ids( $taxonomy, (array) ( $item[ $field ] ?? array() ), $label, $errors );
			}

			$thumbnail = $cover;
			if ( null === $cover && '' !== (string) ( $item['cover'] ?? '' ) ) {
				$thumbnail = null; // Non risolta: lascia quella esistente.
			} elseif ( null === $cover ) {
				$thumbnail = 0;
			}

			$summary = self::upsert( 'corso', $existing[ $source ] ?? 0, $post, $meta, $thumbnail, $terms, $args, $summary, $label );
			foreach ( $errors as $error ) {
				$summary['errors'][] = $error;
			}
		}
		return $summary;
	}

	/* ------------------------------------------------------------------
	 * Step: docenti e testimonianze.
	 * ------------------------------------------------------------------ */

	/**
	 * Docenti (chiave: _ecm_source_key).
	 *
	 * @param array $data    Bundle.
	 * @param array $args    Argomenti.
	 * @param array $summary Riepilogo.
	 * @return array
	 */
	private static function step_docenti( array $data, array $args, array $summary ): array {
		$media    = self::media_map();
		$existing = self::map_by_meta( 'docente', '_ecm_source_key' );

		foreach ( (array) ( $data['docenti'] ?? array() ) as $item ) {
			$item = (array) $item;
			$key  = sanitize_text_field( (string) ( $item['key'] ?? '' ) );
			$name = sanitize_text_field( (string) ( $item['name'] ?? '' ) );
			if ( '' === $key || '' === $name ) {
				$summary['errors'][] = sprintf( 'Docente senza key o nome: %s', mb_substr( (string) wp_json_encode( $item ), 0, 120 ) );
				continue;
			}
			$label  = sprintf( 'Docente "%s"', $name );
			$errors = array();
			$photo  = self::media_id( $media, (string) ( $item['photo'] ?? '' ), $label . ' foto', $errors );
			$cv     = self::media_id( $media, (string) ( $item['cv'] ?? '' ), $label . ' CV', $errors );

			$post = array(
				'post_title'   => $name,
				'post_content' => (string) ( $item['bio_html'] ?? '' ),
				'menu_order'   => (int) ( $item['menu_order'] ?? 0 ),
				'post_status'  => 'publish',
			);
			$meta = array(
				'_ecm_source_key' => $key,
				'_ecm_titolo'     => sanitize_text_field( (string) ( $item['titolo'] ?? '' ) ),
				'_ecm_cv_id'      => (int) $cv,
				'_ecm_cv_url'     => esc_url_raw( (string) ( $item['cv_url'] ?? '' ) ),
			);
			if ( null === $cv && '' !== (string) ( $item['cv'] ?? '' ) ) {
				unset( $meta['_ecm_cv_id'] );
			}
			$thumbnail = $photo;
			if ( null === $photo ) {
				$thumbnail = '' !== (string) ( $item['photo'] ?? '' ) ? null : 0;
			}

			$summary = self::upsert( 'docente', $existing[ $key ] ?? 0, $post, $meta, $thumbnail, array(), $args, $summary, $label );
			foreach ( $errors as $error ) {
				$summary['errors'][] = $error;
			}
		}
		return $summary;
	}

	/**
	 * Testimonianze (chiave: _ecm_source_key).
	 *
	 * @param array $data    Bundle.
	 * @param array $args    Argomenti.
	 * @param array $summary Riepilogo.
	 * @return array
	 */
	private static function step_testimonianze( array $data, array $args, array $summary ): array {
		$existing = self::map_by_meta( 'testimonianza', '_ecm_source_key' );
		$courses  = self::map_by_meta( 'corso', '_ecm_source_slug' );

		foreach ( (array) ( $data['testimonianze'] ?? array() ) as $item ) {
			$item = (array) $item;
			$key  = sanitize_text_field( (string) ( $item['key'] ?? '' ) );
			$name = sanitize_text_field( (string) ( $item['name'] ?? '' ) );
			if ( '' === $key || '' === $name ) {
				$summary['errors'][] = sprintf( 'Testimonianza senza key o nome: %s', mb_substr( (string) wp_json_encode( $item ), 0, 120 ) );
				continue;
			}
			$label    = sprintf( 'Testimonianza "%s"', $name );
			$corso_id = 0;
			$slug     = (string) ( $item['corso_slug'] ?? '' );
			if ( '' !== $slug ) {
				$corso_id = (int) ( $courses[ $slug ] ?? 0 );
				if ( ! $corso_id ) {
					$found    = get_page_by_path( sanitize_title( $slug ), OBJECT, 'corso' );
					$corso_id = $found instanceof WP_Post ? (int) $found->ID : 0;
				}
				if ( ! $corso_id ) {
					$summary['log'][] = sprintf( '%s: corso "%s" non trovato, uso l\'etichetta.', $label, $slug );
				}
			}

			$post = array(
				'post_title'   => $name,
				'post_content' => sanitize_textarea_field( (string) ( $item['text'] ?? '' ) ),
				'menu_order'   => (int) ( $item['menu_order'] ?? 0 ),
				'post_status'  => 'publish',
			);
			$meta = array(
				'_ecm_source_key'  => $key,
				'_ecm_corso_id'    => $corso_id,
				'_ecm_corso_label' => sanitize_text_field( (string) ( $item['corso_label'] ?? '' ) ),
				'_ecm_fonte'       => ECM_Meta::sanitize_fonte( (string) ( $item['fonte'] ?? 'entrambe' ) ),
				'_ecm_rating'      => ECM_Meta::sanitize_rating( $item['rating'] ?? '' ),
			);

			$summary = self::upsert( 'testimonianza', $existing[ $key ] ?? 0, $post, $meta, null, array(), $args, $summary, $label );
		}
		return $summary;
	}

	/* ------------------------------------------------------------------
	 * Step: pagine.
	 * ------------------------------------------------------------------ */

	/**
	 * Pagine (chiave: slug) e pagina iniziale statica.
	 *
	 * @param array $data    Bundle.
	 * @param array $args    Argomenti.
	 * @param array $summary Riepilogo.
	 * @return array
	 */
	private static function step_pages( array $data, array $args, array $summary ): array {
		$existing = self::map_by_meta( 'page', '_ecm_source_slug' );
		$ids      = array();

		foreach ( (array) ( $data['pages'] ?? array() ) as $item ) {
			$item  = (array) $item;
			$slug  = sanitize_title( (string) ( $item['slug'] ?? '' ) );
			$title = sanitize_text_field( (string) ( $item['title'] ?? '' ) );
			if ( '' === $slug || '' === $title ) {
				$summary['errors'][] = sprintf( 'Pagina senza slug o titolo: %s', mb_substr( (string) wp_json_encode( $item ), 0, 120 ) );
				continue;
			}

			$page_id = (int) ( $existing[ $slug ] ?? 0 );
			if ( ! $page_id ) {
				$found   = get_page_by_path( $slug, OBJECT, 'page' );
				$page_id = $found instanceof WP_Post && 'trash' !== $found->post_status ? (int) $found->ID : 0;
			}

			$parent_id = 0;
			if ( ! empty( $item['parent'] ) ) {
				$parent    = get_page_by_path( sanitize_title( (string) $item['parent'] ), OBJECT, 'page' );
				$parent_id = $parent instanceof WP_Post ? (int) $parent->ID : 0;
			}

			$post = array(
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => (string) ( $item['content_html'] ?? '' ),
				'menu_order'   => (int) ( $item['menu_order'] ?? 0 ),
				'post_parent'  => $parent_id,
				'post_status'  => 'publish',
			);
			$meta = array(
				'_ecm_source_slug'  => $slug,
				'_wp_page_template' => '' !== (string) ( $item['template'] ?? '' ) ? sanitize_text_field( (string) $item['template'] ) : 'default',
				'_ecm_seo_title'    => sanitize_text_field( (string) ( $item['seo_title'] ?? '' ) ),
				'_ecm_seo_desc'     => sanitize_textarea_field( (string) ( $item['seo_desc'] ?? '' ) ),
			);

			$before  = $summary['created'] + $summary['updated'] + $summary['skipped'];
			$summary = self::upsert( 'page', $page_id, $post, $meta, null, array(), $args, $summary, sprintf( 'Pagina "%s"', $title ) );
			if ( $summary['created'] + $summary['updated'] + $summary['skipped'] > $before ) {
				$found       = self::map_by_meta( 'page', '_ecm_source_slug' );
				$ids[ $slug ] = (int) ( $found[ $slug ] ?? 0 );
			}
		}

		if ( ! empty( $ids['home'] ) ) {
			if ( 'page' !== get_option( 'show_on_front' ) || (int) get_option( 'page_on_front' ) !== $ids['home'] ) {
				update_option( 'show_on_front', 'page' );
				update_option( 'page_on_front', $ids['home'] );
				$summary['log'][] = sprintf( 'Pagina iniziale statica impostata: "home" (ID %d).', $ids['home'] );
			}
		}
		foreach ( array( 'privacy', 'privacy-policy' ) as $privacy_slug ) {
			if ( ! empty( $ids[ $privacy_slug ] ) && (int) get_option( 'wp_page_for_privacy_policy' ) !== $ids[ $privacy_slug ] ) {
				update_option( 'wp_page_for_privacy_policy', $ids[ $privacy_slug ] );
				$summary['log'][] = sprintf( 'Pagina privacy impostata: "%s".', $privacy_slug );
				break;
			}
		}
		return $summary;
	}

	/* ------------------------------------------------------------------
	 * Step: menu.
	 * ------------------------------------------------------------------ */

	/**
	 * Menu "Principale" e "Footer" assegnati alle posizioni primary e footer.
	 *
	 * Voci: { label, type: archive|page|corso|area|term|url|home, target, new_tab?, classes?, children? }.
	 *
	 * @param array $data    Bundle.
	 * @param array $args    Argomenti.
	 * @param array $summary Riepilogo.
	 * @return array
	 */
	private static function step_menus( array $data, array $args, array $summary ): array {
		$force     = ! empty( $args['force'] );
		$locations = array();

		foreach ( (array) ( $data['menus'] ?? array() ) as $location => $items ) {
			$location = sanitize_key( (string) $location );
			$name     = self::MENU_NAMES[ $location ] ?? ucfirst( $location );
			$errors   = array();
			$resolved = self::resolve_menu_items( (array) $items, $errors );
			foreach ( $errors as $error ) {
				$summary['errors'][] = sprintf( 'Menu %s: %s', $name, $error );
			}

			$menu    = wp_get_nav_menu_object( $name );
			$created = false;
			if ( $menu ) {
				$menu_id = (int) $menu->term_id;
			} else {
				$menu_id = wp_create_nav_menu( $name );
				if ( is_wp_error( $menu_id ) ) {
					$summary['errors'][] = sprintf( 'Menu %s: %s', $name, $menu_id->get_error_message() );
					continue;
				}
				$menu_id = (int) $menu_id;
				$created = true;
			}
			$locations[ $location ] = $menu_id;

			$sig     = self::sig( $resolved );
			$current = wp_get_nav_menu_items( $menu_id, array( 'post_status' => 'any' ) );
			if ( ! $created && ! $force && get_term_meta( $menu_id, '_ecm_import_sig', true ) === $sig && is_array( $current ) && count( $current ) === self::count_menu_items( $resolved ) ) {
				++$summary['skipped'];
				continue;
			}

			foreach ( is_array( $current ) ? $current : array() as $menu_item ) {
				wp_delete_post( (int) $menu_item->ID, true );
			}
			$position = 0;
			self::add_menu_items( $menu_id, $resolved, 0, $position, $summary );
			update_term_meta( $menu_id, '_ecm_import_sig', $sig );

			if ( $created ) {
				++$summary['created'];
			} else {
				++$summary['updated'];
			}
			$summary['log'][] = sprintf( 'Menu "%s": %d voci.', $name, $position );
		}

		if ( $locations ) {
			$mods = get_theme_mod( 'nav_menu_locations', array() );
			$mods = is_array( $mods ) ? $mods : array();
			set_theme_mod( 'nav_menu_locations', array_merge( $mods, $locations ) );

			// Se il tema "ecm" è installato ma non ancora attivo, prepara anche le sue posizioni.
			$theme = wp_get_theme( 'ecm' );
			if ( $theme->exists() && 'ecm' !== get_stylesheet() ) {
				$ecm_mods = get_option( 'theme_mods_ecm', array() );
				$ecm_mods = is_array( $ecm_mods ) ? $ecm_mods : array();
				$ecm_loc  = isset( $ecm_mods['nav_menu_locations'] ) && is_array( $ecm_mods['nav_menu_locations'] ) ? $ecm_mods['nav_menu_locations'] : array();

				$ecm_mods['nav_menu_locations'] = array_merge( $ecm_loc, $locations );
				update_option( 'theme_mods_ecm', $ecm_mods );
			}
			$summary['log'][] = sprintf( 'Posizioni assegnate: %s.', implode( ', ', array_keys( $locations ) ) );
		}
		return $summary;
	}

	/**
	 * Risolve le voci di menu in argomenti per wp_update_nav_menu_item.
	 *
	 * @param array $items  Voci.
	 * @param array $errors Errori (per riferimento).
	 * @return array
	 */
	private static function resolve_menu_items( array $items, array &$errors ): array {
		$out = array();
		foreach ( $items as $item ) {
			$item   = (array) $item;
			$label  = sanitize_text_field( (string) ( $item['label'] ?? '' ) );
			$type   = sanitize_key( (string) ( $item['type'] ?? 'url' ) );
			$target = (string) ( $item['target'] ?? '' );
			$args   = array(
				'menu-item-title'   => $label,
				'menu-item-status'  => 'publish',
				'menu-item-target'  => ! empty( $item['new_tab'] ) ? '_blank' : '',
				'menu-item-classes' => sanitize_text_field( (string) ( $item['classes'] ?? '' ) ),
			);

			switch ( $type ) {
				case 'archive':
					$post_type = sanitize_key( '' !== $target ? $target : 'corso' );
					if ( ! post_type_exists( $post_type ) ) {
						$errors[] = sprintf( 'tipo di contenuto "%s" inesistente.', $post_type );
						continue 2;
					}
					$args['menu-item-type']   = 'post_type_archive';
					$args['menu-item-object'] = $post_type;
					break;

				case 'page':
				case 'corso':
					$post_type = 'page' === $type ? 'page' : 'corso';
					$post_id   = 0;
					if ( 'corso' === $post_type ) {
						$map     = self::map_by_meta( 'corso', '_ecm_source_slug' );
						$post_id = (int) ( $map[ $target ] ?? 0 );
					}
					if ( ! $post_id ) {
						$found   = get_page_by_path( sanitize_title( $target ), OBJECT, $post_type );
						$post_id = $found instanceof WP_Post ? (int) $found->ID : 0;
					}
					if ( ! $post_id ) {
						$errors[] = sprintf( '%s "%s" non trovata per la voce "%s".', 'page' === $type ? 'pagina' : 'corso', $target, $label );
						continue 2;
					}
					$args['menu-item-type']      = 'post_type';
					$args['menu-item-object']    = $post_type;
					$args['menu-item-object-id'] = $post_id;
					break;

				case 'area':
				case 'term':
					$taxonomy = 'area_tematica';
					$slug     = $target;
					if ( str_contains( $target, ':' ) ) {
						list( $taxonomy, $slug ) = array_map( 'trim', explode( ':', $target, 2 ) );
					}
					$term = get_term_by( 'slug', sanitize_title( $slug ), sanitize_key( $taxonomy ) );
					if ( ! $term instanceof WP_Term ) {
						$errors[] = sprintf( 'termine "%s" non trovato per la voce "%s".', $target, $label );
						continue 2;
					}
					$args['menu-item-type']      = 'taxonomy';
					$args['menu-item-object']    = $term->taxonomy;
					$args['menu-item-object-id'] = (int) $term->term_id;
					break;

				case 'home':
					$args['menu-item-type'] = 'custom';
					$args['menu-item-url']  = home_url( '/' );
					break;

				case 'url':
				default:
					if ( '' === $target ) {
						$errors[] = sprintf( 'voce "%s" senza URL.', $label );
						continue 2;
					}
					$args['menu-item-type'] = 'custom';
					$args['menu-item-url']  = preg_match( '#^(https?:|mailto:|tel:|\#)#i', $target ) ? esc_url_raw( $target, array( 'http', 'https', 'mailto', 'tel' ) ) : home_url( '/' . ltrim( $target, '/' ) );
					break;
			}

			$args['children'] = ! empty( $item['children'] ) ? self::resolve_menu_items( (array) $item['children'], $errors ) : array();
			$out[]            = $args;
		}
		return $out;
	}

	/**
	 * Aggiunge le voci (ricorsivo per i sottomenu).
	 *
	 * @param int   $menu_id  Menu.
	 * @param array $items    Voci risolte.
	 * @param int   $parent   Voce superiore.
	 * @param int   $position Posizione corrente (per riferimento).
	 * @param array $summary  Riepilogo (per riferimento).
	 */
	private static function add_menu_items( int $menu_id, array $items, int $parent, int &$position, array &$summary ): void {
		foreach ( $items as $args ) {
			$children = $args['children'] ?? array();
			unset( $args['children'] );
			++$position;
			$args['menu-item-parent-id'] = $parent;
			$args['menu-item-position']  = $position;
			$item_id                     = wp_update_nav_menu_item( $menu_id, 0, $args );
			if ( is_wp_error( $item_id ) ) {
				$summary['errors'][] = sprintf( 'Voce "%s": %s', $args['menu-item-title'], $item_id->get_error_message() );
				continue;
			}
			if ( $children ) {
				self::add_menu_items( $menu_id, $children, (int) $item_id, $position, $summary );
			}
		}
	}

	/**
	 * Numero totale di voci (con sottovoci).
	 *
	 * @param array $items Voci risolte.
	 * @return int
	 */
	private static function count_menu_items( array $items ): int {
		$n = 0;
		foreach ( $items as $item ) {
			$n += 1 + self::count_menu_items( $item['children'] ?? array() );
		}
		return $n;
	}

	/* ------------------------------------------------------------------
	 * Step: opzioni e redirect.
	 * ------------------------------------------------------------------ */

	/**
	 * Unisce le opzioni del bundle a ecm_options senza cancellare le altre chiavi.
	 *
	 * @param array $data    Bundle.
	 * @param array $args    Argomenti.
	 * @param array $summary Riepilogo.
	 * @return array
	 */
	private static function step_options( array $data, array $args, array $summary ): array {
		$media  = self::media_map();
		$values = array();
		foreach ( (array) ( $data['options'] ?? array() ) as $key => $value ) {
			$resolved = self::resolve_placeholders( $value, $media );
			if ( null === $resolved ) {
				$summary['errors'][] = sprintf( 'Opzione %s non aggiornata: media non trovato in %s (eseguire prima lo step media).', $key, is_scalar( $value ) ? (string) $value : 'valore' );
				continue;
			}
			$values[ $key ] = $resolved;
		}
		$result             = ECM_Options::merge( $values );
		$summary['updated'] = count( $result['updated'] );
		$summary['skipped'] = count( $result['skipped'] );
		if ( $result['updated'] ) {
			$summary['log'][] = 'Aggiornate: ' . implode( ', ', $result['updated'] );
		}
		if ( $result['unknown'] ) {
			$summary['log'][] = 'Chiavi fuori schema salvate come testo: ' . implode( ', ', $result['unknown'] );
		}
		return $summary;
	}

	/**
	 * Sostituisce i segnaposto {media:KEY} con l'URL dell'allegato importato con quella chiave.
	 *
	 * @param mixed $value Valore (stringa o array).
	 * @param array $media Mappa _ecm_import_key => ID.
	 * @return mixed Valore risolto, null se un segnaposto non è risolvibile.
	 */
	public static function resolve_placeholders( $value, array $media ) {
		if ( is_array( $value ) ) {
			$out = array();
			foreach ( $value as $k => $v ) {
				$resolved = self::resolve_placeholders( $v, $media );
				if ( null === $resolved ) {
					return null;
				}
				$out[ $k ] = $resolved;
			}
			return $out;
		}
		if ( ! is_string( $value ) || false === strpos( $value, '{media:' ) ) {
			return $value;
		}
		$failed   = false;
		$replaced = preg_replace_callback(
			'/\{media:([^}\s]+)\}/',
			static function ( $m ) use ( $media, &$failed ) {
				$id  = (int) ( $media[ trim( $m[1] ) ] ?? 0 );
				$url = $id ? wp_get_attachment_url( $id ) : false;
				if ( ! $url ) {
					$failed = true;
					return $m[0];
				}
				return $url;
			},
			$value
		);
		return $failed ? null : (string) $replaced;
	}

	/**
	 * Unisce i redirect del bundle alla tabella ecm_redirects.
	 *
	 * @param array $data    Bundle.
	 * @param array $args    Argomenti.
	 * @param array $summary Riepilogo.
	 * @return array
	 */
	private static function step_redirects( array $data, array $args, array $summary ): array {
		$media = self::media_map();
		$pairs = array();
		foreach ( (array) ( $data['redirects'] ?? array() ) as $key => $pair ) {
			$to       = is_array( $pair ) ? ( $pair['to'] ?? '' ) : $pair;
			$resolved = self::resolve_placeholders( $to, $media );
			if ( null === $resolved ) {
				$summary['errors'][] = sprintf( 'Redirect %s non importato: media non trovato in %s (eseguire prima lo step media).', is_array( $pair ) ? (string) ( $pair['from'] ?? '' ) : (string) $key, (string) $to );
				continue;
			}
			if ( is_array( $pair ) ) {
				$pair['to']    = $resolved;
				$pairs[]       = $pair;
			} else {
				$pairs[ $key ] = $resolved;
			}
		}
		$result             = ECM_Redirects::merge( $pairs );
		$summary['created'] = $result['created'];
		$summary['updated'] = $result['updated'];
		$summary['skipped'] = $result['skipped'];
		$summary['errors']  = array_merge( $summary['errors'], $result['errors'] );
		return $summary;
	}

	/* ------------------------------------------------------------------
	 * Step: verifica.
	 * ------------------------------------------------------------------ */

	/**
	 * Conteggi e problemi.
	 *
	 * @param array $data    Bundle.
	 * @param array $args    Argomenti.
	 * @param array $summary Riepilogo.
	 * @return array
	 */
	private static function step_verify( array $data, array $args, array $summary ): array {
		$problems = array();
		$media    = self::media_map();

		foreach ( (array) ( $data['media'] ?? array() ) as $item ) {
			$key = (string) ( $item['key'] ?? '' );
			if ( '' !== $key && ! isset( $media[ $key ] ) ) {
				$problems[] = sprintf( 'Media non importato: %s', $key );
			}
		}
		foreach ( $media as $key => $id ) {
			$file = get_attached_file( $id );
			if ( ! $file || ! file_exists( $file ) ) {
				$problems[] = sprintf( 'File mancante sul server per il media %s (ID %d).', $key, $id );
			}
		}

		$course_map = self::map_by_meta( 'corso', '_ecm_source_slug' );
		foreach ( (array) ( $data['courses'] ?? array() ) as $item ) {
			$source = (string) ( $item['source_slug'] ?? '' );
			if ( '' !== $source && empty( $course_map[ $source ] ) ) {
				$problems[] = sprintf( 'Corso del bundle non presente nel sito: %s', $source );
			}
		}

		$courses = get_posts(
			array(
				'post_type'        => 'corso',
				'post_status'      => 'publish',
				'posts_per_page'   => -1,
				'suppress_filters' => true,
				'no_found_rows'    => true,
			)
		);
		update_meta_cache( 'post', wp_list_pluck( $courses, 'ID' ) );
		foreach ( $courses as $course ) {
			$title = ecm_plain_title( $course );
			if ( ! has_post_thumbnail( $course ) ) {
				$problems[] = sprintf( 'Corso senza copertina: %s', $title );
			}
			$brochure = (int) get_post_meta( $course->ID, '_ecm_brochure_id', true );
			if ( ! $brochure ) {
				$problems[] = sprintf( 'Corso senza brochure: %s', $title );
			} elseif ( 'attachment' !== get_post_type( $brochure ) ) {
				$problems[] = sprintf( 'Brochure collegata ma allegato inesistente: %s', $title );
			} else {
				$file = get_attached_file( $brochure );
				if ( ! $file || ! file_exists( $file ) ) {
					$problems[] = sprintf( 'File della brochure mancante: %s', $title );
				}
			}
			$areas = get_the_terms( $course, 'area_tematica' );
			if ( ! $areas || is_wp_error( $areas ) ) {
				$problems[] = sprintf( 'Corso senza area tematica: %s', $title );
			}
		}

		$front = (int) get_option( 'page_on_front' );
		if ( 'page' !== get_option( 'show_on_front' ) || ! $front ) {
			$problems[] = 'La pagina iniziale statica non è impostata (step "pages" con una pagina "home").';
		}
		$locations = get_nav_menu_locations();
		foreach ( array_keys( (array) ( $data['menus'] ?? array() ) ) as $location ) {
			if ( empty( $locations[ $location ] ) ) {
				$problems[] = sprintf( 'Nessun menu assegnato alla posizione "%s".', $location );
			}
		}

		$broken = 0;
		foreach ( (array) ( $data['courses'] ?? array() ) as $item ) {
			$source = (string) ( $item['source_slug'] ?? '' );
			$id     = (int) ( $course_map[ $source ] ?? 0 );
			if ( ! $id || 'publish' !== get_post_status( $id ) ) {
				continue;
			}
			$permalink = (string) get_permalink( $id );
			foreach ( (array) ( $item['old_paths'] ?? array() ) as $old ) {
				$target = ECM_Redirects::resolve( (string) $old );
				$same   = ECM_Redirects::normalize( (string) $old ) === ECM_Redirects::normalize( $permalink );
				if ( ! $same && $target !== $permalink ) {
					++$broken;
					$problems[] = sprintf( 'Redirect non risolto: %s', (string) $old );
				}
			}
		}

		$counts = array(
			'corsi'          => count( $courses ),
			'docenti'        => (int) ( wp_count_posts( 'docente' )->publish ?? 0 ),
			'testimonianze'  => (int) ( wp_count_posts( 'testimonianza' )->publish ?? 0 ),
			'aree'           => (int) wp_count_terms( array( 'taxonomy' => 'area_tematica', 'hide_empty' => false ) ),
			'destinatari'    => (int) wp_count_terms( array( 'taxonomy' => 'destinatario', 'hide_empty' => false ) ),
			'media_import'   => count( $media ),
			'pagine'         => (int) ( wp_count_posts( 'page' )->publish ?? 0 ),
			'redirect_admin' => count( ECM_Redirects::table() ),
			'redirect_ko'    => $broken,
		);

		$summary['counts']   = $counts;
		$summary['bundle']   = array(
			'media'         => count( (array) ( $data['media'] ?? array() ) ),
			'corsi'         => count( (array) ( $data['courses'] ?? array() ) ),
			'docenti'       => count( (array) ( $data['docenti'] ?? array() ) ),
			'testimonianze' => count( (array) ( $data['testimonianze'] ?? array() ) ),
			'pagine'        => count( (array) ( $data['pages'] ?? array() ) ),
		);
		$summary['problems'] = $problems;
		$summary['ok']       = ! $problems;
		foreach ( $counts as $label => $value ) {
			$summary['log'][] = sprintf( '%s: %d', $label, $value );
		}
		return $summary;
	}

	/* ------------------------------------------------------------------
	 * Motore di creazione/aggiornamento.
	 * ------------------------------------------------------------------ */

	/**
	 * Crea o aggiorna un post in modo idempotente.
	 *
	 * - Se la firma dei dati coincide con l'ultima importazione: invariato (salvo force).
	 * - Con preserve_manual: i campi il cui valore attuale differisce dall'ultimo importato
	 *   (quindi modificati a mano) non vengono sovrascritti.
	 *
	 * @param string   $post_type Tipo.
	 * @param int      $post_id   ID esistente o 0.
	 * @param array    $post      Campi del post.
	 * @param array    $meta      Meta.
	 * @param int|null $thumbnail ID immagine, 0 per rimuovere, null per non toccare.
	 * @param array    $terms     Tassonomia => ID termini.
	 * @param array    $args      force, preserve_manual.
	 * @param array    $summary   Riepilogo.
	 * @param string   $label     Etichetta per il log.
	 * @return array Riepilogo aggiornato.
	 */
	private static function upsert( string $post_type, int $post_id, array $post, array $meta, ?int $thumbnail, array $terms, array $args, array $summary, string $label ): array {
		$force    = ! empty( $args['force'] );
		$preserve = ! empty( $args['preserve_manual'] );

		$fields = array();
		foreach ( $post as $key => $value ) {
			$fields[ 'post:' . $key ] = $value;
		}
		foreach ( $meta as $key => $value ) {
			$fields[ 'meta:' . $key ] = $value;
		}
		if ( null !== $thumbnail ) {
			$fields['thumbnail'] = (int) $thumbnail;
		}
		foreach ( $terms as $taxonomy => $ids ) {
			$ids = array_map( 'intval', (array) $ids );
			sort( $ids );
			$fields[ 'tax:' . $taxonomy ] = $ids;
		}
		$sig = self::sig( $fields );

		if ( $post_id ) {
			$current_post = get_post( $post_id );
			if ( ! $current_post instanceof WP_Post ) {
				$post_id = 0;
			} elseif ( 'trash' === $current_post->post_status ) {
				++$summary['skipped'];
				$summary['log'][] = sprintf( '%s è nel cestino: non modificato.', $label );
				return $summary;
			} elseif ( ! $force && get_post_meta( $post_id, '_ecm_import_sig', true ) === $sig ) {
				++$summary['skipped'];
				return $summary;
			}
		}

		$preserved = array();
		if ( $post_id && $preserve ) {
			$hashes = get_post_meta( $post_id, '_ecm_import_hashes', true );
			$hashes = is_array( $hashes ) ? $hashes : array();
			$now    = self::current_values( $post_id, array_keys( $fields ) );
			foreach ( array_keys( $fields ) as $field ) {
				if ( isset( $hashes[ $field ] ) && $hashes[ $field ] !== self::hash( $now[ $field ] ?? null ) ) {
					$preserved[] = $field;
					unset( $fields[ $field ] );
				}
			}
		}

		$postarr = array( 'post_type' => $post_type );
		foreach ( $fields as $field => $value ) {
			if ( str_starts_with( $field, 'post:' ) ) {
				$postarr[ substr( $field, 5 ) ] = $value;
			}
		}

		if ( $post_id ) {
			$postarr['ID'] = $post_id;
			$result        = count( $postarr ) > 2 ? wp_update_post( wp_slash( $postarr ), true ) : $post_id;
		} else {
			$postarr['post_status'] = $postarr['post_status'] ?? 'publish';
			$result                 = wp_insert_post( wp_slash( $postarr ), true );
		}
		if ( is_wp_error( $result ) ) {
			$summary['errors'][] = sprintf( '%s: %s', $label, $result->get_error_message() );
			return $summary;
		}
		$is_new  = ! $post_id;
		$post_id = (int) $result;

		foreach ( $fields as $field => $value ) {
			if ( str_starts_with( $field, 'meta:' ) ) {
				update_post_meta( $post_id, substr( $field, 5 ), wp_slash( $value ) );
			} elseif ( 'thumbnail' === $field ) {
				if ( $value ) {
					set_post_thumbnail( $post_id, (int) $value );
				} else {
					delete_post_thumbnail( $post_id );
				}
			} elseif ( str_starts_with( $field, 'tax:' ) ) {
				$set = wp_set_object_terms( $post_id, $value, substr( $field, 4 ) );
				if ( is_wp_error( $set ) ) {
					$summary['errors'][] = sprintf( '%s: %s', $label, $set->get_error_message() );
				}
			}
		}

		clean_post_cache( $post_id );
		$all_fields = array_keys( self::sig_fields( $post, $meta, $thumbnail, $terms ) );
		$values     = self::current_values( $post_id, $all_fields );
		$old_hashes = get_post_meta( $post_id, '_ecm_import_hashes', true );
		$hashes     = is_array( $old_hashes ) ? $old_hashes : array();
		foreach ( $all_fields as $field ) {
			if ( ! in_array( $field, $preserved, true ) ) {
				$hashes[ $field ] = self::hash( $values[ $field ] ?? null );
			}
		}
		update_post_meta( $post_id, '_ecm_import_hashes', $hashes );
		update_post_meta( $post_id, '_ecm_import_sig', $sig );
		update_post_meta( $post_id, '_ecm_imported_at', time() );

		if ( $is_new ) {
			++$summary['created'];
			$summary['log'][] = sprintf( '%s: creato (ID %d).', $label, $post_id );
		} else {
			++$summary['updated'];
			$summary['log'][] = sprintf( '%s: aggiornato (ID %d)%s.', $label, $post_id, $preserved ? ' – campi modificati a mano conservati: ' . implode( ', ', $preserved ) : '' );
		}
		return $summary;
	}

	/**
	 * Elenco dei campi gestiti (stesse chiavi usate in upsert).
	 *
	 * @param array    $post      Campi post.
	 * @param array    $meta      Meta.
	 * @param int|null $thumbnail Immagine.
	 * @param array    $terms     Termini.
	 * @return array<string,bool>
	 */
	private static function sig_fields( array $post, array $meta, ?int $thumbnail, array $terms ): array {
		$fields = array();
		foreach ( array_keys( $post ) as $key ) {
			$fields[ 'post:' . $key ] = true;
		}
		foreach ( array_keys( $meta ) as $key ) {
			$fields[ 'meta:' . $key ] = true;
		}
		if ( null !== $thumbnail ) {
			$fields['thumbnail'] = true;
		}
		foreach ( array_keys( $terms ) as $taxonomy ) {
			$fields[ 'tax:' . $taxonomy ] = true;
		}
		return $fields;
	}

	/**
	 * Valori attuali nel database per i campi indicati.
	 *
	 * @param int      $post_id ID.
	 * @param string[] $fields  Campi.
	 * @return array
	 */
	private static function current_values( int $post_id, array $fields ): array {
		$post = get_post( $post_id );
		$out  = array();
		foreach ( $fields as $field ) {
			if ( str_starts_with( $field, 'post:' ) ) {
				$key           = substr( $field, 5 );
				$out[ $field ] = $post ? (string) $post->$key : '';
			} elseif ( str_starts_with( $field, 'meta:' ) ) {
				$out[ $field ] = get_post_meta( $post_id, substr( $field, 5 ), true );
			} elseif ( 'thumbnail' === $field ) {
				$out[ $field ] = (int) get_post_thumbnail_id( $post_id );
			} elseif ( str_starts_with( $field, 'tax:' ) ) {
				$ids = wp_get_object_terms( $post_id, substr( $field, 4 ), array( 'fields' => 'ids' ) );
				$ids = is_wp_error( $ids ) ? array() : array_map( 'intval', $ids );
				sort( $ids );
				$out[ $field ] = $ids;
			}
		}
		return $out;
	}

	/* ------------------------------------------------------------------
	 * Utilità.
	 * ------------------------------------------------------------------ */

	/**
	 * Mappa _ecm_import_key => ID allegato.
	 *
	 * @return array<string,int>
	 */
	public static function media_map(): array {
		return self::map_by_meta( 'attachment', '_ecm_import_key' );
	}

	/**
	 * Mappa valore meta => ID post (stati utili, cestino incluso).
	 *
	 * @param string $post_type Tipo.
	 * @param string $meta_key  Chiave.
	 * @return array<string,int>
	 */
	public static function map_by_meta( string $post_type, string $meta_key ): array {
		$statuses = 'attachment' === $post_type
			? array( 'inherit', 'private', 'publish', 'trash' )
			: array( 'publish', 'draft', 'pending', 'private', 'future', 'trash' );

		$ids = get_posts(
			array(
				'post_type'        => $post_type,
				'post_status'      => $statuses,
				'posts_per_page'   => -1,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => true,
				'orderby'          => 'ID',
				'order'            => 'ASC',
				'meta_key'         => $meta_key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			)
		);
		if ( $ids ) {
			update_meta_cache( 'post', $ids );
		}
		$map = array();
		foreach ( $ids as $id ) {
			$value = (string) get_post_meta( (int) $id, $meta_key, true );
			if ( '' !== $value && ! isset( $map[ $value ] ) ) {
				$map[ $value ] = (int) $id;
			}
		}
		return $map;
	}

	/**
	 * ID allegato da chiave media. null se la chiave è indicata ma non trovata, 0 se vuota.
	 *
	 * @param array  $map    Mappa media.
	 * @param string $key    Chiave.
	 * @param string $label  Etichetta per l'errore.
	 * @param array  $errors Errori (per riferimento).
	 * @return int|null
	 */
	private static function media_id( array $map, string $key, string $label, array &$errors ): ?int {
		$key = trim( $key );
		if ( '' === $key ) {
			return 0;
		}
		if ( isset( $map[ $key ] ) ) {
			return (int) $map[ $key ];
		}
		$errors[] = sprintf( '%s: media "%s" non trovato (eseguire prima lo step media).', $label, $key );
		return null;
	}

	/**
	 * ID dei termini da slug (senza crearne di nuovi).
	 *
	 * @param string $taxonomy Tassonomia.
	 * @param array  $slugs    Slug.
	 * @param string $label    Etichetta.
	 * @param array  $errors   Errori (per riferimento).
	 * @return int[]
	 */
	private static function term_ids( string $taxonomy, array $slugs, string $label, array &$errors ): array {
		$ids = array();
		foreach ( $slugs as $slug ) {
			$slug = sanitize_title( (string) $slug );
			if ( '' === $slug ) {
				continue;
			}
			$term = get_term_by( 'slug', $slug, $taxonomy );
			if ( $term instanceof WP_Term ) {
				$ids[] = (int) $term->term_id;
			} else {
				$errors[] = sprintf( '%s: termine %s "%s" non trovato (eseguire prima lo step terms).', $label, $taxonomy, $slug );
			}
		}
		return array_values( array_unique( $ids ) );
	}

	/**
	 * Percorso sicuro di un file del bundle.
	 *
	 * @param string $dir      Cartella bundle.
	 * @param string $relative Percorso relativo.
	 * @return string|null
	 */
	private static function bundle_file( string $dir, string $relative ): ?string {
		$relative = ltrim( str_replace( '\\', '/', trim( $relative ) ), '/' );
		if ( '' === $relative || str_contains( $relative, '..' ) ) {
			return null;
		}
		$base = realpath( $dir );
		$real = realpath( $dir . $relative );
		if ( ! $base || ! $real || ! str_starts_with( $real, $base . DIRECTORY_SEPARATOR ) || ! is_file( $real ) ) {
			return null;
		}
		return $real;
	}

	/**
	 * Firma stabile di un insieme di valori.
	 *
	 * @param mixed $values Valori.
	 * @return string
	 */
	private static function sig( $values ): string {
		return md5( (string) wp_json_encode( $values ) );
	}

	/**
	 * Hash di un valore letto dal database.
	 *
	 * @param mixed $value Valore.
	 * @return string
	 */
	private static function hash( $value ): string {
		if ( is_int( $value ) || is_float( $value ) ) {
			$value = (string) $value;
		}
		return md5( (string) wp_json_encode( $value ) );
	}

	/**
	 * Include le funzioni admin necessarie (media_handle_sideload, menu).
	 */
	private static function require_admin_includes(): void {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/nav-menu.php';
		require_once ABSPATH . 'wp-admin/includes/post.php';
	}
}
