<?php
/**
 * Home.
 *
 * Struttura: storia (hero + lente + aree tematiche in un unico palcoscenico che si fissa allo scroll),
 * fiducia (numeri reali + patrocini), corsi in evidenza, docenti, testimonianze, newsletter, CTA finale.
 * Senza JS o con motion ridotto la storia diventa un normale flusso di sezioni.
 *
 * @package ecm
 */

defined( 'ABSPATH' ) || exit;

get_header();

$ecm_areas    = ecm_areas();
$ecm_stats    = ecm_stats();
$ecm_featured = ecm_courses( array( 'featured' => true, 'limit' => 5 ) );
if ( count( $ecm_featured ) < 3 ) {
	$ecm_featured = array_slice( ecm_courses( array( 'limit' => 5 ) ), 0, 5 );
}
$ecm_docenti = ecm_docenti();
$ecm_testi   = ecm_testimonianze( array( 'fonte' => 'home', 'limit' => 12 ) );
if ( ! $ecm_testi ) {
	$ecm_testi = ecm_testimonianze( array( 'limit' => 12 ) );
}

// Loghi patrocinanti unici (da tutti i corsi).
$ecm_logos = array();
foreach ( ecm_courses() as $ecm_c ) {
	foreach ( (array) $ecm_c['patrocini'] as $ecm_p ) {
		$ecm_k = $ecm_p['name'] ? sanitize_title( $ecm_p['name'] ) : 'img-' . $ecm_p['image_id'];
		// I banner compositi (più loghi in un'immagine) restano nella scheda del corso, non nel nastro.
		$ecm_wide = $ecm_p['height'] > 0 && ( $ecm_p['width'] / $ecm_p['height'] ) > 2.6;
		if ( $ecm_p['image_id'] && ! $ecm_wide && ! isset( $ecm_logos[ $ecm_k ] ) ) {
			$ecm_logos[ $ecm_k ] = $ecm_p;
		}
	}
}

$ecm_first_key = $ecm_areas ? ecm_area_img_key( $ecm_areas[0] ) : 'lens-nutrizione-umana';
$ecm_wa        = ecm_opt( 'whatsapp_number' ) ? ecm_whatsapp_generic() : '';

/**
 * Anello della lente: doppio cerchio cremisi (citazione del logo) + testo circolare.
 */
$ecm_ring = static function ( string $id ): string {
	$text = str_repeat( __( 'Educazione Continua in Medicina', 'ecm' ) . '   ·   ', 3 );
	return '<svg class="lens-ring" viewBox="0 0 400 400" aria-hidden="true" focusable="false" data-lens-ring>'
		. '<defs><path id="' . esc_attr( $id ) . '" d="M200,200 m-181,0 a181,181 0 1,1 362,0 a181,181 0 1,1 -362,0"/></defs>'
		. '<circle class="lens-ring__stroke" cx="200" cy="200" r="198"/>'
		. '<circle class="lens-ring__stroke lens-ring__stroke--thin" cx="200" cy="200" r="166"/>'
		. '<g class="lens-ring__type" data-lens-type><text><textPath href="#' . esc_attr( $id ) . '" textLength="1137">' . esc_html( $text ) . '</textPath></text></g>'
		. '</svg>';
};
?>

<section class="story" data-story aria-label="<?php esc_attr_e( 'Presentazione e aree tematiche', 'ecm' ); ?>">
	<div class="story__stage" data-story-stage>

		<div class="hero" data-hero>
			<div class="hero__inner wrap">
				<div class="hero__copy">
					<h1 class="hero__title" data-reveal="lines"><?php echo esc_html( (string) ecm_opt( 'hero_title', __( 'Formazione ECM per chi si prende cura della salute.', 'ecm' ) ) ); ?></h1>
					<p class="hero__lead" data-reveal="fade"><?php echo esc_html( (string) ecm_opt( 'hero_text', __( 'Corsi ECM online per nutrizionisti, medici, farmacisti e veterinari, con docenti universitari e il patrocinio degli ordini professionali.', 'ecm' ) ) ); ?></p>
					<div class="hero__ctas" data-reveal="fade">
						<?php
						echo ecm_button( // phpcs:ignore WordPress.Security.EscapeOutput
							array(
								'label' => __( 'Esplora i corsi', 'ecm' ),
								'url'   => ecm_catalog_url(),
								'icon'  => 'arrow-right',
							)
						);
						if ( $ecm_wa ) {
							echo ecm_button( // phpcs:ignore WordPress.Security.EscapeOutput
								array(
									'label'    => __( 'Scrivici su WhatsApp', 'ecm' ),
									'url'      => $ecm_wa,
									'variant'  => 'secondary',
									'icon'     => 'whatsapp',
									'icon_pos' => 'start',
									'attrs'    => ecm_ext_attrs(),
								)
							);
						}
						?>
					</div>
				</div>
				<div class="hero__slot" data-lens-slot>
					<div class="lens-static">
						<div class="lens-static__clip">
							<?php echo ecm_img( $ecm_first_key, array( 'sizes' => '100vw', 'priority' => true, 'alt' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</div>
						<?php echo $ecm_ring( 'lens-path-a' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>
				</div>
			</div>
		</div>

		<div class="lens" data-lens aria-hidden="true">
			<div class="lens__clip" data-lens-clip>
				<div class="lens__img" data-lens-img>
					<?php echo ecm_img( $ecm_first_key, array( 'sizes' => '100vw', 'lazy' => false, 'alt' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</div>
			<div class="lens__ring" data-lens-ringwrap><?php echo $ecm_ring( 'lens-path-b' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
		</div>

		<?php if ( $ecm_areas ) : ?>
			<div class="areas" data-areas>
				<h2 class="areas__heading wrap"><?php esc_html_e( 'Le aree tematiche', 'ecm' ); ?></h2>
				<?php foreach ( $ecm_areas as $ecm_i => $ecm_a ) : ?>
					<article class="area" data-area style="--i: <?php echo (int) $ecm_i; ?>" aria-labelledby="area-<?php echo esc_attr( $ecm_a['slug'] ); ?>">
						<div class="area__media" data-area-media>
							<?php echo ecm_img( ecm_area_img_key( $ecm_a ), array( 'sizes' => '(min-width: 900px) 100vw, 100vw', 'alt' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</div>
						<div class="area__panel" data-area-panel>
							<p class="area__count"><?php echo esc_html( sprintf( _n( '%d corso', '%d corsi', (int) $ecm_a['count'], 'ecm' ), (int) $ecm_a['count'] ) ); ?></p>
							<h3 class="area__name" id="area-<?php echo esc_attr( $ecm_a['slug'] ); ?>"><?php echo esc_html( $ecm_a['name'] ); ?></h3>
							<?php if ( ! empty( $ecm_a['short'] ) ) : ?>
								<p class="area__text"><?php echo esc_html( $ecm_a['short'] ); ?></p>
							<?php endif; ?>
							<a class="link-arrow link-arrow--on-ink" href="<?php echo esc_url( $ecm_a['url'] ); ?>">
								<span><?php echo esc_html( sprintf( /* translators: %s nome area */ __( 'Vedi i corsi di %s', 'ecm' ), mb_strtolower( $ecm_a['name'] ) ) ); ?></span>
								<?php echo ecm_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</a>
						</div>
					</article>
				<?php endforeach; ?>
				<ol class="areas__nav" data-areas-nav aria-label="<?php esc_attr_e( 'Aree tematiche', 'ecm' ); ?>">
					<?php foreach ( $ecm_areas as $ecm_i => $ecm_a ) : ?>
						<li><button type="button" data-areas-go="<?php echo (int) $ecm_i; ?>"><span class="visually-hidden"><?php echo esc_html( $ecm_a['name'] ); ?></span></button></li>
					<?php endforeach; ?>
				</ol>
			</div>
		<?php endif; ?>
	</div>
</section>

<?php if ( $ecm_stats['corsi'] || $ecm_logos ) : ?>
<section class="trust" aria-labelledby="trust-title">
	<div class="wrap">
		<h2 class="visually-hidden" id="trust-title"><?php esc_html_e( 'Perché sceglierci', 'ecm' ); ?></h2>
		<p class="trust__statement" data-reveal="lines">
			<?php
			$ecm_parts = array();
			foreach ( ecm_stats_extra() as $ecm_x ) {
				if ( false !== stripos( $ecm_x['label'], 'docent' ) ) {
					continue; // i docenti li contiamo dal catalogo, sempre aggiornati.
				}
				$ecm_parts[] = ecm_stat_html( $ecm_x['value'] ) . ' ' . esc_html( mb_strtolower( $ecm_x['label'] ) );
			}
			if ( $ecm_stats['corsi'] ) {
				/* translators: %s: numero */
				$ecm_parts[] = sprintf( __( '%s corsi online', 'ecm' ), '<span class="num" data-count="' . (int) $ecm_stats['corsi'] . '">' . ecm_num( $ecm_stats['corsi'] ) . '</span>' );
			}
			if ( $ecm_stats['docenti'] ) {
				/* translators: %s: numero */
				$ecm_parts[] = sprintf( __( '%s docenti', 'ecm' ), '<span class="num" data-count="' . (int) $ecm_stats['docenti'] . '">' . ecm_num( $ecm_stats['docenti'] ) . '</span>' );
			}
			if ( $ecm_stats['patrocini_ordini'] ) {
				/* translators: %s: numero */
				$ecm_parts[] = sprintf( __( 'il patrocinio di %s enti e ordini professionali', 'ecm' ), '<span class="num" data-count="' . (int) $ecm_stats['patrocini_ordini'] . '">' . ecm_num( $ecm_stats['patrocini_ordini'] ) . '</span>' );
			}
			$ecm_last = count( $ecm_parts ) > 1 ? array_pop( $ecm_parts ) : '';
			$ecm_sent = implode( ', ', $ecm_parts ) . ( $ecm_last ? ' ' . __( 'e', 'ecm' ) . ' ' . $ecm_last : '' );
			echo wp_kses( ucfirst( $ecm_sent ) . '.', array( 'span' => array( 'class' => true, 'data-count' => true ) ) );
			?>
		</p>
	</div>
	<?php if ( $ecm_logos ) : ?>
		<div class="marquee" data-marquee>
			<p class="marquee__label wrap"><?php esc_html_e( 'Con il patrocinio di', 'ecm' ); ?></p>
			<div class="marquee__viewport">
				<?php for ( $ecm_loop = 0; $ecm_loop < 2; $ecm_loop++ ) : ?>
					<ul class="marquee__track" <?php echo $ecm_loop ? 'aria-hidden="true"' : ''; ?>>
						<?php foreach ( $ecm_logos as $ecm_p ) : ?>
							<li class="logo-tile">
								<?php
								echo wp_get_attachment_image(
									$ecm_p['image_id'],
									'ecm-logo',
									false,
									array(
										'alt'     => $ecm_loop ? '' : $ecm_p['name'],
										'loading' => 'lazy',
										'style'   => '--h:' . ecm_logo_height( (int) $ecm_p['width'], (int) $ecm_p['height'], 56 ) . 'px',
									)
								);
								?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endfor; ?>
			</div>
		</div>
	<?php endif; ?>
</section>
<?php endif; ?>

<?php if ( $ecm_featured ) : ?>
<section class="featured section" aria-labelledby="featured-title">
	<div class="wrap">
		<header class="section-head">
			<h2 class="section-title" id="featured-title" data-reveal="lines"><?php esc_html_e( 'Corsi in evidenza', 'ecm' ); ?></h2>
			<a class="link-arrow" href="<?php echo esc_url( ecm_catalog_url() ); ?>">
				<span><?php echo esc_html( sprintf( /* translators: %d numero corsi */ __( 'Tutti i %d corsi', 'ecm' ), (int) $ecm_stats['corsi'] ) ); ?></span>
				<?php echo ecm_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</a>
		</header>
		<div class="featured__grid">
			<?php
			foreach ( $ecm_featured as $ecm_i => $ecm_c ) {
				ecm_card_corso(
					$ecm_c,
					array(
						'size'  => 0 === $ecm_i ? 'large' : 'default',
						'sizes' => 0 === $ecm_i ? '(min-width: 900px) 56vw, 92vw' : '(min-width: 900px) 34vw, 92vw',
					)
				);
			}
			?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php if ( $ecm_docenti ) : ?>
<section class="faculty section" aria-labelledby="faculty-title" data-faculty>
	<div class="wrap faculty__grid">
		<header class="faculty__head">
			<h2 class="section-title" id="faculty-title" data-reveal="lines"><?php esc_html_e( 'Chi insegna', 'ecm' ); ?></h2>
			<p class="faculty__intro"><?php esc_html_e( 'Docenti universitari, ricercatori e clinici che portano nei corsi ciò che fanno ogni giorno.', 'ecm' ); ?></p>
			<a class="link-arrow" href="<?php echo esc_url( ecm_page_url( 'docenti' ) ); ?>"><span><?php esc_html_e( 'Tutti i docenti', 'ecm' ); ?></span><?php echo ecm_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
		</header>
		<ul class="faculty__list">
			<?php foreach ( array_slice( $ecm_docenti, 0, 8 ) as $ecm_d ) : ?>
				<li class="faculty__item" data-faculty-item>
					<span class="faculty__avatar">
						<?php
						if ( $ecm_d['photo_id'] ) {
							echo wp_get_attachment_image( $ecm_d['photo_id'], 'thumbnail', false, array( 'alt' => '', 'loading' => 'lazy' ) );
						} else {
							echo '<span class="avatar-type" aria-hidden="true">' . esc_html( $ecm_d['initials'] ) . '</span>';
						}
						?>
					</span>
					<span class="faculty__name"><?php echo esc_html( $ecm_d['name'] ); ?></span>
					<?php if ( $ecm_d['titolo'] ) : ?>
						<span class="faculty__role"><?php echo esc_html( $ecm_d['titolo'] ); ?></span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
<?php endif; ?>

<?php if ( $ecm_testi ) : ?>
<section class="voices section" aria-labelledby="voices-title" data-voices>
	<div class="wrap">
		<header class="section-head">
			<h2 class="section-title" id="voices-title" data-reveal="lines"><?php esc_html_e( 'Testimonianze', 'ecm' ); ?></h2>
			<a class="link-arrow" href="<?php echo esc_url( ecm_page_url( 'testimonianze' ) ); ?>"><span><?php esc_html_e( 'Leggi tutte', 'ecm' ); ?></span><?php echo ecm_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
		</header>
		<div class="voices__viewport" data-voices-viewport tabindex="0" role="region" aria-label="<?php esc_attr_e( 'Testimonianze, scorri orizzontalmente', 'ecm' ); ?>">
			<ul class="voices__track">
				<?php foreach ( $ecm_testi as $ecm_t ) : ?>
					<li class="voice">
						<figure>
							<?php echo ecm_icon( 'quote', 'voice__mark' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<blockquote class="voice__text" data-clamp><p><?php echo esc_html( $ecm_t['text'] ); ?></p></blockquote>
							<figcaption class="voice__by">
								<span class="voice__name"><?php echo esc_html( $ecm_t['name'] ); ?></span>
								<?php if ( $ecm_t['corso_title'] ) : ?>
									<?php if ( $ecm_t['corso_url'] ) : ?>
										<a class="voice__course" href="<?php echo esc_url( $ecm_t['corso_url'] ); ?>"><?php echo esc_html( $ecm_t['corso_title'] ); ?></a>
									<?php else : ?>
										<span class="voice__course"><?php echo esc_html( $ecm_t['corso_title'] ); ?></span>
									<?php endif; ?>
								<?php endif; ?>
							</figcaption>
						</figure>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<div class="voices__controls">
			<button type="button" class="icon-btn" data-voices-prev aria-label="<?php esc_attr_e( 'Testimonianza precedente', 'ecm' ); ?>"><?php echo ecm_icon( 'arrow-left' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			<button type="button" class="icon-btn" data-voices-next aria-label="<?php esc_attr_e( 'Testimonianza successiva', 'ecm' ); ?>"><?php echo ecm_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
		</div>
	</div>
</section>
<?php endif; ?>

<?php get_template_part( 'template-parts/newsletter' ); ?>
<?php get_template_part( 'template-parts/cta-final' ); ?>

<?php
get_footer();
