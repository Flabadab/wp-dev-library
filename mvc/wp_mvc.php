<?php

//Is this an AJAX request? - allow bypass for .load requests
define('AJAX_REQUEST',( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])==='xmlhttprequest') && ( !isset($_REQUEST['ajax-bypass']) ));

/**
 * MVC-like Controller class, with autorouting etc
 * @author jeremys
 *
 * @package WP_Library
 * @since 0.1
 *
 */
class WP_Mvc_Controller {

	/**
	 * prefixes for action method routing
	 * @var string
	 */
	const PREFIX_ACTION = 'action_'
		, PREFIX_POST_ACTION = 'post_'
		, ACTION_KEY = 'action'
		, PARAMS_KEY = 'params'
		;

	/**
	 * What to render as the shell
	 * @var string
	 */
	public $template = 'layout', $template_source = NULL;

	/**
	 * Routing variables - used to extract stuff from querystring
	 * @var string
	 */
	protected $action_key, $params_key, $default_action = 'index';

	/**
	 * Set the default action name
	 * @param unknown_type $action_name
	 */
	protected function set_default($action_name){
		$this->default_action = $action_name;
	}//--	fn	set_default

	/**
	 * Set up default routing variables
	 * @param $template_source {optional} usually pass __FILE__; used as base directory for template file
	 * @param $action_key {optional} URL parameter to use for action routing
	 * @param $params_key {optional} URL parameter to use for action method params
	 */
	function __construct($template_source = NULL, $action_key = self::ACTION_KEY, $params_key = self::PARAMS_KEY){
		$this->action_key = $action_key;
		$this->params_key = $params_key;
		$this->template_source = $template_source;

		//check for ajax layout
		if( AJAX_REQUEST ){
			$this->template = 'layout-ajax';
		}

		/* TODO: figure out how micromvc does this
		//Override PHP's default error handling if in debug mode
		if(true === WP_DEBUG){set_error_handler(array('error','handler'));register_shutdown_function(array('error','fatal'));set_exception_handler(array('error','exception'));}
		*/
	}//--	fn	__construct


	/**
	 * Internal routing - based on URL/post, automagically process internal action method
	 */
	function _action(){
		//prefix action method depending on post or get
		if( isset( $_POST ) && !empty( $_POST ) ){
			$is_post = true;
		}
		else {
			$is_post = false;
		}
		$action = self::get_action($this->action_key);

		//use default if none given
		if( empty( $action ) ){
			$action = $this->default_action;
		}
		$this->action = $action;

		//also perform nonce check on post
		if( true === $is_post ){
			check_admin_referer($action, 'mvc_nonce');	//maybe wp_verify_nonce?
			//TODO: add links with wp_nonce_url( $actionurl, $action )

			// clean up POST values -- remove WP autoescaping (as of WP 3.0)
			$_POST = stripslashes_deep($_POST);

			//update action var with appropriate prefix for routing
			$action = self::PREFIX_POST_ACTION . $action;
		}
		else {
			//update action var with appropriate prefix for routing
			$action = self::PREFIX_ACTION . $action;
		}

		//only run action if it exists
		if( method_exists( $this, $action) ):
			/*
			//slightly more efficient call, depending on params length?
			switch( count( $params )):
				case 0:
					$this->$action();
					break;
				case 1:
					$this->$action($params[0]);
					break;
				case 2:
					$this->$action($params[0], $params[1]);
					break;
				case 3:
					$this->$action($params[0], $params[1], $params[2]);
					break;
				//fallback for 4+
				default:
				*/
					$params = self::get_param();
					call_user_func_array(array(&$this, $action), $params);
				/*
					break;
			endswitch;
			*/
		else:
			throw new WP_MvcException("Invalid action {{$action}}", WP_MvcException::ACTION_DNE );
		endif;
	}//--	fn	_action

	/**
	 * Admin page - caller for action routing; also include admin-specific stuff
	 */
	function admin_page(){
		if( ! is_admin() ) wp_die( new WP_MvcException('You\'ve reached this page in error', WP_MvcException::ADMINONLY));
		//run mvc stuff
		$this->_action();
		$this->render();
	}//--	fn	admin_page
	/**
	 * Public page - caller for action routing; include other non-admin checks?
	 */
	function page(){
		//run mvc stuff
		$this->_action();
		$this->render();
	}//--	fn	page

	/**
	 * Check through given capabilities/roles,
	 * @param array $capabilities list of roles or capabilities (used with current_user_can)
	 * @return true if user is allowed, false if not.
	 */
	protected function allow( array $capabilities ) {
		foreach($capabilities as $permission){
			if( ! current_user_can( trim($permission) ) ) return false;
		}
		return true;
	}//--	fn	allow


	/**
	 * Get the given MVC parameter from the url
	 * @param string $key {optional} if given, return value of specified parameter; if omitted, returns entire parameter list
	 * @param string $section {optional} if given, use this to get a second-level param (from a nested array)
	 * @return mixed value or array of all param values
	 */
	static function get_param($key = NULL, $section = NULL){
		//get params from request; UPDATE - wordpress already merges $_GET and $_POST into $_REQUEST
		$params = $_REQUEST;
		//originally, using custom parameter (SELF::PARAM_KEY):	$params = kv($_REQUEST,$query_field);

		//remove admin keys from list
		if( is_admin() ) {
			$remove_keys = array('post_type', 'page', 'message');
			foreach($remove_keys as $remove){
				if( isset($params[$remove]) ) unset($params[$remove]);
			}
		}

		//if none given, return null
		if( is_null($params) || empty($params) ) return array();

		/* no longer need to decode, as we're just using the regular POST/GET
		//decode from querystring, parse to array
		parse_str( urldecode($params) , $params);
		*/

		//return all params if so requested
		if( is_null($key) ) return $params;

		//return specific parameter
		return v($params[$key]);
	}//--	fn	get_param


	/**
	 * Get the given MVC action from the url
	 * @param string $query_field {optional, defaults to ACTION_KEY} what part of the querystring holds the action value
	 * @return mixed value or array of all param values
	 */
	static function get_action($query_field = self::ACTION_KEY){
		return kv($_REQUEST, $query_field);
	}//--	fn	param

	#region =============== RENDER ==================

	/**
	 * Add a (temporary) flash message, like error or success
	 * @param $msg
	 */
	public function addFlash($msg){
		if( ! isset( $this->flash ) ) $this->flash = $msg;
		else $this->flash .= $msg;
	}//--	fn	addFlash

	/**
	 * Render the final layout template
	 * @param $template_source {optional} if given, use it as the view "module" directory instead of the THEME/MVC/View folder
	 */
	public function render() {
		headers_sent()||header('Content-Type: text/html; charset=utf-8');$l=new WP_Mvc_View($this->template, $this->template_source);$l->set((array)$this);print$l;$l=0;if(WP_DEBUG)print new WP_Mvc_View('debug', __FILE__);
	}

	#endregion =============== RENDER ==================


}///---	class	WP_Mvc_Controller



/**
 * MVC-like View class
 * @author jeremys
 * @author http://github.com/tweetmvc/tweetmvc-app
 *
 * @package WP_Library
 * @since 0.1
 *
 */
class WP_Mvc_View {
	const EXT = '.tpl.php';
/**
 * Returns a new view object for the given view.
 *
 * @param string $f the view file to load
 * @param string $m the module name (i.e. the calling file) (blank for current theme)
 */
public function __construct($filename, $module = NULL) {
	$this->__f=( $module ? dirname($module) : get_stylesheet_directory().'/mvc/view' ) . "/$filename" . self::EXT;
}

/**
 * Set an array of values
 *
 * @param array $a array of values
 */
public function set($a) {
	foreach($a as$k=>$v){
		$k = trim($k);	//remove any null-char artefacts from private-method declarations in older versions of PHP
		$this->$k=$v;
	}
}

/**
 * Return the view's HTML
 * @return string
 */
public function __toString() {
	ob_start();extract((array)$this);require$__f;return ob_get_clean();
}

/**
 * Build an admin url for the given action + params
 * @param string $controller {optional} give as "TRUE" to use current page
 * @param string $action {optional} an action to use, otherwise will use default
 * @param array $params {optional} query params to send to action
 * @param bool $use_escape {optional, default true} whether or not to escape the attribute
 */
public function admin_url($controller = true, $action, $params = NULL, $use_escape = true){
	$url = array(
		'page' => (true === $controller ? $_GET['page'] : $controller)
		, 'action' => $action
	);
	if( NULL !== $params ){
		$url = wp_parse_args($url, $params);	//merge?
	}

	$url = '/' . url() . '?' . http_build_query($url);

	return ($use_escape ? esc_attr( $url ) : $url);
}//--	fn	admin_url
}///---	class	WP_Mvc_View




/**
 * Form
 *
 * Creates HTML forms from arrays describing the available fields . Format:
 *
 * $fields = array(
 *	'all_inputs' => array(
 *		'value' => '',
 *		'label' => '',
 *		'type' => '',
 *		'div' => array(),
 *		'attributes' => array()
 *	),
 *	'input_type' => array('type' => 'text'),
 *	'select_type' => array('type' => 'select', 'options' => $options);

 *	'textarea_type' => array('type' => 'textarea'),
 * );

 *
 * @package		MicroMVC
 * @author		David Pennington
 * @copyright	(c) 2011 MicroMVC Framework
 * @license		http://micromvc.com/license
 ********************************** 80 Columns *********************************
 */
class WP_Mvc_Form extends WP_Mvc_View {

	/**
	 * Create an HTML form containing the given fields
	 *
	 * @param object $validation object
	 * @param array $attributes
	 * @param string $view
	 * @return string
	 */
	public function __construct($validation = NULL, array $attributes = NULL, $view = 'form', $source = NULL)
	{
		require_once('html.micromvc.php');	//need html renderer
		require_once('validation.micromvc.php');	//need form validator renderer
		parent::__construct($view, ($source ? $source : __FILE__));
		$this->set(array('attributes' => $attributes, 'validation' => $validation));
	}


	/**
	 * Set the form fields
	 *
	 * @param array $fields list of fields
	 * @param mixde $section {optional, default} which section to add to; if given as TRUE, treat $fields as nested array given in sections
	 */
	public function fields(array $fields, $section='default')
	{
		//if given as nested sections, recurse through each section
		if( TRUE === $section ){
			foreach($fields as $section_id => $section){
				$this->fields($section, $section_id);
			}
			return false;
		}

		//otherwise, loop each field
		foreach($fields as $field => &$options)
		{
			$defaults = array(
				'label' => ucwords($field),
				'value'=> $this->validation->value($field, $section),
				'type' => 'text',
				#'attributes' => array('id' => $field, 'name' => $field),
				'container' => array('class'=>'field')
			);

			//merge properties
			$options = $options + $defaults;
			//re-merge default attributes
			$post_val = ($options['type'] == 'submit') ? $options['value'] : $this->validation->value($field, $section);
			$options['value'] = v($post_val, $options['value']);
			#$options['value'] = v($defaults['value'], $options['value']);

			if( ! isset( $options['attributes'] ) ){ $options['attributes'] = array(); }
			$options['attributes'] = array('id' => $section . '-' . $field, 'name' => $section.'['.$field.']') + $options['attributes'];

			//special RENDERING type for select, multiselect, textarea, radio, checkbox
			if( ! in_array( $options['type'], array('select', 'textarea', 'radio', 'checkbox', 'paragraph') ) ) {
				$options['attributes']['type'] = $options['type'];
				$options['type']='input';
			}
			if( in_array('multiple', $options['attributes'])){
				$options['attributes']['name'] = $section.'['.$field.'][]';
			}


			//default container stuff
			if( ! isset($options['container']) ) {
				$options['container'] = array('class'=>'field');
			}
			if( ! isset( $options['container']['class'] )){
				$options['container']['class'] = 'field';
			}
			//add validation as class or HTML5 attribute
			if( isset($options['validation'])) {
				$options['container']['class'] .= ' ' . esc_attr(str_replace('|', ' ', $options['validation']));
			}

			// tweak checkbox/radio container

			//add required attribute from validation, for *
			if( false !== strpos( v($options['validation']), 'required' ) ){
				#$options['div']['class'] .= ' required';
				$options['label'] .= '<em> * </em>';
			}

		}// foreach $fields

		if(!isset($this->sections[$section])){
				$this->add_section($section);
		}

		$this->fields[$section] = $fields;
	}

	/**
	 * Update the field values, given a list of key/value pairs
	 * @param array $data the list of values for each section; if $section arg is TRUE, treat $data as a nested list and process recursively
	 * @param string $section the section id for the nested field grouping
	 */
	public function setFieldValues(array $data = array(), $section = 'default'){
		//if given as nested sections, recurse through each section
		if( TRUE === $section ){
			foreach($data as $section_id => $section){
				$this->setFieldValues($section, $section_id);
			}
			return false;
		}

		//loop through provided values to update internal fields
		foreach($data as $field_id => $value){
			if( ! isset( $this->fields[$section] ) || ! isset( $this->fields[$section][$field_id] ) ) {
				continue;
				//throw new WP_MvcException("Cannot set value for requested field {$field_id} in section {$section} - does not exist in form", WP_MvcException::FORM_NO_FIELD);
			}
			$this->fields[$section][$field_id]['value'] = $value;
		}// foreach $data

	}//--	fn	setFieldValues

	function add_section($section_id, $att=array())
	{
		$this->sections[$section_id] = $att;
	}

	/**
	 * Return the current HTML form as a string
	 */
	public function __toString()
	{
		if( ! $this->attributes)
		{
			$this->attributes = array();
		}
		return html::tag('form', parent::__toString(), $this->attributes + array('method' => 'post'));
	}

}///---	class	WP_Mvc_Form



/**
 * Database relational mapper
 * @author jeremys
 *
 * @package WP_Library
 * @since 0.1
 */
class WP_Mvc_Model {
	const EXT = '.mdl.php';
	/**
	 * Returns a new model object for the given model. (override to NOT USE "simple" model file, for more complicated construction)
	 *
	 * @param string $filename the model file to load
	 * @param string $module the module name (blank for current theme)
	 * @param $query_builder {optional, reference} query builder object to use to create query strings; if omitted will use static instance of WP_QueryBuilder
	 */
	public function __construct($filename, $module = NULL, &$query_builder = NULL) {
		//load model file
		$this->__f=( $module ? dirname($module) : get_stylesheet_directory().'/mvc/model' ) . "/$filename" . self::EXT;

		//load model file to set up variables
		require($this->__f);

		/* set up default internal representations, if expected */
		// if not explicitly declaring this a "composite", default to filename
		if( ! isset( $this->_table ) ) {
			$this->_table = strtolower( basename($this->__f, self::EXT) );
		}

		//finish up creating
		$this->init($query_builder);

		/**
		pbug(__CLASS__. ' constructed:'
			, "filename: $filename"
			, "module: $module"
			, $query_builder
			, self::$qb
			, (array)$this
			);
		/**/

	}

	/**
	 * Common initialization code, when not loading from simple model file
	 * @param $query_builder {optional, reference} query builder object to use to create query strings; if omitted will use static instance of WP_QueryBuilder
	 */
	protected function init(&$query_builder = NULL){
		// start up querybuilder interface
		// use default class if not otherwise specified
		if( NULL === $query_builder ){
			//only make a new one if it's not already there; saves memory?
			if( ! isset( self::$qb ) ){
				self::$qb = new WP_QueryBuilder();
			}
		}
		// otherwise, use given instance reference class (dependency injection?)
		else {
			self::$qb = $query_builder;
		}
	}

	/**
	 * Load a model file, return an instance of that class
	 * @param unknown_type $model
	 * @param unknown_type $source
	 */
	public static function load($model, $source){
		$file = ( $source ? dirname($source) : get_stylesheet_directory().'/mvc/model' ) . "/$model" . self::EXT;

		//load it if not ready
		require_once($file);
		return new $model();
	}//--	fn	load

	/**
	 * Table this model corresponds to
	 * @var string
	 */
	protected $_table = NULL;
	/**
	 * Returns whether or not this is a composite model, or 1:1 corresponds to a database table
	 * (basically, if a table has not been set, assumes that this is a composite)
	 */
	private function isComposite(){
		return ( false === $this->_table );
	}//--	fn	isComposite

	/**
	 * Column name of primary key
	 * @var unknown_type
	 */
	private $_pk = 'GUID';
	/**
	 * Get the primary key value
	 */
	public function pk(){
		return v($this->_values[ $this->_pk ]);
	}

	private $_values = array();

	/**
	 * Get model as array
	 */
	public function __toArray(){
		return (array) $this->_values;
	}

	/**
	 * Last result run
	 * @var unknown_type
	 */
	private $_last_result = NULL;

	/**
	 * Set an array of values (automagic???)
	 *
	 * @param array $a array of values
	 */
	public function set($a) {
		foreach((array)$a as $k=>$v){
			$k = trim($k);	//remove any null-char artefacts from private-method declarations in older versions of PHP
			$this->_values[$k]=$v;
		}
	}//--	fn	set
	/**
	 * Same as set(), but clear values first
	 * @param array $a array of values
	 */
	public function reset($a){
		unset($this->_values);
		$this->_values = array();
		if( !empty( $a ))
			$this->set($a);
	}//--	fn	reset

	/**
	 * Get "property" $k
	 * @param string $k
	 */
	public function __get($k){
		return $this->_values[$k];
	}//--	fn	__get


	/**
	 * SET "property" $k
	 * @param string $k
	 *
	public function __set($k, $value){
		$this->_values[$k] = $value;
	}//--	fn	__get
	*/

	#region ------------------- DB-Interaction wrappers --------------------

	public static $qb;

	/**
	 * Internal consistency checks before running a query
	 */
	protected function _pre_query(){
		self::$qb->clear();
		if( NULL === $this->_table || !isset( $this->_pk ) || empty($this->_pk) ){
			throw new WP_MvcException('Table or PK must be declared', WP_MvcException::MODEL_NO_QUERY);
		}
	}//--	fn	_pre_query

	/**
	 * Internal consistency checks after running a query
	 */
	protected function _post_query(){
		return;

		$args = func_get_args();
		global $wpdb;
		pbug('--- POST QUERY args // ---', $args );
		pbug('--- POST QUERY etc // ---', 'insert_id '. $wpdb->insert_id, 'num_rows '.$wpdb->num_rows, isset( self::$qb ) ? '<b>QUERY = </b>' . self::$qb->query() : ''/*, $wpdb->print_error()*/ );
		pbug('--- // POST QUERY, model ---', $this );
	}//--	fn	_pre_query

	/**
	 * Reuseable filter clause - does everything but the select and query parts
	 * @param array $filter the filter clause declarations - given as "clause placeholders", value1, value2, ...
	 * @param mixde $orderby sorting - given as array(column, direction) or "column direction"; cheat for multiple by putting string list in first array param
	 */
	protected function _filter($filter, $orderby = NULL){
		self::$qb	//->select('*')
			->from($this->_table)
			;

		//no filter clause?
		if( ! empty($filter) ){
			self::$qb->where($filter);
		}

		if( is_array( $orderby )) {
			foreach($orderby as $o_clause){
				self::$qb->orderby($o_clause[0], $o_clause[1]);
			}
		}
		elseif( !empty($orderby) ) {
			self::$qb->orderby($orderby);
		}
	}//--	fn	_filter

	/**
	 * Actually do the query
	 * @param string $style how to run it and what to get back; {array = get_results, single = get_row, var = get_var}
	 */
	protected function _do_query($style = 'array'){
		global $wpdb;

		//retrieve result and set internally
		switch($style) {
			case 'array':
				$results = $wpdb->get_results( self::$qb->render()->query(), ARRAY_A );
				break;
			case 'single':
				$results = $wpdb->get_row( self::$qb->render()->query(), ARRAY_A );
				break;
			case 'var':
				$results = $wpdb->get_var( self::$qb->render()->query() );
				break;
			case 'col':
				$results = $wpdb->get_col( self::$qb->render()->query() );
				break;
			// direct query - return results accordinly
			case 'query':
				return $wpdb->query( self::$qb->query() );
				break;
		}// switch $style

		if( !empty( $results )) $this->set( $results );

		$this->_post_query($results);
		return $results;
	}//--	fn	_do_query

	/**
	 * Retrieve model data based on primary key; assumes numeric primary key
	 * @param mixed $pk primary key value (single)
	 */
	public function fetch($pk){
		$this->_pre_query();

		self::$qb->select('*');

		$this->_filter(array($this->_pk . ' = %d', $pk));

		//retrieve result and set internally
		$results = $this->_do_query('single');
		return $results;
	}//--	fn	fetch

	/**
	 * Filter and return
	 * @param array $filter an array of the form ( $clause, $param1, $param2, ... ), where $clause can be the query mask or an array specifying the mask and 'join' and 'sanitize' methods, like array(clause, 'join'=>'AND', 'sanitize'=>true|pattern)
	 * @param array $orderby (sort_column, direction)
	 */
	public function fetch_where($filter, $orderby = null){
		$this->_pre_query();

		self::$qb->select('*');

		$this->_filter($filter, $orderby);

		//retrieve result and set internally
		$results = $this->_do_query();
		return $results;
	}//--	fn	fetch_where

	public function count($filter, $count_for = 'GUID'){
		$this->_pre_query();

		self::$qb->select("COUNT($count_for)");

		$this->_filter($filter);


		//retrieve result and set internally
		$results = $this->_do_query('var');

		return $results;
	}

	/**
	 * Save data to database
	 * @param array $data
	 * @return result of query
	 */
	public function save( $data = array() ){
		$this->_pre_query();

		global $wpdb;

		$params = $this->_values;//get_class_vars( get_class($this) );//(array)$this;
		$params = wp_parse_args($data, $params); // array_merge($data, $params );

		//check if we're inserting or updating (look for presence of primary key
		if( isset( $params[ $this->_pk ] ) && $params[ $this->_pk ] ){
			//remove pk from submit
			$pk = $params[ $this->_pk ];
			unset( $params[ $this->_pk ] );
			$isInsert = false;
		}
		else {
			$isInsert = true;
		}

		//get "sanitizing" placeholders
		foreach( $params as $key => $value ){
			if( is_numeric($value) ){
				$placeholder = '%d';
			}
			else {
				$placeholder = '%s';
			}

			$placeholders []= $placeholder;
		}

		//check if we're inserting or updating (look for presence of primary key
		if( $isInsert ){
			$this->_last_result = $wpdb->insert($this->_table, $params, $placeholders);
		}
		else {
			$this->_last_result = $wpdb->update($this->_table, $params, array( $this->_pk => $pk), $placeholders, (is_numeric($pk) ? '%d' : '%s'));
		}

		$this->_post_query($this->_last_result);

		//first, make sure no errors returned by query, then
		//if inserted, check that insertion was performed; otherwise check that the number of rows updated are greater than 0?
		///TODO: is last_result false? then error; assume if rows affected > 0 then success?
		// Altered result check `: 0 < $wpdb->num_rows` to `: 0 < $wpdb->rows_affected` to fix false negatives - AD
		$result = (0 < $this->_last_result) && ( $isInsert ? 0 !== $wpdb->insert_id : 0 < $wpdb->rows_affected );

		//update internal values with submission if successful
		if( $result ){
			$this->set($params);
			//don't forget the new ID
			if( $isInsert ){
				$this->_values[ $this->_pk ] = $wpdb->insert_id;
			}
		}

		return $result;
	}//--	fn	save

	/**
	 * Delete data from database
	 * @param array $data
	 * @return result of query
	 */
	public function delete( $data = array() ){
	    $this->_pre_query();

	    global $wpdb;
	    $wpdb->show_errors();

	    $params = $this->_values;//get_class_vars( get_class($this) );//(array)$this;
	    $params = wp_parse_args($data, $params); // array_merge($data, $params );

	    //get "sanitizing" placeholders
	    foreach( $params as $key => $value ){
	        if( is_numeric($value) ){
	            $placeholder = '%d';
	        }
	        else {
	            $placeholder = '%s';
	        }

	        $placeholders []= $placeholder;
	    }

	    $formats = $format = (array) $placeholders;
	    $fields = array_keys( $params );
	    $formatted_fields = array();
	    foreach ( $fields as $field ) {
	        if ( !empty( $format ) )
	            $form = ( $form = array_shift( $formats ) ) ? $form : $format[0];
	        elseif ( isset( $this->field_types[$field] ) )
	        $form = $this->field_types[$field];
	        else
	            $form = '%s';
	        $formatted_fields[] = $form;
	    }
	    $sql = "DELETE FROM `$this->_table` WHERE ";
	    foreach($formatted_fields as $key => $value){
	        $sql .= ($key == 0) ? "`$fields[$key]`=$value" : " AND `$fields[$key]`=$value";
	    }
	    $prep_query = $wpdb->prepare( $sql, $params );

	    $this->_last_result = $wpdb->query( $prep_query );
	    $wpdb->print_error();

	    $this->_post_query($this->_last_result);

	    //first, make sure no errors returned by query, then
	    //if inserted, check that insertion was performed; otherwise check that the number of rows updated are greater than 0?
	    ///TODO: is last_result false? then error; assume if rows affected > 0 then success?
	    // Altered result check `: 0 < $wpdb->num_rows` to `: 0 < $wpdb->rows_affected` to fix false negatives - AD
	    $result = ( 0 < $wpdb->rows_affected );

	    //update internal values with submission if successful
	    if( $result ){
	        $this->set($params);
	    }

	    return $result;
	}//--	fn	delete

	#endregion ------------------- DB-Interaction wrappers --------------------

}///---	class	WP_Mvc_Model





wp_library_include('customexception.class.php');
/**
 * Associated error class
 * @author jeremys
 *
 * @package WP_Library
 * @since 0.1
 *
 */
class WP_MvcException extends CustomException {
	/**
	 * Error Codes
	 * @var int
	 */
	const GENERAL = 0
		,
		/**
		 * Controller action does not exist
		 */
		ACTION_DNE = 1
		,
		/**
		 * Action should only be performed from the admin pages
		 */
		ADMINONLY = 2
		,
		/**
		 * Model file/instance does not exist
		 */
		MODEL_DNE = 3
		,
		/**
		 * Model file/instance not fully set up, cannot run a query
		 */
		MODEL_NO_QUERY = 4
		,
		/**
		 * Given form does not contain indicated field
		 */
		FORM_NO_FIELD = 5
		;


	public function __construct($message = null, $code = 0) {
		if (!$message) {
			throw new $this('Unknown '. get_class($this));
		}

		$prefix = "[$code] ";
		/*
		switch($code){
			case self::GENERAL:
				$prefix = '[General] ';
				break;
			case self::ACTIONDOESNOTEXIST:
				$prefix = '[Action DNE] ';
				break;
			default:
				break;
		}//	switch $code
		*/

		parent::__construct($prefix.$message, $code);
	}//--	fn	__construct

}///---	class	WP_MvcException

?>