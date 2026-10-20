<?php
/**
 * Footer.
 *
 * @package MercuryElectrical
 */
$me_priv = get_privacy_policy_url();
$me_base = is_front_page() ? '' : home_url( '/' );
$me_name = me_opt( 'business_name' );
?>
</main>
<footer class="me-footer">
	<div class="me-footer__inner">
		<div class="me-footer__brand">
			<a class="me-brand me-brand--footer" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<span class="me-brand__badge"><?php me_logo(); ?></span>
				<?php me_brand_name(); ?>
			</a>
			<p class="me-footer__blurb">Residential electrician for <?php echo esc_html( me_opt( 'service_area' ) ); ?>.</p>
			<a class="me-footer__call" href="<?php echo esc_attr( me_tel() ); ?>"><?php echo me_phone_svg( 20 ); // phpcs:ignore ?><?php echo esc_html( me_opt( 'phone' ) ); ?></a>
		</div>
		<div class="me-footer__col">
			<h2 class="me-footer__h">Contact</h2>
			<ul>
				<li><a href="<?php echo esc_attr( me_tel() ); ?>"><?php echo esc_html( me_opt( 'phone' ) ); ?></a></li>
				<li><a href="mailto:<?php echo esc_attr( me_opt( 'email' ) ); ?>"><?php echo esc_html( me_opt( 'email' ) ); ?></a></li>
				<li><?php echo do_shortcode( '[me_hours]' ); // phpcs:ignore ?></li>
			</ul>
		</div>
		<div class="me-footer__col">
			<h2 class="me-footer__h">Service Area</h2>
			<ul>
				<li>Based in <?php echo esc_html( me_opt( 'based_in' ) ); ?></li>
				<li><?php echo esc_html( me_opt( 'service_area' ) ); ?></li>
			</ul>
		</div>
		<div class="me-footer__col">
			<h2 class="me-footer__h">Quick Links</h2>
			<ul>
				<li><a href="<?php echo esc_url( $me_base . '#about' ); ?>">About</a></li>
				<li><a href="<?php echo esc_url( $me_base . '#services' ); ?>">Services</a></li>
				<li><a href="<?php echo esc_url( $me_base . '#work' ); ?>">Recent Work</a></li>
				<li><a href="<?php echo esc_url( $me_base . '#quote' ); ?>">Get a Quote</a></li>
			</ul>
			<?php if ( me_opt( 'facebook' ) || me_opt( 'instagram' ) ) : ?>
			<div class="me-social">
				<?php if ( me_opt( 'facebook' ) ) : ?><a href="<?php echo esc_url( me_opt( 'facebook' ) ); ?>" target="_blank" rel="noopener"><?php echo me_facebook_svg(); // phpcs:ignore ?><span class="screen-reader-text"><?php echo esc_html( $me_name ); ?> on Facebook (opens in a new tab)</span></a><?php endif; ?>
				<?php if ( me_opt( 'instagram' ) ) : ?><a href="<?php echo esc_url( me_opt( 'instagram' ) ); ?>" target="_blank" rel="noopener"><?php echo me_instagram_svg(); // phpcs:ignore ?><span class="screen-reader-text"><?php echo esc_html( $me_name ); ?> on Instagram (opens in a new tab)</span></a><?php endif; ?>
			</div>
			<?php endif; ?>
		</div>
	</div>
	<div class="me-footer__bar">
		<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( me_opt( 'legal_name' ) ); ?> <span aria-hidden="true">·</span> ABN <?php echo esc_html( me_opt( 'abn' ) ); ?></p>
		<p><?php echo esc_html( me_opt( 'licence' ) ); ?><?php if ( me_opt( 'licence_2' ) ) : ?> <span aria-hidden="true">·</span> <?php echo esc_html( me_opt( 'licence_2' ) ); ?><?php endif; ?></p>
		<?php if ( $me_priv ) : ?><p><a href="<?php echo esc_url( $me_priv ); ?>">Privacy Policy</a></p><?php endif; ?>
		<p>Designed and Built by <a href="https://onsitewebsites.com.au/" target="_blank" rel="noopener">OnSite Websites<span class="screen-reader-text"> (opens in a new tab)</span></a></p>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
