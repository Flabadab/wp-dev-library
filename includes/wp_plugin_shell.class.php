<?php
/*
 * 
Plugin Name: Generic Plugin Shell
Description: Part of Library suite - just provides an inherited class for making plugins
Author: JRS @ ABT
Version: 0.1
Author URI: http://atlanticbt.com
Changelog:
	0.1 - creating shell

*/

/* ------------- USAGE --------------

class My_Plugin extends WP_Plugin_Shell { ...

//declare to instantiate
new My_Plugin;

// declare new installer
require_once(ABT_ECOM_DIR.'/includes/plugin_installer.class.php');
new My_Plugin_Installer( My_Plugin::N, $this->calling_source, My_Plugin::pluginVersion );

---------------- /Usage ------------- */

abstract class WP_Plugin_Shell { 

	#region =============== CONSTANTS AND VARIABLE NAMES (redeclare in inheriting class) ===============
	
	/**
	 * Internal namespace - for options and stuff
	 */
	protected $N = 'My_Plugin';
	
	/**
	 * Version of current plugin -- match it to the comment
	 * @var string
	 */
	protected $pluginVersion = '0.1';
	
	/**
	 * Admin page title
	 */
	protected $adminTitle = 'My Plugin: Full Title';
	/**
	 * Short title for menu bar
	 */
	protected $shortTitle = 'My Plugin';
	
	/**
	 * Admin - role capability to view the options page
	 * @var string
	 */
	protected $adminOptionsCapability = 'manage_options';
	
	
	/**
	 * Wrapper for translate function, uses self as domain
	 * @param $message
	 */
	private function __($message){
		return __($message, $this->N);
	}//--	fn	__
	/**
	 * Wrapper for translate function, uses self as domain
	 * @param $message
	 */
	private function _e($message){
		_e($message, $this->N);
	}//--	fn	__
	
	/**
	 * Namespace an aspect, for option key, etc
	 * @param string $aspect
	 */
	protected function namespaced($aspect){
		return $this->N."_$aspect";
	}//--	fn	namespace
	
	
	/**
	 * Internal representation of Settings class
	 * @var obj
	 */
	protected $settings;
	
	#endregion =============== CONSTANTS AND VARIABLE NAMES (redeclare in inheriting class) ===============
	
	
	
	
	
	#region =============== CONSTRUCTOR and INIT (admin, regular) ===============
	
	/**
	 * Need reference to inheriting class file
	 * @var string
	 */
	private $calling_source, $calling_class;
	
	/**
	 * Need reference to calling source file to function properly
	 * @param $calling_source
	 */
	function __construct($calling_source, $calling_class = NULL) {
		$this->calling_source = $calling_source;
		if( NULL === $calling_class ){ $this->calling_class = basename($calling_source, '.class.php'); }
		
		add_action( 'admin_menu', array( &$this, '_admin_init' ), 9 );
		add_action( 'init', array( &$this, '_init' ), 9 );
		
		//pluggable admin notice
		add_action('admin_notices', array(__CLASS__, '_admin_notices'));
		
	}//--	fn	__construct


	/**
	 * Copy this to child?
	 */
	function _admin_init() {
		
		# perform your code here
		//add_action('admin_menu', array(&$this, 'config_page'));
		
		//add plugin entry settings link
		add_filter( 'plugin_row_meta', array(&$this, 'plugin_action_links'), 10, 2 );
		
		//only add stuff for plugin pages
		if( false !== strpos( get('page'), $this->N) )
			$this->add_admin_headers();
		
		
		//@hookpoint
		do_action($this->namespaced('admin_init'), $this->namespaced('config'));
		
	}//--	fn	admin_init
	

	/**
	 * General init
	 * Add scripts and styles
	 * but save the enqueue for when the shortcode actually called?
	 */
	function _init(){
		#wp_register_script('jquery-flip', plugins_url('jquery.flip.min.js', $this->calling_source), array('jquery'), self::$pluginVersion, true);
		#wp_register_style('sponsor-flip', plugins_url('styles.css', $this->calling_source), array(), self::$pluginVersion, 'all');
		#
		#if( !is_admin() ){
		#	/*
		#	add_action('wp_print_header_scripts', array(&$this, 'add_headers'), 1);
		#	add_action('wp_print_footer_scripts', array(&$this, 'add_footers'), 1);
		#	*/
		#	wp_enqueue_script('jquery-flip');
		#	wp_enqueue_script('sponsor-flip-init');
		#	wp_enqueue_style('sponsor-flip');
		#}
		
		if(!is_admin()){
			//@hookpoint
			do_action( $this->namespaced('init') );
		}
	
	}//--	fn	init
	
	#endregion =============== CONSTRUCTOR and INIT (admin, regular) ===============
	
	
	
	
	
	#region =============== HEADER/FOOTER -- scripts and styles ===============
	
	/**
	 * Add admin header stuff 
	 * @see http://codex.wordpress.org/Function_Reference/wp_enqueue_script#Load_scripts_only_on_plugin_pages
	 */
	function add_admin_headers(){
		
		#region ------------ scripts ----------------
		$itemsToAdd = array(
			$this->namespaced('admin') => array('source'=>'ui/plugin.admin.js', 'dependencies'=>array('jquery'), 'in_footer' => true)
		);
		
		//@hookpoint
		$itemsToAdd = apply_filters( $this->namespaced('admin_scripts'), $itemsToAdd );
		
		foreach($itemsToAdd as $handle => $stylesheet){
			wp_enqueue_script(
				$handle									//id
				, plugins_url($stylesheet['source'], $this->calling_source)	//file
				, $stylesheet['dependencies']			//dependencies
				, $this->pluginVersion					//version
				, v($stylesheet['in_footer'], true)				//in header or footer?
			);
		}
		#endregion ------------ scripts ----------------		
		
		#region ------------ styles ----------------
		$itemsToAdd = array(
			$this->namespaced('admin') => array('source'=>'ui/plugin.admin.css', 'dependencies'=>array(), 'media'=>'all')
		);
		
		//@hookpoint
		$itemsToAdd = apply_filters( $this->namespaced('admin_styles'), $itemsToAdd );
		
		foreach($itemsToAdd as $handle => $stylesheet){
			wp_enqueue_style(
				$handle									//id
				, plugins_url($stylesheet['source'], $this->calling_source)	//file
				, $stylesheet['dependencies']			//dependencies
				, $this->pluginVersion					//version
				, v($stylesheet['media'], 'all')					//media
			);
		}
		#endregion ------------ styles ----------------
		
	}//---	function add_admin_headers

	/**
	 * Only add scripts and stuff if shortcode found on page
	 * TODO: figure out how this works -- global $wpdb not correct
	 * @source http://shibashake.com/wordpress-theme/wp_enqueue_script-after-wp_head
	 * @source http://old.nabble.com/wp-_-enqueue-_-script%28%29-not-working-while-in-the-Loop-td26818198.html
	 */
	function add_headers() {
		
		//@hookpoint
		$stylesToAdd = apply_filters( $this->namespaced('add_styles'), array() );
		
		// Have to manually add to in_footer
		// Check if script is done, if not, then add to footer
		foreach($stylesToAdd as $style){
			if (!in_array($style, $wp_styles->done) && !in_array($style, $wp_styles->in_footer)) {
				$wp_styles->in_header[] = $style;
			}
		}
	}//--	function add_headers
	
	/**
	 * Only add scripts and stuff if shortcode found on page
	 * @see http://scribu.net/wordpress/optimal-script-loading.html
	 */
	function add_footers() {
		
		//@hookpoint
		$scriptsToAdd = apply_filters( $this->namespaced('add_footers'), array() );
		
		
		// Have to manually add to in_footer
		// Check if script is done, if not, then add to footer
		foreach($scriptsToAdd as $script){
			if (!in_array($script, $wp_scripts->done) && !in_array($script, $wp_scripts->in_footer)) {
				$wp_scripts->in_footer[] = $script;
			}
		}
	}
	
	#endregion =============== HEADER/FOOTER -- scripts and styles ===============
		
	#region =============== Administrative Settings ========
	
	/**
	 * Quick links on the plugin admin page for specific meta.
	 *
	 * copied from Prospress
	 */
	function plugin_action_links( $links, $file ) {
		$base = plugin_basename( $this->calling_source );
	
		if ( $file == $base ) {
			$url = $this->plugin_admin_url( array( 'page' => $this->namespaced('config') ) );
			$links[] = '<a title="Capability ' . $this->adminOptionsCapability . ' required" href="' . esc_attr( $url ) . '">'
			. esc_html( $this->__( 'Settings' ) ) . '</a>';
			$links[] = '<a href="http://atlanticbt.com/contact/">' . $this->__( 'Support' ) . '</a>';
		}
		
		//@hookpoint
		return apply_filters($this->namespaced('plugin_links'), $links);
	}
	
	/**
	 * Return the plugin settings
	 */
	static function get_settings(){
		return get_option( $this->namespaced('settings') );
	}//---	get_settings
	
	/**
	 * The submenu page
	 */
	function admin_page(){
		include_once( dirname($this->calling_source) . '/ui/plugin-ui.php' );
	}
	
	/**
	 * Copied from Contact Form 7, for adding the plugin link
	 * @param array $query
	 */
	function plugin_admin_url( $query = array() ) {
		global $plugin_page;
	
		if ( ! isset( $query['page'] ) )
			$query['page'] = $plugin_page;
	
		$path = 'admin.php';
	
		if ( $query = build_query( $query ) )
			$path .= '?' . $query;
	
		$url = admin_url( $path );
	
		return esc_url_raw( $url );
	}
	
	#endregion =============== Administrative Settings ========
	
	
	
	
	
	#region =================== ADMIN NOTICE ======================
	
	private static $admin_notices = array();
	
	/**
	 * Create admin notices
	 * @see http://wptheming.com/2011/08/admin-notices-in-wordpress/
	 */
	
	/**
	 * Admin notice hook - process saved notices
	 */
	public static function _admin_notices(){
		if( empty(self::$admin_notices) ) return;	//nothing to see here...
		
		//render each notice
		foreach(self::$admin_notices as $notice => $detail) :
			// check if we need to limit notices
			$show = true;
			
			// by role
			if( isset( $detail['capabilities'] )):
				foreach( (array)$detail['capabilities'] as $cap ){
					if( ! current_user_can( $cap ) ){ 
						$show = false;
						break;
					}
				}
			endif;	//capabilities
			
			// by page
			if( isset( $detail['pages']) ):
				global $pagenow;
				if( ! in_array($pagenow, (array)$detail['pages']) ) $show = false;
			endif;	//pages
			
			// dismissable
			if( isset( $detail['nag'] )):
				global $current_user;
				
				//quick dismiss check
				$nag = 'nag_ignore_'.$notice;
				
				if ( isset($_GET[$nag]) && '1' == $_GET[$nag] ) {
					add_user_meta($current_user->ID, $nag, 'true', true);
					$show = false;
				}
				elseif( get_user_meta($current_user->ID, $nag) ) $show = false;
				//otherwise, tack on the nag dismiss to the message
				else {
					if( empty( $_SERVER['QUERY_STRING'] ) ) { $url = '?'; }
					else { $url = '?' . $_SERVER['QUERY_STRING'] . '&'; }
					
					$detail['msg'] .= ' | <a href="/' . esc_attr( url() . $url . $nag . '=1') . '">' . __('Dismiss') . '</a>';
				}
				
			endif;	//nag
			
			if( $show ):
			?>
			<div class="notice <?php echo $detail['type']; if( isset($detail['class']) ) echo ' ', $detail['class']; ?>" id="notice-<?php echo $notice ?>">
				<p><?php echo $detail['msg']; ?></p>
			</div>
			<?php
			endif;	//show?
			
		endforeach;	//loop notices
		
		//clear notices once shown
		self::clear_notices();
		
	}//--	fn	_admin_notices
	
	public static function clear_notices(){
		self::$admin_notices = array();
	}//--	fn	clear_notices
	
	/**
	 * Add an admin notice
	 * @param string $key unique key (needed for nag notice checking)
	 * @param string $msg the message string - will appear within p tags
	 * @param string $type {default = 'info'} what kind of message: {warning, error, info = updated}
	 * @param array $options extra options like pages (array of specific pages), capabilities (array for current_user_can), nag (dismissible)
	 */
	public static function add_notice($key, $msg, $type = 'info', $options = array()){
		//merge type, also add wordpress "default" style
		$options['type'] = ( 'info' == $type ? 'info updated' : $type );
		
		$options['msg'] = __($msg);
		
		self::$admin_notices[$key] = $options;
	}//--	fn	add_notice
	
	#endregion =================== ADMIN NOTICE ======================

}//end class


	#region =================== ADMIN NOTICE ======================
	
	/**
	 * shorthand functions
	 */

	if( function_exists('add_admin_notice') ):
		throw new WP_PluginException('Function add_admin_notice already exists - please update all uses to conform to expected WP function!');
	else:
	/**
	 * Add an admin notice
	 * @param string $msg the message string - will appear within p tags
	 * @param string $type {default = 'info'} what kind of message: {warning, error, info = updated}
	 * @param array $options extra options like pages (array of specific pages), capabilities (array for current_user_can), nag (dismissible)
	 */
		function add_admin_notice($msg, $type = 'info', $options = array()){
			WP_Plugin_Shell::add_notice($msg, $type, $options);
		}
	endif;
	
	#endregion =================== ADMIN NOTICE ======================






wp_library_include('includes/customexception.class.php');
/**
 * Associated error class
 * @author jeremys
 *
 */
class WP_PluginException extends CustomException {
	/**
	 * Error Codes
	 * @var int
	 */
	const GENERAL = 0
		, ATTRIBUTE = 1
		;
	
		
	public function __construct($message = null, $code = 0)
	{
		if (!$message) {
			throw new $this('Unknown '. get_class($this));
		}
		
		switch($code){
			case self::GENERAL:
				$prefix = '[General] ';
				break;
			case self::ATTRIBUTE:
				$prefix = '[Attribute] ';
				break;
			default:
				break;
		}//	switch $code
		
		parent::__construct($prefix.$message, $code);
	}//--	fn	__construct
	
}///---	class	WP_QueryBuilderException

