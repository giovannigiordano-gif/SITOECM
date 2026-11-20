<?php
/**
 * Newsletter: email + consenso privacy. Invio via REST (ECM Core), fallback senza JS non necessario
 * perché il form richiede JS solo per l'invio asincrono; senza JS mostra il messaggio di contatto.
 *
 * @package ecm
 */

defined( 'ABSPATH' ) || exit;

$ecm_urls = ecm_privacy_urls();
$ecm_uid  = wp_unique_id( 'nl-' );
?>
<section class="newsletter" aria-labelledby="<?php echo esc_attr( $ecm_uid ); ?>-title">
	<div class="newsletter__bg" aria-hidden="true">
		<?php echo ecm_img( 'lens-microbiota', array( 'sizes' => '100vw', 'alt' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<div class="wrap newsletter__grid">
		<div class="newsletter__copy">
			<h2 class="newsletter__title" id="<?php echo esc_attr( $ecm_uid ); ?>-title" data-reveal="lines"><?php echo esc_html( (string) ecm_opt( 'newsletter_title', __( 'Nuovi corsi, prima di tutti.', 'ecm' ) ) ); ?></h2>
			<p class="newsletter__text"><?php echo esc_html( (string) ecm_opt( 'newsletter_text', __( 'Iscriviti alla newsletter: ti scriviamo quando apre un nuovo corso o una nuova edizione.', 'ecm' ) ) ); ?></p>
		</div>
		<form class="form form--on-ink newsletter__form" data-form="newsletter" novalidate>
			<div class="field field--inline">
				<label class="field__label" for="<?php echo esc_attr( $ecm_uid ); ?>-email"><?php esc_html_e( 'La tua email', 'ecm' ); ?></label>
				<div class="field__row">
					<input class="field__input" type="email" id="<?php echo esc_attr( $ecm_uid ); ?>-email" name="email" autocomplete="email" required aria-describedby="<?php echo esc_attr( $ecm_uid ); ?>-email-err">
					<button class="btn btn--primary" type="submit"><span class="btn__fill" aria-hidden="true"></span><span class="btn__label"><?php esc_html_e( 'Iscriviti', 'ecm' ); ?></span></button>
				</div>
				<p class="field__error" id="<?php echo esc_attr( $ecm_uid ); ?>-email-err" data-error-for="email"></p>
			</div>
			<div class="field field--check">
				<input type="checkbox" id="<?php echo esc_attr( $ecm_uid ); ?>-privacy" name="privacy" value="1" required aria-describedby="<?php echo esc_attr( $ecm_uid ); ?>-privacy-err">
				<label for="<?php echo esc_attr( $ecm_uid ); ?>-privacy">
					<?php
					$ecm_consent = (string) ecm_opt( 'newsletter_consent', __( 'Ho letto l’informativa privacy e acconsento al trattamento dei miei dati per ricevere la newsletter.', 'ecm' ) );
					echo esc_html( $ecm_consent );
					if ( $ecm_urls['privacy'] ) {
						echo ' <a href="' . esc_url( $ecm_urls['privacy'] ) . '" target="_blank" rel="noopener">' . esc_html__( 'Informativa', 'ecm' ) . '</a>';
					}
					?>
				</label>
				<p class="field__error" id="<?php echo esc_attr( $ecm_uid ); ?>-privacy-err" data-error-for="privacy"></p>
			</div>
			<div class="visually-hidden" aria-hidden="true">
				<label for="<?php echo esc_attr( $ecm_uid ); ?>-website">Website</label>
				<input type="text" id="<?php echo esc_attr( $ecm_uid ); ?>-website" name="website" tabindex="-1" autocomplete="off">
			</div>
			<input type="hidden" name="_ts" value="<?php echo esc_attr( (string) time() ); ?>">
			<p class="form__status" role="status" aria-live="polite" data-form-status></p>
		</form>
	</div>
</section>
