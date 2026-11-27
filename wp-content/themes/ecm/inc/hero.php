<?php
/**
 * Hero condivisa da tutte le pagine: immagine a tutto schermo, titolo in basso a sinistra,
 * luce radente WebGL che segue il cursore con lieve parallasse (deriva lenta sui dispositivi touch).
 * Senza JS o con motion ridotto resta un'immagine statica con il testo leggibile sopra.
 *
 * @package ecm
 */

defined( 'ABSPATH' ) || exit;

/**
 * Anello della lente: doppio cerchio cremisi (citazione del logo) + testo circolare.
 *
 * @param string $id   ID univoco del tracciato.
 * @param string $text Testo che gira intorno all'anello.
 */
function ecm_lens_ring_svg( string $id, string $text = '' ): string {
	$text = '' !== $text ? $text : __( 'Educazione Continua in Medicina', 'ecm' );
	$loop = str_repeat( $text . '   ·   ', 3 );
	return '<svg class="lens-ring" viewBox="0 0 400 400" aria-hidden="true" focusable="false">'
		. '<defs><path id="' . esc_attr( $id ) . '" d="M200,200 m-181,0 a181,181 0 1,1 362,0 a181,181 0 1,1 -362,0"/></defs>'
		. '<circle class="lens-ring__stroke" cx="200" cy="200" r="198"/>'
		. '<circle class="lens-ring__stroke lens-ring__stroke--thin" cx="200" cy="200" r="166"/>'
		. '<g class="lens-ring__type"><text><textPath href="#' . esc_attr( $id ) . '" textLength="1137">' . esc_html( $loop ) . '</textPath></text></g>'
		. '</svg>';
}

/**
 * Stampa una hero.
 *
 * @param array $a {
 *     @type string $title       Titolo (testo). La parte tra asterischi esce nel secondo colore:
 *                               "*Formazione ECM* per chi si prende cura della salute.".
 *     @type string $title_html  Titolo già formattato (alternativo a title).
 *     @type string $title_class Classi extra del titolo.
 *     @type string $lead        Testo introduttivo (testo semplice).
 *     @type string $lead_html   Testo introduttivo HTML (kses post).
 *     @type string $image       Chiave dell'immagine del tema (manifest).
 *     @type string $image_m     Chiave dell'immagine verticale per smartphone (facoltativa).
 *     @type string $image_html  Markup immagine alternativo (es. allegato della Libreria media).
 *     @type string $focus       object-position dell'immagine (es. "70% 50%").
 *     @type string $focus_m     object-position su smartphone.
 *     @type string $size        full | page | compact.
 *     @type array  $crumbs      Briciole di pane (vedi ecm_breadcrumbs).
 *     @type string $meta_html   Riga sopra il titolo (badge, crediti...).
 *     @type array  $ctas        Pulsanti: array di argomenti per ecm_button().
 *     @type string $after_html  Markup dopo i pulsanti.
 *     @type string $aside_html  Colonna destra in basso (es. copertina del corso).
 *     @type bool   $lens        Lente interattiva.
 *     @type bool   $mono        Immagine in scala di grigi, a colori dentro la lente.
 *     @type bool   $photo       Fotografia reale (velatura più forte per la leggibilità).
 *     @type string $scroll      Ancora della sezione successiva per il pulsante "scorri".
 *     @type string $id          ID della sezione.
 *     @type string $class       Classi extra.
 *     @type string $ring_text   Testo dell'anello.
 * }
 */
function ecm_hero( array $a ): void {
	$a = wp_parse_args(
		$a,
		array(
			'title'       => '',
			'title_html'  => '',
			'title_class' => '',
			'lead'        => '',
			'lead_html'   => '',
			'image'       => '',
			'image_m'     => '',
			'image_html'  => '',
			'focus'       => '72% 50%',
			'focus_m'     => '',
			'size'        => 'page',
			'crumbs'      => array(),
			'meta_html'   => '',
			'ctas'        => array(),
			'ctas_attrs'  => '',
			'after_html'  => '',
			'aside_html'  => '',
			'lens'        => true,
			'mono'        => false,
			'photo'       => false,
			'scroll'      => '',
			'id'          => '',
			'class'       => '',
			'ring_text'   => '',
		)
	);
	static $n = 0;
	++$n;

	// Titolo bicolore: *testo* diventa l'accento cremisi.
	if ( ! $a['title_html'] && preg_match( '/\*[^*]+\*/u', $a['title'] ) ) {
		$a['title_html'] = (string) preg_replace( '/\*([^*]+)\*/u', '<span class="hero__accent">$1</span>', esc_html( $a['title'] ) );
		$a['title']      = str_replace( '*', '', $a['title'] );
	}

	$classes = array( 'hero', 'hero--' . sanitize_html_class( $a['size'] ) );
	if ( $a['photo'] ) {
		$classes[] = 'hero--photo';
	}
	if ( $a['mono'] ) {
		$classes[] = 'hero--mono';
	}
	if ( $a['class'] ) {
		$classes[] = $a['class'];
	}
	$style = '--focus:' . $a['focus'] . ';' . ( $a['focus_m'] ? '--focus-m:' . $a['focus_m'] . ';' : '' );

	if ( $a['image_html'] ) {
		$media = $a['image_html'];
	} elseif ( $a['image'] ) {
		$media = ecm_img(
			$a['image'],
			array(
				'sizes'     => '100vw',
				'priority'  => true,
				'alt'       => '',
				'img_class' => 'hero__img',
				'mobile'    => $a['image_m'],
				'attrs'     => array( 'data-hero-img' => '' ),
			)
		);
	} else {
		$media = '';
	}
	$has_img = $media && false === strpos( $media, 'pic--fallback' );
	?>
	<section class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" style="<?php echo esc_attr( $style ); ?>" data-hero<?php echo ( $a['lens'] && $has_img ) ? ' data-hero-lens' : ''; ?><?php echo $a['mono'] ? ' data-hero-mono' : ''; ?><?php echo $a['id'] ? ' id="' . esc_attr( $a['id'] ) . '"' : ''; ?>>
		<div class="hero__media" data-hero-media>
			<?php echo $media; // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php if ( $a['lens'] && $has_img ) : ?>
				<canvas class="hero__gl" data-hero-gl aria-hidden="true"></canvas>
			<?php endif; ?>
		</div>
		<div class="hero__shade" aria-hidden="true"></div>

		<div class="hero__body wrap">
			<div class="hero__copy">
				<?php
				if ( $a['crumbs'] ) {
					ecm_breadcrumbs( $a['crumbs'] );
				}
				if ( $a['meta_html'] ) {
					echo '<div class="hero__meta hero__in" style="--d:.05s">' . $a['meta_html'] . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
				<h1 class="hero__title hero__in <?php echo esc_attr( $a['title_class'] ); ?>" style="--d:.1s">
					<?php echo $a['title_html'] ? wp_kses_post( $a['title_html'] ) : esc_html( $a['title'] ); ?>
				</h1>
				<?php if ( $a['lead_html'] ) : ?>
					<div class="hero__lead hero__in" style="--d:.28s"><?php echo wp_kses_post( $a['lead_html'] ); ?></div>
				<?php elseif ( $a['lead'] ) : ?>
					<p class="hero__lead hero__in" style="--d:.28s"><?php echo esc_html( $a['lead'] ); ?></p>
				<?php endif; ?>
				<?php if ( $a['ctas'] ) : ?>
					<div class="hero__ctas hero__in" style="--d:.42s"<?php echo $a['ctas_attrs'] ? ' ' . $a['ctas_attrs'] : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
						<?php
						foreach ( $a['ctas'] as $cta ) {
							echo ecm_button( $cta ); // phpcs:ignore WordPress.Security.EscapeOutput
						}
						?>
					</div>
				<?php endif; ?>
				<?php echo $a['after_html']; // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
			<?php if ( $a['aside_html'] ) : ?>
				<div class="hero__aside"><?php echo $a['aside_html']; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<?php endif; ?>
		</div>

		<?php if ( $a['scroll'] ) : ?>
			<a class="hero__scroll" href="<?php echo esc_attr( $a['scroll'] ); ?>" data-scroll-to>
				<span class="visually-hidden"><?php esc_html_e( 'Vai alla sezione successiva', 'ecm' ); ?></span>
				<?php echo ecm_icon( 'arrow-down' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</a>
		<?php endif; ?>
	</section>
	<?php
}
