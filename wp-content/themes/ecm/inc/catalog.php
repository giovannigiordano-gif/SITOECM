<?php
/**
 * Stato dei filtri del catalogo (URL condivisibili: /corsi/?area=…&per=…&q=…).
 * Il filtro è applicato anche lato server, così un link condiviso mostra subito
 * i risultati giusti, con o senza JavaScript.
 *
 * @package ecm
 */

defined( 'ABSPATH' ) || exit;

/**
 * Filtri correnti dalla query string (o dal termine area interrogato).
 *
 * @return array{area:string, per:string, q:string}
 */
function ecm_catalog_state(): array {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- filtri di sola lettura.
	$area = isset( $_GET['area'] ) ? sanitize_title( wp_unslash( $_GET['area'] ) ) : '';
	$per  = isset( $_GET['per'] ) ? sanitize_title( wp_unslash( $_GET['per'] ) ) : '';
	$q    = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
	// phpcs:enable
	if ( ! $area && is_tax( 'area_tematica' ) ) {
		$term = get_queried_object();
		$area = $term instanceof WP_Term ? $term->slug : '';
	}
	return array(
		'area' => $area,
		'per'  => $per,
		'q'    => mb_substr( $q, 0, 80 ),
	);
}

/**
 * Testo indicizzato per la ricerca client-side di un corso.
 */
function ecm_course_haystack( array $c ): string {
	$parts = array( $c['title'], $c['excerpt'] );
	foreach ( array_merge( (array) $c['areas'], (array) $c['destinatari'] ) as $t ) {
		$parts[] = $t['name'] ?? '';
	}
	return ecm_search_norm( implode( ' ', $parts ) );
}

/**
 * True se il corso soddisfa lo stato dei filtri.
 */
function ecm_course_matches( array $c, array $state ): bool {
	if ( $state['area'] && ! in_array( $state['area'], wp_list_pluck( (array) $c['areas'], 'slug' ), true ) ) {
		return false;
	}
	if ( $state['per'] && ! in_array( $state['per'], wp_list_pluck( (array) $c['destinatari'], 'slug' ), true ) ) {
		return false;
	}
	if ( '' !== $state['q'] ) {
		$hay = ecm_course_haystack( $c );
		foreach ( preg_split( '/\s+/u', ecm_search_norm( $state['q'] ) ) as $word ) {
			if ( '' !== $word && false === mb_strpos( $hay, $word ) ) {
				return false;
			}
		}
	}
	return true;
}

/**
 * Le aree tematiche usano lo stesso template del catalogo, con l'area preselezionata.
 */
add_filter(
	'taxonomy_template',
	static function ( string $template ): string {
		if ( is_tax( 'area_tematica' ) ) {
			$catalog = locate_template( 'archive-corso.php' );
			return $catalog ? $catalog : $template;
		}
		return $template;
	}
);

/**
 * Sulle pagine area mostriamo comunque tutti i corsi (il filtro lo fa il catalogo).
 */
add_action(
	'pre_get_posts',
	static function ( WP_Query $q ) {
		if ( ! is_admin() && $q->is_main_query() && $q->is_tax( 'area_tematica' ) ) {
			$q->set( 'posts_per_page', 1 );
			$q->set( 'no_found_rows', true );
		}
	}
);
