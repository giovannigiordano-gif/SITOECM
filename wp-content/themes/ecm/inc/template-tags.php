<?php
/**
 * Helper di presentazione riutilizzati dai template.
 *
 * @package ecm
 */

defined( 'ABSPATH' ) || exit;

/**
 * Icona dallo sprite Phosphor.
 */
function ecm_icon( string $name, string $class = '' ): string {
	return sprintf(
		'<svg class="icon %1$s" aria-hidden="true" focusable="false"><use href="#i-%2$s"></use></svg>',
		esc_attr( $class ),
		esc_attr( $name )
	);
}

/**
 * Bottone/link con il disco che si espande (vedi components.css → .btn).
 *
 * @param array $a label, url, variant (primary|secondary|ghost|on-ink), icon, icon_pos (end|start), attrs (array), class.
 */
function ecm_button( array $a ): string {
	$a = wp_parse_args(
		$a,
		array(
			'label'    => '',
			'url'      => '#',
			'variant'  => 'primary',
			'icon'     => '',
			'icon_pos' => 'end',
			'attrs'    => array(),
			'class'    => '',
			'tag'      => 'a',
		)
	);
	$attrs = '';
	foreach ( (array) $a['attrs'] as $k => $val ) {
		$attrs .= sprintf( ' %s="%s"', esc_attr( $k ), esc_attr( (string) $val ) );
	}
	$icon  = $a['icon'] ? '<span class="btn__icon">' . ecm_icon( $a['icon'] ) . '</span>' : '';
	$label = '<span class="btn__label">' . esc_html( $a['label'] ) . '</span>';
	$inner = 'start' === $a['icon_pos'] ? $icon . $label : $label . $icon;
	$cls   = trim( 'btn btn--' . $a['variant'] . ' ' . $a['class'] . ( $a['icon'] ? ' has-icon' : '' ) );
	if ( 'button' === $a['tag'] ) {
		return sprintf( '<button class="%s"%s><span class="btn__fill" aria-hidden="true"></span>%s</button>', esc_attr( $cls ), $attrs, $inner );
	}
	return sprintf( '<a class="%s" href="%s"%s><span class="btn__fill" aria-hidden="true"></span>%s</a>', esc_attr( $cls ), esc_url( $a['url'] ), $attrs, $inner );
}

/**
 * Attributi per i link esterni.
 */
function ecm_ext_attrs( string $label_suffix = '' ): array {
	$attrs = array(
		'target' => '_blank',
		'rel'    => 'noopener',
	);
	if ( $label_suffix ) {
		$attrs['aria-label'] = $label_suffix;
	}
	return $attrs;
}

/**
 * Badge dello stato del corso.
 */
function ecm_badge_stato( array $stato ): string {
	if ( empty( $stato['key'] ) ) {
		return '';
	}
	return sprintf(
		'<span class="badge badge--%1$s"><span class="badge__dot" aria-hidden="true"></span>%2$s</span>',
		esc_attr( $stato['key'] ),
		esc_html( $stato['label'] )
	);
}

/**
 * Classi tipografiche per titoli di lunghezza e "maiuscolo" variabile.
 * Molti titoli originali sono interamente in maiuscolo: li manteniamo così come sono,
 * ma riduciamo larghezza e corpo per non trasformarli in un muro.
 */
function ecm_title_class( string $title ): string {
	$plain   = wp_strip_all_tags( $title );
	$len     = mb_strlen( $plain );
	$letters = preg_replace( '/[^\p{L}]+/u', '', $plain );
	$upper   = preg_replace( '/[^\p{Lu}]+/u', '', $letters );
	$classes = array();
	if ( $letters && mb_strlen( $upper ) / max( 1, mb_strlen( $letters ) ) > 0.7 ) {
		$classes[] = 'is-caps';
	}
	if ( $len > 70 ) {
		$classes[] = 'is-xlong';
	} elseif ( $len > 42 ) {
		$classes[] = 'is-long';
	} elseif ( $len < 20 ) {
		$classes[] = 'is-short';
	}
	return implode( ' ', $classes );
}

/**
 * Iniziali per l'avatar tipografico (salta titoli come Prof., Dott.ssa).
 */
function ecm_theme_initials( string $name ): string {
	$name  = preg_replace( '/\b(prof|dott|dr|dott\.ssa|prof\.ssa|dottssa|profssa)\.?\s+/iu', '', $name );
	$parts = preg_split( '/\s+/u', trim( (string) $name ) );
	$parts = array_values( array_filter( $parts ) );
	if ( ! $parts ) {
		return '';
	}
	$first = mb_substr( $parts[0], 0, 1 );
	$last  = count( $parts ) > 1 ? mb_substr( end( $parts ), 0, 1 ) : '';
	return mb_strtoupper( $first . $last );
}

/**
 * Logo: versione scura (header) o chiara (footer, menu).
 */
function ecm_logo( string $variant = 'dark', string $class = '', ?string $alt = null ): string {
	$file = 'light' === $variant ? 'logo-light' : 'logo-dark';
	$base = ECM_THEME_URI . '/assets/img/brand/' . $file;
	return sprintf(
		'<picture class="logo %1$s"><source srcset="%2$s.webp" type="image/webp"><img src="%2$s.png" width="840" height="90" alt="%3$s" decoding="async"></picture>',
		esc_attr( $class ),
		esc_url( $base ),
		esc_attr( null !== $alt ? $alt : ( get_bloginfo( 'name' ) ?: 'Corsi di Formazione ECM' ) )
	);
}

/**
 * Testo semplice da HTML, accorciato a N parole.
 */
function ecm_words( string $html, int $words = 30 ): string {
	return wp_trim_words( wp_strip_all_tags( $html ), $words, '…' );
}

/**
 * Normalizza per la ricerca client-side: minuscolo, senza accenti.
 */
function ecm_search_norm( string $text ): string {
	$text = remove_accents( wp_strip_all_tags( $text ) );
	$text = mb_strtolower( $text );
	return trim( preg_replace( '/\s+/u', ' ', $text ) );
}

/**
 * URL dell'archivio corsi.
 */
function ecm_catalog_url( array $query = array() ): string {
	$url = get_post_type_archive_link( 'corso' );
	$url = $url ? $url : home_url( '/corsi/' );
	return $query ? add_query_arg( $query, $url ) : $url;
}

/**
 * URL di una pagina per slug, con fallback.
 */
function ecm_page_url( string $slug ): string {
	$p = get_page_by_path( $slug );
	return $p ? get_permalink( $p ) : home_url( '/' . $slug . '/' );
}

/**
 * Link WhatsApp generico (senza corso).
 */
function ecm_whatsapp_generic(): string {
	return ecm_whatsapp_url( (string) ecm_opt( 'whatsapp_generic', 'Salve, vorrei avere maggiori informazioni sui vostri corsi ECM' ) );
}

/**
 * Card corso (anteprima in home, catalogo, correlati).
 *
 * @param array $c    Dati da ecm_course().
 * @param array $args size (default|large), heading (h2|h3), attrs (array per il catalogo).
 */
function ecm_card_corso( array $c, array $args = array() ): void {
	get_template_part( 'template-parts/card-corso', null, array( 'c' => $c ) + $args );
}

/**
 * Breadcrumb semplice e accessibile.
 *
 * @param array $items Coppie [label, url]; l'ultima voce è la pagina corrente (senza url).
 */
function ecm_breadcrumbs( array $items ): void {
	if ( ! $items ) {
		return;
	}
	echo '<nav class="crumbs" aria-label="' . esc_attr__( 'Percorso', 'ecm' ) . '"><ol>';
	$last = count( $items ) - 1;
	foreach ( $items as $i => $item ) {
		if ( $i === $last || empty( $item[1] ) ) {
			printf( '<li><span aria-current="page">%s</span></li>', esc_html( $item[0] ) );
		} else {
			printf( '<li><a href="%s">%s</a></li>', esc_url( $item[1] ), esc_html( $item[0] ) );
		}
	}
	echo '</ol></nav>';
}

/**
 * Titolo con righe mascherate per il reveal "respiro".
 * Il testo resta un unico nodo accessibile: lo split in righe lo fa il JS.
 */
function ecm_reveal_attr(): string {
	return ' data-reveal="lines"';
}

/**
 * Altezza ottica uniforme dei loghi: a parità di "area visiva",
 * i loghi larghi diventano più bassi, quelli quadrati più alti.
 */
function ecm_logo_height( int $w, int $h, int $base = 64 ): int {
	if ( $w <= 0 || $h <= 0 ) {
		return $base;
	}
	$ratio = $w / $h;
	$px    = $base / sqrt( max( 0.5, $ratio ) ) * 1.35;
	return (int) round( max( $base * 0.42, min( $base * 1.25, $px ) ) );
}

/**
 * Numero formattato all'italiana.
 */
function ecm_num( $n ): string {
	return number_format_i18n( (float) $n );
}

/**
 * Menu di default se il menu "primary" non è ancora assegnato.
 *
 * @param array $args Argomenti di wp_nav_menu.
 */
function ecm_nav_fallback( array $args ): void {
	$items = array(
		array( __( 'Corsi', 'ecm' ), ecm_catalog_url(), is_post_type_archive( 'corso' ) || is_singular( 'corso' ) || is_tax( 'area_tematica' ) ),
		array( __( 'Docenti', 'ecm' ), ecm_page_url( 'docenti' ), is_page( 'docenti' ) ),
		array( __( 'Chi siamo', 'ecm' ), ecm_page_url( 'chi-siamo' ), is_page( 'chi-siamo' ) ),
		array( __( 'Testimonianze', 'ecm' ), ecm_page_url( 'testimonianze' ), is_page( 'testimonianze' ) ),
		array( __( 'Contatti', 'ecm' ), ecm_page_url( 'contatti' ), is_page( 'contatti' ) ),
	);
	printf( '<ul class="%s">', esc_attr( $args['menu_class'] ?? '' ) );
	foreach ( $items as $it ) {
		printf(
			'<li class="menu-item%3$s"><a href="%1$s"%4$s>%2$s</a></li>',
			esc_url( $it[1] ),
			esc_html( $it[0] ),
			$it[2] ? ' current-menu-item' : '',
			$it[2] ? ' aria-current="page"' : ''
		);
	}
	echo '</ul>';
}

/**
 * aria-current sulle voci di menu attive (anche per il singolo corso sotto "Corsi").
 */
add_filter(
	'nav_menu_link_attributes',
	static function ( array $atts, $item ): array {
		$classes = (array) $item->classes;
		$is_corsi = ( 'post_type_archive' === $item->type && 'corso' === $item->object );
		if ( in_array( 'current-menu-item', $classes, true ) || ( $is_corsi && ( is_singular( 'corso' ) || is_tax( 'area_tematica' ) ) ) ) {
			$atts['aria-current'] = 'page';
		}
		return $atts;
	},
	10,
	2
);

/**
 * Numeri dichiarati dall'ente (opzione "stats_extra", una riga per numero: "10k+|Corsisti").
 *
 * @return array<int, array{value:string,label:string}>
 */
function ecm_stats_extra(): array {
	$raw = (string) ecm_opt( 'stats_extra', '' );
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
		$parts = array_map( 'trim', explode( '|', $line, 2 ) );
		if ( count( $parts ) === 2 && '' !== $parts[0] && '' !== $parts[1] ) {
			$out[] = array( 'value' => $parts[0], 'label' => $parts[1] );
		}
	}
	return $out;
}

/**
 * Valore numerico con contatore animato sulla parte intera ("10k+" → 10 animato + "k+").
 */
function ecm_stat_html( string $value ): string {
	if ( preg_match( '/^(\D*)(\d+)(.*)$/u', $value, $m ) ) {
		return '<span class="num">' . esc_html( $m[1] ) . '<span data-count="' . (int) $m[2] . '">' . esc_html( $m[2] ) . '</span>' . esc_html( $m[3] ) . '</span>';
	}
	return '<span class="num">' . esc_html( $value ) . '</span>';
}

/**
 * Indirizzo del lettore incorporato per un video Vimeo o YouTube (senza tracciamento, avvio automatico al click).
 *
 * @param string $url Indirizzo del video.
 * @return string Vuoto se il servizio non è riconosciuto.
 */
function ecm_video_embed( string $url ): string {
	if ( preg_match( '~vimeo\.com/(?:video/)?(\d{5,12})~', $url, $m ) ) {
		return 'https://player.vimeo.com/video/' . $m[1] . '?autoplay=1&dnt=1&title=0&byline=0&portrait=0';
	}
	if ( preg_match( '~(?:youtube\.com/(?:watch\?v=|shorts/|embed/)|youtu\.be/)([\w-]{6,15})~', $url, $m ) ) {
		return 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?autoplay=1&rel=0';
	}
	return '';
}

/**
 * Video degli eventi dal vivo dalle impostazioni (una riga: indirizzo|titolo|ID anteprima).
 *
 * @return array<int,array{url:string,embed:string,title:string,thumb_id:int}>
 */
function ecm_video_eventi(): array {
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) ecm_opt( 'video_eventi', '' ) ) as $line ) {
		$parts = array_map( 'trim', explode( '|', $line ) );
		$embed = ecm_video_embed( $parts[0] ?? '' );
		if ( '' === $embed ) {
			continue;
		}
		$out[] = array(
			'url'      => $parts[0],
			'embed'    => $embed,
			'title'    => $parts[1] ?? '',
			'thumb_id' => absint( $parts[2] ?? 0 ),
		);
	}
	return $out;
}

/**
 * Card di una recensione (stelle, testo, nome, professione, corso).
 *
 * @param array $t     Testimonianza (ecm_testimonianze()).
 * @param bool  $clamp Testo accorciato (nastro scorrevole).
 */
function ecm_review_card( array $t, bool $clamp = false ): string {
	$rating = (int) ( $t['rating'] ?? 0 );
	$stars  = '';
	if ( $rating > 0 ) {
		$stars = '<p class="review__stars" role="img" aria-label="' . esc_attr( sprintf( /* translators: %d valutazione */ __( 'Valutazione: %d stelle su 5', 'ecm' ), $rating ) ) . '">' . str_repeat( ecm_icon( 'star' ), $rating ) . '</p>';
	}
	$course = '';
	if ( ! empty( $t['corso_title'] ) ) {
		$course = ! empty( $t['corso_url'] )
			? '<a class="review__course" href="' . esc_url( $t['corso_url'] ) . '">' . esc_html( $t['corso_title'] ) . '</a>'
			: '<span class="review__course">' . esc_html( $t['corso_title'] ) . '</span>';
	}
	$text = trim( (string) $t['text'] );
	$text = preg_replace( '/^[“"”]+|[“"”]+$/u', '', $text );
	return sprintf(
		'<figure class="review%1$s">%2$s<blockquote class="review__text"><p>%3$s</p></blockquote><figcaption class="review__by"><span class="review__name">%4$s</span>%5$s%6$s</figcaption></figure>',
		$clamp ? ' review--clamp' : '',
		$stars,
		nl2br( esc_html( (string) $text ) ),
		esc_html( (string) $t['name'] ),
		! empty( $t['ruolo'] ) ? '<span class="review__role">' . esc_html( (string) $t['ruolo'] ) . '</span>' : '',
		$course
	);
}

/**
 * Icona di un'area tematica (Phosphor light, nello sprite come "area-{slug}"); per le aree nuove una lente da microscopio.
 *
 * @param array|null $area Area (ecm_areas()).
 * @param string     $class Classi extra.
 */
function ecm_area_icon( ?array $area, string $class = '' ): string {
	$known = array( 'nutrizione-umana', 'nutrizione-animale', 'fitoterapia', 'microbiota-genomica', 'comunicazione-sanitaria' );
	$slug  = $area ? (string) $area['slug'] : '';
	return ecm_icon( in_array( $slug, $known, true ) ? 'area-' . $slug : 'area-default', $class );
}
