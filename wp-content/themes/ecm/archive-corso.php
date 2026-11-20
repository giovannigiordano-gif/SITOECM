<?php
/**
 * Catalogo corsi (/corsi/) e pagine area (/area/{slug}/).
 * Filtri per area e destinatario + ricerca, applicati lato server (link condivisibili)
 * e poi lato client senza ricaricare, con transizioni animate (GSAP Flip).
 *
 * @package ecm
 */

defined( 'ABSPATH' ) || exit;

get_header();

$ecm_state   = ecm_catalog_state();
$ecm_all     = ecm_courses();
$ecm_areas   = ecm_areas();
$ecm_dest    = ecm_destinatari();
$ecm_visible = 0;
foreach ( $ecm_all as $ecm_c ) {
	$ecm_visible += ecm_course_matches( $ecm_c, $ecm_state ) ? 1 : 0;
}
$ecm_term    = is_tax( 'area_tematica' ) ? get_queried_object() : null;
$ecm_title   = $ecm_term instanceof WP_Term ? $ecm_term->name : __( 'Corsi', 'ecm' );
$ecm_intro   = $ecm_term instanceof WP_Term && $ecm_term->description ? wp_strip_all_tags( $ecm_term->description ) : __( 'Formazione ECM online in nutrizione umana e animale, fitoterapia, microbiota e comunicazione sanitaria. Filtra per area o per professione.', 'ecm' );
$ecm_count_l = 0 === $ecm_visible ? __( 'Nessun corso', 'ecm' ) : sprintf( _n( '%d corso', '%d corsi', $ecm_visible, 'ecm' ), $ecm_visible );
?>

<section class="page-head page-head--catalog">
	<div class="wrap page-head__grid">
		<div>
			<?php
			if ( $ecm_term instanceof WP_Term ) {
				ecm_breadcrumbs( array( array( __( 'Corsi', 'ecm' ), ecm_catalog_url() ), array( $ecm_term->name, '' ) ) );
			}
			?>
			<h1 class="page-title page-title--count"><span data-reveal="lines"><?php echo esc_html( $ecm_title ); ?></span> <sup class="page-title__count" aria-label="<?php echo esc_attr( sprintf( /* translators: %d */ _n( '%d corso', '%d corsi', $ecm_term instanceof WP_Term ? (int) $ecm_term->count : count( $ecm_all ), 'ecm' ), $ecm_term instanceof WP_Term ? (int) $ecm_term->count : count( $ecm_all ) ) ); ?>"><?php echo (int) ( $ecm_term instanceof WP_Term ? $ecm_term->count : count( $ecm_all ) ); ?></sup></h1>
		</div>
		<p class="page-head__intro" data-reveal="fade"><?php echo esc_html( $ecm_intro ); ?></p>
	</div>
</section>

<div class="catalog" data-catalog>
	<form class="filters" role="search" data-filters action="<?php echo esc_url( ecm_catalog_url() ); ?>" method="get" aria-label="<?php esc_attr_e( 'Filtra i corsi', 'ecm' ); ?>">
		<div class="wrap filters__inner">
			<fieldset class="filters__areas">
				<legend class="visually-hidden"><?php esc_html_e( 'Area tematica', 'ecm' ); ?></legend>
				<div class="chips" data-chips>
					<label class="chip">
						<input type="radio" name="area" value="" <?php checked( '', $ecm_state['area'] ); ?>>
						<span class="chip__ui"><?php esc_html_e( 'Tutte le aree', 'ecm' ); ?> <span class="chip__count"><?php echo (int) count( $ecm_all ); ?></span></span>
					</label>
					<?php foreach ( $ecm_areas as $ecm_a ) : ?>
						<label class="chip">
							<input type="radio" name="area" value="<?php echo esc_attr( $ecm_a['slug'] ); ?>" <?php checked( $ecm_a['slug'], $ecm_state['area'] ); ?>>
							<span class="chip__ui"><?php echo esc_html( $ecm_a['name'] ); ?> <span class="chip__count"><?php echo (int) $ecm_a['count']; ?></span></span>
						</label>
					<?php endforeach; ?>
					<span class="chips__indicator" aria-hidden="true" data-chips-indicator></span>
				</div>
			</fieldset>

			<div class="filters__row">
				<div class="field field--select">
					<label class="field__label" for="f-per"><?php esc_html_e( 'Professione', 'ecm' ); ?></label>
					<div class="select">
						<select id="f-per" name="per">
							<option value=""><?php esc_html_e( 'Tutte le professioni', 'ecm' ); ?></option>
							<?php foreach ( $ecm_dest as $ecm_d ) : ?>
								<option value="<?php echo esc_attr( $ecm_d['slug'] ); ?>" <?php selected( $ecm_d['slug'], $ecm_state['per'] ); ?>><?php echo esc_html( $ecm_d['name'] ); ?></option>
							<?php endforeach; ?>
						</select>
						<?php echo ecm_icon( 'caret-down', 'select__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>
				</div>
				<div class="field field--search">
					<label class="field__label" for="f-q"><?php esc_html_e( 'Cerca', 'ecm' ); ?></label>
					<div class="search">
						<?php echo ecm_icon( 'search', 'search__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<input id="f-q" type="search" name="q" value="<?php echo esc_attr( $ecm_state['q'] ); ?>" placeholder="<?php esc_attr_e( 'Es. microbiota, chetogenica, gatto', 'ecm' ); ?>" autocomplete="off" enterkeyhint="search">
						<button type="button" class="search__clear" data-search-clear aria-label="<?php esc_attr_e( 'Svuota la ricerca', 'ecm' ); ?>" <?php echo '' === $ecm_state['q'] ? 'hidden' : ''; ?>><?php echo ecm_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
					</div>
				</div>
				<p class="filters__count" aria-live="polite" data-count-label><?php echo esc_html( $ecm_count_l ); ?></p>
				<noscript><button class="btn btn--primary btn--sm" type="submit"><span class="btn__label"><?php esc_html_e( 'Filtra', 'ecm' ); ?></span></button></noscript>
			</div>
		</div>
	</form>

	<div class="wrap">
		<h2 class="visually-hidden"><?php esc_html_e( 'Risultati', 'ecm' ); ?></h2>
		<div class="catalog__grid" data-grid>
			<?php
			foreach ( $ecm_all as $ecm_c ) {
				ecm_card_corso(
					$ecm_c,
					array(
						'catalog' => true,
						'hidden'  => ! ecm_course_matches( $ecm_c, $ecm_state ),
					)
				);
			}
			?>
		</div>

		<div class="empty" data-empty <?php echo $ecm_visible ? 'hidden' : ''; ?>>
			<?php echo ecm_img( 'empty-plate', array( 'class' => 'empty__img', 'sizes' => '240px', 'alt' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<h2 class="empty__title"><?php esc_html_e( 'Nessun corso con questi filtri', 'ecm' ); ?></h2>
			<p class="empty__text"><?php esc_html_e( 'Prova a togliere un filtro o a cercare un’altra parola. Se cerchi un argomento preciso, chiedici: potrebbe essere in arrivo.', 'ecm' ); ?></p>
			<div class="empty__actions">
				<a class="btn btn--secondary btn--sm" href="<?php echo esc_url( ecm_catalog_url() ); ?>" data-filters-reset><span class="btn__fill" aria-hidden="true"></span><span class="btn__label"><?php esc_html_e( 'Mostra tutti i corsi', 'ecm' ); ?></span></a>
				<?php if ( ecm_opt( 'whatsapp_number' ) ) : ?>
					<a class="btn btn--ghost btn--sm has-icon" href="<?php echo esc_url( ecm_whatsapp_generic() ); ?>" target="_blank" rel="noopener"><span class="btn__icon"><?php echo ecm_icon( 'whatsapp' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span class="btn__label"><?php esc_html_e( 'Scrivici su WhatsApp', 'ecm' ); ?></span></a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>

<?php get_template_part( 'template-parts/cta-final' ); ?>

<?php
get_footer();
