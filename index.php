<?php
/**
 * Fallback template (also used for 404s).
 *
 * @package MercuryElectrical
 */
get_header();
?>
<article class="me-page">
	<header class="me-page__head">
		<h1 class="me-page__title"><?php echo is_404() ? 'Page not found' : esc_html( wp_get_document_title() ); ?></h1>
	</header>
	<div class="me-page__body">
		<?php if ( have_posts() && ! is_404() ) : ?>
			<?php while ( have_posts() ) : the_post(); ?>
				<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
				<?php the_excerpt(); ?>
			<?php endwhile; ?>
		<?php else : ?>
			<p>That page doesn't exist. Head back to the <a href="<?php echo esc_url( home_url( '/' ) ); ?>">home page</a> or call <a href="<?php echo esc_attr( me_tel() ); ?>"><?php echo esc_html( me_opt( 'phone' ) ); ?></a>.</p>
		<?php endif; ?>
	</div>
</article>
<?php
get_footer();
