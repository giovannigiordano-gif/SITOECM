<?php
/**
 * Tipi di contenuto, tassonomie, posizioni dei menu, editor classico, ordinamento.
 *
 * @package ECM_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registrazione dei CPT e delle tassonomie.
 */
final class ECM_Post_Types {

	/**
	 * CPT che usano l'editor classico.
	 */
	public const CLASSIC_TYPES = array( 'corso', 'docente', 'testimonianza', 'ecm_richiesta', 'ecm_iscritto' );

	/**
	 * CPT ordinati manualmente (menu_order, poi titolo).
	 */
	public const ORDERED_TYPES = array( 'corso', 'docente', 'testimonianza' );

	/**
	 * Aggancia gli hook.
	 */
	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register' ), 5 );
		add_action( 'after_setup_theme', array( __CLASS__, 'register_nav_locations' ), 99 );
		add_filter( 'use_block_editor_for_post_type', array( __CLASS__, 'disable_block_editor' ), 10, 2 );
		add_action( 'pre_get_posts', array( __CLASS__, 'default_order' ) );
		add_filter( 'enter_title_here', array( __CLASS__, 'title_placeholder' ), 10, 2 );
	}

	/**
	 * Registra CPT e tassonomie.
	 */
	public static function register(): void {
		if ( post_type_exists( 'corso' ) ) {
			return;
		}

		register_post_type(
			'corso',
			array(
				'labels'          => array(
					'name'                  => __( 'Corsi', 'ecm' ),
					'singular_name'         => __( 'Corso', 'ecm' ),
					'menu_name'             => __( 'Corsi', 'ecm' ),
					'all_items'             => __( 'Tutti i corsi', 'ecm' ),
					'add_new'               => __( 'Aggiungi corso', 'ecm' ),
					'add_new_item'          => __( 'Aggiungi nuovo corso', 'ecm' ),
					'edit_item'             => __( 'Modifica corso', 'ecm' ),
					'new_item'              => __( 'Nuovo corso', 'ecm' ),
					'view_item'             => __( 'Visualizza corso', 'ecm' ),
					'view_items'            => __( 'Visualizza corsi', 'ecm' ),
					'search_items'          => __( 'Cerca corsi', 'ecm' ),
					'not_found'             => __( 'Nessun corso trovato.', 'ecm' ),
					'not_found_in_trash'    => __( 'Nessun corso nel cestino.', 'ecm' ),
					'featured_image'        => __( 'Copertina del corso', 'ecm' ),
					'set_featured_image'    => __( 'Imposta copertina', 'ecm' ),
					'remove_featured_image' => __( 'Rimuovi copertina', 'ecm' ),
					'use_featured_image'    => __( 'Usa come copertina', 'ecm' ),
					'archives'              => __( 'Catalogo corsi', 'ecm' ),
					'attributes'            => __( 'Ordine nel catalogo', 'ecm' ),
					'item_published'        => __( 'Corso pubblicato.', 'ecm' ),
					'item_updated'          => __( 'Corso aggiornato.', 'ecm' ),
				),
				'description'     => __( 'Corsi ECM del catalogo.', 'ecm' ),
				'public'          => true,
				'show_in_rest'    => true,
				'menu_position'   => 5,
				'menu_icon'       => 'dashicons-book-alt',
				'supports'        => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes', 'revisions' ),
				'has_archive'     => 'corsi',
				'rewrite'         => array(
					'slug'       => 'courses',
					'with_front' => false,
				),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
			)
		);

		register_post_type(
			'docente',
			array(
				'labels'              => array(
					'name'                  => __( 'Docenti', 'ecm' ),
					'singular_name'         => __( 'Docente', 'ecm' ),
					'all_items'             => __( 'Tutti i docenti', 'ecm' ),
					'add_new'               => __( 'Aggiungi docente', 'ecm' ),
					'add_new_item'          => __( 'Aggiungi nuovo docente', 'ecm' ),
					'edit_item'             => __( 'Modifica docente', 'ecm' ),
					'new_item'              => __( 'Nuovo docente', 'ecm' ),
					'search_items'          => __( 'Cerca docenti', 'ecm' ),
					'not_found'             => __( 'Nessun docente trovato.', 'ecm' ),
					'not_found_in_trash'    => __( 'Nessun docente nel cestino.', 'ecm' ),
					'featured_image'        => __( 'Foto del docente', 'ecm' ),
					'set_featured_image'    => __( 'Imposta foto', 'ecm' ),
					'remove_featured_image' => __( 'Rimuovi foto', 'ecm' ),
					'use_featured_image'    => __( 'Usa come foto', 'ecm' ),
					'attributes'            => __( 'Ordine', 'ecm' ),
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_nav_menus'   => false,
				'show_in_rest'        => false,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'menu_position'       => 6,
				'menu_icon'           => 'dashicons-groups',
				'supports'            => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
				'map_meta_cap'        => true,
			)
		);

		register_post_type(
			'testimonianza',
			array(
				'labels'              => array(
					'name'               => __( 'Testimonianze', 'ecm' ),
					'singular_name'      => __( 'Testimonianza', 'ecm' ),
					'all_items'          => __( 'Tutte le testimonianze', 'ecm' ),
					'add_new'            => __( 'Aggiungi testimonianza', 'ecm' ),
					'add_new_item'       => __( 'Aggiungi nuova testimonianza', 'ecm' ),
					'edit_item'          => __( 'Modifica testimonianza', 'ecm' ),
					'new_item'           => __( 'Nuova testimonianza', 'ecm' ),
					'search_items'       => __( 'Cerca testimonianze', 'ecm' ),
					'not_found'          => __( 'Nessuna testimonianza trovata.', 'ecm' ),
					'not_found_in_trash' => __( 'Nessuna testimonianza nel cestino.', 'ecm' ),
					'attributes'         => __( 'Ordine', 'ecm' ),
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_nav_menus'   => false,
				'show_in_rest'        => false,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'menu_position'       => 7,
				'menu_icon'           => 'dashicons-format-quote',
				'supports'            => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
				'map_meta_cap'        => true,
			)
		);

		$private_args = array(
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => 'ecm-settings',
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => false,
			'show_in_rest'        => false,
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'supports'            => false,
			'capability_type'     => 'post',
			'capabilities'        => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap'        => true,
			'can_export'          => false,
		);

		register_post_type(
			'ecm_richiesta',
			array_merge(
				$private_args,
				array(
					'labels' => array(
						'name'               => __( 'Richieste contatto', 'ecm' ),
						'singular_name'      => __( 'Richiesta contatto', 'ecm' ),
						'menu_name'          => __( 'Richieste contatto', 'ecm' ),
						'all_items'          => __( 'Richieste contatto', 'ecm' ),
						'edit_item'          => __( 'Dettaglio richiesta', 'ecm' ),
						'search_items'       => __( 'Cerca richieste', 'ecm' ),
						'not_found'          => __( 'Nessuna richiesta ricevuta.', 'ecm' ),
						'not_found_in_trash' => __( 'Nessuna richiesta nel cestino.', 'ecm' ),
					),
				)
			)
		);

		register_post_type(
			'ecm_iscritto',
			array_merge(
				$private_args,
				array(
					'labels' => array(
						'name'               => __( 'Iscritti newsletter', 'ecm' ),
						'singular_name'      => __( 'Iscritto newsletter', 'ecm' ),
						'menu_name'          => __( 'Iscritti newsletter', 'ecm' ),
						'all_items'          => __( 'Iscritti newsletter', 'ecm' ),
						'edit_item'          => __( 'Dettaglio iscritto', 'ecm' ),
						'search_items'       => __( 'Cerca iscritti', 'ecm' ),
						'not_found'          => __( 'Nessun iscritto.', 'ecm' ),
						'not_found_in_trash' => __( 'Nessun iscritto nel cestino.', 'ecm' ),
					),
				)
			)
		);

		register_taxonomy(
			'area_tematica',
			array( 'corso' ),
			array(
				'labels'            => array(
					'name'              => __( 'Aree tematiche', 'ecm' ),
					'singular_name'     => __( 'Area tematica', 'ecm' ),
					'menu_name'         => __( 'Aree tematiche', 'ecm' ),
					'all_items'         => __( 'Tutte le aree', 'ecm' ),
					'edit_item'         => __( 'Modifica area', 'ecm' ),
					'view_item'         => __( 'Visualizza area', 'ecm' ),
					'update_item'       => __( 'Aggiorna area', 'ecm' ),
					'add_new_item'      => __( 'Aggiungi nuova area', 'ecm' ),
					'new_item_name'     => __( 'Nome della nuova area', 'ecm' ),
					'parent_item'       => __( 'Area superiore', 'ecm' ),
					'parent_item_colon' => __( 'Area superiore:', 'ecm' ),
					'search_items'      => __( 'Cerca aree', 'ecm' ),
					'not_found'         => __( 'Nessuna area trovata.', 'ecm' ),
					'back_to_items'     => __( '← Torna alle aree', 'ecm' ),
				),
				'hierarchical'      => true,
				'public'            => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'show_in_nav_menus' => true,
				'rewrite'           => array(
					'slug'       => 'area',
					'with_front' => false,
				),
			)
		);

		register_taxonomy(
			'destinatario',
			array( 'corso' ),
			array(
				'labels'            => array(
					'name'              => __( 'Destinatari', 'ecm' ),
					'singular_name'     => __( 'Destinatario', 'ecm' ),
					'menu_name'         => __( 'Destinatari', 'ecm' ),
					'all_items'         => __( 'Tutti i destinatari', 'ecm' ),
					'edit_item'         => __( 'Modifica destinatario', 'ecm' ),
					'update_item'       => __( 'Aggiorna destinatario', 'ecm' ),
					'add_new_item'      => __( 'Aggiungi nuovo destinatario', 'ecm' ),
					'new_item_name'     => __( 'Nome del nuovo destinatario', 'ecm' ),
					'parent_item'       => __( 'Destinatario superiore', 'ecm' ),
					'parent_item_colon' => __( 'Destinatario superiore:', 'ecm' ),
					'search_items'      => __( 'Cerca destinatari', 'ecm' ),
					'not_found'         => __( 'Nessun destinatario trovato.', 'ecm' ),
					'back_to_items'     => __( '← Torna ai destinatari', 'ecm' ),
				),
				'hierarchical'      => true,
				'public'            => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'show_in_nav_menus' => false,
				'rewrite'           => array(
					'slug'       => 'destinatari',
					'with_front' => false,
				),
			)
		);
	}

	/**
	 * Registra le posizioni menu `primary` e `footer` solo se il tema non lo ha già fatto.
	 */
	public static function register_nav_locations(): void {
		$wanted  = array(
			'primary' => __( 'Menu principale', 'ecm' ),
			'footer'  => __( 'Menu footer', 'ecm' ),
		);
		$missing = array_diff_key( $wanted, get_registered_nav_menus() );
		if ( $missing ) {
			register_nav_menus( $missing );
		}
	}

	/**
	 * Disattiva Gutenberg per i CPT del plugin.
	 *
	 * @param bool   $use       Valore corrente.
	 * @param string $post_type Tipo di contenuto.
	 * @return bool
	 */
	public static function disable_block_editor( $use, $post_type ) {
		return in_array( $post_type, self::CLASSIC_TYPES, true ) ? false : $use;
	}

	/**
	 * Ordinamento manuale predefinito (menu_order ASC, poi titolo) per le query principali.
	 *
	 * @param WP_Query $query Query.
	 */
	public static function default_order( $query ): void {
		if ( ! $query instanceof WP_Query || ! $query->is_main_query() ) {
			return;
		}
		if ( $query->get( 'orderby' ) ) {
			return;
		}

		$post_type = $query->get( 'post_type' );
		$is_ours   = is_string( $post_type ) && in_array( $post_type, self::ORDERED_TYPES, true );

		if ( is_admin() ) {
			if ( $is_ours ) {
				$query->set(
					'orderby',
					array(
						'menu_order' => 'ASC',
						'title'      => 'ASC',
					)
				);
			}
			return;
		}

		if ( $query->is_post_type_archive( 'corso' ) || $query->is_tax( array( 'area_tematica', 'destinatario' ) ) ) {
			$query->set(
				'orderby',
				array(
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				)
			);
		}
	}

	/**
	 * Segnaposto del titolo nell'editor.
	 *
	 * @param string  $text Testo.
	 * @param WP_Post $post Post.
	 * @return string
	 */
	public static function title_placeholder( $text, $post ) {
		switch ( $post->post_type ?? '' ) {
			case 'corso':
				return __( 'Titolo del corso', 'ecm' );
			case 'docente':
				return __( 'Nome e cognome del docente (es. Prof. Mario Rossi)', 'ecm' );
			case 'testimonianza':
				return __( 'Nome di chi lascia la testimonianza', 'ecm' );
		}
		return $text;
	}
}
