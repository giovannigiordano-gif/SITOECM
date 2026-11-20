<?php
/**
 * Pagina Docenti (/docenti/): elenco dal CPT docente. L'introduzione è il contenuto della pagina.
 *
 * @package ecm
 */

defined( 'ABSPATH' ) || exit;

get_header();
the_post();
$ecm_docenti = ecm_docenti();
?>
<?php
$ecm_intro = trim( (string) get_the_content() );
ecm_hero(
	array(
		'size'      => 'page',
		'photo'     => true,
		'mono'      => true,
		'title'     => get_the_title(),
		'lead_html' => '' !== $ecm_intro ? apply_filters( 'the_content', $ecm_intro ) : '<p>' . esc_html__( 'Docenti universitari, ricercatori e professionisti della clinica: chi insegna nei nostri corsi lavora ogni giorno sui temi che porta nelle lezioni.', 'ecm' ) . '</p>',
		'image'     => 'hero-docenti',
		'focus'     => '60% 40%',
		'ring_text' => __( 'I nostri docenti', 'ecm' ),
		'scroll'    => '#elenco-docenti',
	)
);
?>

<section class="people section-tight" id="elenco-docenti" aria-label="<?php esc_attr_e( 'Elenco docenti', 'ecm' ); ?>">
	<div class="wrap">
		<ul class="people__list">
			<?php foreach ( $ecm_docenti as $ecm_d ) : ?>
				<li class="person" id="docente-<?php echo esc_attr( sanitize_title( $ecm_d['name'] ) ); ?>" data-reveal="fade">
					<div class="person__avatar">
						<?php
						if ( $ecm_d['photo_id'] ) {
							echo wp_get_attachment_image( $ecm_d['photo_id'], 'medium', false, array( 'alt' => $ecm_d['name'], 'loading' => 'lazy', 'sizes' => '160px' ) );
						} else {
							echo '<span class="avatar-type avatar-type--lg" aria-hidden="true">' . esc_html( $ecm_d['initials'] ) . '</span>';
						}
						?>
					</div>
					<div class="person__body">
						<h2 class="person__name"><?php echo esc_html( $ecm_d['name'] ); ?></h2>
						<?php if ( $ecm_d['titolo'] ) : ?>
							<p class="person__role"><?php echo esc_html( $ecm_d['titolo'] ); ?></p>
						<?php endif; ?>
						<?php if ( $ecm_d['bio_html'] ) : ?>
							<div class="person__bio prose prose--sm" data-clamp="6"><?php echo wp_kses_post( wpautop( $ecm_d['bio_html'] ) ); ?></div>
						<?php endif; ?>
						<?php if ( $ecm_d['cv_url'] ) : ?>
							<a class="link-arrow" href="<?php echo esc_url( $ecm_d['cv_url'] ); ?>" target="_blank" rel="noopener">
								<?php echo ecm_icon( 'pdf' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<span><?php echo esc_html( sprintf( /* translators: %s nome */ __( 'Vedi il CV', 'ecm' ) ) ); ?><span class="visually-hidden"> <?php echo esc_html( $ecm_d['name'] ); ?></span></span>
							</a>
						<?php endif; ?>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>

<?php get_template_part( 'template-parts/cta-final' ); ?>
<?php
get_footer();
