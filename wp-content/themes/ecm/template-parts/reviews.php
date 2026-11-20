<?php
/**
 * Recensioni come sul sito originale: due nastri di schede a 5 stelle che scorrono in direzioni opposte,
 * con pulsante di pausa (WCAG 2.2.2). Nella pagina Testimonianze c'è anche l'elenco completo da leggere.
 *
 * Args: context (home|page), heading, intro (HTML), id.
 *
 * @package ecm
 */

defined( 'ABSPATH' ) || exit;

$ecm_ctx  = $args['context'] ?? 'home';
$ecm_id   = $args['id'] ?? 'recensioni';
$ecm_all  = ecm_testimonianze();
// La testimonianza con foto o video ha una sezione propria: non la ripetiamo nei nastri.
$ecm_all = array_values( array_filter( $ecm_all, static fn( $t ) => empty( $t['photo_id'] ) && empty( $t['video'] ) ) );
if ( ! $ecm_all ) {
	return;
}
$ecm_rated = array_values( array_filter( $ecm_all, static fn( $t ) => (int) $t['rating'] > 0 ) );
$ecm_other = array_values( array_filter( $ecm_all, static fn( $t ) => (int) $t['rating'] <= 0 ) );
if ( count( $ecm_rated ) < 4 || count( $ecm_other ) < 4 ) {
	$ecm_half  = (int) ceil( count( $ecm_all ) / 2 );
	$ecm_rated = array_slice( $ecm_all, 0, $ecm_half );
	$ecm_other = array_slice( $ecm_all, $ecm_half );
}
$ecm_rows = array_filter( array( $ecm_rated, $ecm_other ) );
?>
<section class="reviews section" id="<?php echo esc_attr( $ecm_id ); ?>" data-reviews aria-labelledby="<?php echo esc_attr( $ecm_id ); ?>-title">
	<header class="reviews__head wrap">
		<h2 class="section-title" id="<?php echo esc_attr( $ecm_id ); ?>-title" data-reveal="lines"><?php echo esc_html( $args['heading'] ?? __( 'Testimonianze', 'ecm' ) ); ?></h2>
		<div class="reviews__tools">
			<button type="button" class="btn btn--ghost btn--sm has-icon reviews__pause" data-reviews-pause aria-pressed="false">
				<span class="btn__fill" aria-hidden="true"></span>
				<span class="btn__icon"><?php echo ecm_icon( 'pause', 'reviews__ico-pause' ) . ecm_icon( 'play', 'reviews__ico-play' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<span class="btn__label"><?php esc_html_e( 'Metti in pausa', 'ecm' ); ?></span>
			</button>
			<?php if ( 'home' === $ecm_ctx ) : ?>
				<a class="link-arrow" href="<?php echo esc_url( ecm_page_url( 'testimonianze' ) ); ?>"><span><?php esc_html_e( 'Tutte le testimonianze', 'ecm' ); ?></span><?php echo ecm_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
			<?php endif; ?>
		</div>
	</header>

	<div class="reviews__rows">
		<?php foreach ( array_values( $ecm_rows ) as $ecm_r => $ecm_row ) : ?>
			<div class="reviews__row<?php echo $ecm_r % 2 ? ' reviews__row--rev' : ''; ?>" style="--dur: <?php echo (int) max( 40, count( $ecm_row ) * 9 ); ?>s">
				<?php for ( $ecm_loop = 0; $ecm_loop < 2; $ecm_loop++ ) : ?>
					<ul class="reviews__track" <?php echo $ecm_loop ? 'aria-hidden="true" inert' : ''; ?>>
						<?php foreach ( $ecm_row as $ecm_t ) : ?>
							<li><?php echo ecm_review_card( $ecm_t, true ); // phpcs:ignore WordPress.Security.EscapeOutput ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endfor; ?>
			</div>
		<?php endforeach; ?>
	</div>

	<?php if ( 'page' === $ecm_ctx ) : ?>
		<div class="wrap reviews__more">
			<button type="button" class="btn btn--secondary" data-reviews-more aria-expanded="false" aria-controls="<?php echo esc_attr( $ecm_id ); ?>-wall">
				<span class="btn__fill" aria-hidden="true"></span>
				<span class="btn__label"><?php esc_html_e( 'Leggi tutte le recensioni', 'ecm' ); ?></span>
			</button>
			<ul class="reviews__wall" id="<?php echo esc_attr( $ecm_id ); ?>-wall" data-reviews-wall hidden>
				<?php foreach ( $ecm_all as $ecm_t ) : ?>
					<li><?php echo ecm_review_card( $ecm_t ); // phpcs:ignore WordPress.Security.EscapeOutput ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>
</section>
