<?php
/**
 * Testimonianze (/testimonianze/): la stessa struttura della pagina originale.
 * Hero con foto, recensioni a 5 stelle che scorrono, citazione con ritratto, video degli eventi dal vivo,
 * formazione continua con i numeri e la foto di un corso, citazione della fondatrice.
 *
 * @package ecm
 */

defined( 'ABSPATH' ) || exit;

get_header();
the_post();

$ecm_lead = trim( (string) get_the_content() );
ecm_hero(
	array(
		'size'      => 'page',
		'photo'     => true,
		'title'     => get_the_title(),
		'lead_html' => '' !== $ecm_lead ? apply_filters( 'the_content', $ecm_lead ) : '<p>' . esc_html__( 'Ecco cosa dicono i professionisti sanitari che ogni giorno si formano con noi.', 'ecm' ) . '</p>',
		'image'     => 'hero-testimonianze',
		'focus'     => '34% 30%',
		'focus_m'   => '30% 22%',
		'ctas'      => array(
			array(
				'label' => __( 'Leggi le testimonianze', 'ecm' ),
				'url'   => '#recensioni',
				'icon'  => 'arrow-down',
				'attrs' => array( 'data-scroll-to' => '' ),
			),
		),
		'scroll'    => '#recensioni',
	)
);

get_template_part(
	'template-parts/reviews',
	null,
	array(
		'context' => 'page',
		'id'      => 'recensioni',
		'heading' => __( 'Cosa dicono di noi', 'ecm' ),
	)
);
get_template_part( 'template-parts/quote-featured' );
get_template_part( 'template-parts/videos-eventi' );
get_template_part( 'template-parts/founder' );
get_template_part( 'template-parts/cta-final' );

get_footer();
