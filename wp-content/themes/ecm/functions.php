<?php
/**
 * ECM La Lente: bootstrap del tema.
 *
 * Il tema si occupa solo della presentazione. Dati, opzioni, SEO, form e redirect
 * sono nel plugin "ECM Core" (vedi docs/contract-ecm-core.md).
 *
 * @package ecm
 */

defined( 'ABSPATH' ) || exit;

define( 'ECM_THEME_VERSION', '1.0.0' );
define( 'ECM_THEME_DIR', get_template_directory() );
define( 'ECM_THEME_URI', get_template_directory_uri() );

require ECM_THEME_DIR . '/inc/fallbacks.php';
require ECM_THEME_DIR . '/inc/setup.php';
require ECM_THEME_DIR . '/inc/assets.php';
require ECM_THEME_DIR . '/inc/template-tags.php';
require ECM_THEME_DIR . '/inc/images.php';
require ECM_THEME_DIR . '/inc/catalog.php';
