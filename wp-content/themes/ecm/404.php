<?php
/**
 * 404: la lente non trova nulla, e la strada per tornare ai corsi.
 *
 * @package ecm
 */

defined( 'ABSPATH' ) || exit;

get_header();
ecm_hero(
	array(
		'size'      => 'full',
		'title'     => __( 'Questa pagina non c’è più', 'ecm' ),
		'meta_html' => '<span class="hero__code">404</span>',
		'lead'      => __( 'Il sito è stato rinnovato e alcuni indirizzi sono cambiati. Il corso che cerchi è quasi certamente nel catalogo.', 'ecm' ),
		'image'     => 'hero-corsi',
		'ring_text' => __( 'Pagina non trovata', 'ecm' ),
		'ctas'      => array(
			array(
				'label' => __( 'Esplora i corsi', 'ecm' ),
				'url'   => ecm_catalog_url(),
				'icon'  => 'arrow-right',
			),
			array(
				'label'   => __( 'Torna alla home', 'ecm' ),
				'url'     => home_url( '/' ),
				'variant' => 'secondary',
			),
		),
	)
);
get_footer();
