<?php
/**
 * Pagina generica (privacy, note legali, pagine aggiunte dal cliente).
 *
 * @package ecm
 */

defined( 'ABSPATH' ) || exit;

get_header();
while ( have_posts() ) :
	the_post();
	?>
	<?php
	ecm_hero(
		array(
			'size'  => 'compact',
			'title' => get_the_title(),
			'lead'  => has_excerpt() ? get_the_excerpt() : '',
			'image' => 'hero-contatti',
		)
	);
	?>
<section class="section-tight">
	<div class="wrap">
		<div class="prose prose--editorial" data-prose><?php the_content(); ?></div>
	</div>
</section>
	<?php
endwhile;
get_footer();
