<?php
/**
 * Langauge
 *
 * Autoloads the correct language file based on cookie, useragent, and available
 * module languages. The entire language system is based on country codes in ISO
 * 3166-1 alpha-2.
 *
 * @package		MicroMVC
 * @author		David Pennington
 * @copyright	(c) 2011 MicroMVC Framework
 * @license		http://micromvc.com/license
 * @source		https://github.com/Xeoncross/micromvc/blob/development/Core/Lang.php
 ********************************** 80 Columns *********************************
 */
//namespace Core;

class Lang
{
	/**
	 * Expected language file extension
	 * @var unknown_type
	 */
	const EXT = '.lang.php';
	/**
	 * Internal storage of language variables
	 * @var unknown_type
	 */
	protected static $lang;
	/**
	 * for lazy choosing - once load is called, remember last relative path
	 * @var unknown_type
	 */
	protected static $last_rel_to = NULL;
	
	/**
	 * Load a language file for the given module
	 *
	 * @param string $language the language ISO/filename
	 * @param string $module the module name
	 * @param string $rel_to a source path to load the file relative to given (pass as __FILE__), defaults to stylesheet directory
	 */
	static function load($language, $module = NULL, $rel_to = NULL)
	{
		self::$last_rel_to = $rel_to;	//remember last loaded relative
		require( /* SP . "$module/Lang/$language" . EXT); */ mvc_filepath(self::EXT, "ui/$language", self::$last_rel_to) );
		self::$lang[$module] = $lang;
	}


	/**
	 * Get an array of all languages supported by useragent
	 *
	 * @return array
	 */
	static function accepted()
	{
		static $a;
		if($a)return $a;
		foreach(explode(',', getenv('HTTP_ACCEPT_LANGUAGE')) as $v)
		{
			$a[] = substr($v, 0, 2);
		}
		return $a;
	}


	/**
	 * Fetch a language key (loading the language file if needed)
	 *
	 * @param string $k the key name
	 * @param string $module the module name
	 * @return string
	 */
	static function get($key, $module = NULL)
	{
		if(empty(self::$lang[$module]))
		{
			self::load(self::choose($module), $module/*, self::$last_rel_to*/);
		}
		return __(self::$lang[$module][$key], $module);
	}


	/**
	 * Figure out which language file to load for this module
	 *
	 * @param string $module the module name
	 * @return string
	 */
	static function choose($module = NULL)
	{
		$p = mvc_filepath(self::EXT, "$module/ui/"/*, self::$last_rel_to*/);	//	SP . $module . '/Lang/';
		
		// Has the user choosen a custom language?
		if(!empty($_COOKIE['lang']) AND strlen($c = $_COOKIE['lang']) == 2 AND is_file($p . $c . self::EXT)) return $c;

		// Auto-detect the languages they want
		foreach(self::accepted() as $c)
		{
			if(is_file($p . $c . self::EXT)) return $c;
		}

		return WPLANG;	//config('language');
	}

}

// END
