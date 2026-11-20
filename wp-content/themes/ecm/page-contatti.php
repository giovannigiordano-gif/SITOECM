<?php
/**
 * Contatti (/contatti/): canali diretti (email segreteria, supporto studenti, WhatsApp) + modulo richiesta informazioni.
 *
 * @package ecm
 */

defined( 'ABSPATH' ) || exit;

get_header();
the_post();

$ecm_urls     = ecm_privacy_urls();
$ecm_courses  = ecm_courses();
$ecm_dest     = ecm_destinatari();
$ecm_channels = array();
if ( ecm_opt( 'email_segreteria' ) ) {
	$ecm_channels[] = array( 'mail', __( 'Segreteria', 'ecm' ), __( 'Informazioni su corsi, iscrizioni e pagamenti.', 'ecm' ), antispambot( (string) ecm_opt( 'email_segreteria' ) ), 'mailto:' . antispambot( (string) ecm_opt( 'email_segreteria' ) ), true );
}
if ( ecm_opt( 'email_supporto' ) ) {
	$ecm_channels[] = array( 'student', __( 'Supporto studenti', 'ecm' ), __( 'Per chi è già iscritto: accesso alla piattaforma, lezioni, attestati.', 'ecm' ), antispambot( (string) ecm_opt( 'email_supporto' ) ), 'mailto:' . antispambot( (string) ecm_opt( 'email_supporto' ) ), true );
}
if ( ecm_opt( 'whatsapp_number' ) ) {
	$ecm_channels[] = array( 'whatsapp', 'WhatsApp', __( 'La via più rapida per una domanda veloce.', 'ecm' ), (string) ecm_opt( 'whatsapp_display' ), ecm_whatsapp_generic(), false );
}
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- preselezione del corso via link.
$ecm_pre = isset( $_GET['corso'] ) ? absint( $_GET['corso'] ) : 0;
?>
<section class="page-head">
	<div class="wrap page-head__grid">
		<h1 class="page-title" data-reveal="lines"><?php the_title(); ?></h1>
		<div class="page-head__intro prose" data-reveal="fade">
			<?php
			if ( '' !== trim( get_the_content() ) ) {
				the_content();
			} else {
				echo '<p>' . esc_html__( 'Scrivici per informazioni su un corso, sull’iscrizione o sull’accesso alla piattaforma.', 'ecm' ) . '</p>';
			}
			?>
		</div>
	</div>
</section>

<section class="channels section-tight" aria-label="<?php esc_attr_e( 'Canali di contatto', 'ecm' ); ?>">
	<div class="wrap">
		<ul class="channels__list">
			<?php foreach ( $ecm_channels as $ecm_ch ) : ?>
				<li class="channel" data-reveal="fade">
					<span class="channel__icon" aria-hidden="true"><?php echo ecm_icon( $ecm_ch[0] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<div class="channel__body">
						<h2 class="channel__title"><?php echo esc_html( $ecm_ch[1] ); ?></h2>
						<p class="channel__text"><?php echo esc_html( $ecm_ch[2] ); ?></p>
					</div>
					<div class="channel__actions">
						<a class="channel__value" href="<?php echo esc_url( $ecm_ch[4] ); ?>" <?php echo $ecm_ch[5] ? '' : 'target="_blank" rel="noopener"'; ?>><?php echo esc_html( $ecm_ch[3] ); ?></a>
						<?php if ( $ecm_ch[5] ) : ?>
							<button type="button" class="icon-btn icon-btn--sm" data-copy="<?php echo esc_attr( $ecm_ch[3] ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s indirizzo */ __( 'Copia %s', 'ecm' ), $ecm_ch[3] ) ); ?>"><?php echo ecm_icon( 'copy' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
						<?php endif; ?>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>

<section class="contact-form section-tight" aria-labelledby="cf-title">
	<div class="wrap contact-form__grid">
		<header class="contact-form__head">
			<h2 class="section-title section-title--md" id="cf-title" data-reveal="lines"><?php esc_html_e( 'Richiedi informazioni', 'ecm' ); ?></h2>
			<p><?php esc_html_e( 'Compila il modulo: la segreteria ti risponde via email.', 'ecm' ); ?></p>
		</header>

		<form class="form" data-form="contact" novalidate>
			<div class="form__grid">
				<div class="field">
					<label class="field__label" for="cf-nome"><?php esc_html_e( 'Nome e cognome', 'ecm' ); ?> <span class="req" aria-hidden="true">*</span></label>
					<input class="field__input" id="cf-nome" name="nome" type="text" autocomplete="name" required aria-describedby="cf-nome-err">
					<p class="field__error" id="cf-nome-err" data-error-for="nome"></p>
				</div>
				<div class="field">
					<label class="field__label" for="cf-email"><?php esc_html_e( 'Email', 'ecm' ); ?> <span class="req" aria-hidden="true">*</span></label>
					<input class="field__input" id="cf-email" name="email" type="email" autocomplete="email" required aria-describedby="cf-email-err">
					<p class="field__error" id="cf-email-err" data-error-for="email"></p>
				</div>
				<div class="field">
					<label class="field__label" for="cf-tel"><?php esc_html_e( 'Telefono', 'ecm' ); ?> <span class="field__opt"><?php esc_html_e( 'facoltativo', 'ecm' ); ?></span></label>
					<input class="field__input" id="cf-tel" name="telefono" type="tel" autocomplete="tel" inputmode="tel">
				</div>
				<div class="field field--select">
					<label class="field__label" for="cf-prof"><?php esc_html_e( 'Professione', 'ecm' ); ?> <span class="field__opt"><?php esc_html_e( 'facoltativo', 'ecm' ); ?></span></label>
					<div class="select">
						<select id="cf-prof" name="professione">
							<option value=""><?php esc_html_e( 'Seleziona', 'ecm' ); ?></option>
							<?php foreach ( $ecm_dest as $ecm_d ) : ?>
								<option value="<?php echo esc_attr( $ecm_d['name'] ); ?>"><?php echo esc_html( $ecm_d['name'] ); ?></option>
							<?php endforeach; ?>
							<option value="Altro"><?php esc_html_e( 'Altro', 'ecm' ); ?></option>
						</select>
						<?php echo ecm_icon( 'caret-down', 'select__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>
				</div>
				<div class="field field--select field--full">
					<label class="field__label" for="cf-corso"><?php esc_html_e( 'Corso di interesse', 'ecm' ); ?> <span class="field__opt"><?php esc_html_e( 'facoltativo', 'ecm' ); ?></span></label>
					<div class="select">
						<select id="cf-corso" name="corso">
							<option value=""><?php esc_html_e( 'Nessun corso in particolare', 'ecm' ); ?></option>
							<?php foreach ( $ecm_courses as $ecm_c ) : ?>
								<option value="<?php echo esc_attr( $ecm_c['title'] ); ?>" <?php selected( $ecm_pre, $ecm_c['id'] ); ?>><?php echo esc_html( wp_trim_words( $ecm_c['title'], 12, '…' ) ); ?></option>
							<?php endforeach; ?>
						</select>
						<?php echo ecm_icon( 'caret-down', 'select__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>
				</div>
				<div class="field field--full">
					<label class="field__label" for="cf-msg"><?php esc_html_e( 'Messaggio', 'ecm' ); ?> <span class="req" aria-hidden="true">*</span></label>
					<textarea class="field__input" id="cf-msg" name="messaggio" rows="6" required aria-describedby="cf-msg-err"></textarea>
					<p class="field__error" id="cf-msg-err" data-error-for="messaggio"></p>
				</div>
				<div class="field field--check field--full">
					<input type="checkbox" id="cf-privacy" name="privacy" value="1" required aria-describedby="cf-privacy-err">
					<label for="cf-privacy">
						<?php
						echo esc_html( (string) ecm_opt( 'contact_consent', __( 'Ho letto l’informativa privacy e acconsento al trattamento dei miei dati per ricevere una risposta.', 'ecm' ) ) );
						if ( $ecm_urls['privacy'] ) {
							echo ' <a href="' . esc_url( $ecm_urls['privacy'] ) . '" target="_blank" rel="noopener">' . esc_html__( 'Informativa', 'ecm' ) . '</a>';
						}
						?>
					</label>
					<p class="field__error" id="cf-privacy-err" data-error-for="privacy"></p>
				</div>
			</div>
			<div class="visually-hidden" aria-hidden="true">
				<label for="cf-website">Website</label>
				<input type="text" id="cf-website" name="website" tabindex="-1" autocomplete="off">
			</div>
			<input type="hidden" name="_ts" value="<?php echo esc_attr( (string) time() ); ?>">
			<div class="form__foot">
				<button class="btn btn--primary has-icon" type="submit"><span class="btn__fill" aria-hidden="true"></span><span class="btn__label"><?php esc_html_e( 'Invia la richiesta', 'ecm' ); ?></span><span class="btn__icon"><?php echo ecm_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span></button>
				<p class="form__status" role="status" aria-live="polite" data-form-status></p>
			</div>
		</form>
	</div>
</section>
<?php
get_footer();
