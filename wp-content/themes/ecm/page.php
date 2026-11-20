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
<section class="page-head page-head--simple">
	<div class="wrap">
		<h1 class="page-title page-title--md" data-reveal="lines"><?php the_title(); ?></h1>
	</div>
</section>
<section class="section-tight">
	<div class="wrap">
		<div class="prose prose--editorial" data-prose><?php the_content(); ?></div>
	</div>
</section>
	<?php
endwhile;
get_footer();
