<?php
/**
 * Standard pages (e.g. the Privacy Policy).
 *
 * @package MercuryElectrical
 */
get_header();
while ( have_posts() ) :
	the_post();
	?>
	<article class="me-page">
		<header class="me-page__head">
			<h1 class="me-page__title"><?php the_title(); ?></h1>
		</header>
		<div class="me-page__body">
			<?php the_content(); ?>
		</div>
	</article>
	<?php
endwhile;
get_footer();
