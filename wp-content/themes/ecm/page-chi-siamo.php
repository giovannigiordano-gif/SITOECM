<?php
/**
 * Chi siamo (/chi-siamo/).
 *
 * Tutti i testi vengono dalla pagina (introduzione, Mission, Vision) e dalle impostazioni (fondatrice, numeri):
 * il primo paragrafo apre la hero, gli altri diventano "Cosa facciamo", i titoli H2 "Mission" e "Vision" la fascia
 * scura. Seguono numeri e fondatrice, i volti dei docenti, i patrocini. Se il contenuto non ha questa forma,
 * viene stampato così com'è.
 *
 * @package ecm
 */

defined( 'ABSPATH' ) || exit;

get_header();
the_post();

$ecm_raw   = (string) get_post_field( 'post_content', get_the_ID() );
$ecm_parts = preg_split( '#<h2[^>]*>(.*?)</h2>#is', $ecm_raw, -1, PREG_SPLIT_DELIM_CAPTURE );
$ecm_intro = preg_match_all( '#<p[^>]*>(.*?)</p>#is', (string) ( $ecm_parts[0] ?? '' ), $ecm_m ) ? array_values( array_filter( array_map( 'trim', $ecm_m[1] ) ) ) : array();
$ecm_secs  = array();
for ( $ecm_i = 1; $ecm_i < count( $ecm_parts ) - 1; $ecm_i += 2 ) {
	$ecm_secs[ sanitize_title( wp_strip_all_tags( $ecm_parts[ $ecm_i ] ) ) ] = trim( wp_strip_all_tags( $ecm_parts[ $ecm_i + 1 ] ) );
}
$ecm_structured = (bool) $ecm_intro;
$ecm_docenti    = ecm_docenti();
$ecm_stats      = ecm_stats();

ecm_hero(
	array(
		'size'    => 'page',
		'photo'   => true,
		'title'   => get_the_title(),
		'lead'    => $ecm_structured ? wp_strip_all_tags( $ecm_intro[0] ) : ( has_excerpt() ? get_the_excerpt() : '' ),
		'image'   => 'hero-chi-siamo',
		'focus'   => '42% 40%',
		'focus_m' => '36% 30%',
		'scroll'  => '#cosa-facciamo',
	)
);
?>

<?php if ( $ecm_structured ) : ?>
	<?php if ( count( $ecm_intro ) > 1 ) : ?>
		<section class="about-do section" id="cosa-facciamo" aria-labelledby="about-do-title">
			<div class="wrap about-do__grid">
				<h2 class="section-title about-do__title" id="about-do-title" data-reveal="lines"><?php esc_html_e( 'Cosa facciamo', 'ecm' ); ?></h2>
				<ul class="about-do__list">
					<?php foreach ( array_slice( $ecm_intro, 1 ) as $ecm_p ) : ?>
						<li class="about-do__item" data-reveal="fade">
							<span class="about-do__ring" aria-hidden="true"></span>
							<p><?php echo wp_kses_post( $ecm_p ); ?></p>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( ! empty( $ecm_secs['mission'] ) || ! empty( $ecm_secs['vision'] ) ) : ?>
		<section class="about-mv" aria-label="<?php esc_attr_e( 'Mission e Vision', 'ecm' ); ?>">
			<div class="wrap about-mv__grid">
				<?php foreach ( array( 'mission' => 'Mission', 'vision' => 'Vision' ) as $ecm_k => $ecm_label ) : ?>
					<?php if ( ! empty( $ecm_secs[ $ecm_k ] ) ) : ?>
						<article class="about-mv__item">
							<h2 class="about-mv__label"><span class="about-mv__ring" aria-hidden="true"></span><?php echo esc_html( $ecm_label ); ?></h2>
							<p class="about-mv__text" data-scrub-words><?php echo esc_html( $ecm_secs[ $ecm_k ] ); ?></p>
						</article>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>
<?php else : ?>
	<section class="about-body section-tight" id="cosa-facciamo">
		<div class="wrap">
			<div class="prose prose--editorial" data-prose><?php the_content(); ?></div>
		</div>
	</section>
<?php endif; ?>

<?php get_template_part( 'template-parts/founder' ); ?>

<?php if ( $ecm_docenti ) : ?>
	<section class="about-team section" aria-labelledby="about-team-title">
		<div class="wrap about-team__grid">
			<header class="about-team__head">
				<h2 class="section-title" id="about-team-title" data-reveal="lines"><?php esc_html_e( 'Chi insegna', 'ecm' ); ?></h2>
				<p class="about-team__intro"><?php esc_html_e( 'Docenti universitari, ricercatori e clinici che portano nei corsi ciò che fanno ogni giorno.', 'ecm' ); ?></p>
				<a class="link-arrow" href="<?php echo esc_url( ecm_page_url( 'docenti' ) ); ?>">
					<span><?php echo esc_html( sprintf( /* translators: %d numero docenti */ __( 'Conosci tutti i %d docenti', 'ecm' ), count( $ecm_docenti ) ) ); ?></span>
					<?php echo ecm_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</a>
			</header>
			<ul class="about-team__faces">
				<?php foreach ( $ecm_docenti as $ecm_d ) : ?>
					<li class="about-team__face" data-reveal="fade">
						<a href="<?php echo esc_url( ecm_page_url( 'docenti' ) . '#docente-' . sanitize_title( $ecm_d['name'] ) ); ?>">
							<span class="about-team__photo">
								<?php
								if ( $ecm_d['photo_id'] ) {
									echo wp_get_attachment_image( $ecm_d['photo_id'], 'thumbnail', false, array( 'alt' => '', 'loading' => 'lazy' ) );
								} else {
									echo '<span class="avatar-type" aria-hidden="true">' . esc_html( $ecm_d['initials'] ) . '</span>';
								}
								?>
							</span>
							<span class="about-team__name"><?php echo esc_html( $ecm_d['name'] ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>
<?php endif; ?>

<?php
ecm_patrons_band(
	ecm_all_patrons(),
	array(
		'id'         => 'patrocini',
		'theme'      => 'paper',
		/* translators: %d numero enti */
		'count_text' => $ecm_stats['patrocini_ordini'] ? sprintf( __( '%d enti e ordini professionali', 'ecm' ), (int) $ecm_stats['patrocini_ordini'] ) : '',
	)
);
get_template_part( 'template-parts/cta-final' );
get_footer();
