<?php if (!defined('ABSPATH')) { exit; } ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<header class="hb-header">
	<div class="hb-container">
		<div class="hb-brand">
			<span>🏨</span>
			<a href="<?php echo esc_url(home_url('/')); ?>">HotelBookings</a>
		</div>
		<nav class="hb-nav">
			<?php
			wp_nav_menu([
				'theme_location' => 'primary',
				'container'      => false,
				'menu_class'     => '',
				'fallback_cb'    => function(){ echo '<a href="'.esc_url(admin_url('nav-menus.php')).'">'.esc_html__('Add Menu', 'hotelbookings').'</a>'; }
			]);
			?>
		</nav>
	</div>
</header>
<main>
	<div class="hb-container">