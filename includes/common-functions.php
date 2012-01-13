<?php
/*
 * Common shared functions and stuff -- could put it in theme/functions.php, but this way is "available to everything"
 * 
 * @package WordPress
 * @subpackage AtlanticBT_Common
 * @since Twenty Ten 1.0
 */

/*
 * Shared functions to be available to other files
 */
#region ========================== Common Functions =============================

#region ------------------- DEBUGGING -----------------------

if( WP_DEBUG || WP_DEBUG_DISPLAY ) include('debugging.class.php');

#endregion ------------------- DEBUGGING -----------------------


if( !function_exists('current_page') ):
/**
 * Get the current wordpress page
 */
function current_page(){
		//optional paging
	global $wp_query;  
	$currentPage = $wp_query->query_vars['paged'];
	if(!($currentPage > 1)){ $currentPage = 1; }
	return $currentPage;
}//----	end function currentPage
endif;//function_exists




#region ------------------- Useful Helpers -----------------------


if( !function_exists('replaceholders')):
/**
 * Replace .NET-style string placeholders (like String.Format), using numerical or named placeholders
 * @see http://blargh.tommymontgomery.com/2010/01/string-format-in-php/
 *
 * @param string $mask the format mask, like "Person Name: {0}" or using named placeholders "Person Name: {name}"
 * @param mixed $vars give the rest as multiple parameters (func_get_args), or as an array of placeholder/value pairs - these will be used as replacements
 * 
 */
/*
function replaceholders(){
	$args = func_get_args();
	$format = array_shift($args);
	
	//check if we have named placeholders
	if( 1 === count($args) && is_array($args[0]) ){
		$args = $args[0];
	}

	preg_match_all('/(?=\{)\{([0-9a-zA-Z]+)\}(?!\})/', $format, $matches, PREG_OFFSET_CAPTURE);
	$offset = 0;
	foreach ($matches[1] as $data) {
		$i = $data[0];
		$format = substr_replace($format, @$args[$i], $offset + $data[1] - 1, 2 + strlen($i));
		$offset += strlen(@$args[$i]) - 2 - strlen($i);
	}

	return $format;
}//--	fn	replaceholders
/**/


function _replaceholders_formatter($key){ return "{{$key}}"; }
/**
 * Replace dot-NET-style string placeholders (like String.Format), using numeric or named placeholders
 *
 * @param string $mask the format mask, like "Person Name: {0}" or using named placeholders "Person Name: {name}"
 * @param array/list $replacements give the rest as an array of placeholder/value pairs
 * @param string $replacements2, 3, etc {optional format} can give list of placeholders as param args
 * 
 */
function replaceholders($mask, $replacements = array()){
	//check if mask even has placeholder
	if( false === strpos($mask, '{') || empty( $replacements ) ) return $mask;
	
	//get args, allow for optional list
	$args = func_get_args();
	//pop mask
	array_shift($args);
	
	//format argument replacements
	//one argument given, use replacements
	if( 1 == count($args) ){
		//use replacements
	}
	else {
		$replacements = $args;
	}
	
	//add mask formatting
	$placeholders = array_map('_replaceholders_formatter', array_keys($replacements));
	$values = array_values($replacements);
	return str_replace($placeholders, $values, $mask);
}//--	fn	replaceholders

endif;	//function_exists


if ( ! function_exists( 'get_called_class' ) ):
	/**
	 * Reprint of PHP > 5.3.0 function
	 * @see http://www.sitepoint.com/forums/php-34/get_called_class-5-3-%5Bsolution%5D-605318.html
	 */
	function get_called_class ($depth = 0)
	{
		$t = debug_backtrace(); $t = $t[ $depth ];
		if ( isset( $t['object'] ) && $t['object'] instanceof $t['class'] )
		return get_class( $t['object'] );
		return false;
	}
endif;

if ( ! function_exists( 'wp_format_date' ) ):
/**
 * Format a date using WP's format
 * @param mixed $date a date string or value
 * @param bool $is_datetime {false} if true, parse $date from value given as mysql datetime
 */
function wp_format_date($date, $is_datetime = false){
	//caching
	global $_wp_date_format_string;
	if( ! isset($_wp_date_format_string) ) $_wp_date_format_string = get_option('date_format');
	
	//optional parsing
	if( true === $is_datetime ) $date = mysql2date($_wp_date_format_string, $date);
	else $date = date($_wp_date_format_string, $date);
	
	return apply_filters('get_the_date', $date, $_wp_date_format_string);
}
endif;//exists function wp_format_date
if ( ! function_exists( 'wp_format_time' ) ):
/**
 * Format a time using WP's format
 * @param mixed $date a time string or value
 * @param bool $is_datetime {false} if true, parse $date from value given as mysql datetime
 */
function wp_format_time($date, $is_datetime = false){
	//caching
	global $_wp_time_format_string;
	if( ! isset($_wp_time_format_string) ) $_wp_time_format_string = get_option('time_format');
	
	//optional parsing
	if( true === $is_datetime ) $date = mysql2date($_wp_time_format_string, $date);
	else $date = date($_wp_time_format_string, $date);
	
	return apply_filters('get_the_time', $date, $_wp_time_format_string);
}
endif;//exists function wp_format_time

/**
 * Call a user function using named instead of positional parameters.
 * If some of the named parameters are not present in the original function, they
 * will be silently discarded.
 * Does no special processing for call-by-ref functions...
 * @param string $function name of function to be called
 * @param array $params array containing parameters to be passed to the function using their name (ie array key)
 *
 * @source http://php.net/manual/en/function.call-user-func-array.php
 */
function call_user_func_named($function, $params)
{
	// make sure we do not throw exception if function not found: raise error instead...
	// (oh boy, we do like php 4 better than 5, don't we...)
	if ( is_array($function) ){
		if( !method_exists($function[0], $function[1]) ){
			trigger_error('call to non-existant method ' . $function[1] , E_USER_ERROR);
			return NULL;
		}
		
		$reflect = new ReflectionFunction($function);	///TODO: figure out how to work this...
	}
	else {
		if( !function_exists($function) ){
			trigger_error('call to non-existant function '.$function, E_USER_ERROR);
			return NULL;
		}
		
		$reflect = new ReflectionFunction($function);
	}
	
	$real_params = array();
	foreach ($reflect->getParameters() as $i => $param)
	{
		$pname = $param->getName();
		if ($param->isPassedByReference())
		{
			/// @todo shall we raise some warning?
		}
		if (array_key_exists($pname, $params))
		{
			$real_params[] = $params[$pname];
		}
		else if ($param->isDefaultValueAvailable()) {
			$real_params[] = $param->getDefaultValue();
		}
		else
		{
			// missing required parameter: mark an error and exit
			//return new Exception('call to '.$function.' missing parameter nr. '.$i+1);
			trigger_error(sprintf('call to %s missing parameter nr. %d', $function, $i+1), E_USER_ERROR);
			return NULL;
		}
	}
	return call_user_func_array($function, $real_params);
}

#endregion ------------------- Useful Helpers -----------------------





#region ------------------- Custom Theming -----------------------

#endregion ------------------- Custom Theming -----------------------



#endregion ========================== Common Functions =============================

?>