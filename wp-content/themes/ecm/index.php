<?php
/**
 * Fallback generico.
 *
 * @package ecm
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<?php
ecm_hero(
	array(
		'size'  => 'compact',
		'title' => wp_get_document_title(),
		'image' => 'hero-corsi',
	)
);
?>
<section class="section-tight">
	<div class="wrap prose prose--editorial">
		<?php
		if ( have_posts() ) {
			while ( have_posts() ) {
				the_post();
				printf( '<h2><a href="%s">%s</a></h2>', esc_url( get_permalink() ), esc_html( get_the_title() ) );
				the_excerpt();
			}
		} else {
			echo '<p>' . esc_html__( 'Nessun contenuto trovato.', 'ecm' ) . '</p>';
		}
		?>
	</div>
</section>
<?php
get_footer();
