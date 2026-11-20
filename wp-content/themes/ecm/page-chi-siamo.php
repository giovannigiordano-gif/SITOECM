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
<?php
ecm_hero(
	array(
		'size'      => 'page',
		'photo'     => true,
		'title'     => get_the_title(),
		'lead'      => has_excerpt() ? get_the_excerpt() : '',
		'image'     => 'hero-chi-siamo',
		'focus'     => '42% 40%',
		'focus_m'   => '36% 30%',
		'ring_text' => __( 'Formazione continua', 'ecm' ),
		'scroll'    => '#chi-siamo-testo',
	)
);
?>

<section class="about-body section-tight" id="chi-siamo-testo">
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
