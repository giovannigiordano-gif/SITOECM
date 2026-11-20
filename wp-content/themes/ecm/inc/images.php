<?php
/**
 * Immagini art-directed del tema (generate con Higgsfield e ottimizzate in build).
 * Ogni chiave ha varianti AVIF + WebP a più larghezze, descritte in assets/img/manifest.json
 * (generato da tools/build/images.mjs).
 *
 * @package ecm
 */

defined( 'ABSPATH' ) || exit;

/**
 * Manifest delle immagini del tema.
 */
function ecm_img_manifest(): array {
	static $m = null;
	if ( null === $m ) {
		$file = ECM_THEME_DIR . '/assets/img/manifest.json';
		$m    = is_readable( $file ) ? (array) json_decode( (string) file_get_contents( $file ), true ) : array(); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
	return $m;
}

/**
 * True se l'immagine del tema esiste.
 */
function ecm_has_img( string $key ): bool {
	$m = ecm_img_manifest();
	return ! empty( $m[ $key ]['widths'] );
}

/**
 * <picture> responsive per un'immagine del tema.
 *
 * @param string $key  Chiave nel manifest (es. "lens-nutrizione-umana").
 * @param array  $args sizes, alt, class, img_class, lazy (bool), priority (bool), attrs.
 */
function ecm_img( string $key, array $args = array() ): string {
	$args = wp_parse_args(
		$args,
		array(
			'sizes'     => '100vw',
			'alt'       => null,
			'class'     => '',
			'img_class' => '',
			'lazy'      => true,
			'priority'  => false,
			'attrs'     => array(),
		)
	);
	$m = ecm_img_manifest();
	if ( empty( $m[ $key ]['widths'] ) ) {
		return ecm_img_fallback( $key, $args );
	}
	$item   = $m[ $key ];
	$base   = ECM_THEME_URI . '/assets/img/' . $item['dir'] . '/' . $key;
	$widths = array_map( 'intval', (array) $item['widths'] );
	sort( $widths );
	$srcset = static function ( string $ext ) use ( $widths, $base ): string {
		return implode( ', ', array_map( static fn( $w ) => esc_url( "{$base}-{$w}.{$ext}" ) . " {$w}w", $widths ) );
	};
	$alt   = null !== $args['alt'] ? $args['alt'] : ( $item['alt'] ?? '' );
	$fallw = $widths[ (int) floor( ( count( $widths ) - 1 ) / 2 ) ];
	$extra = '';
	foreach ( (array) $args['attrs'] as $k => $v ) {
		$extra .= sprintf( ' %s="%s"', esc_attr( $k ), esc_attr( (string) $v ) );
	}
	$loading = $args['priority'] ? ' fetchpriority="high" loading="eager"' : ( $args['lazy'] ? ' loading="lazy"' : '' );

	return sprintf(
		'<picture class="%1$s"><source type="image/avif" srcset="%2$s" sizes="%4$s"><source type="image/webp" srcset="%3$s" sizes="%4$s"><img class="%5$s" src="%6$s" width="%7$d" height="%8$d" alt="%9$s" decoding="async"%10$s%11$s></picture>',
		esc_attr( trim( 'pic ' . $args['class'] ) ),
		$srcset( 'avif' ),
		$srcset( 'webp' ),
		esc_attr( $args['sizes'] ),
		esc_attr( $args['img_class'] ),
		esc_url( "{$base}-{$fallw}.webp" ),
		(int) ( $item['width'] ?? 1600 ),
		(int) ( $item['height'] ?? 1600 ),
		esc_attr( (string) $alt ),
		$loading,
		$extra
	);
}

/**
 * Fallback elegante se un'immagine del tema non è (ancora) disponibile:
 * una "lente" tipografica con il cerchio del marchio, mai un rettangolo vuoto.
 */
function ecm_img_fallback( string $key, array $args ): string {
	$alt = (string) ( $args['alt'] ?? '' );
	return sprintf(
		'<span class="pic pic--fallback %s" %s><span class="pic__ring" aria-hidden="true"></span></span>',
		esc_attr( (string) $args['class'] ),
		'' === $alt ? 'aria-hidden="true"' : 'role="img" aria-label="' . esc_attr( $alt ) . '"'
	);
}

/**
 * Immagine di area tematica (chiave immagine dal term meta, con mappa di default).
 */
function ecm_area_img_key( ?array $area ): string {
	$map = array(
		'nutrizione-umana'        => 'lens-nutrizione-umana',
		'nutrizione-animale'      => 'lens-nutrizione-animale',
		'fitoterapia'             => 'lens-fitoterapia',
		'microbiota-genomica'     => 'lens-microbiota',
		'comunicazione-sanitaria' => 'lens-comunicazione',
	);
	if ( ! $area ) {
		return 'lens-nutrizione-umana';
	}
	$k = ! empty( $area['image_key'] ) ? $area['image_key'] : $area['slug'];
	if ( isset( $map[ $k ] ) ) {
		return $map[ $k ];
	}
	return ecm_has_img( 'lens-' . $k ) ? 'lens-' . $k : 'lens-nutrizione-umana';
}
