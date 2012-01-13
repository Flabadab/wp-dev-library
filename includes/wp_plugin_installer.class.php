<?php

/**
 * Helper class to install/delete/activate/upgrade a plugin
 * Basically a wrapper for installation etc hooks
 * @author jeremys
 * 
 * @see http://codex.wordpress.org/Creating_Tables_with_Plugins
 *
 */

#wp_library_include('Singleton.class.php', true, true);

abstract class WP_Plugin_Installer /*extends Singleton*/ {
	
	/**
	 * Action switches for hook names
	 * @var int
	 */
	const ACTIVATE = '_activate', DEACTIVATE = '_deactivate', INSTALL = '_install', UNINSTALL = '_uninstall', UPDATE = '_update';
	
	/**
	 * Internal, unique identifier, for hooks
	 * @var string
	 */
	protected $guid;
	
	/**
	 * Plugin source path
	 * @var string
	 */
	protected $source;
	
	/**
	 * Plugin version - for update checking
	 * @var string like "1.2.3"
	 */
	protected $version = null;
	
	
	
	
	
	/**
	 * Register a plugin for the install, etc; set hooks
	 * @param $guid a unique identifier for this instance - used to keep track of hooks
	 * @param $source the file path for (de)activation hooks; typically __FILE__
	 */
	public function __construct(&$caller, $guid, $source, $version = "1.0") {
		$this->guid = $guid;
		$this->source = $source;
		$this->version = $version;
		
		/* housecleaning - set up "secret" hooks */
		register_activation_hook( $source, array(&$caller, 'activate') );		//do stuff on activation
		register_deactivation_hook( $source, array(&$caller, 'deactivate') );	//do stuff on deactivation
		register_uninstall_hook( $source, array(get_class($caller), 'uninstall') );		//do stuff on uninstall
		$this->add_action( self::UPDATE, array(&$caller, 'update') );		//update stuff when activated (internal hook)
		
		$this->db_is_installed = get_option( $this->namespaced('db_version') );
	}//--	fn	register
	
	#region =============== (DE)ACTIVATION / (UN)INSTALL ===============
	
	/**
	 * Turn on things, version check, etc
	 */
	protected function activate(){
		//custom change title, otherwise use guid
		$title = apply_filters($this->namespaced('title'), $this->guid);
		
		$this->update();	//call here, will perform version checks within method
		
		//@hookpoint
		do_action( $this->namespaced(self::ACTIVATE), $title );
		
		### _log( __CLASS__. '::'. __FUNCTION__ );
	}//--	fn	_activate

	/**
	 * Housekeeping on disable - turn off things, etc
	 */
	protected function deactivate(){
		//@hookpoint
		do_action( $this->namespaced(self::DEACTIVATE) );
		
		### _log( __CLASS__. '::'. __FUNCTION__ );
	}
	
	/**
	 * During install
	 */
	protected function install(){
		//@hookpoint
		do_action( $this->namespaced(self::INSTALL) );

		### _log( __CLASS__. '::'. __FUNCTION__ );
	}
	/**
	 * Remove settings and clean up database, files
	 * NOTE: this will provide GLOBAL hook for anything using this abstract class,
	 * may redeclare without inheriting in child class
	 */
	protected static function uninstall(){
		//@hookpoint
		do_action( __CLASS__.self::UNINSTALL ); // delete plugin data created upon uninstallation, never delete user generated data
		
		//other calls within uninstall.php
		### _log( __CLASS__. '::'. __FUNCTION__ );
	}//--	fn	uninstall

	/**
	 * Clean up database meta deprecated between versions.
	 *
	 * @since 0.1
	 */
	protected function update() {
		//@hookpoint
		do_action( $this->namespaced(self::UPDATE) );
		
		### _log( __CLASS__. '::'. __FUNCTION__ );
	}
	
	#endregion =============== (DE)ACTIVATION / (UN)INSTALL ===============
	
	
	
	
	
	#region =============== CRAZY SQL INTERACTION ===============
	
	/**
	 * Limit query calls to internal methods previously given the OK
	 * @var bool
	 */
	private static $can_query = false;
	
	/**
	 * Limit query calls to internal methods previously given the OK - uses this variable to compare file names
	 * @var string
	 */
	protected static $WP_PLUGIN_SHELL_QUERY;
	
	/**
	 * Load the given sql file, perform appropriate replacements, and execute "hardcore"
	 * @param string $file absolute path to file
	 * @param array $replacements replacements for fn 'replaceholders'
	 */
	static function execute_sql_file($file, $replacements){
		if( ! isset(self::$WP_PLUGIN_SHELL_QUERY) || self::$WP_PLUGIN_SHELL_QUERY !== $file ):
			wp_die('You may not use this function by itself, or it was called in the wrong place.');
		endif;
		
		self::$can_query = true;	//enable
		
		//get sql statements
		$sql = file_get_contents( $file );	///TODO: is this a security risk, exposing it? mitigating by changing extension from .sql and "hiding" with .htaccess
		//fix placeholders
		$sql = replaceholders($sql, $replacements);
		
		$result = self::hardcore_query($sql);
		if( ! $result ) wp_die('Error executing plugin query using installer shell.');
		
		self::$can_query = false;	//disable
		
	}
	
	/**
	 * Run some hardcore sql stuff, like changing the database or creating tables, using the upgrade file?
	 * @param string $sql
	 */
	protected static function hardcore_query($sql){
		//authorization check! because this is static call!
		if( ! self::$can_query ):
		//originally using uninstall check, but this is run on install too...
		//	if( ! defined('ABSPATH') && ! defined('WP_UNINSTALL_PLUGIN') ):
			wp_die('You may not use this function by itself.');
		endif;
		
		//dbDelta doesn't work for drop -- see source, and http://wordpress.org/support/topic/drop-plugin-table-dbdelta-no-good
		global $wpdb;
		
		//and, because ->query can probably only run one thing at a time (?), split on arbitrary breaks
		$queries = explode('/* <break> */', $sql);
		
		$results = true;
		foreach($queries as $i => $query){
			$query = trim($query);
			if( empty($query) ) continue;	//skip accidental empty stuff
			
			$results = ($results && (0 === $wpdb->query($query)));
			if(!$results){ 
				ob_start();
				$wpdb->print_error();
				$error = ob_get_clean();
				_log( "Plugin installation error on query #{$i}: ", $error );
				wp_die("Error during (un)installation of plugin: could not run included query #{$i}");
			}
			
			//delay table creation, to allow dependencies to resolve...
			if( false === sleep ( 2 ) ):
				wp_die("Error during (un)installation of plugin [{$this->guid}]: could not delay query #{$i} to resolve dependencies");
			endif;
		}
		return $results;
		///TODO: how to print errors IF THEY HAPPENED??? $wpdb->print_error();
	}//--	fn	hardcore_query
	
	#endregion =============== CRAZY SQL INTERACTION ===============
	
	
	
	
	
	#region ===================== Inheriting Interaction ==========================
	
	/**
	 * Turn given aspect into a namespaced "key" -- i.e. guid . _ . $aspect
	 * @param $aspect
	 */
	protected function namespaced($aspect){
		return "{$this->guid}_$aspect"; 
	}//--	fn	key
	
	/**
	 * Return properly formatted table name, for insert/create/etc
	 * @param string $table the "short" table name
	 */
	protected static function table_name($table){
		global $wpdb;
		return $wpdb->prefix . $table;
	}//--	fn	table_name
	
	/**
	 * Flag - have we installed tables
	 * @var bool
	 */
	protected $db_is_installed = false;
	
	/**
	 * Hook to existing action
	 * @param string $action which action; use SELF::ACTIONS
	 * @param array $function the child-class (&$this, 'FUNCTION')
	 */
	protected function add_action($action, $function){
		add_action($this->namespaced($action), $function);
	}
	
	#endregion ===================== Inheriting Interaction ==========================
	
	
}///---	class	WP_Plugin_Installer


