<?php
/**
 * WPMovieLibrary-Hotlink-Movie-Poster
 *
 * Hotlink TMBD Movie Poster link support to WPMovieLibrary
 *
 * @package   WPMovieLibrary-Hotlink-Movie-Poster
 * @author    Filipe
 * @license   GPL-3.0
 *
 * @wordpress-plugin
 * Plugin Name: WPMovieLibrary-Hotlink-Movie-Poster
 * Description: Hotlink TMBD Movie Poster link  support to WPMovieLibrary
 * Version:     1.0
 * Author:      Filipe
 * Text Domain: wpml-hotlink-movie-poster
 * License:     GPL-3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path: /languages
 * GitHub Plugin URI: 
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'WPMOLYMP_NAME',                    'WPMovieLibrary-Hotlink-Movie-Poster' );
define( 'WPMOLYMP_VERSION',                 '1.0' );
define( 'WPMOLYMP_SLUG',                    'wpmoly-hotlink-movie-poster' );
define( 'WPMOLYMP_URL',                     plugins_url( basename( __DIR__ ) ) );
define( 'WPMOLYMP_PATH',                    plugin_dir_path( __FILE__ ) );
define( 'WPMOLYMP_REQUIRED_PHP_VERSION',    '5.4' );
define( 'WPMOLYMP_REQUIRED_WP_VERSION',     '3.8' );
define( 'WPMOLYMP_REQUIRED_WPMOLY_VERSION', '2.0' );

/**
 * Determine whether WPMOLY is active or not.
 *
 * @since    1.0
 *
 * @return   boolean
 */
if ( ! function_exists( 'is_wpmoly_active' ) ) :
	function is_wpmoly_active() {

		return defined( 'WPMOLYVERSION' );
	}
endif;

/**
 * Checks if the system requirements are met
 * 
 * @since    1.0
 * 
 * @return   bool    True if system requirements are met, false if not
 */
function wpmolymp_requirements_met() {

	global $wp_version;

	if ( version_compare( PHP_VERSION, WPMOLYMP_REQUIRED_PHP_VERSION, '<=' ) )
		return false;

	if ( version_compare( $wp_version, WPMOLYMP_REQUIRED_WP_VERSION, '<=' ) )
		return false;

	return true;
}

/**
 * Prints an error that the system requirements weren't met.
 * 
 * @since    1.0
 */
function wpmolymp_requirements_error() {

	global $wp_version;

	require_once WPMOLYMP_PATH . '/views/requirements-error.php';
}

/**
 * Prints an error that the system requirements weren't met.
 * 
 * @since    1.0.1
 */
function wpmolymp_l10n() {

	$domain = 'wpmovielibrary-hotlink-movie-poster';
	$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

	load_textdomain( $domain, WPMOLYMP_PATH . 'languages/' . $domain . '-' . $locale . '.mo' );
	load_plugin_textdomain( $domain, FALSE, basename( __DIR__ ) . '/languages/' );
}

/*
 * Check requirements and load main class
 * The main program needs to be in a separate file that only gets loaded if the
 * plugin requirements are met. Otherwise older PHP installations could crash
 * when trying to parse it.
 */
if ( wpmolymp_requirements_met() ) {

	require_once( WPMOLYMP_PATH . 'includes/class-module.php' );
	require_once( WPMOLYMP_PATH . 'class-wpmoly-hotlink-movie-poster.php' );

	if ( class_exists( 'WPMovieLibrary_Hotlink_Movie_Poster' ) ) {
		$GLOBALS['wpmolymp'] = new WPMovieLibrary_Hotlink_Movie_Poster();
		register_activation_hook(   __FILE__, array( $GLOBALS['wpmolymp'], 'activate' ) );
		register_deactivation_hook( __FILE__, array( $GLOBALS['wpmolymp'], 'deactivate' ) );
	}
	WPMovieLibrary_Hotlink_Movie_Poster::require_wpmoly_first();
}
else {
	add_action( 'init', 'wpmolymp_l10n' );
	add_action( 'admin_notices', 'wpmolymp_requirements_error' );
}

function wpmolyhl_global_vars() {

	global $wpmolyhl;
	$wpmolyhl = array(
		'size'  => ''
	);
}