<?php
/**
 * Testimonianze (/testimonianze/): tutte le recensioni dal CPT testimonianza, in colonne.
 *
 * @package ecm
 */

defined( 'ABSPATH' ) || exit;

get_header();
the_post();
$ecm_testi = ecm_testimonianze();
?>
<section class="page-head">
	<div class="wrap page-head__grid">
		<h1 class="page-title" data-reveal="lines"><?php the_title(); ?></h1>
		<div class="page-head__intro prose" data-reveal="fade">
			<?php
			if ( '' !== trim( get_the_content() ) ) {
				the_content();
			} else {
				/* translators: %d numero testimonianze */
				echo '<p>' . esc_html( sprintf( _n( '%d recensione di chi ha seguito i nostri corsi.', '%d recensioni di chi ha seguito i nostri corsi.', count( $ecm_testi ), 'ecm' ), count( $ecm_testi ) ) ) . '</p>';
			}
			?>
		</div>
	</div>
</section>

<section class="wall section-tight" aria-label="<?php esc_attr_e( 'Recensioni', 'ecm' ); ?>">
	<div class="wrap">
		<ul class="wall__cols">
			<?php foreach ( $ecm_testi as $ecm_t ) : ?>
				<li class="voice voice--wall" data-reveal="fade">
					<figure>
						<?php echo ecm_icon( 'quote', 'voice__mark' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<blockquote class="voice__text"><p><?php echo nl2br( esc_html( $ecm_t['text'] ) ); ?></p></blockquote>
						<figcaption class="voice__by">
							<span class="voice__name"><?php echo esc_html( $ecm_t['name'] ); ?></span>
							<?php if ( $ecm_t['corso_title'] ) : ?>
								<?php if ( $ecm_t['corso_url'] ) : ?>
									<a class="voice__course" href="<?php echo esc_url( $ecm_t['corso_url'] ); ?>"><?php echo esc_html( $ecm_t['corso_title'] ); ?></a>
								<?php else : ?>
									<span class="voice__course"><?php echo esc_html( $ecm_t['corso_title'] ); ?></span>
								<?php endif; ?>
							<?php endif; ?>
						</figcaption>
					</figure>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>

<?php get_template_part( 'template-parts/cta-final' ); ?>
<?php
get_footer();
