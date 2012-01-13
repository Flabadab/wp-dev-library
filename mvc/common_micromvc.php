<?php
/*
 * Helper functions "borrowed" from MicroMVC
 * @author Xeoncross
 * @mod jeremys (zaus)
 * @source https://github.com/Xeoncross/micromvc/blob/master/bootstrap.php
 */


#region =========================== micromvc helper functions ==============================

if( !function_exists('h')):
/**
 * Convert special characters to HTML safe entities.
 *
 * @param string $str the string to encode
 * @return string
 */
function h($data)
{
	return htmlspecialchars($data,ENT_QUOTES,'utf-8');
}
endif;	//function_exists h



if( !function_exists('v')):
/**
 * Get a value "safely" (i.e. check isset), otherwise return a default
 * BE CAREFUL - THIS WILL ACTUALLY MODIFY VALUE IF DNE, SO DO NOT USE ON GLOBALS
 * @param $value the value object
 * @param $default a default value
 * 
 * @return $value if set
 */
function v(&$value, $default = NULL){
	### pbug(__FUNCTION__, 'called');
	if( isset($value) ) return $value;
	
	return $default;
}//--	fn	v
endif;

if( !function_exists('kv')):
/**
 * Get a nested value "safely" (i.e. check isset recursively), otherwise return NULL
 * @param $value the value object
 * @param $default a default value
 */
function kv($value, $key1 = NULL) {
	$args = func_get_args();
	//remove $value from args
	array_shift($args);
	
	foreach($args as $key){
		if( isset($value[$key]) ) {
			$value = $value[$key];
		}
		else {
			return NULL;
		}
	}
	
	return $value;
}//--	fn	kv
endif;	//function_exists v



if( !function_exists('str')):
/**
 * Type cast the given variable into a string - on fail return default.
 *
 * @param mixed $string the value to convert
 * @param string $default the default value to assign
 * @return string
 */
function str($str, $default = '')
{
	return(is_scalar($str)?(string)$str:$default);
}
endif;	//function_exists str



if( !function_exists('post')):
/**
 * Safely fetch a $_POST value, defaulting to the value provided if the key is
 * not found.  Also - as of WP 3.0, fixes stupid escaping issues
 *
 * @param string $k the key name
 * @param mixed $d the default value if key is not found
 * @param boolean $s true to require string type
 * @return mixed
 */
function post($k, $d = NULL, $s = FALSE)
{
	if(isset($_POST[$k]))
		return $s ? 
			str( stripslashes_deep($_POST[$k]) ,$d)
			:
			stripslashes_deep($_POST[$k]);
			
	return $d;
}
endif;	//function_exists post



if( !function_exists('get')):
/**
 * Safely fetch a $_GET value, defaulting to the value provided if the key is
 * not found.  Also - as of WP 3.0, fixes stupid escaping issues
 *
 * @param string $k the key name
 * @param mixed $d the default value if key is not found
 * @param boolean $s true to require string type
 * @return mixed
 */
function get($k, $d = NULL, $s = FALSE)
{
	if(isset($_GET[$k]))
		return $s ?
			str( stripslashes_deep($_GET[$k]), $d)
			:
			stripslashes_deep( $_GET[$k] );
	return $d;
}
endif;	//function_exists get



if( !function_exists('request')):
/**
 * Safely fetch a $_GET or $_POST value, defaulting to the value provided if the key is
 * not found.  Also - as of WP 3.0, fixes stupid escaping issues
 *
 * @param string $k the key name
 * @param mixed $d the default value if key is not found
 * @param boolean $s true to require string type
 * @return mixed
 */
function request($k, $d = NULL, $s = FALSE)
{
	if(isset($_REQUEST[$k]))
		return $s ?
			str( stripslashes_deep($_REQUEST[$k]), $d)
			:
			stripslashes_deep( $_REQUEST[$k] );
	return $d;
}
endif;	//function_exists get



if( !function_exists('server')):
/**
 * Safely fetch a $_SERVER value, defaulting to the value provided if the key is
 * not found.
 *
 * @param string $k the key name
 * @param mixed $d the default value if key is not found
 * @return mixed
 */
function server($k, $d = NULL)
{
	return isset($_SERVER[$k])?$_SERVER[$k]:$d;
}
endif;	//function_exists server



if( !function_exists('url')):
/**
 * Returns the current URL path string (if valid)
 * PHP before 5.3.3 throws E_WARNING for bad uri in parse_url()
 *
 * @param int $k the key of URL segment to return
 * @param mixed $d the default if the segment isn't found
 * @return string
 */
function url($k = NULL, $d = NULL)
{
	static$s;if(!$s){foreach(array('REQUEST_URI','PATH_INFO','ORIG_PATH_INFO')as$v){preg_match('/^\/[\w\-~\/\.+%]{1,600}/',server($v),$p);if(!empty($p)){$s=explode('/',trim($p[0],'/'));break;}}}if($s)return($k!==NULL?(isset($s[$k])?$s[$k]:$d):implode('/',$s));
}
endif;	//function_exists url

/**
 * Helper function used by a bunch of the mvc files to "autoload" a path; needed to fit micromvc functions within WP filesystem
 * @param $ext {required} specify filename extension (like '.php' or '.lbl.php', etc
 * @param $filename the short name of the file to include
 * @param $module {optional} pass __FILE__ to get a file relative to the current "module" (i.e. in the same directory); add subdirectory slashes to filename to search lower
 */
function mvc_filepath($ext, $filename, $module = NULL){
	return ( $module ? dirname($module) : WP_LIBRARY_DIR.'/mvc' ) . "/$filename" .$ext;
}//--	fn	mvc_filepath

#endregion =========================== micromvc helper functions ==============================


?>