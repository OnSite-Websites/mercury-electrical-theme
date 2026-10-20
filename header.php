<?php
/**
 * Header.
 *
 * @package MercuryElectrical
 */
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="me-skip" href="#main">Skip to content</a>
<header class="me-header" id="me-header">
	<div class="me-header__bg" aria-hidden="true"></div>
	<div class="me-header__inner">
		<a class="me-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
			<span class="me-brand__badge"><?php me_logo(); ?></span>
			<?php me_brand_name(); ?>
		</a>
		<nav class="me-nav" id="me-nav" aria-label="Main">
			<?php me_menu(); ?>
			<a class="me-nav__call" href="<?php echo esc_attr( me_tel() ); ?>"><?php echo me_phone_svg( 20 ); // phpcs:ignore ?><span><?php echo esc_html( me_opt( 'phone' ) ); ?></span></a>
		</nav>
		<a class="me-call" href="<?php echo esc_attr( me_tel() ); ?>"><?php echo me_phone_svg( 19 ); // phpcs:ignore ?><span class="me-call__num"><?php echo esc_html( me_opt( 'phone' ) ); ?></span><span class="me-call__short">Call Now</span></a>
		<button class="me-menu-btn" type="button" aria-controls="me-nav" aria-expanded="false"><span class="screen-reader-text">Open menu</span><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" aria-hidden="true" focusable="false"><path class="me-menu-btn__top" d="M4 7h16"/><path class="me-menu-btn__mid" d="M4 12h16"/><path class="me-menu-btn__bot" d="M4 17h16"/></svg></button>
	</div>
</header>
<main id="main" class="me-main" tabindex="-1">
