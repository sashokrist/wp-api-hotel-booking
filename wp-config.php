<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'api-hotel-wordpess' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'Eb!^n8f5oOhp|b)$LUu9=$kS5pkQA!~ktU=$Tj.Ds 1d}:MYk3K>ZK-,QFJJ !SI' );
define( 'SECURE_AUTH_KEY',  'rZ0b9JKY9DsT1G1ms}J)i2yg[4Aa(j#3w| 7KE%;crgWQo(E-dcy=[h+R85tltak' );
define( 'LOGGED_IN_KEY',    'x)cy3D[>+zjUg=[8{,h?RrF6@<9PwEJNcj-de|hj0AE4lDAhceOX^gn>xUcv^@42' );
define( 'NONCE_KEY',        '6xh -Ck8=ClK6j-5SOZ32;Qrxs%,m^-D9=p~e1v:5n-|e,SWRh~DE$x<%HTW@FMy' );
define( 'AUTH_SALT',        'r?ZdzRi@n.}oymp@)LN+G(^p=N .%x5dg=fbX9tt~-Wt%284`Efq:)]=qf`JFyh?' );
define( 'SECURE_AUTH_SALT', 'fc)?y$ppb;@mx_o&0z_>86@:b6S2Q36;1rJ%O.Ui2#XM0>k+J9n~A<q{J}mmE[SA' );
define( 'LOGGED_IN_SALT',   '3)9WUww2K#l:{>btJ*gq/nvJ)eM1ZE=W.=hl;6*g%NO{@tc8ofl99gE2V,yoTp[z' );
define( 'NONCE_SALT',       '9>zb$$vcv){6V)MTNaVHG*78nnrgN#%:MS&M*:2O##ToG(g6L^EM#@W5AgKU`,P4' );

define( 'WP_DEBUG', true );

// Write errors to /wp-content/debug.log instead of showing on screen
define( 'WP_DEBUG_LOG', true );

// Hide errors from being displayed to site visitors
define( 'WP_DEBUG_DISPLAY', false );
@ini_set( 'display_errors', 0 );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
