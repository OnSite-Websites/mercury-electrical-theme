<?php
/**
 * Mercury Electrical QLD theme functions.
 *
 * @package MercuryElectrical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ME_VERSION', '1.0.6' );

/**
 * Business details. Editable in Appearance → Customize → Business Details.
 */
function me_defaults() {
	return array(
		'business_name' => 'Mercury Electrical QLD',
		'legal_name'    => 'Mercury Electrical QLD',
		'owner'         => 'Mitchell',
		'phone'         => '0401 587 947',
		'email'         => 'info@mercuryelectrical.com.au',
		'hours'         => "Mon to Fri: 24 hours\nSat: 8am to 12pm\nSun: Closed",
		'based_in'      => 'Maryborough QLD 4650',
		'service_area'  => 'Hervey Bay, Maryborough, Gympie and the wider Fraser Coast',
		'abn'           => '40 125 725 602',
		'licence'       => 'QLD Electrical Contractor Licence 92226',
		'licence_2'     => 'Electrical Work Licence 177057',
		'facebook'      => 'https://www.facebook.com/p/Mercury-Electrical-QLD-61584244492394/',
		'instagram'     => '',
		'google_url'    => 'https://www.google.com/maps/place/Mercury+Electrical+QLD/@-25.3416907,152.670169,9z/data=!3m1!4b1!4m6!3m5!1s0x2b8aa1412df2c479:0x9ddfe6bf2d59e1dc!8m2!3d-25.3416907!4d152.670169!16s%2Fg%2F11yrw9m5jq',
		'map_embed'     => 'https://maps.google.com/maps?q=Mercury%20Electrical%20QLD&ll=-25.3416907,152.670169&z=9&output=embed',
		'reviews_code'  => '',
		'quote_form_id' => '',
		'meta_title'    => 'Mercury Electrical QLD | Electrician | Fraser Coast',
		'meta_desc'     => 'Electrician for homes in Hervey Bay, Maryborough and the Fraser Coast. Switchboards, power, lighting and more. Call 0401 587 947 for a quote.',
	);
}

function me_opt( $key ) {
	$d = me_defaults();
	$v = get_theme_mod( 'me_' . $key, isset( $d[ $key ] ) ? $d[ $key ] : '' );
	return is_string( $v ) ? trim( $v ) : $v;
}

function me_tel() {
	return 'tel:' . preg_replace( '/[^0-9+]/', '', me_opt( 'phone' ) );
}

/** The services offered (used by the stand-in quote form). */
function me_services() {
	return array(
		'Power to a shed or modular home',
		'Switchboard upgrade or surge protection',
		'Power points and switches',
		'Lighting and ceiling fans',
		'Split system air conditioning',
		'EV charger installation',
		'Smoke alarm upgrade',
		'Fault finding and repairs',
		'Something else',
	);
}

/* -------------------------------------------------------------------------
 * Setup
 * ---------------------------------------------------------------------- */
add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support( 'custom-logo', array( 'height' => 120, 'width' => 124, 'flex-height' => true, 'flex-width' => true ) );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/editor.css' );
	register_nav_menus( array( 'primary' => __( 'Main menu', 'mercury-electrical' ) ) );
} );

/* -------------------------------------------------------------------------
 * Assets
 * ---------------------------------------------------------------------- */
add_action( 'wp_enqueue_scripts', function () {
	// The stylesheet is printed inline in the page head so the first paint
	// doesn't wait on a separate request (faster on slow mobile connections).
	$css = file_get_contents( get_stylesheet_directory() . '/style.css' ); // phpcs:ignore
	if ( $css ) {
		$base = trailingslashit( get_template_directory_uri() );
		$css  = preg_replace( '#url\((["\']?)assets/#', 'url($1' . $base . 'assets/', $css );
		wp_register_style( 'mercury-electrical', false, array(), ME_VERSION );
		wp_enqueue_style( 'mercury-electrical' );
		wp_add_inline_style( 'mercury-electrical', $css );
	} else {
		wp_enqueue_style( 'mercury-electrical', get_stylesheet_uri(), array(), ME_VERSION );
	}
	wp_enqueue_script( 'mercury-electrical', get_template_directory_uri() . '/assets/js/site.js', array(), ME_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
	// Not needed on a one-page brochure site.
	wp_dequeue_style( 'classic-theme-styles' );
	if ( is_front_page() ) {
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'global-styles' );
	}
}, 20 );

// Newer WordPress versions print each core block's own CSS separately, after the theme's styles,
// which overrides the theme (e.g. it squashed the hero photo). The theme styles every block itself,
// so ask for the single combined block stylesheet instead (dequeued on the home page above).
add_filter( 'should_load_separate_core_block_assets', '__return_false' );
add_filter( 'should_load_block_assets_on_demand', '__return_false' );

// Remove emoji scripts for speed.
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );

/* -------------------------------------------------------------------------
 * Speed (Lighthouse) tweaks
 * ---------------------------------------------------------------------- */

// The hero photo fills the screen width on phones and a bit over half of it on desktop.
define( 'ME_HERO_SIZES', '(max-width: 1023px) 100vw, 60vw' );

/** Finds the hero photo's attachment ID in the front page content. */
function me_hero_image_id() {
	static $id = null;
	if ( null !== $id ) {
		return $id;
	}
	$id   = 0;
	$post = get_post( (int) get_option( 'page_on_front' ) );
	if ( ! $post ) {
		return $id;
	}
	$find = function ( $blocks ) use ( &$find ) {
		foreach ( $blocks as $b ) {
			if ( 'core/image' === $b['blockName'] && ! empty( $b['attrs']['id'] ) && false !== strpos( $b['innerHTML'], 'me-hero__photo' ) ) {
				return (int) $b['attrs']['id'];
			}
			if ( ! empty( $b['innerBlocks'] ) ) {
				$r = $find( $b['innerBlocks'] );
				if ( $r ) {
					return $r;
				}
			}
		}
		return 0;
	};
	$id = $find( parse_blocks( $post->post_content ) );
	return $id;
}

// Correct "sizes" on the hero photo so phones download a phone-sized file.
add_filter( 'wp_calculate_image_sizes', function ( $sizes, $size, $src, $meta, $attachment_id = 0 ) {
	if ( $attachment_id && ! is_admin() && is_front_page() && (int) $attachment_id === me_hero_image_id() ) {
		return ME_HERO_SIZES;
	}
	return $sizes;
}, 10, 5 );

// The hero photo is the first thing seen: load it straight away, at high priority.
add_filter( 'wp_get_attachment_image_attributes', function ( $attr, $attachment ) {
	if ( ! is_admin() && is_front_page() && (int) $attachment->ID === me_hero_image_id() ) {
		$attr['loading']       = 'eager';
		$attr['fetchpriority'] = 'high';
		$attr['decoding']      = 'async';
	}
	return $attr;
}, 10, 2 );
add_filter( 'wp_content_img_tag', function ( $img, $context, $attachment_id ) {
	if ( is_front_page() && $attachment_id && (int) $attachment_id === me_hero_image_id() ) {
		$img = str_replace( ' loading="lazy"', '', $img );
		if ( false === strpos( $img, 'fetchpriority' ) ) {
			$img = str_replace( '<img ', '<img fetchpriority="high" ', $img );
		}
	}
	return $img;
}, 10, 3 );

// Only the hero photo loads straight away; everything else waits until it's near view.
add_filter( 'wp_omit_loading_attr_threshold', function () {
	return 1;
} );

/**
 * Accurate "sizes" for each photo slot so phones and laptops download
 * a file that matches how big the photo really shows.
 */
function me_image_sizes_for( $cls ) {
	$map = array(
		'me-service__img' => '(max-width: 767px) 92vw, (max-width: 1279px) 20vw, 300px',
		'me-about__main'  => '(max-width: 1099px) 70vw, 350px',
		'me-about__trench'=> '(max-width: 1099px) 60vw, 300px',
		'me-ba__before'   => '(max-width: 1099px) 100vw, 760px',
		'me-ba__after'    => '(max-width: 1099px) 100vw, 760px',
	);
	foreach ( $map as $k => $v ) {
		if ( false !== strpos( $cls, $k ) ) {
			return $v;
		}
	}
	return '';
}
add_filter( 'render_block', function ( $html, $block ) {
	if ( is_admin() || ! is_front_page() ) {
		return $html;
	}
	if ( 'core/image' === $block['blockName'] && ! empty( $block['attrs']['className'] ) ) {
		$sizes = me_image_sizes_for( $block['attrs']['className'] );
		if ( $sizes ) {
			$html = preg_replace( '/\ssizes="[^"]*"/', ' sizes="' . esc_attr( $sizes ) . '"', $html, 1 );
		}
	} elseif ( 'core/gallery' === $block['blockName'] ) {
		// First photo is double size on the grid; the rest are a quarter of the width.
		$n    = 0;
		$html = preg_replace_callback( '/\ssizes="[^"]*"/', function () use ( &$n ) {
			$n++;
			return ' sizes="' . ( 1 === $n ? '(max-width: 767px) 92vw, 600px' : '(max-width: 767px) 46vw, 300px' ) . '"';
		}, $html );
	}
	return $html;
}, 20, 2 );

// The quote form styles load without holding up the first paint.
add_filter( 'style_loader_tag', function ( $tag, $handle ) {
	if ( is_admin() || ! in_array( $handle, array( 'fluent-form-styles', 'fluentform-public-default' ), true ) ) {
		return $tag;
	}
	$deferred = str_replace( " media='all'", " media='print' onload=\"this.media='all'\"", $tag );
	return $deferred . '<noscript>' . $tag . '</noscript>';
}, 10, 2 );

// Lighter WebP compression for the resized copies WordPress makes (still sharp, much smaller).
add_filter( 'wp_editor_set_quality', function ( $quality, $mime = '' ) {
	return ( 'image/webp' === $mime ) ? 74 : $quality;
}, 10, 2 );

add_action( 'wp_head', function () {
	$fonts = get_template_directory_uri() . '/assets/fonts/';
	echo '<link rel="preload" href="' . esc_url( $fonts . 'anton-latin-400-normal.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
	echo '<link rel="preload" href="' . esc_url( $fonts . 'work-sans-latin-400-normal.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
	// Start fetching the hero photo straight away, at the right size for the screen.
	$hero = is_front_page() ? me_hero_image_id() : 0;
	if ( $hero ) {
		$src    = wp_get_attachment_image_url( $hero, 'full' );
		$srcset = wp_get_attachment_image_srcset( $hero, 'full' );
		if ( $src ) {
			echo '<link rel="preload" as="image" href="' . esc_url( $src ) . '"' . ( $srcset ? ' imagesrcset="' . esc_attr( $srcset ) . '" imagesizes="' . esc_attr( ME_HERO_SIZES ) . '"' : '' ) . ' fetchpriority="high">' . "\n";
		}
	}
	echo '<meta name="theme-color" content="#1B1813">' . "\n";
	// Adds the "js" class before paint so reveal styles only apply when scripts run.
	echo "<script>document.documentElement.classList.add('js');</script>\n";
	if ( ! has_site_icon() ) {
		$img = get_template_directory_uri() . '/assets/img/';
		echo '<link rel="icon" href="' . esc_url( $img . 'favicon.ico' ) . '" sizes="any">' . "\n";
		echo '<link rel="icon" type="image/png" href="' . esc_url( $img . 'favicon-512.png' ) . '">' . "\n";
		echo '<link rel="apple-touch-icon" href="' . esc_url( $img . 'apple-touch-icon.png' ) . '">' . "\n";
	}
	// Meta description, unless Yoast SEO is handling it.
	if ( ! defined( 'WPSEO_VERSION' ) ) {
		$desc = is_front_page() ? me_opt( 'meta_desc' ) : ( is_singular() ? wp_strip_all_tags( get_the_excerpt() ) : '' );
		if ( $desc ) {
			echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
		}
	}
}, 1 );

// Front page title, unless Yoast SEO is handling it.
add_filter( 'pre_get_document_title', function ( $title ) {
	if ( ! defined( 'WPSEO_VERSION' ) && is_front_page() && me_opt( 'meta_title' ) ) {
		return me_opt( 'meta_title' );
	}
	return $title;
} );

add_filter( 'document_title_separator', function () {
	return '|';
} );

/* -------------------------------------------------------------------------
 * Customizer: Business Details
 * ---------------------------------------------------------------------- */
add_action( 'customize_register', function ( $wp_customize ) {
	$wp_customize->add_section( 'me_business', array(
		'title'    => __( 'Business Details', 'mercury-electrical' ),
		'priority' => 30,
	) );
	$fields = array(
		'business_name' => 'Business name (header)',
		'legal_name'    => 'Legal name (footer)',
		'owner'         => 'Owner first name',
		'phone'         => 'Phone',
		'email'         => 'Email',
		'hours'         => 'Hours (one line each)',
		'based_in'      => 'Suburb, state and postcode (no street address)',
		'service_area'  => 'Service area',
		'abn'           => 'ABN',
		'licence'       => 'Contractor licence (e.g. QLD Electrical Contractor Licence 12345)',
		'licence_2'     => 'Second licence (optional)',
		'facebook'      => 'Facebook URL',
		'instagram'     => 'Instagram URL',
		'google_url'    => 'Google Business Profile URL',
		'meta_title'    => 'Home page meta title (ignored if Yoast is active)',
		'meta_desc'     => 'Home page meta description (ignored if Yoast is active)',
		'map_embed'     => 'Google Maps embed URL (the src from Share → Embed a map)',
		'reviews_code'  => 'Reviews shortcode (e.g. the Trustindex shortcode)',
		'quote_form_id' => 'Fluent Forms quote form ID',
	);
	$d = me_defaults();
	foreach ( $fields as $key => $label ) {
		$is_url = in_array( $key, array( 'facebook', 'instagram', 'google_url', 'map_embed' ), true );
		$wp_customize->add_setting( 'me_' . $key, array(
			'default'           => $d[ $key ],
			'sanitize_callback' => $is_url ? 'esc_url_raw' : ( 'reviews_code' === $key ? 'wp_kses_post' : ( 'hours' === $key ? 'sanitize_textarea_field' : 'sanitize_text_field' ) ),
		) );
		$wp_customize->add_control( 'me_' . $key, array(
			'label'   => $label,
			'section' => 'me_business',
			'type'    => in_array( $key, array( 'meta_desc', 'reviews_code', 'hours' ), true ) ? 'textarea' : ( $is_url ? 'url' : 'text' ),
		) );
	}
} );

/* -------------------------------------------------------------------------
 * Decorative artwork
 * The lightning bolts follow the bolt on Mitchell's van: one bold yellow
 * band with two sharp kinks, a black band beside it and a thin second bolt.
 * ---------------------------------------------------------------------- */

/** Desktop hero divider. Fixed width, stretches to the hero's height. */
function me_bolt_vertical() {
	return '<svg class="me-bolt me-bolt--v" viewBox="0 0 400 800" preserveAspectRatio="none" aria-hidden="true" focusable="false">'
		. '<polygon class="me-bolt__paper" points="-400,-40 278,-30 207,272 245,306 189,544 227,578 168,830 -400,840"/>'
		. '<polygon class="me-bolt__black" points="300,-30 229,272 267,306 211,544 249,578 190,830 270,830 329,578 291,544 347,306 309,272 380,-30"/>'
		. '<polygon class="me-bolt__main" points="278,-30 207,272 245,306 189,544 227,578 168,830 212,830 271,578 233,544 289,306 251,272 322,-30"/>'
		. '<polygon class="me-bolt__thin" points="256,-30 185,272 223,306 167,544 205,578 146,830 154,830 214,578 176,544 232,306 194,272 264,-30"/>'
		. '</svg>';
}

/** Phone and tablet hero divider, along the top of the photo. */
function me_bolt_horizontal() {
	return '<svg class="me-bolt me-bolt--h" viewBox="0 0 390 130" preserveAspectRatio="none" aria-hidden="true" focusable="false">'
		. '<polygon class="me-bolt__paper" points="-30,-40 -30,60 117,19 131,45 257,10 271,36 420,-6 420,-40"/>'
		. '<polygon class="me-bolt__black" points="-30,76 117,35 131,61 257,26 271,52 420,10 420,50 271,92 257,66 131,101 117,75 -30,116"/>'
		. '<polygon class="me-bolt__main" points="-30,60 117,19 131,45 257,10 271,36 420,-6 420,18 271,60 257,34 131,69 117,43 -30,84"/>'
		. '<polygon class="me-bolt__thin" points="-30,50 117,8 131,34 257,-1 271,25 420,-16 420,-12 271,30 257,4 131,39 117,13 -30,54"/>'
		. '</svg>';
}

/** Faint bolt that creeps in from the left edge of the About section. */
function me_bolt_side() {
	return '<div class="me-side-bolt" aria-hidden="true"><svg viewBox="0 0 300 900" preserveAspectRatio="none" focusable="false">'
		. '<polygon fill="#1B1813" points="150,-30 92,330 136,372 84,640 126,680 50,930 140,930 206,680 164,640 216,372 172,330 230,-30"/>'
		. '<polygon fill="#E0A912" points="112,-30 54,330 98,372 46,640 88,680 12,930 48,930 120,680 78,640 130,372 86,330 146,-30"/>'
		. '</svg></div>';
}

/** Big faded helmet mark used as a corner watermark. */
function me_watermark( $mod = '' ) {
	return '<img class="me-watermark' . ( $mod ? ' me-watermark--' . esc_attr( $mod ) : '' ) . '" src="' . esc_url( get_template_directory_uri() . '/assets/img/logo-icon.webp' ) . '" width="301" height="290" alt="" aria-hidden="true" loading="lazy" decoding="async">';
}

/**
 * Adds the decorative layers to the Home page sections as they render,
 * so they never clutter the block editor.
 */
add_filter( 'render_block', function ( $html, $block ) {
	if ( is_admin() || empty( $block['attrs']['className'] ) || 'core/group' !== $block['blockName'] ) {
		return $html;
	}
	$cls = ' ' . $block['attrs']['className'] . ' ';
	$add = '';
	if ( false !== strpos( $cls, ' me-hero ' ) ) {
		$add = me_watermark( 'hero' ) . me_bolt_vertical() . me_bolt_horizontal();
	} elseif ( false !== strpos( $cls, ' me-about ' ) ) {
		$add = me_bolt_side();
	} elseif ( false !== strpos( $cls, ' me-quote ' ) ) {
		$add = me_watermark( 'quote' );
	} elseif ( false !== strpos( $cls, ' me-reviews ' ) ) {
		$add = me_watermark( 'reviews' );
	} elseif ( false !== strpos( $cls, ' me-steps ' ) ) {
		$add = '<span class="me-cable" aria-hidden="true"><span class="me-cable__fill"></span><span class="me-cable__spark"></span></span>';
	}
	if ( ! $add ) {
		return $html;
	}
	// Insert straight after the section's opening tag.
	return preg_replace( '/^(\s*<(?:section|div)[^>]*>)/', '$1' . $add, $html, 1 );
}, 10, 2 );

/* -------------------------------------------------------------------------
 * Shortcodes used inside the Home page blocks
 * ---------------------------------------------------------------------- */

/**
 * [me_quote_form] Shows the Fluent Forms quote form once it exists,
 * otherwise a styled stand-in form (preview only, it does not send).
 */
add_shortcode( 'me_quote_form', function ( $atts ) {
	static $n = 0;
	$n++;
	$atts  = shortcode_atts( array( 'layout' => '' ), $atts, 'me_quote_form' );
	$stack = ( 'stack' === $atts['layout'] ) ? ' me-form--stack' : '';
	$id    = absint( me_opt( 'quote_form_id' ) );
	if ( ! $id && defined( 'FLUENTFORM' ) ) {
		global $wpdb;
		$table = $wpdb->prefix . 'fluentform_forms';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) { // phpcs:ignore
			$id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE title = %s ORDER BY id ASC LIMIT 1", 'Quote Request' ) ); // phpcs:ignore
		}
	}
	if ( $id && shortcode_exists( 'fluentform' ) ) {
		return '<div class="me-form me-form--live' . $stack . '">' . do_shortcode( '[fluentform id="' . $id . '"]' ) . '</div>';
	}
	$f = 'me-q' . $n . '-';
	ob_start();
	?>
	<form class="me-form<?php echo esc_attr( $stack ); ?>" data-me-demo action="#quote" method="post" novalidate>
		<p class="me-field"><label for="<?php echo esc_attr( $f ); ?>name">Name <span aria-hidden="true">*</span></label><input id="<?php echo esc_attr( $f ); ?>name" name="name" type="text" autocomplete="name" required></p>
		<p class="me-field"><label for="<?php echo esc_attr( $f ); ?>phone">Phone <span aria-hidden="true">*</span></label><input id="<?php echo esc_attr( $f ); ?>phone" name="phone" type="tel" autocomplete="tel" required></p>
		<p class="me-field"><label for="<?php echo esc_attr( $f ); ?>email">Email <span aria-hidden="true">*</span></label><input id="<?php echo esc_attr( $f ); ?>email" name="email" type="email" autocomplete="email" required></p>
		<p class="me-field"><label for="<?php echo esc_attr( $f ); ?>suburb">Suburb <span aria-hidden="true">*</span></label><input id="<?php echo esc_attr( $f ); ?>suburb" name="suburb" type="text" autocomplete="address-level2" required></p>
		<p class="me-field me-field--service"><label for="<?php echo esc_attr( $f ); ?>service">What do you need done? <span aria-hidden="true">*</span></label><select id="<?php echo esc_attr( $f ); ?>service" name="service" required><option value="">Choose a service</option><?php foreach ( me_services() as $s ) : ?><option><?php echo esc_html( $s ); ?></option><?php endforeach; ?></select></p>
		<p class="me-field me-field--msg"><label for="<?php echo esc_attr( $f ); ?>msg">Tell us about the job</label><textarea id="<?php echo esc_attr( $f ); ?>msg" name="message" rows="2"></textarea></p>
		<p class="me-form__submit"><button type="submit" class="me-submit">Send It Through <span class="me-arrow" aria-hidden="true">→</span></button></p>
		<p class="me-form__note" role="status" aria-live="polite"></p>
	</form>
	<?php
	return ob_get_clean();
} );

/** [me_licence] The licence line shown beside the quote form. */
add_shortcode( 'me_licence', function () {
	return '<span class="me-licence">' . esc_html( me_opt( 'licence' ) ) . '</span>';
} );

/** [me_reviews] Trustindex once connected, otherwise an obvious placeholder. */
add_shortcode( 'me_reviews', function () {
	$code = me_opt( 'reviews_code' );
	if ( $code ) {
		return '<div class="me-reviews__live">' . do_shortcode( $code ) . '</div>';
	}
	return '<div class="me-placeholder me-placeholder--reviews"><p class="me-placeholder__title">Google reviews will appear here</p><p>This spot shows real reviews from the Mercury Electrical QLD Google Business Profile once it is connected. Nothing here is written by us.</p></div>';
} );

/**
 * [me_map] Google Maps, loaded only when it scrolls near view
 * (keeps the page fast), otherwise an obvious placeholder.
 */
add_shortcode( 'me_map', function () {
	$src = me_opt( 'map_embed' );
	if ( $src ) {
		return '<div class="me-map" data-me-map="' . esc_url( $src ) . '" data-title="' . esc_attr( me_opt( 'business_name' ) . ' on Google Maps' ) . '"><noscript><iframe src="' . esc_url( $src ) . '" title="' . esc_attr( me_opt( 'business_name' ) . ' on Google Maps' ) . '" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe></noscript><p class="me-map__label">Servicing ' . esc_html( me_opt( 'service_area' ) ) . '</p></div>';
	}
	return '<div class="me-placeholder me-placeholder--map"><p class="me-placeholder__title">Google Map goes here</p><p>Servicing ' . esc_html( me_opt( 'service_area' ) ) . '.</p></div>';
} );

/** [me_hours] Hours, one per line. */
add_shortcode( 'me_hours', function () {
	$lines = array_filter( array_map( 'trim', explode( "\n", me_opt( 'hours' ) ) ) );
	return implode( '<br>', array_map( 'esc_html', $lines ) );
} );

/* -------------------------------------------------------------------------
 * Menu, logo and icons
 * ---------------------------------------------------------------------- */
function me_menu_items() {
	$base = is_front_page() ? '' : home_url( '/' );
	return array(
		array( 'About', $base . '#about' ),
		array( 'Services', $base . '#services' ),
		array( 'Reviews', $base . '#reviews' ),
		array( 'Contact', $base . '#contact' ),
	);
}

function me_menu() {
	if ( has_nav_menu( 'primary' ) ) {
		wp_nav_menu( array(
			'theme_location' => 'primary',
			'container'      => false,
			'menu_class'     => 'me-nav__list',
			'depth'          => 1,
		) );
		return;
	}
	echo '<ul class="me-nav__list">';
	foreach ( me_menu_items() as $item ) {
		echo '<li><a href="' . esc_url( $item[1] ) . '">' . esc_html( $item[0] ) . '</a></li>';
	}
	echo '</ul>';
}

function me_logo() {
	if ( has_custom_logo() ) {
		$id = get_theme_mod( 'custom_logo' );
		echo wp_get_attachment_image( $id, 'medium', false, array( 'class' => 'me-brand__logo', 'alt' => '', 'loading' => 'eager' ) );
		return;
	}
	echo '<img class="me-brand__logo" src="' . esc_url( get_template_directory_uri() . '/assets/img/logo-icon.webp' ) . '" width="301" height="290" alt="">';
}

/** Brand name: first word large, the rest small underneath (e.g. MERCURY / ELECTRICAL QLD). */
function me_brand_name() {
	$parts = preg_split( '/\s+/', me_opt( 'business_name' ), 2 );
	echo '<span class="me-brand__name">' . esc_html( $parts[0] ) . ( isset( $parts[1] ) ? '<span class="me-brand__sub">' . esc_html( $parts[1] ) . '</span>' : '' ) . '</span>';
}

function me_phone_svg( $size = 20 ) {
	return '<svg class="me-ico" width="' . (int) $size . '" height="' . (int) $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/></svg>';
}

function me_facebook_svg() {
	return '<svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M13.5 21v-7.5h2.5l.4-3h-2.9V8.6c0-.9.3-1.5 1.5-1.5h1.6V4.4c-.3 0-1.2-.1-2.3-.1-2.3 0-3.8 1.4-3.8 3.9v2.2H8v3h2.5V21h3z"/></svg>';
}

function me_instagram_svg() {
	return '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg>';
}

/* -------------------------------------------------------------------------
 * One-click setup: runs when the theme is activated.
 * Imports the photos into the Media Library, builds the Home and Privacy
 * Policy pages from editable blocks, and sets the front page and site icon.
 * ---------------------------------------------------------------------- */
function me_import_image( $file, $alt, $title = '' ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$existing = get_posts( array(
		'post_type'   => 'attachment',
		'post_status' => 'inherit',
		'numberposts' => 1,
		'meta_key'    => '_me_source', // phpcs:ignore
		'meta_value'  => $file, // phpcs:ignore
		'fields'      => 'ids',
	) );
	if ( $existing ) {
		return (int) $existing[0];
	}
	$src = get_template_directory() . '/assets/' . $file;
	if ( ! file_exists( $src ) ) {
		return 0;
	}
	$tmp = wp_tempnam( basename( $src ) );
	copy( $src, $tmp );
	$id = media_handle_sideload( array( 'name' => basename( $src ), 'tmp_name' => $tmp ), 0, $title );
	if ( is_wp_error( $id ) ) {
		@unlink( $tmp ); // phpcs:ignore
		return 0;
	}
	update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	update_post_meta( $id, '_me_source', $file );
	return (int) $id;
}

function me_run_setup( $force = false ) {
	if ( get_option( 'me_setup_done' ) && ! $force ) {
		return;
	}
	// Stops two page loads (e.g. the admin redirect and WP-Cron) running the import at once.
	if ( get_transient( 'me_setup_lock' ) ) {
		return;
	}
	set_transient( 'me_setup_lock', 1, 2 * MINUTE_IN_SECONDS );
	if ( function_exists( 'set_time_limit' ) ) {
		@set_time_limit( 180 ); // phpcs:ignore
	}
	$dir    = get_template_directory() . '/inc/';
	$home   = file_get_contents( $dir . 'home.html' ); // phpcs:ignore
	$priv   = file_get_contents( $dir . 'privacy.html' ); // phpcs:ignore
	$images = json_decode( file_get_contents( $dir . 'images.json' ), true ); // phpcs:ignore
	if ( ! $home || ! $images ) {
		delete_transient( 'me_setup_lock' );
		return;
	}
	foreach ( $images as $img ) {
		$title = ucwords( str_replace( '-', ' ', preg_replace( '/\.\w+$/', '', $img['file'] ) ) );
		$id    = me_import_image( 'photos/' . $img['file'], $img['alt'], $title );
		if ( ! $id ) {
			continue;
		}
		$size = ( 'hero' === $img['key'] ) ? 'full' : 'large';
		$url  = wp_get_attachment_image_url( $id, $size );
		$home = str_replace(
			array( '"id":' . $img['id'] . ',', 'wp-image-' . $img['id'] . '"', '"' . $img['url'] . '"' ),
			array( '"id":' . $id . ',', 'wp-image-' . $id . '"', '"' . $url . '"' ),
			$home
		);
	}

	// Home page.
	$home_id = (int) get_option( 'me_home_page' );
	$args    = array(
		'post_title'   => 'Home',
		'post_content' => wp_slash( $home ),
		'post_status'  => 'publish',
		'post_type'    => 'page',
	);
	if ( $home_id && get_post( $home_id ) ) {
		$args['ID'] = $home_id;
		wp_update_post( $args );
	} else {
		$home_id = wp_insert_post( $args );
		update_option( 'me_home_page', $home_id );
	}
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $home_id );

	// Privacy Policy page (reuses the one WordPress creates on install).
	$priv_id = (int) get_option( 'wp_page_for_privacy_policy' );
	$pargs   = array(
		'post_title'   => 'Privacy Policy',
		'post_name'    => 'privacy-policy',
		'post_content' => wp_slash( $priv ),
		'post_excerpt' => 'How Mercury Electrical QLD collects, uses and protects your personal information.',
		'post_status'  => 'publish',
		'post_type'    => 'page',
	);
	if ( $priv_id && get_post( $priv_id ) ) {
		$pargs['ID'] = $priv_id;
		wp_update_post( $pargs );
	} else {
		$priv_id = wp_insert_post( $pargs );
		update_option( 'wp_page_for_privacy_policy', $priv_id );
	}

	// Remove the default sample content so it doesn't get indexed.
	$sample = get_page_by_path( 'sample-page' );
	if ( $sample ) {
		wp_trash_post( $sample->ID );
	}
	$hello = get_page_by_path( 'hello-world', OBJECT, 'post' );
	if ( $hello ) {
		wp_trash_post( $hello->ID );
	}

	// Site title, tagline and icon.
	update_option( 'blogname', me_opt( 'business_name' ) );
	update_option( 'blogdescription', 'Residential electrician for Hervey Bay, Maryborough and the Fraser Coast' );
	$icon = me_import_image( 'img/favicon-512.png', '', 'Mercury Electrical QLD Icon' );
	if ( $icon ) {
		update_option( 'site_icon', $icon );
	}
	update_option( 'me_setup_done', ME_VERSION );
	delete_transient( 'me_setup_lock' );
}
add_action( 'after_switch_theme', 'me_run_setup' );

// If the theme was activated without the setup running (e.g. switched by a script or host tool),
// finish it on the next page load. The import skips photos already in the Media Library, so a
// request that runs out of time just carries on where it stopped.
add_action( 'init', function () {
	if ( ! get_option( 'me_setup_done' ) && ! wp_doing_ajax() && ! wp_doing_cron() ) {
		me_run_setup();
	}
}, 20 );

// Lets the setup be re-run from the dashboard if the pages are ever deleted.
add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'manage_options' ) || get_option( 'me_setup_done' ) ) {
		return;
	}
	$url = wp_nonce_url( admin_url( 'themes.php?me_setup=1' ), 'me_setup' );
	echo '<div class="notice notice-info"><p>Mercury Electrical QLD theme: <a href="' . esc_url( $url ) . '">build the Home and Privacy Policy pages</a>.</p></div>';
} );
add_action( 'admin_init', function () {
	if ( isset( $_GET['me_setup'] ) && current_user_can( 'manage_options' ) && check_admin_referer( 'me_setup' ) ) {
		me_run_setup( true );
		wp_safe_redirect( admin_url( 'post.php?post=' . (int) get_option( 'page_on_front' ) . '&action=edit' ) );
		exit;
	}
} );

add_filter( 'body_class', function ( $classes ) {
	$classes[] = is_front_page() ? 'me-is-home' : 'me-is-inner';
	return $classes;
} );
