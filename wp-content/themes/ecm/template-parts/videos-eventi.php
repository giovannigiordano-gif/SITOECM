<?php
/**
 * Video degli eventi dal vivo (come sul sito originale, su fondo cremisi).
 * Anteprime locali: Vimeo viene contattato solo quando il visitatore preme play.
 *
 * @package ecm
 */

defined( 'ABSPATH' ) || exit;

$ecm_videos = ecm_video_eventi();
if ( ! $ecm_videos ) {
	return;
}
?>
<section class="reels section" aria-labelledby="reels-title">
	<div class="wrap">
		<h2 class="section-title reels__title" id="reels-title" data-reveal="lines"><?php echo esc_html( (string) ecm_opt( 'video_titolo', __( 'Testimonianze dei nostri eventi dal vivo', 'ecm' ) ) ); ?></h2>
		<ul class="reels__grid">
			<?php foreach ( $ecm_videos as $ecm_i => $ecm_v ) : ?>
				<?php $ecm_label = $ecm_v['title'] ? $ecm_v['title'] : __( 'Video', 'ecm' ); ?>
				<li class="reel" data-reveal="fade">
					<button type="button" class="reel__play" data-video="<?php echo esc_url( $ecm_v['embed'] ); ?>" data-video-title="<?php echo esc_attr( $ecm_label ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: 1: titolo, 2: numero */ __( 'Guarda il video: %1$s (%2$d)', 'ecm' ), $ecm_label, $ecm_i + 1 ) ); ?>">
						<?php
						if ( $ecm_v['thumb_id'] ) {
							echo wp_get_attachment_image( $ecm_v['thumb_id'], 'medium_large', false, array( 'class' => 'reel__img', 'alt' => '', 'loading' => 'lazy', 'sizes' => '(min-width: 1100px) 18vw, (min-width: 700px) 30vw, 45vw' ) );
						}
						?>
						<span class="reel__icon" aria-hidden="true"><?php echo ecm_icon( 'play' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<span class="reel__label" aria-hidden="true"><?php echo esc_html( $ecm_label ); ?></span>
					</button>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
