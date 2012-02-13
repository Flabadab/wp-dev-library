<?php
/*
Plugin Name: WP-DEV-Library
Plugin URI: http://atlanticbt.com/
Description: Provides a foundation and extensions for/to common Wordpress functions or tasks, such as creating and installing plugins, making complex database queries, etc
Version: 0.4.1
Provides: wp-dev-library
Author: atlanticbt, zaus, tnblueswirl
Author URI: http://www.atlanticbt.com/
License: GPLv2 or later
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/


/*
 * Common shared functions and stuff -- could put it in theme/functions.php, but this way is "available to everything"
 *
 * @package AtlanticBT_Common
 * @subpackage WP_Library
 * @since MM-Solid Rock 1.0, MPS Society 1.0
 * 
 * @version 0.4.1
 * 
 * HISTORY:
 * 	- 0.1	creation, shared stuff
 * 	- 0.2	mvc stub
 * 	- 0.2.1	form validation, lang
 *	- 0.3	turned into a plugin
 *	- 0.4	reintegrated adam daniel's (tnblueswirl) fixes
 *  - 0.4.1	plugin-dependency inclusion
 *  */
/*
 * Use this section to include other library files that should be available globally.
 * Basically just used so that you can include all the other files by including this one.
 */
#region ========================== FILE INCLUSIONS =============================

define( 'WP_LIBRARY_DIR', dirname(__FILE__)/*.'/includes'*/ );
define( 'WP_LIBRARY_URL', plugins_url('', __FILE__) );

if( ! function_exists('wp_library_path')):
/**
 * Get the filepath to library file
 * @param $file the file to include; provide with extension
 */
function wp_library_path($file){
	return WP_LIBRARY_DIR.'/'.$file;
}//--	fn	wp_library_path
endif;	//func-exists

if( ! function_exists('wp_library_url')):
/**
 * Get the filepath to library file
 * @param $file the file to include; provide with extension
 */
function wp_library_url($file){
	return WP_LIBRARY_URL.'/'.$file;
}//--	fn	wp_library_url
endif;	//func-exists

if( ! function_exists('wp_library_include') ):
/**
 * Include given library file, optional subdirectory
 * NOT PART OF WP CORE
 *
 * @param $shortpath the file to include; provide with extension, optional subdirectory (no beginning slash)
 * @param $isRequired {false} include() or require(), as given
 * @param $isOnce {true} *_once() if specified
 */
function wp_library_include($shortpath, $isRequired = false, $isOnce = true){
	$path = wp_library_path($shortpath);
	if( WP_DEBUG && !file_exists($path) ) {
		throw new Exception( "Could not include/require file {{$shortpath}}, originally requested in " . abt_debug::calling_file() );
	}
	
	if( $isRequired ){
		if( $isOnce ){
			require_once($path);
		}
		else {
			require($path);
		}
	}// isRequired
	else {
		if( $isOnce ){
			include_once($path);
		}
		else {
			include($path);
		}
	}// not required
}//--	fn	wp_library_include
endif;	//func-exists

if( ! function_exists('wp_is_library')):
/**
 * Check if the given library (file) exists
 * @param $shortpath the file to include; provide with extension, optional subdirectory (no beginning slash)
 */
function wp_is_library($shortpath){
	return file_exists( wp_library_path($shortpath) );
}//--	fn	wp_is_library
endif;	//func-exists

// "mandatory" includes
wp_library_include('includes/common-functions.php');	//library file - Shared functions to be available to other files
wp_library_include('mvc/common_micromvc.php');	//more shared functions - mainly for micromvc/mvc files, but helpful in general: v() and kv()
wp_library_include('includes/wp_library_base.class.php');	//root class for library - inheritable common methods
#wp_library_include('includes/wp_pagination.class.php' );	// pagination helper
#wp_library_include('includes/wp_querybuilder.class.php' );	// complex-sql statement builder

/*
function wp_dev_library_autoloader($class){
	///TODO: figure out fancy folder structure
	$path = str_replace('__', '/', strtolower($class) ) . '.class.php';
	
	wp_library_include("includes/$path");
}
spl_autoload_register('wp_dev_library_autoloader');
*/


#endregion ========================== FILE INCLUSIONS =============================





#region ========================== ADMIN PAGE =============================


/**
 * Actual plugin class, to integrate with admin
 * For now more of an example
 */
class WP_Library_Plugin extends WP_Library_Base {
	/**
	 * Internal namespace
	 */
	private $N = 'wp_library_plugin';
	
	/**
	 * Bucket for settings pages
	 */
	private $settings;

	public function __construct(){
		
		// add the neato dependency checker
		// based on discussion here: http://core.trac.wordpress.org/ticket/11308
		register_activation_hook(__FILE__, array(__CLASS__, 'resolve_dependencies'));
		
		wp_library_include('includes/wp_options_page.class.php');	//options page helper

		//create settings pages
		$this->settings = array();
		$this->settings['main'] = new WP_Options_Page($this->N);
		
		//need to check for any one of these, since registering one should happen when we're on that specific page
		if( $this->settings['main']->register(
			'WP Library'
			, 'WP-Library'
			, array(
				'style'=>array('submenu', 'tools.php')
				, 'capabilities'=>'manage_options'
				)
			) ) {
			
			//add action to set up plugin settings
			$this->add_hook('admin_init');

		}// if settings registered

	}//--	fn	__construct
	
	
	
	public function admin_init(){
		$this->settings['main']
			#->add_scripts( array('ABT_Ecom', 'admin_scripts' ))
			->add_section('general', 'General', 'Global administration settings (this is just an example for now).')
				->add_field('general', 'admin_percentage', 'Admin Percentage (default)', array(
						'type'			=> 'text',
						'description'	=> 'How much of each purchase should be "diverted" as an admin fee?  <em>Note: this only applies for reporting</em>',
						'std'			=> '5',
						'suffix'		=> '%',
						'sanitize'		=> array('numeric', array('min'=>0), array('max'=>100))
					))
				->add_field('general', 'some_text', 'Some Text', array(
						'type'			=> 'p',
						'text'	=> 'arbitrary paragraph'
					))
			->add_section('tax_shipping', 'Tax and Shipping', 'Apply tax and/or shipping to items.')
				->add_field('tax_shipping', 'has_tax', 'Apply Tax', array(
						'type'			=> 'select',
						'description'	=> 'Can items be taxed?',
						'std'			=> true,
						'choices'		=> array(
							true			=> 'Yes',
							false			=> 'No'
							),
						'sanitize'		=> array('boolean')
					))
				->add_field('tax_shipping', 'tax_rate', 'Tax Rate', array(
						'type'			=> 'text',
						'description'	=> 'If items are taxed, what is their default tax rate (percent)?',
						'std'			=> '7.75',
						'suffix'		=> '%',
						'sanitize'		=> array('numeric', array('min'=>0), array('max'=>100))
					))
				->add_field('tax_shipping', 'has_shipping', 'Apply Shipping', array(
						'type'			=> 'select',
						'description'	=> 'Can items be shipped?  If so, they\'ll have a shipping cost and weight.',
						'std'			=> true,
						'choices'		=> array(
							true			=> 'Yes',
							false			=> 'No'
							),
						'sanitize'		=> array('boolean')
					))
				->add_field('tax_shipping', 'shipping_rate', 'Shipping Rate', array(
						'type'			=> 'text',
						'description'	=> 'If items are shipped, what is their default shipping cost (%)?',
						'std'			=> '5.00',
						'suffix'		=> '%',
						'sanitize'		=> array('numeric', array('min'=>0), array('max'=>100))
					))
					;
	}//--	fn	admin_init
	
	/**
	 * Helper function to load the plugin-dependencies plugin checker thingy
	 */
	public static function resolve_dependencies(){
		// check if the plugin is available
		if( !is_plugin_active('plugin-dependencies/plugin-dependencies.php') ) {
			// load manually
			activate_plugin(WP_PLUGIN_DIR . '/plugin-dependencies/plugin-dependencies.php', '', TRUE);
		}
	}//--	fn	resolve_dependencies
	
}///---	class	WP_Library_Plugin

//call it
new WP_Library_Plugin();

#endregion ========================== ADMIN PAGE =============================




/**
 * Provide hook for dependent plugins
 * @see http://core.trac.wordpress.org/ticket/11308#comment:7
 * @usage
 * function dependent_prefix_init() {
 *    // add all bootstrap stuff here
 *    // no code should run "live"
 * }
 * add_action( 'my_plugin_prefix_init', 'dependent_prefix_init' );
 */
function wp_dev_library_dependency_point(){
	do_action('has_wp_dev_library_dependency');
}
add_action( 'plugins_loaded', 'wp_dev_library_dependency_point' );