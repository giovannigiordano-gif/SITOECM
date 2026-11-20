<?php
/**
 * Scheda corso: un solo template per tutti i corsi, che si adatta ai campi presenti.
 * Le sezioni senza contenuto (patrocini, testimonianze, per chi è adatto...) non vengono stampate.
 *
 * @package ecm
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$c = ecm_course( get_the_ID() );
	if ( ! $c ) {
		continue;
	}

	// "Per chi è adatto" ha una sezione propria: lo togliamo dalle FAQ per non ripeterlo.
	$ecm_faq = array_values(
		array_filter(
			(array) $c['faq'],
			static fn( $f ) => ! preg_match( '/per\s+chi\s+[eè]\s+adatto/iu', (string) $f['q'] ) || '' === trim( wp_strip_all_tags( (string) $c['per_chi'] ) )
		)
	);

	$ecm_related = array();
	if ( ! empty( $c['area'] ) ) {
		$ecm_related = ecm_courses( array( 'area' => $c['area']['slug'], 'exclude' => array( $c['id'] ), 'limit' => 3 ) );
	}
	if ( count( $ecm_related ) < 3 ) {
		$ecm_ids = array_merge( array( $c['id'] ), wp_list_pluck( $ecm_related, 'id' ) );
		foreach ( ecm_courses( array( 'exclude' => $ecm_ids, 'limit' => 3 - count( $ecm_related ) ) ) as $ecm_more ) {
			$ecm_related[] = $ecm_more;
		}
	}

	$ecm_b       = $c['brochure'];
	$ecm_b_meta  = array();
	if ( $ecm_b ) {
		$ecm_b_meta[] = 'PDF';
		if ( ! empty( $ecm_b['size'] ) ) {
			$ecm_b_meta[] = $ecm_b['size'];
		}
		if ( ! empty( $ecm_b['pages'] ) ) {
			$ecm_b_meta[] = sprintf( _n( '%d pagina', '%d pagine', (int) $ecm_b['pages'], 'ecm' ), (int) $ecm_b['pages'] );
		}
	}
	$ecm_wa_btn = ecm_button(
		array(
			'label'    => __( 'Scrivici su WhatsApp', 'ecm' ),
			'url'      => $c['whatsapp_url'],
			'icon'     => 'whatsapp',
			'icon_pos' => 'start',
			'attrs'    => ecm_ext_attrs(),
		)
	);
	$ecm_br_btn = $ecm_b ? ecm_button(
		array(
			'label'    => __( 'Scarica la brochure', 'ecm' ),
			'url'      => $ecm_b['url'],
			'variant'  => 'secondary',
			'icon'     => 'download',
			'icon_pos' => 'start',
			'attrs'    => array( 'download' => $ecm_b['filename'] ),
		)
	) : '';
	?>

<article class="course" data-course-page>

	<?php
	$ecm_crumbs = array( array( __( 'Corsi', 'ecm' ), ecm_catalog_url() ) );
	if ( ! empty( $c['area'] ) ) {
		$ecm_crumbs[] = array( $c['area']['name'], $c['area']['url'] );
	}
	$ecm_crumbs[] = array( wp_trim_words( $c['title'], 6, '…' ), '' );

	// Patrocini: i banner compositi contengono più loghi, quindi in quel caso non diamo un numero.
	$ecm_pat_n    = count( (array) $c['patrocini'] );
	$ecm_pat_wide = (bool) array_filter( (array) $c['patrocini'], static fn( $p ) => (int) $p['height'] > 0 && ( (int) $p['width'] / (int) $p['height'] ) > 2.6 );
	/* translators: %d numero enti */
	$ecm_pat_text = ( $ecm_pat_n && ! $ecm_pat_wide ) ? sprintf( _n( '%d ente patrocinante', '%d enti patrocinanti', $ecm_pat_n, 'ecm' ), $ecm_pat_n ) : '';

	$ecm_meta = ecm_badge_stato( $c['stato'] );
	if ( '' !== (string) $c['crediti'] ) {
		$ecm_meta .= ' <span class="credits credits--on-ink"><span class="credits__num">' . esc_html( (string) $c['crediti'] ) . '</span> ' . esc_html__( 'crediti ECM', 'ecm' ) . '</span>';
	}
	if ( $ecm_pat_n ) {
		$ecm_meta .= ' <a class="badge badge--patrons" href="#patrocini"><span class="badge__ring" aria-hidden="true"></span>' . esc_html( $ecm_pat_text ? sprintf( /* translators: %d numero enti */ _n( 'Con il patrocinio di %d ente', 'Con il patrocinio di %d enti', $ecm_pat_n, 'ecm' ), $ecm_pat_n ) : __( 'Con il patrocinio di enti e ordini', 'ecm' ) ) . '</a>';
	}

	$ecm_ctas = array();
	if ( $c['link_iscrizione'] ) {
		$ecm_ctas[] = array(
			'label' => __( 'Iscriviti al corso', 'ecm' ),
			'url'   => $c['link_iscrizione'],
			'icon'  => 'arrow-up-right',
			'attrs' => ecm_ext_attrs(),
		);
	}
	$ecm_ctas[] = array(
		'label'    => __( 'Scrivici su WhatsApp', 'ecm' ),
		'url'      => $c['whatsapp_url'],
		'icon'     => 'whatsapp',
		'icon_pos' => 'start',
		'attrs'    => ecm_ext_attrs(),
	);
	if ( $ecm_b ) {
		$ecm_ctas[] = array(
			'label'    => __( 'Scarica la brochure', 'ecm' ),
			'url'      => $ecm_b['url'],
			'variant'  => 'secondary',
			'icon'     => 'download',
			'icon_pos' => 'start',
			'attrs'    => array( 'download' => $ecm_b['filename'] ),
		);
	}

	$ecm_cover = '';
	if ( $c['cover_id'] ) {
		$ecm_cover = '<figure class="course-hero__cover" data-cover><div class="cover-frame" data-cover-frame>'
			. wp_get_attachment_image(
				$c['cover_id'],
				'ecm-hero',
				false,
				array(
					'class'         => 'cover-frame__img',
					'sizes'         => '(min-width: 1000px) 34vw, 92vw',
					'loading'       => 'eager',
					'fetchpriority' => 'high',
					'alt'           => sprintf( /* translators: %s titolo */ __( 'Copertina del corso %s', 'ecm' ), $c['title'] ),
				)
			)
			. '</div><span class="cover-ring" aria-hidden="true"></span></figure>';
	}

	ecm_hero(
		array(
			'size'        => 'page',
			'class'       => 'hero--course',
			'lens'        => false,
			'title'       => $c['title'],
			'title_class' => ecm_title_class( $c['title'] ),
			'lead'        => $c['destinatari'] ? sprintf( /* translators: %s professioni */ __( 'Per %s', 'ecm' ), implode( ', ', wp_list_pluck( $c['destinatari'], 'name' ) ) ) : '',
			'image'       => ecm_area_img_key( $c['area'] ?? null ),
			'crumbs'      => $ecm_crumbs,
			'meta_html'   => $ecm_meta,
			'ctas'        => $ecm_ctas,
			'ctas_attrs'  => 'data-hero-ctas',
			'aside_html'  => $ecm_cover,
			'ring_text'   => ! empty( $c['area'] ) ? $c['area']['name'] : '',
			'focus'       => '78% 50%',
		)
	);

	// Patrocini subito sotto la hero: nastri obliqui con i loghi a colori.
	if ( $c['patrocini'] ) {
		ecm_patrons_band(
			(array) $c['patrocini'],
			array(
				'id'         => 'patrocini',
				'class'      => 'pband--course',
				'count_text' => $ecm_pat_text,
			)
		);
	}

	// Dati chiave del corso (solo quelli presenti).
	$ecm_facts = array();
	if ( ! empty( $c['area'] ) ) {
		$ecm_facts[] = array( __( 'Area', 'ecm' ), '<a href="' . esc_url( $c['area']['url'] ) . '">' . esc_html( $c['area']['name'] ) . '</a>' );
	}
	$ecm_facts[] = array( __( 'Stato', 'ecm' ), esc_html( $c['stato']['label'] ) );
	if ( '' !== (string) $c['crediti'] ) {
		$ecm_facts[] = array( __( 'Crediti ECM', 'ecm' ), esc_html( (string) $c['crediti'] ) );
	}
	if ( $c['destinatari'] ) {
		/* translators: %d numero professioni */
		$ecm_facts[] = array( __( 'Per', 'ecm' ), esc_html( sprintf( _n( '%d professione', '%d professioni', count( $c['destinatari'] ), 'ecm' ), count( $c['destinatari'] ) ) ) );
	}
	if ( $ecm_b ) {
		$ecm_facts[] = array( __( 'Brochure', 'ecm' ), esc_html( implode( ', ', $ecm_b_meta ) ) );
	}
	if ( $ecm_pat_n ) {
		$ecm_facts[] = array( __( 'Patrocini', 'ecm' ), '<a href="#patrocini">' . esc_html( $ecm_pat_text ? $ecm_pat_text : __( 'Enti e ordini', 'ecm' ) ) . '</a>' );
	}
	?>

	<div class="course-body">
		<?php if ( $ecm_facts ) : ?>
			<div class="wrap">
				<dl class="course-facts" data-reveal="fade">
					<?php foreach ( $ecm_facts as $ecm_fact ) : ?>
						<div class="course-facts__item"><dt><?php echo esc_html( $ecm_fact[0] ); ?></dt><dd><?php echo wp_kses( $ecm_fact[1], array( 'a' => array( 'href' => true ) ) ); ?></dd></div>
					<?php endforeach; ?>
				</dl>
			</div>
		<?php endif; ?>
		<div class="wrap course-body__grid">
			<div class="course-body__main">
				<h2 class="course-kicker"><?php esc_html_e( 'Il corso', 'ecm' ); ?></h2>
				<?php if ( '' !== trim( wp_strip_all_tags( (string) get_the_content() ) ) ) : ?>
					<div class="prose prose--course" data-prose>
						<?php the_content(); ?>
					</div>
				<?php else : ?>
					<div class="prose prose--course">
						<p><?php esc_html_e( 'Programma completo, docenti e modalità di frequenza sono descritti nella brochure del corso.', 'ecm' ); ?></p>
					</div>
				<?php endif; ?>
			</div>

			<aside class="course-body__aside" aria-label="<?php esc_attr_e( 'Brochure e contatti', 'ecm' ); ?>">
				<div class="aside-sticky">
					<?php if ( $ecm_b ) : ?>
						<section class="brochure" aria-labelledby="brochure-title">
							<a class="brochure__preview" href="<?php echo esc_url( $ecm_b['url'] ); ?>" download="<?php echo esc_attr( $ecm_b['filename'] ); ?>" tabindex="-1" aria-hidden="true">
								<?php
								if ( ! empty( $ecm_b['preview_id'] ) ) {
									echo wp_get_attachment_image( $ecm_b['preview_id'], 'medium_large', false, array( 'alt' => '', 'loading' => 'lazy', 'sizes' => '(min-width: 1000px) 300px, 60vw' ) );
								} else {
									echo '<span class="brochure__sheet">' . ecm_icon( 'pdf' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
								}
								?>
							</a>
							<div class="brochure__body">
								<h2 class="brochure__title" id="brochure-title"><?php esc_html_e( 'Vuoi saperne di più?', 'ecm' ); ?></h2>
								<p class="brochure__text"><?php esc_html_e( 'Programma, docenti e modalità nella brochure del corso.', 'ecm' ); ?></p>
								<p class="brochure__meta"><?php echo esc_html( implode( ' / ', $ecm_b_meta ) ); ?></p>
								<?php echo $ecm_br_btn; // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</div>
						</section>
					<?php endif; ?>

					<section class="ask" aria-labelledby="ask-title">
						<h2 class="ask__title" id="ask-title"><?php esc_html_e( 'Hai una domanda su questo corso?', 'ecm' ); ?></h2>
						<p class="ask__text"><?php esc_html_e( 'Ti rispondiamo su WhatsApp con il nome del corso già nel messaggio.', 'ecm' ); ?></p>
						<a class="link-arrow" href="<?php echo esc_url( $c['whatsapp_url'] ); ?>" target="_blank" rel="noopener">
							<?php echo ecm_icon( 'whatsapp' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<span><?php echo esc_html( (string) ecm_opt( 'whatsapp_display', __( 'Apri WhatsApp', 'ecm' ) ) ); ?></span>
						</a>
					</section>
				</div>
			</aside>
		</div>
	</div>

	<?php if ( '' !== trim( wp_strip_all_tags( (string) $c['per_chi'] ) ) ) : ?>
		<section class="fit section-tight" aria-labelledby="fit-title">
			<div class="wrap fit__grid">
				<h2 class="section-title section-title--md" id="fit-title" data-reveal="lines"><?php esc_html_e( 'Per chi è adatto', 'ecm' ); ?></h2>
				<div class="fit__body">
					<div class="prose"><?php echo wp_kses_post( wpautop( $c['per_chi'] ) ); ?></div>
					<?php if ( $c['destinatari'] ) : ?>
						<ul class="tags" aria-label="<?php esc_attr_e( 'Professioni', 'ecm' ); ?>">
							<?php foreach ( $c['destinatari'] as $ecm_d ) : ?>
								<li><a class="tag" href="<?php echo esc_url( ecm_catalog_url( array( 'per' => $ecm_d['slug'] ) ) ); ?>"><?php echo esc_html( $ecm_d['name'] ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $c['testimonianze'] ) : ?>
		<section class="course-voices section-tight" aria-labelledby="cv-title">
			<div class="wrap">
				<h2 class="section-title section-title--md" id="cv-title" data-reveal="lines"><?php esc_html_e( 'Cosa dicono i corsisti', 'ecm' ); ?></h2>
				<ul class="course-voices__grid">
					<?php foreach ( $c['testimonianze'] as $ecm_t ) : ?>
						<li class="voice voice--static">
							<figure>
								<?php echo ecm_icon( 'quote', 'voice__mark' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<blockquote class="voice__text" data-clamp><p><?php echo nl2br( esc_html( $ecm_t['text'] ) ); ?></p></blockquote>
								<figcaption class="voice__by">
									<span class="voice__name"><?php echo esc_html( $ecm_t['name'] ); ?></span>
									<?php if ( $ecm_t['meta'] ) : ?>
										<span class="voice__course"><?php echo esc_html( $ecm_t['meta'] ); ?></span>
									<?php endif; ?>
								</figcaption>
							</figure>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $ecm_faq ) : ?>
		<section class="faq section-tight" aria-labelledby="faq-title">
			<div class="wrap faq__grid">
				<div class="faq__head">
					<h2 class="section-title section-title--md" id="faq-title" data-reveal="lines"><?php esc_html_e( 'Domande frequenti', 'ecm' ); ?></h2>
					<?php if ( ecm_opt( 'faq_email_a' ) && ! ecm_opt( 'faq_email_q' ) ) : ?>
						<p class="faq__intro"><?php echo wp_kses_post( make_clickable( (string) ecm_opt( 'faq_email_a' ) ) ); ?></p>
					<?php endif; ?>
				</div>
				<div class="acc-group">
					<?php foreach ( $ecm_faq as $ecm_i => $ecm_f ) : ?>
						<?php $ecm_id = 'faq-' . $ecm_i; ?>
						<div class="acc" data-acc>
							<h3 class="acc__h">
								<button class="acc__btn" type="button" aria-expanded="false" aria-controls="<?php echo esc_attr( $ecm_id ); ?>" id="<?php echo esc_attr( $ecm_id ); ?>-btn">
									<span class="acc__q"><?php echo esc_html( wp_strip_all_tags( $ecm_f['q'] ) ); ?></span>
									<span class="acc__icon" aria-hidden="true"></span>
								</button>
							</h3>
							<div class="acc__panel" id="<?php echo esc_attr( $ecm_id ); ?>" role="region" aria-labelledby="<?php echo esc_attr( $ecm_id ); ?>-btn">
								<div class="acc__inner"><div class="prose prose--sm"><?php echo wp_kses_post( wpautop( make_clickable( $ecm_f['a'] ) ) ); ?></div></div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $ecm_related ) : ?>
		<section class="related section-tight" aria-labelledby="related-title">
			<div class="wrap">
				<header class="section-head">
					<h2 class="section-title section-title--md" id="related-title" data-reveal="lines"><?php esc_html_e( 'Potrebbero interessarti', 'ecm' ); ?></h2>
					<a class="link-arrow" href="<?php echo esc_url( ! empty( $c['area'] ) ? $c['area']['url'] : ecm_catalog_url() ); ?>"><span><?php esc_html_e( 'Altri corsi dell’area', 'ecm' ); ?></span><?php echo ecm_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
				</header>
				<div class="related__grid">
					<?php
					foreach ( $ecm_related as $ecm_r ) {
						ecm_card_corso( $ecm_r, array( 'heading' => 'h3' ) );
					}
					?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<div class="sticky-cta" data-sticky-cta role="region" aria-label="<?php esc_attr_e( 'Azioni rapide sul corso', 'ecm' ); ?>">
		<p class="sticky-cta__title" aria-hidden="true"><?php echo esc_html( wp_trim_words( $c['title'], 8, '…' ) ); ?></p>
		<div class="sticky-cta__actions">
			<a class="btn btn--primary btn--sm has-icon" href="<?php echo esc_url( $c['whatsapp_url'] ); ?>" target="_blank" rel="noopener"><span class="btn__fill" aria-hidden="true"></span><span class="btn__icon"><?php echo ecm_icon( 'whatsapp' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span class="btn__label">WhatsApp</span></a>
			<?php if ( $ecm_b ) : ?>
				<a class="btn btn--secondary btn--sm has-icon" href="<?php echo esc_url( $ecm_b['url'] ); ?>" download="<?php echo esc_attr( $ecm_b['filename'] ); ?>"><span class="btn__fill" aria-hidden="true"></span><span class="btn__icon"><?php echo ecm_icon( 'download' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span class="btn__label"><?php esc_html_e( 'Brochure', 'ecm' ); ?></span></a>
			<?php endif; ?>
		</div>
	</div>
</article>

	<?php
endwhile;

get_footer();
