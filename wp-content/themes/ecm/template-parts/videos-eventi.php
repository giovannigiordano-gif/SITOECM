<?php
/**
 * Video degli eventi dal vivo (come sul sito originale, su fondo cremisi): doppio slider infinito,
 * due file che scorrono in direzioni opposte. Pausa al passaggio del mouse, con il pulsante e quando parte un video.
 * Anteprime locali: Vimeo viene contattato solo quando il visitatore preme play.
 *
 * @package ecm
 */

defined( 'ABSPATH' ) || exit;

$ecm_videos = ecm_video_eventi();
if ( ! $ecm_videos ) {
	return;
}
// Due file come nell'originale: la seconda riparte dalla metà dell'elenco.
$ecm_half = (int) ceil( count( $ecm_videos ) / 2 );
$ecm_rows = array(
	array_slice( $ecm_videos, 0, max( 1, $ecm_half ) ),
	array_merge( array_slice( $ecm_videos, $ecm_half ), array_slice( $ecm_videos, 0, max( 0, $ecm_half - ( count( $ecm_videos ) - $ecm_half ) ) ) ),
);
$ecm_rows = array_values( array_filter( $ecm_rows ) );

$ecm_reel = static function ( array $v, int $n, bool $copy ): string {
	$label = $v['title'] ? $v['title'] : __( 'Video', 'ecm' );
	$img   = $v['thumb_id'] ? wp_get_attachment_image( $v['thumb_id'], 'medium_large', false, array( 'class' => 'reel__img', 'alt' => '', 'loading' => 'lazy', 'sizes' => '240px' ) ) : '';
	return sprintf(
		'<li class="reel"><button type="button" class="reel__play" data-video="%1$s" data-video-title="%2$s" aria-label="%3$s"%4$s>%5$s<span class="reel__icon" aria-hidden="true">%6$s</span><span class="reel__label" aria-hidden="true">%2$s</span></button></li>',
		esc_url( $v['embed'] ),
		esc_attr( $label ),
		esc_attr( sprintf( /* translators: 1: titolo, 2: numero */ __( 'Guarda il video: %1$s (%2$d)', 'ecm' ), $label, $n ) ),
		$copy ? ' tabindex="-1"' : '',
		$img,
		ecm_icon( 'play' )
	);
};
?>
<section class="reels section" data-reviews aria-labelledby="reels-title">
	<div class="wrap reels__head">
		<h2 class="section-title reels__title" id="reels-title" data-reveal="lines"><?php echo esc_html( (string) ecm_opt( 'video_titolo', __( 'Testimonianze dei nostri eventi dal vivo', 'ecm' ) ) ); ?></h2>
		<button type="button" class="btn btn--ghost-on-ink btn--sm has-icon reviews__pause" data-reviews-pause aria-pressed="false">
			<span class="btn__fill" aria-hidden="true"></span>
			<span class="btn__icon"><?php echo ecm_icon( 'pause', 'reviews__ico-pause' ) . ecm_icon( 'play', 'reviews__ico-play' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<span class="btn__label"><?php esc_html_e( 'Metti in pausa', 'ecm' ); ?></span>
		</button>
	</div>
	<div class="reels__rows">
		<?php foreach ( $ecm_rows as $ecm_r => $ecm_row ) : ?>
			<div class="reviews__row reels__row<?php echo $ecm_r % 2 ? ' reviews__row--rev' : ''; ?>" style="--dur: <?php echo (int) max( 50, count( $ecm_row ) * 14 ); ?>s">
				<?php for ( $ecm_loop = 0; $ecm_loop < 2; $ecm_loop++ ) : ?>
					<ul class="reviews__track reels__track" <?php echo $ecm_loop ? 'aria-hidden="true"' : ''; ?>>
						<?php
						// ogni giro ripete la fila due volte, così il nastro è sempre più largo dello schermo
						for ( $ecm_rep = 0; $ecm_rep < 2; $ecm_rep++ ) {
							foreach ( $ecm_row as $ecm_i => $ecm_v ) {
								$ecm_n = array_search( $ecm_v, $ecm_videos, true );
								echo $ecm_reel( $ecm_v, (int) $ecm_n + 1, $ecm_loop > 0 || $ecm_rep > 0 ); // phpcs:ignore WordPress.Security.EscapeOutput
							}
						}
						?>
					</ul>
				<?php endfor; ?>
			</div>
		<?php endforeach; ?>
	</div>
</section>
