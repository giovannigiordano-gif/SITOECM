<?php
/**
 * Home.
 *
 * Struttura: hero a tutto schermo con la lente, manifesto (numeri reali + patrocini),
 * aree tematiche (palcoscenico che si apre come una lente), corsi in evidenza (scorrimento orizzontale),
 * docenti (i ritratti seguono il cursore), testimonianze come sul sito originale, newsletter, invito finale.
 * Senza JS o con motion ridotto ogni sezione resta un normale flusso leggibile.
 *
 * @package ecm
 */

defined( 'ABSPATH' ) || exit;

get_header();

$ecm_areas    = ecm_areas();
$ecm_stats    = ecm_stats();
$ecm_featured = ecm_courses( array( 'featured' => true, 'limit' => 6 ) );
if ( count( $ecm_featured ) < 3 ) {
	$ecm_featured = array_slice( ecm_courses( array( 'limit' => 6 ) ), 0, 6 );
}
$ecm_docenti = ecm_docenti();
$ecm_wa      = ecm_opt( 'whatsapp_number' ) ? ecm_whatsapp_generic() : '';

// Loghi patrocinanti unici (da tutti i corsi).
$ecm_logos = array();
foreach ( ecm_courses() as $ecm_c ) {
	foreach ( (array) $ecm_c['patrocini'] as $ecm_p ) {
		// I banner compositi (più loghi in un'immagine) entrano anche loro: così si vedono tutti gli enti dichiarati.
		$ecm_wide = $ecm_p['height'] > 0 && ( $ecm_p['width'] / $ecm_p['height'] ) > 2.6;
		$ecm_k    = ( $ecm_wide || ! $ecm_p['name'] ) ? 'img-' . $ecm_p['image_id'] : sanitize_title( $ecm_p['name'] );
		if ( $ecm_p['image_id'] && ! isset( $ecm_logos[ $ecm_k ] ) ) {
			$ecm_logos[ $ecm_k ] = $ecm_p;
		}
	}
}

$ecm_ctas = array(
	array(
		'label' => __( 'Esplora i corsi', 'ecm' ),
		'url'   => ecm_catalog_url(),
		'icon'  => 'arrow-right',
	),
);
if ( $ecm_wa ) {
	$ecm_ctas[] = array(
		'label'    => __( 'Scrivici su WhatsApp', 'ecm' ),
		'url'      => $ecm_wa,
		'variant'  => 'secondary',
		'icon'     => 'whatsapp',
		'icon_pos' => 'start',
		'attrs'    => ecm_ext_attrs(),
	);
}

ecm_hero(
	array(
		'size'    => 'full',
		'class'   => 'hero--home',
		'title'   => (string) ecm_opt( 'hero_title', __( 'Formazione ECM per chi si prende cura della salute.', 'ecm' ) ),
		'lead'    => (string) ecm_opt( 'hero_text', __( 'Corsi ECM online per nutrizionisti, medici, farmacisti e veterinari, con docenti universitari e il patrocinio degli ordini professionali.', 'ecm' ) ),
		'image'   => 'hero-home',
		'image_m' => 'hero-home-m',
		'focus'   => '74% 50%',
		'focus_m' => '50% 28%',
		'ctas'    => $ecm_ctas,
		'scroll'  => '#manifesto',
	)
);
?>

<?php if ( $ecm_stats['corsi'] || $ecm_logos ) : ?>
<section class="trust" id="manifesto" aria-labelledby="trust-title">
	<div class="wrap">
		<h2 class="visually-hidden" id="trust-title"><?php esc_html_e( 'Perché sceglierci', 'ecm' ); ?></h2>
		<p class="trust__statement" data-scrub-words>
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
	<?php
	if ( $ecm_logos ) {
		ecm_patrons_band(
			array_values( $ecm_logos ),
			array(
				'id'         => 'patrocini',
				'heading'    => 'h3',
				'theme'      => 'ink',
				/* translators: %d numero enti */
				'count_text' => $ecm_stats['patrocini_ordini'] ? sprintf( __( '%d enti e ordini professionali', 'ecm' ), (int) $ecm_stats['patrocini_ordini'] ) : '',
			)
		);
	}
	?>
</section>
<?php endif; ?>

<?php if ( $ecm_areas ) : ?>
<section class="areas" data-areas aria-labelledby="areas-title">
	<div class="areas__intro" data-areas-intro>
		<div class="areas__intro-head">
			<h2 class="areas__heading" id="areas-title"><?php esc_html_e( 'Le aree tematiche', 'ecm' ); ?></h2>
			<p class="areas__sub">
				<?php
				/* translators: 1: numero aree, 2: numero corsi */
				echo esc_html( sprintf( __( '%1$d ambiti, %2$d corsi: scorri per entrare in ognuno.', 'ecm' ), count( $ecm_areas ), (int) $ecm_stats['corsi'] ) );
				?>
			</p>
		</div>
		<ol class="areas__index" aria-label="<?php esc_attr_e( 'Indice delle aree', 'ecm' ); ?>">
			<?php foreach ( $ecm_areas as $ecm_a ) : ?>
				<li data-areas-index>
					<a href="<?php echo esc_url( $ecm_a['url'] ); ?>">
						<span class="areas__index-name"><?php echo esc_html( $ecm_a['name'] ); ?></span>
						<span class="areas__index-count"><?php echo esc_html( sprintf( _n( '%d corso', '%d corsi', (int) $ecm_a['count'], 'ecm' ), (int) $ecm_a['count'] ) ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
	<?php foreach ( $ecm_areas as $ecm_i => $ecm_a ) : ?>
		<article class="area" data-area style="--i: <?php echo (int) $ecm_i; ?>" aria-labelledby="area-<?php echo esc_attr( $ecm_a['slug'] ); ?>">
			<div class="area__media" data-area-media>
				<?php echo ecm_img( ecm_area_img_key( $ecm_a ), array( 'sizes' => '100vw', 'alt' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
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
</section>
<?php endif; ?>

<?php if ( $ecm_featured ) : ?>
<section class="featured" data-featured aria-labelledby="featured-title">
	<div class="featured__inner">
		<header class="featured__head wrap">
			<h2 class="section-title" id="featured-title" data-reveal="lines"><?php esc_html_e( 'Corsi in evidenza', 'ecm' ); ?></h2>
			<a class="link-arrow" href="<?php echo esc_url( ecm_catalog_url() ); ?>">
				<span><?php echo esc_html( sprintf( /* translators: %d numero corsi */ __( 'Tutti i %d corsi', 'ecm' ), (int) $ecm_stats['corsi'] ) ); ?></span>
				<?php echo ecm_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</a>
		</header>
		<div class="featured__viewport" data-featured-viewport tabindex="0" role="region" aria-label="<?php esc_attr_e( 'Corsi in evidenza, scorri orizzontalmente', 'ecm' ); ?>">
			<ul class="featured__track" data-featured-track>
				<?php foreach ( $ecm_featured as $ecm_c ) : ?>
					<li class="featured__item">
						<?php ecm_card_corso( $ecm_c, array( 'sizes' => '(min-width: 900px) 34vw, 82vw' ) ); ?>
					</li>
				<?php endforeach; ?>
				<li class="featured__item featured__item--end">
					<a class="featured__all" href="<?php echo esc_url( ecm_catalog_url() ); ?>">
						<span class="featured__all-ring" aria-hidden="true"><?php echo ecm_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<span class="featured__all-label"><?php echo esc_html( sprintf( /* translators: %d numero corsi */ __( 'Tutti i %d corsi', 'ecm' ), (int) $ecm_stats['corsi'] ) ); ?></span>
					</a>
				</li>
			</ul>
		</div>
	</div>
</section>
<?php endif; ?>

<?php if ( $ecm_docenti ) : ?>
	<?php $ecm_show = array_slice( $ecm_docenti, 0, 10 ); ?>
<section class="faculty section" aria-labelledby="faculty-title">
	<div class="wrap faculty__grid">
		<header class="faculty__head">
			<h2 class="section-title" id="faculty-title" data-reveal="lines"><?php esc_html_e( 'Chi insegna', 'ecm' ); ?></h2>
			<p class="faculty__intro"><?php esc_html_e( 'Docenti universitari, ricercatori e clinici che portano nei corsi ciò che fanno ogni giorno.', 'ecm' ); ?></p>
			<a class="link-arrow" href="<?php echo esc_url( ecm_page_url( 'docenti' ) ); ?>">
				<span><?php echo esc_html( sprintf( /* translators: %d numero docenti */ __( 'Tutti i %d docenti', 'ecm' ), count( $ecm_docenti ) ) ); ?></span>
				<?php echo ecm_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</a>
		</header>
		<ul class="faculty__list" data-faculty-list>
			<?php foreach ( $ecm_show as $ecm_i => $ecm_d ) : ?>
				<li class="faculty__item" data-faculty-item="<?php echo (int) $ecm_i; ?>">
					<a class="faculty__link" href="<?php echo esc_url( ecm_page_url( 'docenti' ) . '#docente-' . sanitize_title( $ecm_d['name'] ) ); ?>">
						<span class="faculty__avatar" aria-hidden="true">
							<?php
							if ( $ecm_d['photo_id'] ) {
								echo wp_get_attachment_image( $ecm_d['photo_id'], 'thumbnail', false, array( 'alt' => '', 'loading' => 'lazy' ) );
							} else {
								echo '<span class="avatar-type">' . esc_html( $ecm_d['initials'] ) . '</span>';
							}
							?>
						</span>
						<span class="faculty__name"><?php echo esc_html( $ecm_d['name'] ); ?></span>
						<?php if ( $ecm_d['titolo'] ) : ?>
							<span class="faculty__role"><?php echo esc_html( $ecm_d['titolo'] ); ?></span>
						<?php endif; ?>
						<?php echo ecm_icon( 'arrow-up-right', 'faculty__arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<div class="faculty__float" data-faculty-float aria-hidden="true">
		<?php foreach ( $ecm_show as $ecm_i => $ecm_d ) : ?>
			<?php if ( $ecm_d['photo_id'] ) : ?>
				<?php echo wp_get_attachment_image( $ecm_d['photo_id'], 'medium', false, array( 'alt' => '', 'loading' => 'lazy', 'data-faculty-pic' => (string) $ecm_i, 'sizes' => '260px' ) ); ?>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
</section>
<?php endif; ?>

<?php
get_template_part(
	'template-parts/reviews',
	null,
	array(
		'context' => 'home',
		'heading' => __( 'Testimonianze e recensioni', 'ecm' ),
	)
);
get_template_part( 'template-parts/quote-featured' );
?>

<?php get_template_part( 'template-parts/newsletter' ); ?>
<?php get_template_part( 'template-parts/cta-final' ); ?>

<?php
get_footer();
