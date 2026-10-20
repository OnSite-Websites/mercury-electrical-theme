<?php
/**
 * One-page home. All section content lives in the Home page's blocks,
 * so it's edited in Pages → Home with the standard WordPress editor.
 *
 * @package MercuryElectrical
 */
get_header();
while ( have_posts() ) :
	the_post();
	?>
	<div class="me-home">
		<?php the_content(); ?>
	</div>
	<?php
endwhile;
get_footer();
