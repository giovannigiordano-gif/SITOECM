<?php
/**
 * Plugin Name:       ECM Core
 * Plugin URI:        https://www.corsidiformazionecm.it/
 * Description:       Dati del sito Corsi di Formazione ECM: corsi, docenti, testimonianze, aree tematiche, impostazioni, redirect, SEO, moduli e importazione.
 * Version:           1.0.0
 * Requires at least: 6.9
 * Requires PHP:      8.1
 * Author:            Corsi di Formazione ECM
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ecm
 * Domain Path:       /languages
 *
 * @package ECM_Core
 */

defined( 'ABSPATH' ) || exit;

define( 'ECM_CORE_VERSION', '1.0.0' );
define( 'ECM_CORE_FILE', __FILE__ );
define( 'ECM_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'ECM_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once ECM_CORE_DIR . 'includes/class-ecm-logger.php';
require_once ECM_CORE_DIR . 'includes/class-ecm-options.php';
require_once ECM_CORE_DIR . 'includes/class-ecm-post-types.php';
require_once ECM_CORE_DIR . 'includes/class-ecm-meta.php';
require_once ECM_CORE_DIR . 'includes/helpers.php';
require_once ECM_CORE_DIR . 'includes/class-ecm-media.php';
require_once ECM_CORE_DIR . 'includes/class-ecm-redirects.php';
require_once ECM_CORE_DIR . 'includes/class-ecm-seo.php';
require_once ECM_CORE_DIR . 'includes/class-ecm-consent.php';
require_once ECM_CORE_DIR . 'includes/class-ecm-newsletter.php';
require_once ECM_CORE_DIR . 'includes/class-ecm-forms.php';
require_once ECM_CORE_DIR . 'includes/class-ecm-importer.php';
require_once ECM_CORE_DIR . 'includes/class-ecm-plugin.php';

if ( is_admin() ) {
	require_once ECM_CORE_DIR . 'includes/admin/class-ecm-admin.php';
	require_once ECM_CORE_DIR . 'includes/admin/class-ecm-term-meta-ui.php';
	require_once ECM_CORE_DIR . 'includes/admin/class-ecm-meta-boxes.php';
	require_once ECM_CORE_DIR . 'includes/admin/class-ecm-list-columns.php';
	require_once ECM_CORE_DIR . 'includes/admin/class-ecm-settings-page.php';
	require_once ECM_CORE_DIR . 'includes/admin/class-ecm-submissions-admin.php';
	require_once ECM_CORE_DIR . 'includes/admin/class-ecm-import-page.php';
}

register_activation_hook( __FILE__, array( 'ECM_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ECM_Plugin', 'deactivate' ) );

ECM_Plugin::boot();
