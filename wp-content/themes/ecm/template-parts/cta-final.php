<?php
/**
 * CTA finale: un solo invito, due canali (WhatsApp, contatti).
 *
 * @package ecm
 */

defined( 'ABSPATH' ) || exit;

$ecm_title = (string) ecm_opt( 'cta_title', __( 'Non sai quale corso fa per te?', 'ecm' ) );
$ecm_text  = (string) ecm_opt( 'cta_text', __( 'Scrivici: ti aiutiamo a scegliere in base alla tua professione e ai crediti che ti servono.', 'ecm' ) );
?>
<section class="cta-final" aria-labelledby="cta-final-title" data-cta-final>
	<div class="cta-final__disc" aria-hidden="true"></div>
	<div class="wrap cta-final__inner">
		<h2 class="cta-final__title" id="cta-final-title" data-reveal="lines"><?php echo esc_html( $ecm_title ); ?></h2>
		<p class="cta-final__text"><?php echo esc_html( $ecm_text ); ?></p>
		<div class="cta-final__actions">
			<?php
			if ( ecm_opt( 'whatsapp_number' ) ) {
				echo ecm_button( // phpcs:ignore WordPress.Security.EscapeOutput
					array(
						'label'    => __( 'Scrivici su WhatsApp', 'ecm' ),
						'url'      => ecm_whatsapp_generic(),
						'variant'  => 'on-ink',
						'icon'     => 'whatsapp',
						'icon_pos' => 'start',
						'attrs'    => ecm_ext_attrs(),
					)
				);
			}
			echo ecm_button( // phpcs:ignore WordPress.Security.EscapeOutput
				array(
					'label'   => __( 'Vai ai contatti', 'ecm' ),
					'url'     => ecm_page_url( 'contatti' ),
					'variant' => 'ghost-on-ink',
					'icon'    => 'arrow-right',
				)
			);
			?>
		</div>
	</div>
</section>
