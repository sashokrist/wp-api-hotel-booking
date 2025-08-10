<?php
/**
 * Theme bootstrap
 */

if (!defined('ABSPATH')) { exit; }

/**
 * Setup theme supports and menus
 */
add_action('after_setup_theme', function () {
	add_theme_support('title-tag');
	add_theme_support('post-thumbnails');
	register_nav_menus([
		'primary' => __('Primary Menu', 'hotelbookings'),
	]);
});

/**
 * Enqueue styles (no external frameworks to keep it lean)
 */
add_action('wp_enqueue_scripts', function () {
	// style.css is auto-enqueued as the main stylesheet if we call get_stylesheet_uri()
	wp_enqueue_style('hotelbookings-style', get_stylesheet_uri(), [], wp_get_theme()->get('Version'));
});

/**
 * Admin notice if the Laravel Bookings plugin is missing
 */
add_action('admin_notices', function () {
	if (!is_plugin_active('laravel-bookings/laravel-bookings.php')) {
		echo '<div class="notice notice-warning"><p>'
		     . esc_html__('HotelBookings theme: The "Laravel Bookings Integration" plugin is not active. Shortcodes will not render.', 'hotelbookings')
		     . '</p></div>';
	}
});

/**
 * On theme activation:
 * - Create "Bookings" and "New Booking" pages if they don’t exist
 * - Assign the right templates
 * - Set "Bookings" as Front Page
 */
add_action('after_switch_theme', function () {
	// Helper to upsert a page by title
	$ensure_page = function ($title, $content, $template_basename) {
		$page = get_page_by_title($title);
		if (!$page) {
			$page_id = wp_insert_post([
				'post_title'   => $title,
				'post_content' => $content,
				'post_status'  => 'publish',
				'post_type'    => 'page',
			]);
			if (!is_wp_error($page_id)) {
				update_post_meta($page_id, '_wp_page_template', 'page-templates/' . $template_basename);
				return $page_id;
			}
			return 0;
		} else {
			// Ensure correct template & content if page already exists
			update_post_meta($page->ID, '_wp_page_template', 'page-templates/' . $template_basename);
			// Keep user edits if any; only set content if empty
			if (empty($page->post_content)) {
				wp_update_post([
					'ID'           => $page->ID,
					'post_content' => $content,
				]);
			}
			return $page->ID;
		}
	};

	// Create pages
	$bookings_id = $ensure_page(
		__('Bookings', 'hotelbookings'),
		// The template also calls do_shortcode, but this keeps content visible if user switches themes.
		'[laravel_bookings]',
		'template-bookings.php'
	);

	$create_id = $ensure_page(
		__('New Booking', 'hotelbookings'),
		'[laravel_create_booking]',
		'template-create-booking.php'
	);

	// Set "Bookings" as front page (only if we successfully created/found it)
	if ($bookings_id) {
		update_option('show_on_front', 'page');
		update_option('page_on_front', $bookings_id);
	}
});

/**
 * Small helper for safe shortcode rendering in templates
 */
function hb_safe_do_shortcode($shortcode) {
	if (!shortcode_exists(str_replace(['[',']'],'', $shortcode))) {
		// still try to render; some page builders hook late
	}
	return do_shortcode($shortcode);
}