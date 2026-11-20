<?php
/**
 * Footer del sito.
 *
 * @package ecm
 */

defined( 'ABSPATH' ) || exit;

$ecm_urls  = ecm_privacy_urls();
$ecm_areas = ecm_areas();
$ecm_soc   = array_filter(
	array(
		'instagram' => array( 'Instagram', (string) ecm_opt( 'instagram_url' ) ),
		'facebook'  => array( 'Facebook', (string) ecm_opt( 'facebook_url' ) ),
		'youtube'   => array( 'YouTube', (string) ecm_opt( 'youtube_url' ) ),
	),
	static fn( $s ) => '' !== $s[1]
);
?>
</main>

<footer class="site-footer" data-footer>
	<div class="site-footer__ring" aria-hidden="true"></div>
	<div class="wrap site-footer__grid">
		<div class="site-footer__brand">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'Torna alla home', 'ecm' ); ?>">
				<?php echo ecm_logo( 'light' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</a>
			<p class="site-footer__claim"><?php esc_html_e( 'Formazione ECM online per i professionisti della salute.', 'ecm' ); ?></p>
			<?php if ( $ecm_soc ) : ?>
				<ul class="site-footer__social" aria-label="<?php esc_attr_e( 'Social', 'ecm' ); ?>">
					<?php foreach ( $ecm_soc as $ecm_icon => $ecm_s ) : ?>
						<li><a href="<?php echo esc_url( $ecm_s[1] ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( $ecm_s[0] ); ?>"><?php echo ecm_icon( $ecm_icon ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>

		<?php if ( $ecm_areas ) : ?>
			<nav class="site-footer__col" aria-labelledby="ft-aree">
				<h2 class="site-footer__title" id="ft-aree"><?php esc_html_e( 'Aree tematiche', 'ecm' ); ?></h2>
				<ul>
					<?php foreach ( $ecm_areas as $ecm_a ) : ?>
						<li><a href="<?php echo esc_url( $ecm_a['url'] ); ?>"><?php echo esc_html( $ecm_a['name'] ); ?></a></li>
					<?php endforeach; ?>
					<li><a href="<?php echo esc_url( ecm_catalog_url() ); ?>"><?php esc_html_e( 'Tutti i corsi', 'ecm' ); ?></a></li>
				</ul>
			</nav>
		<?php endif; ?>

		<nav class="site-footer__col" aria-labelledby="ft-ente">
			<h2 class="site-footer__title" id="ft-ente"><?php esc_html_e( 'Ente', 'ecm' ); ?></h2>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'footer',
					'container'      => false,
					'depth'          => 1,
					'fallback_cb'    => 'ecm_nav_fallback',
				)
			);
			?>
		</nav>

		<div class="site-footer__col">
			<h2 class="site-footer__title"><?php esc_html_e( 'Contatti', 'ecm' ); ?></h2>
			<ul class="site-footer__contacts">
				<?php if ( ecm_opt( 'email_segreteria' ) ) : ?>
					<li><span class="site-footer__label"><?php esc_html_e( 'Segreteria', 'ecm' ); ?></span><a href="mailto:<?php echo esc_attr( antispambot( (string) ecm_opt( 'email_segreteria' ) ) ); ?>"><?php echo esc_html( antispambot( (string) ecm_opt( 'email_segreteria' ) ) ); ?></a></li>
				<?php endif; ?>
				<?php if ( ecm_opt( 'email_supporto' ) ) : ?>
					<li><span class="site-footer__label"><?php esc_html_e( 'Supporto studenti', 'ecm' ); ?></span><a href="mailto:<?php echo esc_attr( antispambot( (string) ecm_opt( 'email_supporto' ) ) ); ?>"><?php echo esc_html( antispambot( (string) ecm_opt( 'email_supporto' ) ) ); ?></a></li>
				<?php endif; ?>
				<?php if ( ecm_opt( 'whatsapp_number' ) ) : ?>
					<li><span class="site-footer__label">WhatsApp</span><a href="<?php echo esc_url( ecm_whatsapp_generic() ); ?>" target="_blank" rel="noopener"><?php echo esc_html( (string) ecm_opt( 'whatsapp_display' ) ); ?></a></li>
				<?php endif; ?>
			</ul>
		</div>
	</div>

	<div class="wrap site-footer__legal">
		<p>
			&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?>
			<?php echo esc_html( (string) ecm_opt( 'ragione_sociale', get_bloginfo( 'name' ) ) ); ?>
			<?php if ( ecm_opt( 'piva' ) ) : ?>
				<span class="site-footer__sep" aria-hidden="true">/</span> <?php esc_html_e( 'P.IVA', 'ecm' ); ?> <?php echo esc_html( (string) ecm_opt( 'piva' ) ); ?>
			<?php endif; ?>
		</p>
		<ul>
			<?php if ( $ecm_urls['termini'] ) : ?>
				<li><a href="<?php echo esc_url( $ecm_urls['termini'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Termini e condizioni', 'ecm' ); ?></a></li>
			<?php endif; ?>
			<?php if ( $ecm_urls['privacy'] ) : ?>
				<li><a href="<?php echo esc_url( $ecm_urls['privacy'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Privacy e cookie', 'ecm' ); ?></a></li>
			<?php endif; ?>
			<li><button type="button" class="linklike" data-cookie-open><?php esc_html_e( 'Preferenze cookie', 'ecm' ); ?></button></li>
		</ul>
	</div>
</footer>

<?php get_template_part( 'template-parts/cookie-banner' ); ?>
<div class="toast" role="status" aria-live="polite" data-toast hidden></div>
<?php wp_footer(); ?>
</body>
</html>
