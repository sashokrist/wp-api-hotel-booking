<?php
/**
 * Template Name: Create Booking
 * Description: Renders the Laravel create-booking shortcode with theme styling.
 */
if (!defined('ABSPATH')) { exit; }
get_header(); ?>
	<div class="hb-card">
		<h1><?php echo esc_html(get_the_title()); ?></h1>
		<div class="entry">
			<?php
			the_content();
			echo hb_safe_do_shortcode('[laravel_create_booking]');
			?>
		</div>
	</div>
<?php get_footer();