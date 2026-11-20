<?php
/**
 * Costruzione dei dati letti dal tema (corsi, aree, docenti, testimonianze, statistiche).
 *
 * Le funzioni pubbliche per il tema sono in helpers.php; questa classe contiene
 * l'implementazione con cache per richiesta e priming delle cache di meta/termini.
 *
 * @package ECM_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Accesso ai dati con cache.
 */
final class ECM_Data {

	public const STATS_TRANSIENT = 'ecm_stats';

	/**
	 * Corsi già costruiti in questa richiesta.
	 *
	 * @var array<int,array>
	 */
	private static array $courses = array();

	/**
	 * Termini arricchiti per tassonomia.
	 *
	 * @var array<string,array<int,array>>
	 */
	private static array $terms = array();

	/**
	 * Risultati di query già eseguite.
	 *
	 * @var array<string,array>
	 */
	private static array $queries = array();

	/**
	 * Hook di invalidazione.
	 */
	public static function init(): void {
		add_action( 'save_post', array( __CLASS__, 'maybe_flush' ), 20, 2 );
		add_action( 'deleted_post', array( __CLASS__, 'maybe_flush' ), 10, 2 );
		add_action( 'trashed_post', array( __CLASS__, 'maybe_flush' ) );
		add_action( 'untrashed_post', array( __CLASS__, 'maybe_flush' ) );
		add_action( 'updated_post_meta', array( __CLASS__, 'meta_changed' ), 10, 3 );
		add_action( 'added_post_meta', array( __CLASS__, 'meta_changed' ), 10, 3 );
		add_action( 'deleted_post_meta', array( __CLASS__, 'meta_changed' ), 10, 3 );
		foreach ( array( 'created_term', 'edited_term', 'delete_term' ) as $hook ) {
			add_action( $hook, array( __CLASS__, 'flush_terms' ) );
		}
	}

	/**
	 * Invalida le cache quando cambia un contenuto del plugin.
	 *
	 * @param int          $post_id ID.
	 * @param WP_Post|null $post    Post.
	 */
	public static function maybe_flush( $post_id, $post = null ): void {
		$type = $post instanceof WP_Post ? $post->post_type : get_post_type( $post_id );
		if ( in_array( $type, array( 'corso', 'docente', 'testimonianza' ), true ) ) {
			self::flush();
		}
	}

	/**
	 * Invalida le statistiche quando cambiano i patrocini.
	 *
	 * @param int|array $meta_id  ID meta.
	 * @param int       $post_id  ID post.
	 * @param string    $meta_key Chiave.
	 */
	public static function meta_changed( $meta_id, $post_id, $meta_key ): void {
		if ( is_string( $meta_key ) && 0 === strpos( $meta_key, '_ecm_' ) ) {
			self::$courses = array();
			self::$queries = array();
			if ( '_ecm_patrocini' === $meta_key ) {
				delete_transient( self::STATS_TRANSIENT );
			}
		}
	}

	/**
	 * Svuota le cache dei termini.
	 */
	public static function flush_terms(): void {
		self::$terms   = array();
		self::$courses = array();
		self::$queries = array();
	}

	/**
	 * Svuota tutte le cache.
	 */
	public static function flush(): void {
		self::$courses = array();
		self::$terms   = array();
		self::$queries = array();
		delete_transient( self::STATS_TRANSIENT );
	}

	/* ------------------------------------------------------------------
	 * Termini.
	 * ------------------------------------------------------------------ */

	/**
	 * Termini di una tassonomia arricchiti con i meta, ordinati per _ecm_order e nome.
	 *
	 * @param string $taxonomy area_tematica|destinatario.
	 * @return array<int,array> ID termine => dati.
	 */
	public static function terms( string $taxonomy ): array {
		if ( isset( self::$terms[ $taxonomy ] ) ) {
			return self::$terms[ $taxonomy ];
		}

		$list  = array();
		$terms = get_terms(
			array(
				'taxonomy'               => $taxonomy,
				'hide_empty'             => false,
				'update_term_meta_cache' => true,
			)
		);

		if ( ! is_wp_error( $terms ) ) {
			$archive = get_post_type_archive_link( 'corso' );
			$archive = $archive ? $archive : home_url( '/corsi/' );
			$param   = 'area_tematica' === $taxonomy ? 'area' : 'destinatario';

			foreach ( $terms as $term ) {
				$item = array(
					'id'          => (int) $term->term_id,
					'slug'        => $term->slug,
					'name'        => html_entity_decode( $term->name, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
					'description' => $term->description,
					'count'       => (int) $term->count,
					'parent'      => (int) $term->parent,
					'order'       => (int) get_term_meta( $term->term_id, '_ecm_order', true ),
					'url'         => add_query_arg( $param, $term->slug, $archive ),
				);
				if ( 'area_tematica' === $taxonomy ) {
					$item['image_key'] = (string) get_term_meta( $term->term_id, '_ecm_image_key', true );
					$item['short']     = (string) get_term_meta( $term->term_id, '_ecm_short', true );
				}
				$list[ $item['id'] ] = $item;
			}

			uasort(
				$list,
				static function ( $a, $b ) {
					return array( $a['order'], strtolower( remove_accents( $a['name'] ) ) ) <=> array( $b['order'], strtolower( remove_accents( $b['name'] ) ) );
				}
			);
		}

		self::$terms[ $taxonomy ] = $list;
		return $list;
	}

	/**
	 * Termini di un post nell'ordine della tassonomia.
	 *
	 * @param int    $post_id  ID.
	 * @param string $taxonomy Tassonomia.
	 * @return array<int,array>
	 */
	private static function post_terms( int $post_id, string $taxonomy ): array {
		$assigned = get_the_terms( $post_id, $taxonomy );
		if ( ! $assigned || is_wp_error( $assigned ) ) {
			return array();
		}
		$ids = array_map( 'intval', wp_list_pluck( $assigned, 'term_id' ) );
		$out = array();
		foreach ( self::terms( $taxonomy ) as $term_id => $term ) {
			if ( in_array( $term_id, $ids, true ) ) {
				$out[] = $term;
			}
		}
		return $out;
	}

	/* ------------------------------------------------------------------
	 * Corsi.
	 * ------------------------------------------------------------------ */

	/**
	 * Dati completi di un corso.
	 *
	 * @param int|WP_Post|null $post Corso.
	 * @return array Vuoto se non è un corso.
	 */
	public static function course( $post = null ): array {
		$post = get_post( $post );
		if ( ! $post instanceof WP_Post || 'corso' !== $post->post_type ) {
			return array();
		}
		if ( isset( self::$courses[ $post->ID ] ) ) {
			return self::$courses[ $post->ID ];
		}

		self::prime_course_attachments( array( $post->ID ) );

		$id      = (int) $post->ID;
		$stati   = ecm_stati();
		$stato   = ECM_Meta::sanitize_stato( get_post_meta( $id, '_ecm_stato', true ) );
		$per_chi = (string) get_post_meta( $id, '_ecm_per_chi', true );

		$areas = array_map(
			static function ( $term ) {
				return array(
					'id'        => $term['id'],
					'slug'      => $term['slug'],
					'name'      => $term['name'],
					'image_key' => $term['image_key'] ?? '',
					'url'       => $term['url'],
				);
			},
			self::post_terms( $id, 'area_tematica' )
		);

		$destinatari = array_map(
			static function ( $term ) {
				return array(
					'id'   => $term['id'],
					'slug' => $term['slug'],
					'name' => $term['name'],
				);
			},
			self::post_terms( $id, 'destinatario' )
		);

		$data = array(
			'id'              => $id,
			'title'           => ecm_plain_title( $post ),
			'url'             => (string) get_permalink( $post ),
			'slug'            => $post->post_name,
			'cover_id'        => (int) get_post_thumbnail_id( $post ),
			'excerpt'         => self::excerpt( $post ),
			'content'         => self::content( $post ),
			'area'            => $areas ? $areas[0] : null,
			'areas'           => $areas,
			'destinatari'     => $destinatari,
			'stato'           => array(
				'key'   => $stato,
				'label' => $stati[ $stato ] ?? $stato,
			),
			'crediti'         => (string) get_post_meta( $id, '_ecm_crediti', true ),
			'brochure'        => self::brochure( $id ),
			'patrocini'       => self::patrocini( $id ),
			'testimonianze'   => self::course_testimonianze( $id ),
			'per_chi'         => '' !== trim( $per_chi ) ? wpautop( $per_chi ) : '',
			'faq'             => self::faq( $post, $per_chi ),
			'whatsapp_url'    => ecm_course_whatsapp_url( $post ),
			'link_iscrizione' => (string) get_post_meta( $id, '_ecm_link_iscrizione', true ),
			'in_evidenza'     => '1' === get_post_meta( $id, '_ecm_in_evidenza', true ),
		);

		/**
		 * Filtra i dati di un corso restituiti al tema.
		 *
		 * @param array   $data Dati.
		 * @param WP_Post $post Corso.
		 */
		$data = (array) apply_filters( 'ecm_course_data', $data, $post );

		self::$courses[ $id ] = $data;
		return $data;
	}

	/**
	 * Elenco di corsi ordinati.
	 *
	 * @param array $args featured(bool), area(slug), destinatario(slug), stato, exclude(int[]), include(int[]), limit(int).
	 * @return array<int,array>
	 */
	public static function courses( array $args = array() ): array {
		$args = wp_parse_args(
			$args,
			array(
				'featured'     => null,
				'area'         => '',
				'destinatario' => '',
				'stato'        => '',
				'exclude'      => array(),
				'include'      => array(),
				'limit'        => -1,
			)
		);

		$key = 'courses:' . md5( (string) wp_json_encode( $args ) );
		if ( isset( self::$queries[ $key ] ) ) {
			return self::$queries[ $key ];
		}

		$limit = (int) $args['limit'];
		$query = array(
			'post_type'           => 'corso',
			'post_status'         => 'publish',
			'posts_per_page'      => $limit > 0 ? $limit : -1,
			'orderby'             => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
			'suppress_filters'    => false,
		);

		$meta_query = array();
		if ( true === $args['featured'] || '1' === $args['featured'] || 1 === $args['featured'] ) {
			$meta_query[] = array(
				'key'   => '_ecm_in_evidenza',
				'value' => '1',
			);
		}
		if ( '' !== (string) $args['stato'] ) {
			$meta_query[] = array(
				'key'   => '_ecm_stato',
				'value' => ECM_Meta::sanitize_stato( $args['stato'] ),
			);
		}
		if ( $meta_query ) {
			$query['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		$tax_query = array();
		if ( '' !== (string) $args['area'] ) {
			$tax_query[] = array(
				'taxonomy' => 'area_tematica',
				'field'    => 'slug',
				'terms'    => array_map( 'sanitize_title', (array) $args['area'] ),
			);
		}
		if ( '' !== (string) ( is_array( $args['destinatario'] ) ? implode( ',', $args['destinatario'] ) : $args['destinatario'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'destinatario',
				'field'    => 'slug',
				'terms'    => array_map( 'sanitize_title', (array) $args['destinatario'] ),
			);
		}
		if ( $tax_query ) {
			$query['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		$exclude = array_filter( array_map( 'absint', (array) $args['exclude'] ) );
		if ( $exclude ) {
			$query['post__not_in'] = $exclude; // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
		}
		$include = array_filter( array_map( 'absint', (array) $args['include'] ) );
		if ( $include ) {
			$query['post__in'] = $include;
		}

		$wp_query = new WP_Query( $query );
		$ids      = wp_list_pluck( $wp_query->posts, 'ID' );
		update_post_thumbnail_cache( $wp_query );
		self::prime_course_attachments( $ids );

		$out = array();
		foreach ( $wp_query->posts as $post ) {
			$course = self::course( $post );
			if ( $course ) {
				$out[] = $course;
			}
		}

		self::$queries[ $key ] = $out;
		return $out;
	}

	/**
	 * Carica in un'unica query gli allegati usati dai corsi (brochure, anteprime, loghi).
	 *
	 * @param int[] $course_ids ID corsi (con meta già in cache o da caricare).
	 */
	private static function prime_course_attachments( array $course_ids ): void {
		$course_ids = array_filter( array_map( 'intval', $course_ids ) );
		if ( ! $course_ids ) {
			return;
		}
		update_meta_cache( 'post', $course_ids );

		$attachment_ids = array();
		foreach ( $course_ids as $id ) {
			$attachment_ids[] = (int) get_post_meta( $id, '_ecm_brochure_id', true );
			$attachment_ids[] = (int) get_post_meta( $id, '_ecm_brochure_preview_id', true );
			$attachment_ids[] = (int) get_post_thumbnail_id( $id );
			$patrocini        = get_post_meta( $id, '_ecm_patrocini', true );
			foreach ( is_array( $patrocini ) ? $patrocini : array() as $row ) {
				$attachment_ids[] = (int) ( $row['image_id'] ?? 0 );
			}
		}
		$attachment_ids = array_values( array_unique( array_filter( $attachment_ids ) ) );
		if ( $attachment_ids ) {
			_prime_post_caches( $attachment_ids, false, true );
		}
	}

	/**
	 * Estratto in testo semplice.
	 *
	 * @param WP_Post $post Post.
	 * @return string
	 */
	private static function excerpt( WP_Post $post ): string {
		$excerpt = trim( (string) $post->post_excerpt );
		if ( '' === $excerpt ) {
			$text    = excerpt_remove_blocks( strip_shortcodes( (string) $post->post_content ) );
			$text    = preg_replace( '#<(br|/p|/li|/h[1-6])\s*/?>#i', '$0 ', $text );
			$excerpt = wp_trim_words( wp_strip_all_tags( (string) $text ), (int) apply_filters( 'ecm_excerpt_words', 32 ), '…' );
		} else {
			$excerpt = wp_strip_all_tags( $excerpt );
		}
		$excerpt = html_entity_decode( $excerpt, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		return trim( (string) preg_replace( '/\s+/u', ' ', $excerpt ) );
	}

	/**
	 * Contenuto filtrato come the_content.
	 *
	 * @param WP_Post $post Post.
	 * @return string
	 */
	private static function content( WP_Post $post ): string {
		$content = (string) $post->post_content;
		if ( '' === trim( $content ) ) {
			return '';
		}
		$previous = $GLOBALS['post'] ?? null;
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['post'] = $post;
		$html = (string) apply_filters( 'the_content', $content );
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['post'] = $previous;
		return str_replace( ']]>', ']]&gt;', $html );
	}

	/**
	 * Dati della brochure.
	 *
	 * @param int $course_id ID corso.
	 * @return array|null
	 */
	private static function brochure( int $course_id ): ?array {
		$id = (int) get_post_meta( $course_id, '_ecm_brochure_id', true );
		if ( ! $id || 'attachment' !== get_post_type( $id ) ) {
			return null;
		}
		$url = wp_get_attachment_url( $id );
		if ( ! $url ) {
			return null;
		}

		$file  = get_attached_file( $id );
		$meta  = wp_get_attachment_metadata( $id );
		$meta  = is_array( $meta ) ? $meta : array();
		$bytes = (int) ( $meta['filesize'] ?? 0 );
		if ( ! $bytes && $file && file_exists( $file ) ) {
			$bytes = (int) filesize( $file );
		}
		$pages = get_post_meta( $id, '_ecm_pdf_pages', true );

		$preview = (int) get_post_meta( $course_id, '_ecm_brochure_preview_id', true );
		if ( $preview && ! wp_attachment_is_image( $preview ) ) {
			$preview = 0;
		}
		if ( ! $preview && ! empty( $meta['sizes'] ) ) {
			// Anteprima della prima pagina generata da WordPress: wp_get_attachment_image( $id ) funziona anche sul PDF.
			$preview = $id;
		}

		return array(
			'id'         => $id,
			'url'        => $url,
			'bytes'      => $bytes,
			'size'       => $bytes ? ecm_human_filesize( $bytes ) : '',
			'pages'      => ( '' !== $pages && null !== $pages && (int) $pages > 0 ) ? (int) $pages : null,
			'filename'   => wp_basename( $file ? $file : $url ),
			'preview_id' => $preview,
		);
	}

	/**
	 * Patrocini con dati dell'immagine.
	 *
	 * @param int $course_id ID corso.
	 * @return array
	 */
	private static function patrocini( int $course_id ): array {
		$rows = get_post_meta( $course_id, '_ecm_patrocini', true );
		$out  = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$image_id = (int) ( $row['image_id'] ?? 0 );
			$name     = trim( (string) ( $row['name'] ?? '' ) );
			$src      = $image_id ? wp_get_attachment_image_src( $image_id, 'full' ) : false;
			if ( ! $src && '' === $name ) {
				continue;
			}
			if ( '' === $name && $src ) {
				$name = trim( (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true ) );
				if ( '' === $name ) {
					$name = ecm_plain_title( get_post( $image_id ) );
				}
			}
			$out[] = array(
				'image_id' => $src ? $image_id : 0,
				'name'     => $name,
				'url'      => $src ? (string) $src[0] : '',
				'width'    => $src ? (int) $src[1] : 0,
				'height'   => $src ? (int) $src[2] : 0,
			);
		}
		return $out;
	}

	/**
	 * Testimonianze inserite nel corso.
	 *
	 * @param int $course_id ID corso.
	 * @return array
	 */
	private static function course_testimonianze( int $course_id ): array {
		$rows = get_post_meta( $course_id, '_ecm_testimonianze', true );
		$out  = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$out[] = array(
				'name' => (string) ( $row['name'] ?? '' ),
				'meta' => (string) ( $row['meta'] ?? '' ),
				'text' => (string) ( $row['text'] ?? '' ),
			);
		}
		return $out;
	}

	/**
	 * FAQ del corso: "Per chi è adatto" + FAQ comuni + FAQ specifiche.
	 *
	 * @param WP_Post $post    Corso.
	 * @param string  $per_chi HTML "Per chi è adatto".
	 * @return array
	 */
	private static function faq( WP_Post $post, string $per_chi ): array {
		$faq = array();

		if ( '' !== trim( wp_strip_all_tags( $per_chi ) ) ) {
			$faq[] = array(
				'q' => (string) apply_filters( 'ecm_per_chi_question', __( 'Per chi è adatto questo corso?', 'ecm' ), $post ),
				'a' => wpautop( $per_chi ),
			);
		}

		$rows = get_post_meta( $post->ID, '_ecm_faq', true );
		$rows = is_array( $rows ) ? $rows : array();

		// Una FAQ specifica del corso con la stessa domanda sostituisce quella comune.
		$own = array();
		foreach ( $rows as $row ) {
			$own[ self::normalize_question( (string) ( $row['q'] ?? '' ) ) ] = true;
		}

		foreach ( array( 'email', 'bonifico' ) as $key ) {
			$q = trim( (string) ecm_opt( "faq_{$key}_q" ) );
			$a = (string) ecm_opt( "faq_{$key}_a" );
			if ( '' === $q || '' === trim( wp_strip_all_tags( $a ) ) || isset( $own[ self::normalize_question( $q ) ] ) ) {
				continue;
			}
			$faq[] = array(
				'q' => $q,
				'a' => wpautop( $a ),
			);
		}

		foreach ( $rows as $row ) {
			$q = trim( (string) ( $row['q'] ?? '' ) );
			if ( '' === $q ) {
				continue;
			}
			$faq[] = array(
				'q' => $q,
				'a' => wpautop( (string) ( $row['a'] ?? '' ) ),
			);
		}

		return $faq;
	}

	/**
	 * Domanda normalizzata per il confronto (minuscole, senza accenti, punteggiatura e spazi).
	 *
	 * @param string $question Domanda.
	 * @return string
	 */
	public static function normalize_question( string $question ): string {
		$question = html_entity_decode( wp_strip_all_tags( $question ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		return (string) preg_replace( '/[^a-z0-9]+/', '', strtolower( remove_accents( $question ) ) );
	}

	/**
	 * Chiave di deduplica di un ente patrocinante.
	 *
	 * @param string $name Nome.
	 * @return string
	 */
	private static function normalize_ente( string $name ): string {
		$name = html_entity_decode( $name, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		return strtolower( trim( (string) preg_replace( '/\s+/u', ' ', remove_accents( $name ) ) ) );
	}

	/* ------------------------------------------------------------------
	 * Docenti e testimonianze.
	 * ------------------------------------------------------------------ */

	/**
	 * Docenti ordinati.
	 *
	 * @return array
	 */
	public static function docenti(): array {
		if ( isset( self::$queries['docenti'] ) ) {
			return self::$queries['docenti'];
		}

		$query = new WP_Query(
			array(
				'post_type'      => 'docente',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => array(
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				),
				'no_found_rows'  => true,
			)
		);
		update_post_thumbnail_cache( $query );

		$cv_ids = array();
		foreach ( $query->posts as $post ) {
			$cv_ids[] = (int) get_post_meta( $post->ID, '_ecm_cv_id', true );
		}
		$cv_ids = array_values( array_unique( array_filter( $cv_ids ) ) );
		if ( $cv_ids ) {
			_prime_post_caches( $cv_ids, false, true );
		}

		$out = array();
		foreach ( $query->posts as $post ) {
			$name   = ecm_plain_title( $post );
			$cv_id  = (int) get_post_meta( $post->ID, '_ecm_cv_id', true );
			$cv_url = $cv_id ? (string) wp_get_attachment_url( $cv_id ) : '';
			if ( '' === $cv_url ) {
				$cv_url = (string) get_post_meta( $post->ID, '_ecm_cv_url', true );
			}
			$out[] = array(
				'id'       => (int) $post->ID,
				'name'     => $name,
				'titolo'   => (string) get_post_meta( $post->ID, '_ecm_titolo', true ),
				'bio_html' => '' !== trim( $post->post_content ) ? wpautop( wptexturize( $post->post_content ) ) : '',
				'photo_id' => (int) get_post_thumbnail_id( $post ),
				'cv_url'   => $cv_url,
				'initials' => ecm_core_initials( $name ),
			);
		}

		self::$queries['docenti'] = $out;
		return $out;
	}

	/**
	 * Testimonianze (CPT).
	 *
	 * @param array $args fonte (home|testimonianze), corso_id, limit.
	 * @return array
	 */
	public static function testimonianze( array $args = array() ): array {
		$args = wp_parse_args(
			$args,
			array(
				'fonte'    => '',
				'corso_id' => 0,
				'limit'    => -1,
			)
		);
		$key  = 'testimonianze:' . md5( (string) wp_json_encode( $args ) );
		if ( isset( self::$queries[ $key ] ) ) {
			return self::$queries[ $key ];
		}

		$limit = (int) $args['limit'];
		$query = array(
			'post_type'      => 'testimonianza',
			'post_status'    => 'publish',
			'posts_per_page' => $limit > 0 ? $limit : -1,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			'no_found_rows'  => true,
		);

		$meta_query = array();
		$fonte      = sanitize_key( (string) $args['fonte'] );
		if ( in_array( $fonte, array( 'home', 'testimonianze' ), true ) ) {
			$meta_query[] = array(
				'relation' => 'OR',
				array(
					'key'     => '_ecm_fonte',
					'value'   => array( $fonte, 'entrambe' ),
					'compare' => 'IN',
				),
				array(
					'key'     => '_ecm_fonte',
					'compare' => 'NOT EXISTS',
				),
			);
		}
		if ( (int) $args['corso_id'] > 0 ) {
			$meta_query[] = array(
				'key'   => '_ecm_corso_id',
				'value' => (int) $args['corso_id'],
				'type'  => 'NUMERIC',
			);
		}
		if ( $meta_query ) {
			$query['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		$wp_query = new WP_Query( $query );

		$corso_ids = array();
		foreach ( $wp_query->posts as $post ) {
			$corso_ids[] = (int) get_post_meta( $post->ID, '_ecm_corso_id', true );
		}
		$corso_ids = array_values( array_unique( array_filter( $corso_ids ) ) );
		if ( $corso_ids ) {
			_prime_post_caches( $corso_ids, false, false );
		}

		$out = array();
		foreach ( $wp_query->posts as $post ) {
			$corso_id    = (int) get_post_meta( $post->ID, '_ecm_corso_id', true );
			$corso       = $corso_id ? get_post( $corso_id ) : null;
			$published   = $corso instanceof WP_Post && 'corso' === $corso->post_type && 'publish' === $corso->post_status;
			$label       = (string) get_post_meta( $post->ID, '_ecm_corso_label', true );
			$corso_title = $published ? ecm_plain_title( $corso ) : $label;

			$out[] = array(
				'id'          => (int) $post->ID,
				'name'        => ecm_plain_title( $post ),
				'text'        => trim( html_entity_decode( wp_strip_all_tags( (string) $post->post_content ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ),
				'corso_id'    => $published ? $corso_id : 0,
				'corso_title' => $corso_title,
				'corso_url'   => $published ? (string) get_permalink( $corso ) : '',
				'fonte'       => ECM_Meta::sanitize_fonte( get_post_meta( $post->ID, '_ecm_fonte', true ) ),
				'rating'      => (int) get_post_meta( $post->ID, '_ecm_rating', true ),
			);
		}

		self::$queries[ $key ] = $out;
		return $out;
	}

	/**
	 * Numeri del sito (cache transient 12 ore, invalidata a ogni modifica).
	 *
	 * @return array{corsi:int,docenti:int,patrocini_ordini:int,testimonianze:int}
	 */
	public static function stats(): array {
		$cached = get_transient( self::STATS_TRANSIENT );
		if ( is_array( $cached ) && isset( $cached['corsi'] ) ) {
			return $cached;
		}

		$course_ids = get_posts(
			array(
				'post_type'        => 'corso',
				'post_status'      => 'publish',
				'posts_per_page'   => -1,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => false,
			)
		);
		if ( $course_ids ) {
			update_meta_cache( 'post', $course_ids );
		}

		$enti = array();
		foreach ( $course_ids as $id ) {
			$rows = get_post_meta( $id, '_ecm_patrocini', true );
			foreach ( is_array( $rows ) ? $rows : array() as $row ) {
				// Un banner può riunire più enti: "Ordine A; Ordine B; ...".
				$names = array_filter( array_map( array( __CLASS__, 'normalize_ente' ), explode( ';', (string) ( $row['name'] ?? '' ) ) ) );
				if ( $names ) {
					foreach ( $names as $name ) {
						$enti[ 'n:' . $name ] = true;
					}
				} elseif ( (int) ( $row['image_id'] ?? 0 ) ) {
					$enti[ 'i:' . (int) $row['image_id'] ] = true;
				}
			}
		}

		$stats = array(
			'corsi'            => count( $course_ids ),
			'docenti'          => (int) ( wp_count_posts( 'docente' )->publish ?? 0 ),
			'patrocini_ordini' => count( $enti ),
			'testimonianze'    => (int) ( wp_count_posts( 'testimonianza' )->publish ?? 0 ),
		);

		set_transient( self::STATS_TRANSIENT, $stats, 12 * HOUR_IN_SECONDS );
		return $stats;
	}
}
