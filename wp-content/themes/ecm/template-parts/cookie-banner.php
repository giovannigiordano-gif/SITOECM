<?php
/**
 * Banner cookie: non bloccante, consenso granulare, preferenze riapribili dal footer.
 * Gli script marcati con data-ecm-consent="statistics|marketing" vengono attivati solo dopo il consenso.
 *
 * @package ecm
 */

defined( 'ABSPATH' ) || exit;

$ecm_urls = ecm_privacy_urls();
?>
<section class="cookie" data-cookie role="dialog" aria-modal="false" aria-labelledby="cookie-title" aria-describedby="cookie-text" hidden>
	<div class="cookie__body">
		<h2 class="cookie__title" id="cookie-title"><?php esc_html_e( 'Cookie', 'ecm' ); ?></h2>
		<p class="cookie__text" id="cookie-text">
			<?php esc_html_e( 'Usiamo cookie tecnici per far funzionare il sito. Con il tuo consenso usiamo anche cookie statistici e di marketing.', 'ecm' ); ?>
			<?php if ( $ecm_urls['cookie'] ) : ?>
				<a href="<?php echo esc_url( $ecm_urls['cookie'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Leggi l’informativa', 'ecm' ); ?></a>
			<?php endif; ?>
		</p>

		<div class="cookie__prefs" id="cookie-prefs" data-cookie-prefs hidden>
			<label class="switch">
				<input type="checkbox" checked disabled>
				<span class="switch__ui" aria-hidden="true"></span>
				<span class="switch__label"><strong><?php esc_html_e( 'Tecnici', 'ecm' ); ?></strong> <?php esc_html_e( 'Sempre attivi, necessari al sito.', 'ecm' ); ?></span>
			</label>
			<label class="switch">
				<input type="checkbox" name="statistics" data-cookie-cat="statistics">
				<span class="switch__ui" aria-hidden="true"></span>
				<span class="switch__label"><strong><?php esc_html_e( 'Statistici', 'ecm' ); ?></strong> <?php esc_html_e( 'Ci aiutano a capire come viene usato il sito.', 'ecm' ); ?></span>
			</label>
			<label class="switch">
				<input type="checkbox" name="marketing" data-cookie-cat="marketing">
				<span class="switch__ui" aria-hidden="true"></span>
				<span class="switch__label"><strong><?php esc_html_e( 'Marketing', 'ecm' ); ?></strong> <?php esc_html_e( 'Misurano le campagne sui social.', 'ecm' ); ?></span>
			</label>
		</div>
	</div>
	<div class="cookie__actions">
		<button type="button" class="btn btn--ghost btn--sm" data-cookie-custom aria-controls="cookie-prefs" aria-expanded="false"><span class="btn__label"><?php esc_html_e( 'Personalizza', 'ecm' ); ?></span></button>
		<button type="button" class="btn btn--secondary btn--sm" data-cookie-reject><span class="btn__fill" aria-hidden="true"></span><span class="btn__label"><?php esc_html_e( 'Solo necessari', 'ecm' ); ?></span></button>
		<button type="button" class="btn btn--primary btn--sm" data-cookie-accept><span class="btn__fill" aria-hidden="true"></span><span class="btn__label"><?php esc_html_e( 'Accetta tutti', 'ecm' ); ?></span></button>
		<button type="button" class="btn btn--primary btn--sm" data-cookie-save hidden><span class="btn__fill" aria-hidden="true"></span><span class="btn__label"><?php esc_html_e( 'Salva preferenze', 'ecm' ); ?></span></button>
	</div>
</section>
