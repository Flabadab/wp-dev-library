<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

//get environment - specific rules for some dev setups
if( false !== strpos( $_SERVER['HTTP_HOST'], 'dev01' ) ):
	define('WP_ENVIRONMENT', 'dev');
else:
	define('WP_ENVIRONMENT', 'live');
endif;


/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', ('live' == WP_ENVIRONMENT ? false : true) );
define('WP_DEBUG_DISPLAY',	('live' == WP_ENVIRONMENT ? false : true) ); // Turn forced display OFF
define('WP_DEBUG_LOG',		('live' == WP_ENVIRONMENT ? false : true) );	// Turn logging to wp-content/debug.log ON

#region ===================== DEBUGGING & BENCHMARKING @ABT ==================================
if( WP_DEBUG ) :
	// System Start Time
	define('WP_START_TIME', microtime(true));
	
	// System Start Memory
	define('WP_START_MEMORY_USAGE', memory_get_usage());
	
	/**
	 * Record memory usage and timestamp and then return difference next run (and restart)
	 * @see micromvc
	 *
	 * @return array
	 */
	function benchmark() {
		static$t,$m;$a=array((microtime(true)-$t),(memory_get_usage()-$m));$t=microtime(true);$m=memory_get_usage();return$a;
	}
	benchmark();
	
	define('SAVEQUERIES', true);	// remember all wordpress queries
endif;	// WP_DEBUG
#endregion ===================== DEBUGGING & BENCHMARKING @ABT ==================================
if(!function_exists('_log')):
function _log( ) {
	if( true === WP_DEBUG_LOG ){
		$args = func_get_args();
		foreach($args as $message){
			if( is_array( $message ) || is_object( $message ) ){
				error_log( print_r( $message, true ) );
			} else {
				error_log( $message );
			}
		}
	}
}//---		function _log
endif;	// !func_exists _log




// ** MySQL settings - You can get this info from your web host ** //

if( 'live' == WP_ENVIRONMENT ) {
	echo '----------------LIVE DATABASE-------------------';
	die('make sure this works in ' . __FILE__);
	/** The name of the database for WordPress */
	define('DB_NAME', 'YOURDB');

	/** MySQL database username */
	define('DB_USER', 'YOURDBUSER');

	/** MySQL database password */
	define('DB_PASSWORD', 'YOURDBPASS');

	/** MySQL hostname */
	define('DB_HOST', 'localhost');
}
else {
	define('DB_NAME', 'YOURDB');
	define('DB_USER', 'YOURDBUSER');
	define('DB_PASSWORD', 'YOURDBPASS');
	define('DB_HOST', 'localhost');
}

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */

define('AUTH_KEY',         'get-a-new-key');
define('SECURE_AUTH_KEY',  'get-a-new-key');
define('LOGGED_IN_KEY',    'get-a-new-key');
define('NONCE_KEY',        'get-a-new-key');
define('AUTH_SALT',        'get-a-new-key');
define('SECURE_AUTH_SALT', 'get-a-new-key');
define('LOGGED_IN_SALT',   'get-a-new-key');
define('NONCE_SALT',       'get-a-new-key');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
define('WPLANG', '');

#region ================== MULTISITE ================
/**
 * Enable multisite
 * @see http://codex.wordpress.org/Create_A_Network#Step_3:_Allow_Multisite
 * @var const bool
 */
/*	define('WP_ALLOW_MULTISITE', true);	//remove this line after installing??

#region ================== generated code ================
define( 'MULTISITE', true );
define( 'SUBDOMAIN_INSTALL', true );
$base = '/';
define( 'DOMAIN_CURRENT_SITE', ('live' == WP_ENVIRONMENT ? 'LIVESITE.com' : 'DEVSITE.com') );
define( 'PATH_CURRENT_SITE', '/' );
define( 'SITE_ID_CURRENT_SITE', 1 );
define( 'BLOG_ID_CURRENT_SITE', 1 );
#endregion ================== generated code ================
#endregion ================== MULTISITE ================

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
