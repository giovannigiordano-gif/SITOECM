<?php
/**
 * Testimonianza in evidenza (come sul sito originale: citazione, firma con professione e ritratto scontornato).
 * È la prima testimonianza con foto o video; se ha un video, il pulsante play lo carica solo al click.
 *
 * @package ecm
 */

defined( 'ABSPATH' ) || exit;

$ecm_feat = null;
foreach ( ecm_testimonianze() as $ecm_t ) {
	if ( ! empty( $ecm_t['photo_id'] ) || ! empty( $ecm_t['video'] ) ) {
		$ecm_feat = $ecm_t;
		break;
	}
}
if ( ! $ecm_feat ) {
	return;
}
$ecm_embed = ! empty( $ecm_feat['video'] ) ? ecm_video_embed( $ecm_feat['video'] ) : '';
$ecm_text  = preg_replace( '/^[“"”]+|[“"”]+$/u', '', trim( (string) $ecm_feat['text'] ) );
?>
<section class="spotlight" aria-label="<?php echo esc_attr( sprintf( /* translators: %s nome */ __( 'Testimonianza di %s', 'ecm' ), $ecm_feat['name'] ) ); ?>">
	<div class="wrap spotlight__grid">
		<figure class="spotlight__quote">
			<?php echo ecm_icon( 'quote', 'spotlight__mark' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<blockquote data-scrub-words><?php echo esc_html( (string) $ecm_text ); ?></blockquote>
			<figcaption>
				<span class="spotlight__name"><?php echo esc_html( $ecm_feat['name'] ); ?></span>
				<?php if ( ! empty( $ecm_feat['ruolo'] ) ) : ?>
					<span class="spotlight__role"><?php echo esc_html( $ecm_feat['ruolo'] ); ?></span>
				<?php endif; ?>
			</figcaption>
		</figure>
		<div class="spotlight__media">
			<span class="spotlight__disc" aria-hidden="true"></span>
			<?php
			if ( ! empty( $ecm_feat['photo_id'] ) ) {
				echo wp_get_attachment_image( (int) $ecm_feat['photo_id'], 'large', false, array( 'class' => 'spotlight__photo', 'alt' => $ecm_feat['name'], 'loading' => 'lazy', 'sizes' => '(min-width: 900px) 34vw, 70vw' ) );
			}
			if ( $ecm_embed ) :
				?>
				<button type="button" class="spotlight__play" data-video="<?php echo esc_url( $ecm_embed ); ?>" data-video-title="<?php echo esc_attr( sprintf( /* translators: %s nome */ __( 'Video testimonianza di %s', 'ecm' ), $ecm_feat['name'] ) ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s nome */ __( 'Guarda il video di %s', 'ecm' ), $ecm_feat['name'] ) ); ?>">
					<?php echo ecm_icon( 'play' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</button>
			<?php endif; ?>
		</div>
	</div>
</section>
