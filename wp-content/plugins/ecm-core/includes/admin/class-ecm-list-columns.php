<?php
/**
 * Colonne e filtri degli elenchi admin di corsi, docenti e testimonianze.
 *
 * @package ECM_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Colonne elenchi.
 */
final class ECM_List_Columns {

	/**
	 * Aggancia gli hook.
	 */
	public static function init(): void {
		add_filter( 'manage_corso_posts_columns', array( __CLASS__, 'corso_columns' ) );
		add_action( 'manage_corso_posts_custom_column', array( __CLASS__, 'corso_cell' ), 10, 2 );
		add_filter( 'manage_edit-corso_sortable_columns', array( __CLASS__, 'sortable' ) );

		add_filter( 'manage_docente_posts_columns', array( __CLASS__, 'docente_columns' ) );
		add_action( 'manage_docente_posts_custom_column', array( __CLASS__, 'docente_cell' ), 10, 2 );
		add_filter( 'manage_edit-docente_sortable_columns', array( __CLASS__, 'sortable' ) );

		add_filter( 'manage_testimonianza_posts_columns', array( __CLASS__, 'testimonianza_columns' ) );
		add_action( 'manage_testimonianza_posts_custom_column', array( __CLASS__, 'testimonianza_cell' ), 10, 2 );
		add_filter( 'manage_edit-testimonianza_sortable_columns', array( __CLASS__, 'sortable' ) );

		add_action( 'restrict_manage_posts', array( __CLASS__, 'filters' ), 10, 2 );
		add_action( 'pre_get_posts', array( __CLASS__, 'apply_filters' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'prime_list' ), 20 );
	}

	/**
	 * Colonne corso.
	 *
	 * @param array $columns Colonne.
	 * @return array
	 */
	public static function corso_columns( $columns ): array {
		return array(
			'cb'                     => $columns['cb'] ?? '<input type="checkbox" />',
			'ecm_cover'              => '<span class="screen-reader-text">' . esc_html__( 'Copertina', 'ecm' ) . '</span>',
			'title'                  => __( 'Corso', 'ecm' ),
			'ecm_stato'              => __( 'Stato', 'ecm' ),
			'taxonomy-area_tematica' => __( 'Area', 'ecm' ),
			'ecm_evidenza'           => '<span class="dashicons dashicons-star-filled" title="' . esc_attr__( 'In evidenza', 'ecm' ) . '"></span><span class="screen-reader-text">' . esc_html__( 'In evidenza', 'ecm' ) . '</span>',
			'ecm_brochure'           => __( 'Brochure', 'ecm' ),
			'ecm_ordine'             => __( 'Ordine', 'ecm' ),
			'date'                   => $columns['date'] ?? __( 'Data', 'ecm' ),
		);
	}

	/**
	 * Celle corso.
	 *
	 * @param string $column  Colonna.
	 * @param int    $post_id ID.
	 */
	public static function corso_cell( $column, $post_id ): void {
		switch ( $column ) {
			case 'ecm_cover':
				self::thumb( (int) get_post_thumbnail_id( $post_id ) );
				break;
			case 'ecm_stato':
				$stato = ECM_Meta::sanitize_stato( get_post_meta( $post_id, '_ecm_stato', true ) );
				$stati = ecm_stati();
				printf( '<span class="ecm-badge ecm-badge--%1$s">%2$s</span>', esc_attr( $stato ), esc_html( $stati[ $stato ] ?? $stato ) );
				$crediti = (string) get_post_meta( $post_id, '_ecm_crediti', true );
				if ( '' !== $crediti ) {
					/* translators: %s: crediti ECM. */
					printf( '<br><small>%s</small>', esc_html( sprintf( __( '%s crediti ECM', 'ecm' ), $crediti ) ) );
				}
				break;
			case 'ecm_evidenza':
				$on = '1' === get_post_meta( $post_id, '_ecm_in_evidenza', true );
				printf(
					'<span class="dashicons %1$s ecm-star%2$s" aria-hidden="true"></span><span class="screen-reader-text">%3$s</span>',
					$on ? 'dashicons-star-filled' : 'dashicons-star-empty',
					$on ? ' is-on' : '',
					$on ? esc_html__( 'In evidenza', 'ecm' ) : esc_html__( 'Non in evidenza', 'ecm' )
				);
				break;
			case 'ecm_brochure':
				$id = (int) get_post_meta( $post_id, '_ecm_brochure_id', true );
				if ( $id && 'attachment' === get_post_type( $id ) ) {
					printf( '<a href="%1$s" target="_blank" rel="noopener" class="ecm-pdf-link"><span class="dashicons dashicons-pdf" aria-hidden="true"></span> %2$s</a>', esc_url( (string) wp_get_attachment_url( $id ) ), esc_html__( 'PDF', 'ecm' ) );
				} else {
					echo '<span class="ecm-muted" aria-label="' . esc_attr__( 'Nessuna brochure', 'ecm' ) . '">—</span>';
				}
				break;
			case 'ecm_ordine':
				echo (int) get_post_field( 'menu_order', $post_id );
				break;
		}
	}

	/**
	 * Colonne docente.
	 *
	 * @param array $columns Colonne.
	 * @return array
	 */
	public static function docente_columns( $columns ): array {
		return array(
			'cb'         => $columns['cb'] ?? '<input type="checkbox" />',
			'ecm_foto'   => '<span class="screen-reader-text">' . esc_html__( 'Foto', 'ecm' ) . '</span>',
			'title'      => __( 'Nome', 'ecm' ),
			'ecm_titolo' => __( 'Ruolo o qualifica', 'ecm' ),
			'ecm_ordine' => __( 'Ordine', 'ecm' ),
		);
	}

	/**
	 * Celle docente.
	 *
	 * @param string $column  Colonna.
	 * @param int    $post_id ID.
	 */
	public static function docente_cell( $column, $post_id ): void {
		switch ( $column ) {
			case 'ecm_foto':
				$photo = (int) get_post_thumbnail_id( $post_id );
				if ( $photo ) {
					self::thumb( $photo, true );
				} else {
					printf( '<span class="ecm-initials" aria-hidden="true">%s</span>', esc_html( ecm_core_initials( ecm_plain_title( $post_id ) ) ) );
				}
				break;
			case 'ecm_titolo':
				echo esc_html( (string) get_post_meta( $post_id, '_ecm_titolo', true ) );
				break;
			case 'ecm_ordine':
				echo (int) get_post_field( 'menu_order', $post_id );
				break;
		}
	}

	/**
	 * Colonne testimonianza.
	 *
	 * @param array $columns Colonne.
	 * @return array
	 */
	public static function testimonianza_columns( $columns ): array {
		return array(
			'cb'         => $columns['cb'] ?? '<input type="checkbox" />',
			'title'      => __( 'Nome', 'ecm' ),
			'ecm_testo'  => __( 'Testimonianza', 'ecm' ),
			'ecm_corso'  => __( 'Corso', 'ecm' ),
			'ecm_fonte'  => __( 'Dove appare', 'ecm' ),
			'ecm_ordine' => __( 'Ordine', 'ecm' ),
		);
	}

	/**
	 * Celle testimonianza.
	 *
	 * @param string $column  Colonna.
	 * @param int    $post_id ID.
	 */
	public static function testimonianza_cell( $column, $post_id ): void {
		switch ( $column ) {
			case 'ecm_testo':
				echo esc_html( wp_trim_words( wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) ), 18, '…' ) );
				$rating = (int) get_post_meta( $post_id, '_ecm_rating', true );
				if ( $rating ) {
					printf( '<br><span class="ecm-stars" aria-label="%1$s">%2$s</span>', esc_attr( sprintf( '%d/5', $rating ) ), esc_html( str_repeat( '★', $rating ) ) );
				}
				break;
			case 'ecm_corso':
				$corso_id = (int) get_post_meta( $post_id, '_ecm_corso_id', true );
				if ( $corso_id && 'corso' === get_post_type( $corso_id ) ) {
					printf( '<a href="%1$s">%2$s</a>', esc_url( (string) get_edit_post_link( $corso_id ) ), esc_html( ecm_plain_title( $corso_id ) ) );
				} else {
					$label = (string) get_post_meta( $post_id, '_ecm_corso_label', true );
					echo '' !== $label ? esc_html( $label ) : '<span class="ecm-muted">—</span>';
				}
				break;
			case 'ecm_fonte':
				$labels = array(
					'home'          => __( 'Home', 'ecm' ),
					'testimonianze' => __( 'Pagina Testimonianze', 'ecm' ),
					'entrambe'      => __( 'Home e Testimonianze', 'ecm' ),
				);
				$fonte  = ECM_Meta::sanitize_fonte( get_post_meta( $post_id, '_ecm_fonte', true ) );
				echo esc_html( $labels[ $fonte ] );
				break;
			case 'ecm_ordine':
				echo (int) get_post_field( 'menu_order', $post_id );
				break;
		}
	}

	/**
	 * Colonna "Ordine" ordinabile per menu_order.
	 *
	 * @param array $columns Colonne.
	 * @return array
	 */
	public static function sortable( $columns ): array {
		$columns['ecm_ordine'] = 'menu_order';
		return $columns;
	}

	/**
	 * Filtri sopra l'elenco corsi (area, stato, in evidenza).
	 *
	 * @param string $post_type Tipo.
	 * @param string $which     top|bottom.
	 */
	public static function filters( $post_type, $which = 'top' ): void {
		if ( 'corso' !== $post_type || 'top' !== $which ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- filtri di sola lettura.
		$area  = isset( $_GET['area_tematica'] ) ? sanitize_title( wp_unslash( $_GET['area_tematica'] ) ) : '';
		$stato = isset( $_GET['ecm_stato'] ) ? sanitize_key( wp_unslash( $_GET['ecm_stato'] ) ) : '';
		$evid  = isset( $_GET['ecm_evidenza'] ) ? sanitize_key( wp_unslash( $_GET['ecm_evidenza'] ) ) : '';
		// phpcs:enable

		wp_dropdown_categories(
			array(
				'taxonomy'        => 'area_tematica',
				'name'            => 'area_tematica',
				'value_field'     => 'slug',
				'selected'        => $area,
				'show_option_all' => __( 'Tutte le aree', 'ecm' ),
				'hide_empty'      => false,
				'hierarchical'    => true,
				'orderby'         => 'name',
			)
		);
		echo '<select name="ecm_stato"><option value="">' . esc_html__( 'Tutti gli stati', 'ecm' ) . '</option>';
		foreach ( ecm_stati() as $key => $label ) {
			printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $key ), selected( $stato, $key, false ), esc_html( $label ) );
		}
		echo '</select>';
		printf(
			'<select name="ecm_evidenza"><option value="">%1$s</option><option value="1" %2$s>%3$s</option></select>',
			esc_html__( 'In evidenza: tutti', 'ecm' ),
			selected( $evid, '1', false ),
			esc_html__( 'Solo in evidenza', 'ecm' )
		);
	}

	/**
	 * Applica i filtri stato/in evidenza.
	 *
	 * @param WP_Query $query Query.
	 */
	public static function apply_filters( $query ): void {
		if ( ! is_admin() || ! $query instanceof WP_Query || ! $query->is_main_query() || 'corso' !== $query->get( 'post_type' ) ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$stato = isset( $_GET['ecm_stato'] ) ? sanitize_key( wp_unslash( $_GET['ecm_stato'] ) ) : '';
		$evid  = isset( $_GET['ecm_evidenza'] ) ? sanitize_key( wp_unslash( $_GET['ecm_evidenza'] ) ) : '';
		// phpcs:enable
		$meta_query = array();
		if ( in_array( $stato, ECM_Meta::STATI, true ) ) {
			$clause = array(
				'key'   => '_ecm_stato',
				'value' => $stato,
			);
			if ( 'disponibile' === $stato ) {
				$clause = array(
					'relation' => 'OR',
					$clause,
					array(
						'key'     => '_ecm_stato',
						'compare' => 'NOT EXISTS',
					),
				);
			}
			$meta_query[] = $clause;
		}
		if ( '1' === $evid ) {
			$meta_query[] = array(
				'key'   => '_ecm_in_evidenza',
				'value' => '1',
			);
		}
		if ( $meta_query ) {
			$query->set( 'meta_query', $meta_query ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}
	}

	/**
	 * Carica in blocco le miniature dell'elenco.
	 *
	 * @param WP_Query $query Query.
	 */
	public static function prime_list( $query ): void {
		if ( ! is_admin() || ! $query instanceof WP_Query || ! $query->is_main_query() ) {
			return;
		}
		if ( in_array( $query->get( 'post_type' ), array( 'corso', 'docente' ), true ) ) {
			$query->set( 'update_post_meta_cache', true );
			add_action(
				'the_posts',
				static function ( $posts, $q ) use ( $query ) {
					if ( $q === $query && $posts ) {
						update_post_thumbnail_cache( $q );
					}
					return $posts;
				},
				10,
				2
			);
		}
	}

	/**
	 * Miniatura.
	 *
	 * @param int  $id    ID allegato.
	 * @param bool $round Tonda.
	 */
	private static function thumb( int $id, bool $round = false ): void {
		if ( ! $id ) {
			echo '<span class="ecm-thumb ecm-thumb--empty" aria-hidden="true"></span>';
			return;
		}
		echo '<span class="ecm-thumb' . ( $round ? ' ecm-thumb--round' : '' ) . '">' . wp_get_attachment_image( $id, 'thumbnail', false, array( 'alt' => '' ) ) . '</span>';
	}
}
