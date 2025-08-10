<?php
/**
 * Template Name: Bookings List
 * Description: Renders the Laravel bookings list shortcode with theme styling.
 */
if (!defined('ABSPATH')) { exit; }
get_header(); ?>
	<div class="hb-card">
		<h1><?php echo esc_html(get_the_title()); ?></h1>
		<div class="entry">
			<?php
			// Prefer template rendering; also keep page content for flexibility:
			the_content();

			// Force-render the shortcode even if content was edited
			echo hb_safe_do_shortcode('[laravel_bookings]');
			?>
		</div>
	</div>
<?php get_footer();