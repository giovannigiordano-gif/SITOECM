<?php
/**
 * Nastri obliqui dei patrocini (home e schede corso).
 *
 * Un nastro bianco con i loghi a colori, incrociato da un nastro cremisi con la scritta; scorrono in direzioni
 * opposte seguendo lo scroll. Il primo giro di loghi è leggibile dagli screen reader (alt = nome dell'ente),
 * le ripetizioni sono decorative. Con motion ridotto i nastri restano fermi.
 *
 * @package ecm
 */

defined( 'ABSPATH' ) || exit;

/**
 * Stampa la fascia dei patrocini.
 *
 * @param array $logos Patrocini: array di { image_id, name, width, height }.
 * @param array $args  label, count_text, id, class, heading (h2|h3).
 */
function ecm_patrons_band( array $logos, array $args = array() ): void {
	$logos = array_values( array_filter( $logos, static fn( $p ) => ! empty( $p['image_id'] ) ) );
	if ( ! $logos ) {
		return;
	}
	$args    = wp_parse_args(
		$args,
		array(
			'label'      => __( 'Con il patrocinio di', 'ecm' ),
			'count_text' => '',
			'id'         => 'patrocini',
			'class'      => '',
			'heading'    => 'h2',
		)
	);
	$tag     = in_array( $args['heading'], array( 'h2', 'h3' ), true ) ? $args['heading'] : 'h2';
	$copies  = max( 2, (int) ceil( 24 / count( $logos ) ) );
	$heading = $args['label'] . ( $args['count_text'] ? ' ' . $args['count_text'] : '' );
	?>
	<section class="pband <?php echo esc_attr( $args['class'] ); ?>" id="<?php echo esc_attr( $args['id'] ); ?>" data-pband aria-labelledby="<?php echo esc_attr( $args['id'] ); ?>-t">
		<<?php echo esc_html( $tag ); ?> class="visually-hidden" id="<?php echo esc_attr( $args['id'] ); ?>-t"><?php echo esc_html( $heading ); ?></<?php echo esc_html( $tag ); ?>>

		<div class="pband__ribbon pband__ribbon--text" aria-hidden="true">
			<div class="pband__track" data-pband-track data-dir="1">
				<?php for ( $i = 0; $i < 8; $i++ ) : ?>
					<span class="pband__phrase"><?php echo esc_html( $args['label'] ); ?></span>
					<span class="pband__ring"></span>
					<?php if ( $args['count_text'] ) : ?>
						<span class="pband__phrase pband__phrase--soft"><?php echo esc_html( $args['count_text'] ); ?></span>
						<span class="pband__ring"></span>
					<?php endif; ?>
				<?php endfor; ?>
			</div>
		</div>

		<div class="pband__ribbon pband__ribbon--logos">
			<ul class="pband__track" data-pband-track data-dir="-1">
				<?php for ( $copy = 0; $copy < $copies; $copy++ ) : ?>
					<?php foreach ( $logos as $p ) : ?>
						<?php
						$wide = (int) $p['height'] > 0 && ( (int) $p['width'] / (int) $p['height'] ) > 2.6;
						$h    = $wide ? 70 : ecm_logo_height( (int) $p['width'], (int) $p['height'], 74 );
						?>
						<li class="pband__logo<?php echo $wide ? ' pband__logo--wide' : ''; ?>"<?php echo $copy ? ' aria-hidden="true"' : ''; ?>>
							<?php
							echo wp_get_attachment_image(
								(int) $p['image_id'],
								'ecm-logo',
								false,
								array(
									'alt'     => $copy ? '' : ( $p['name'] ? $p['name'] : __( 'Logo ente patrocinante', 'ecm' ) ),
									'loading' => 'lazy',
									'style'   => '--h:' . $h . 'px',
									'sizes'   => $wide ? '480px' : '240px',
								)
							);
							?>
						</li>
					<?php endforeach; ?>
				<?php endfor; ?>
			</ul>
		</div>
	</section>
	<?php
}
