<?php
/**
 * Setup del tema: supporti, menu, dimensioni immagini, pulizia dell'head.
 *
 * @package ecm
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'after_setup_theme',
	static function () {
		load_theme_textdomain( 'ecm', ECM_THEME_DIR . '/languages' );

		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
		remove_theme_support( 'core-block-patterns' );

		register_nav_menus(
			array(
				'primary' => __( 'Navigazione principale', 'ecm' ),
				'footer'  => __( 'Footer · Ente', 'ecm' ),
			)
		);

		// Copertine dei corsi: mai ritagliate (spesso contengono testo), solo ridimensionate.
		add_image_size( 'ecm-card', 720, 9999, false );
		add_image_size( 'ecm-hero', 1400, 9999, false );
		// Loghi dei patrocini.
		add_image_size( 'ecm-logo', 480, 240, false );
	}
);

/**
 * Rimuove dall'head ciò che non serve a un sito vetrina.
 */
add_action(
	'init',
	static function () {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		remove_action( 'wp_head', 'rest_output_link_wp_head' );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		add_filter( 'emoji_svg_url', '__return_false' );
	}
);

/**
 * Niente CSS dei blocchi dove non ci sono blocchi.
 */
add_action(
	'wp_enqueue_scripts',
	static function () {
		wp_dequeue_style( 'classic-theme-styles' );
		wp_dequeue_style( 'global-styles' );
		if ( ! is_singular() || ! has_blocks( get_queried_object_id() ) ) {
			wp_dequeue_style( 'wp-block-library' );
			wp_dequeue_style( 'wp-block-library-theme' );
		}
	},
	100
);

/**
 * Classi body utili ai template e al motion.
 */
add_filter(
	'body_class',
	static function ( array $classes ): array {
		if ( is_front_page() ) {
			$classes[] = 'is-home';
		}
		if ( is_singular( 'corso' ) ) {
			$classes[] = 'has-sticky-cta';
		}
		// Tutti i template aprono con una hero scura a tutto schermo: header chiaro finché non si scorre.
		$classes[] = 'has-hero';
		return $classes;
	}
);

/**
 * Favicon e icone app dal marchio esistente (il cerchio con la "e").
 */
add_action(
	'wp_head',
	static function () {
		if ( has_site_icon() ) {
			return;
		}
		$b = ECM_THEME_URI . '/assets/img/brand/';
		printf( '<link rel="icon" href="%s" sizes="32x32">' . "\n", esc_url( $b . 'icon-32.png' ) );
		printf( '<link rel="icon" href="%s" sizes="192x192">' . "\n", esc_url( $b . 'icon-192.png' ) );
		printf( '<link rel="apple-touch-icon" href="%s">' . "\n", esc_url( $b . 'icon-180.png' ) );
	},
	2
);

add_action(
	'wp_head',
	static function () {
		echo '<meta name="theme-color" content="#f7f7fb">' . "\n";
	},
	1
);

/**
 * Estratto: niente "[...]".
 */
add_filter( 'excerpt_more', static fn() => '…' );
add_filter( 'excerpt_length', static fn() => 32 );

/**
 * La ricerca nativa porta al catalogo filtrato: un solo posto dove cercare i corsi.
 */
add_action(
	'template_redirect',
	static function () {
		if ( is_search() && ! is_admin() && function_exists( 'get_post_type_archive_link' ) ) {
			$archive = get_post_type_archive_link( 'corso' );
			if ( $archive ) {
				wp_safe_redirect( add_query_arg( 'q', rawurlencode( get_search_query( false ) ), $archive ), 302 );
				exit;
			}
		}
	}
);

/**
 * Archivio corsi: tutti i corsi in una pagina (sono poche decine) nell'ordine manuale.
 */
add_action(
	'pre_get_posts',
	static function ( WP_Query $q ) {
		if ( ! is_admin() && $q->is_main_query() && $q->is_post_type_archive( 'corso' ) ) {
			$q->set( 'posts_per_page', -1 );
			$q->set( 'orderby', array( 'menu_order' => 'ASC', 'title' => 'ASC' ) );
			$q->set( 'no_found_rows', true );
		}
	}
);

/**
 * Testi identici all'originale: niente sostituzioni tipografiche automatiche
 * (virgolette curve, trattini, puntini) su titoli e descrizioni dei corsi.
 */
add_filter( 'run_wptexturize', '__return_false' );
