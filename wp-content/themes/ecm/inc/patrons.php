<?php
/**
 * Patrocini (home e schede corso): griglia di schede chiare con il logo a colori e il nome dell'ente.
 * I banner compositi (più loghi in un'immagine) occupano due colonne.
 *
 * @package ecm
 */

defined( 'ABSPATH' ) || exit;

/**
 * Stampa la sezione dei patrocini.
 *
 * @param array $logos Patrocini: array di { image_id, name, width, height }.
 * @param array $args  label, count_text, id, class, heading (h2|h3), theme (ink|paper).
 */
function ecm_patrons_band( array $logos, array $args = array() ): void {
	$logos = array_values( array_filter( $logos, static fn( $p ) => ! empty( $p['image_id'] ) ) );
	if ( ! $logos ) {
		return;
	}
	$args = wp_parse_args(
		$args,
		array(
			'label'      => __( 'Con il patrocinio di', 'ecm' ),
			'count_text' => '',
			'id'         => 'patrocini',
			'class'      => '',
			'heading'    => 'h2',
			'theme'      => 'paper',
		)
	);
	$tag = in_array( $args['heading'], array( 'h2', 'h3' ), true ) ? $args['heading'] : 'h2';
	?>
	<section class="patrons-grid patrons-grid--<?php echo esc_attr( $args['theme'] ); ?> <?php echo esc_attr( $args['class'] ); ?>" id="<?php echo esc_attr( $args['id'] ); ?>" aria-labelledby="<?php echo esc_attr( $args['id'] ); ?>-t">
		<div class="wrap">
			<header class="patrons-grid__head">
				<<?php echo esc_html( $tag ); ?> class="patrons-grid__title" id="<?php echo esc_attr( $args['id'] ); ?>-t" data-reveal="lines"><?php echo esc_html( $args['label'] ); ?></<?php echo esc_html( $tag ); ?>>
				<?php if ( $args['count_text'] ) : ?>
					<p class="patrons-grid__count"><?php echo esc_html( $args['count_text'] ); ?></p>
				<?php endif; ?>
			</header>
			<ul class="patrons-grid__list">
				<?php foreach ( $logos as $p ) : ?>
					<?php
					$wide = (int) $p['height'] > 0 && ( (int) $p['width'] / (int) $p['height'] ) > 2.6;
					$h    = $wide ? 96 : ecm_logo_height( (int) $p['width'], (int) $p['height'], 84 );
					?>
					<li class="patron<?php echo $wide ? ' patron--wide' : ''; ?>" data-reveal="fade">
						<span class="patron__logo">
							<?php
							echo wp_get_attachment_image(
								(int) $p['image_id'],
								'ecm-logo',
								false,
								array(
									'alt'     => ( $p['name'] && ! $wide ) ? '' : ( $p['name'] ? $p['name'] : __( 'Logo ente patrocinante', 'ecm' ) ),
									'loading' => 'lazy',
									'style'   => '--h:' . $h . 'px',
									'sizes'   => $wide ? '520px' : '260px',
								)
							);
							?>
						</span>
						<?php if ( $p['name'] && ! $wide ) : ?>
							<span class="patron__name"><?php echo esc_html( $p['name'] ); ?></span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>
	<?php
}
