<?php
/**
 * Chi siamo (/chi-siamo/): testo dalla pagina (mission e vision attuali), impaginato in modo editoriale.
 * Il primo paragrafo diventa il lead; i titoli H2 del contenuto aprono le sezioni.
 *
 * @package ecm
 */

defined( 'ABSPATH' ) || exit;

get_header();
the_post();
$ecm_stats = ecm_stats();
?>
<section class="about-hero">
	<div class="wrap about-hero__grid">
		<div class="about-hero__copy">
			<h1 class="page-title" data-reveal="lines"><?php the_title(); ?></h1>
			<?php if ( has_excerpt() ) : ?>
				<p class="about-hero__lead" data-reveal="fade"><?php echo esc_html( get_the_excerpt() ); ?></p>
			<?php endif; ?>
		</div>
		<div class="about-hero__lens" data-img-lens>
			<?php echo ecm_img( 'chi-siamo', array( 'sizes' => '(min-width: 900px) 40vw, 80vw', 'priority' => true, 'alt' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
	</div>
</section>

<section class="about-body section-tight">
	<div class="wrap">
		<div class="prose prose--editorial" data-prose>
			<?php the_content(); ?>
		</div>
	</div>
</section>

<?php if ( $ecm_stats['corsi'] ) : ?>
	<section class="about-numbers section-tight" aria-label="<?php esc_attr_e( 'In numeri', 'ecm' ); ?>">
		<div class="wrap about-numbers__row">
			<?php foreach ( ecm_stats_extra() as $ecm_x ) : ?>
				<?php
				if ( false !== stripos( $ecm_x['label'], 'docent' ) ) {
					continue;
				}
				?>
				<p><?php echo wp_kses( ecm_stat_html( $ecm_x['value'] ), array( 'span' => array( 'class' => true, 'data-count' => true ) ) ); ?> <?php echo esc_html( mb_strtolower( $ecm_x['label'] ) ); ?></p>
			<?php endforeach; ?>
			<p><span class="num" data-count="<?php echo (int) $ecm_stats['corsi']; ?>"><?php echo esc_html( ecm_num( $ecm_stats['corsi'] ) ); ?></span> <?php esc_html_e( 'corsi online', 'ecm' ); ?></p>
			<?php if ( $ecm_stats['docenti'] ) : ?>
				<p><span class="num" data-count="<?php echo (int) $ecm_stats['docenti']; ?>"><?php echo esc_html( ecm_num( $ecm_stats['docenti'] ) ); ?></span> <?php esc_html_e( 'docenti', 'ecm' ); ?></p>
			<?php endif; ?>
			<?php if ( $ecm_stats['patrocini_ordini'] ) : ?>
				<p><span class="num" data-count="<?php echo (int) $ecm_stats['patrocini_ordini']; ?>"><?php echo esc_html( ecm_num( $ecm_stats['patrocini_ordini'] ) ); ?></span> <?php esc_html_e( 'enti patrocinanti', 'ecm' ); ?></p>
			<?php endif; ?>
		</div>
	</section>
<?php endif; ?>

<?php get_template_part( 'template-parts/cta-final' ); ?>
<?php
get_footer();
