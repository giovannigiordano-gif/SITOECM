<?php
/**
 * Bootstrap del plugin: collega le classi agli hook di WordPress.
 *
 * @package ECM_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Orchestratore del plugin.
 */
final class ECM_Plugin {

	/**
	 * Evita il doppio avvio.
	 *
	 * @var bool
	 */
	private static bool $booted = false;

	/**
	 * Avvia tutti i moduli.
	 */
	public static function boot(): void {
		if ( self::$booted ) {
			return;
		}
		self::$booted = true;

		add_action( 'init', array( __CLASS__, 'load_textdomain' ), 0 );

		ECM_Post_Types::init();
		ECM_Meta::init();
		ECM_Media::init();
		ECM_Redirects::init();
		ECM_Platform_Redirects::init();
		ECM_SEO::init();
		ECM_Consent::init();
		ECM_Forms::init();
		ECM_Data::init();

		if ( is_admin() ) {
			ECM_Admin::init();
			ECM_Term_Meta_UI::init();
			ECM_Meta_Boxes::init();
			ECM_List_Columns::init();
			ECM_Settings_Page::init();
			ECM_Submissions_Admin::init();
			ECM_Import_Page::init();
		}
	}

	/**
	 * Carica le traduzioni (le stringhe sono già in italiano).
	 */
	public static function load_textdomain(): void {
		load_plugin_textdomain( 'ecm', false, dirname( plugin_basename( ECM_CORE_FILE ) ) . '/languages' );
	}

	/**
	 * Attivazione: registra i tipi di contenuto, crea le opzioni e rigenera i permalink.
	 */
	public static function activate(): void {
		ECM_Post_Types::register();
		if ( false === get_option( ECM_Options::OPTION ) ) {
			add_option( ECM_Options::OPTION, ECM_Options::defaults() );
		}
		if ( false === get_option( ECM_Redirects::OPTION ) ) {
			add_option( ECM_Redirects::OPTION, array(), '', false );
		}
		update_option( 'ecm_core_version', ECM_CORE_VERSION, false );
		flush_rewrite_rules( false );
	}

	/**
	 * Disattivazione: rimuove solo le regole di riscrittura. I dati restano.
	 */
	public static function deactivate(): void {
		flush_rewrite_rules( false );
	}
}
