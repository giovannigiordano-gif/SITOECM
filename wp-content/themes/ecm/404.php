<?php
/**
 * 404: un piatto vuoto, e la strada per tornare ai corsi.
 *
 * @package ecm
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<section class="notfound">
	<div class="wrap notfound__grid">
		<div class="notfound__lens"><?php echo ecm_img( 'empty-plate', array( 'sizes' => '(min-width: 900px) 36vw, 70vw', 'alt' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
		<div class="notfound__copy">
			<p class="notfound__code">404</p>
			<h1 class="page-title page-title--md"><?php esc_html_e( 'Questa pagina non c’è più', 'ecm' ); ?></h1>
			<p><?php esc_html_e( 'Il sito è stato rinnovato e alcuni indirizzi sono cambiati. Il corso che cerchi è quasi certamente nel catalogo.', 'ecm' ); ?></p>
			<div class="notfound__actions">
				<?php
				echo ecm_button( array( 'label' => __( 'Vai ai corsi', 'ecm' ), 'url' => ecm_catalog_url(), 'icon' => 'arrow-right' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
				echo ecm_button( array( 'label' => __( 'Torna alla home', 'ecm' ), 'url' => home_url( '/' ), 'variant' => 'secondary' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
				?>
			</div>
		</div>
	</div>
</section>
<?php
get_footer();
