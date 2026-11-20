<?php
/**
 * Card corso: copertina reale in passe-partout, stato, area, titolo, estratto.
 *
 * Args: c (array ecm_course), size (default|large), heading (h2|h3), catalog (bool), hidden (bool), sizes (string).
 *
 * @package ecm
 */

defined( 'ABSPATH' ) || exit;

$c = $args['c'] ?? null;
if ( ! $c ) {
	return;
}
$ecm_size    = $args['size'] ?? 'default';
$ecm_h       = in_array( $args['heading'] ?? '', array( 'h2', 'h3', 'h4' ), true ) ? $args['heading'] : 'h3';
$ecm_catalog = ! empty( $args['catalog'] );
$ecm_sizes   = $args['sizes'] ?? '(min-width: 1100px) 30vw, (min-width: 700px) 45vw, 92vw';

$ecm_attrs = '';
if ( $ecm_catalog ) {
	$ecm_attrs .= ' data-course';
	$ecm_attrs .= ' data-areas="' . esc_attr( implode( ' ', wp_list_pluck( (array) $c['areas'], 'slug' ) ) ) . '"';
	$ecm_attrs .= ' data-per="' . esc_attr( implode( ' ', wp_list_pluck( (array) $c['destinatari'], 'slug' ) ) ) . '"';
	$ecm_attrs .= ' data-search="' . esc_attr( ecm_course_haystack( $c ) ) . '"';
	if ( ! empty( $args['hidden'] ) ) {
		$ecm_attrs .= ' hidden';
	}
}
?>
<article class="card card--<?php echo esc_attr( $ecm_size ); ?>"<?php echo $ecm_attrs; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above ?>>
	<a class="card__link" href="<?php echo esc_url( $c['url'] ); ?>">
		<div class="card__media">
			<div class="card__mat">
				<?php
				if ( $c['cover_id'] ) {
					echo wp_get_attachment_image(
						$c['cover_id'],
						'large' === $ecm_size ? 'ecm-hero' : 'ecm-card',
						false,
						array(
							'class'   => 'card__img',
							'sizes'   => $ecm_sizes,
							'loading' => 'lazy',
							'alt'     => '',
						)
					);
				} else {
					echo '<span class="card__placeholder" aria-hidden="true"></span>';
				}
				?>
			</div>
			<span class="card__go" aria-hidden="true"><?php echo ecm_icon( 'arrow-up-right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
		</div>
		<div class="card__body">
			<p class="card__meta">
				<?php echo ecm_badge_stato( $c['stato'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php if ( ! empty( $c['area'] ) ) : ?>
					<span class="card__area"><?php echo esc_html( $c['area']['name'] ); ?></span>
				<?php endif; ?>
				<?php if ( '' !== (string) $c['crediti'] ) : ?>
					<span class="card__credits"><?php echo esc_html( sprintf( /* translators: %s: numero crediti */ __( '%s crediti ECM', 'ecm' ), $c['crediti'] ) ); ?></span>
				<?php endif; ?>
			</p>
			<<?php echo esc_html( $ecm_h ); ?> class="card__title <?php echo esc_attr( ecm_title_class( $c['title'] ) ); ?>"><?php echo esc_html( $c['title'] ); ?></<?php echo esc_html( $ecm_h ); ?>>
			<?php if ( $c['excerpt'] ) : ?>
				<p class="card__excerpt"><?php echo esc_html( $c['excerpt'] ); ?></p>
			<?php endif; ?>
		</div>
	</a>
</article>
