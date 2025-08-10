<?php
// Fallback template (lists posts or default content)
get_header(); ?>
	<div class="hb-card">
		<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
			<article <?php post_class(); ?>>
				<h1><?php the_title(); ?></h1>
				<div class="entry"><?php the_content(); ?></div>
			</article>
		<?php endwhile; else: ?>
			<p><?php esc_html_e('Nothing found.', 'hotelbookings'); ?></p>
		<?php endif; ?>
	</div>
<?php get_footer();