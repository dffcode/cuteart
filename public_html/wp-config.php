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
define( 'DB_NAME', 'a1130416_app_wordpress_0' );

/** Database username */
define( 'DB_USER', 'a1130416_app_wordpress_0' );

/** Database password */
define( 'DB_PASSWORD', 'd19qa5DXEi' );

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
define( 'AUTH_KEY',         ',{G82IJ!+3sRPYZUsCH2%T+d%E_Z#bs#a3[l#O~Tr.4XvolZt%Ak%2c< yUv&6p3' );
define( 'SECURE_AUTH_KEY',  'PeGw`.L.(I,rgeG^%4^3_<-nUv[NvMspY~%K6z p*_hD8D%Ut=e bKCG7o{d1KCU' );
define( 'LOGGED_IN_KEY',    '<o+dZE`w/`IUhfNIY3?,jIisWERu75YD-1c&A`{.;g U)}6|6C_-E4>{!].n,DEL' );
define( 'NONCE_KEY',        '%YEOHyXy)cV<dt1Kx~w9fZa:ONn25@8)bh,~ 9g:a6iSeR*BpmYB]S_tGYdQfH,|' );
define( 'AUTH_SALT',        '$7XwYd_J|2/ZAsT+]!H}y>%UZDuYSG=wL>@~we:_H<=;Z&}^bNsR5xV@BDboSG.}' );
define( 'SECURE_AUTH_SALT', 'oG2.0kTdqGUil;ZO=lE1||4LcfPQ$*[o+ch#K)xWQXW8^jHO/J4SrdB)6knOxKf?' );
define( 'LOGGED_IN_SALT',   'khu=f0YG>iNN 6Xj?80;3ms;IiO`X:p=*v0-O+a5Wva10D,>G:<Wv]%ofVSr&npU' );
define( 'NONCE_SALT',       '{+},4I?P{l$e7K#2mjO-rPB9q1u~`.;=.>xb+4I6bZ2z&|o,.wCleVit3YBZ_,lA' );

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
