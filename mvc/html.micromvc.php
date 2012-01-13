<?php
/**
 * HTML
 *
 * Provides quick HTML snipets for common tasks
 *
 * @package		MicroMVC
 * @author		David Pennington
 * @copyright	(c) 2010 MicroMVC Framework
 * @license		http://micromvc.com/license
 ********************************** 80 Columns *********************************
 */
class html
{

/**
 * Create a gravatar <img> tag
 *
 * @param $email the users email address
 * @param $size	the size of the image
 * @param $alt the alt text
 * @param $rating max image rating allowed
 * @return string
 */
public static function gravatar($email = '', $size = 80, $alt = 'Gravatar', $rating = 'g')
{
	return '<img src="http://www.gravatar.com/avatar/'.md5($email)."?s=$size&d=wavatar&r=$rating\" alt=\"$alt\" />";
}


/**
 * Generates an obfuscated HTML version of an email address.
 *
 * @param string $email address
 * @return string
 */
public static function email($email)
{
	$s='';foreach(str_split($email)as$l){switch(rand(1,3)){case 1:$s.='&#'.ord($l).';';break;case 2:$s.='&#x'.dechex(ord($l)).';';break;case 3:$s.=$l;}}return$s;
}


/**
 * Convert a multidimensional array to an unfiltered HTML UL. You can
 * pass attributes such as id, class, or javascript.
 *
 * @param array $ul the array of elements
 * @param array $attributes the array of HTML attributes
 * @return string
 */
static function ul_from_array(array $ul, array $attributes = array())
{
	$h='';foreach($ul as$k=>$v)if(is_array($v))$h.=self::tag('li',$k.self::ul_from_array($v));else$h.=self::tag('li',$v);return self::tag('ul',$h,$attributes);
}


/**
 * Compiles an array of HTML attributes into an attribute string and
 * HTML escape it to prevent malicious data.
 *
 * @param array $attributes the tag's attribute list
 * @return string
 */
public static function attributes(array $attributes = array())
{
	if(!$attributes)return;asort($attributes);$h='';foreach($attributes as$k=>$v)$h.=" $k=\"".h($v).'"';return$h;
}


/**
 * Create an HTML tag
 *
 * @param string $tag the tag name
 * @param string $text the text to insert between the tags
 * @param array $attributes of additional tag settings
 * @return string
 */
public static function tag($tag, $text = '', array $attributes = array())
{
	return"\n<$tag".self::attributes($attributes).($text===0?' />':">".__($text)."</$tag>");
}


/**
 * Create an HTML Link
 *
 * @param string $url for the link
 * @param string $text the link text
 * @param array $attributes of additional tag settings
 * @return string
 */
public static function link($url, $text = '', array $attributes = array())
{
	return self::tag('a',$text,($attributes+array('href'=>site_url($url))));
}


/**
 * Auto creates a form select dropdown from the options given.
 *
 * @param string $name the select element name
 * @param array $options the select options
 * @param mixed $selected the selected options(s)
 * @param array $attributes of additional tag settings
 * @return string
 */
public static function select($name, array $options = array(), $selected = NULL , array $attributes = array())
{
	$h='';foreach($options as$k=>$v){$a=array('value'=>$k);if($selected&&in_array($k,(array)$selected))$a['selected']='selected';$h.=self::tag('option',$v,$a);}return self::tag('select',$h,$attributes+array('name'=>$name));
}

/**
 * Create a radio or checkbox list
 * @param string $name the field element name
 * @param string $label the grouping title
 * @param string $type {radio, checkbox} whether radio or checkbox
 * @param array $options the selectable options
 * @param mixed $selected the selected options(s)
 * @param array $attributes of additional tag settings
 */
public static function radios($name, $label, $type = 'radio', array $options = array(), $selected = NULL, array $attributes = array()){
	$h = self::tag('legend', self::tag('span', $label));
	foreach($options as $k => $v) {
		//merge macro attributes on each item
		$a = $attributes + array('value' => $k, 'class' => $type, 'type' => $type, 'name' => $name, 'id' => $name);
		//update the id to make it option-specific
		$a['id'] .= '-'.str_replace(' ', '-', $k);

		// Is this element one of the selected options?
		if(in_array($k, (array)$selected))
		{
			$a['checked'] = 'checked';
		}

		$h .= self::tag('div', self::tag('input', 0, $a) . self::tag('label', $v, array('for'=>$a['id'])), array('class' => 'field radio inline-label') );
	}

	if( ! $attributes) {
		$attributes = array();
	}

	return self::tag('fieldset', $h, $attributes+array('class' => $type . '-list items-'.count($options)));
}//--	fn	radios

/**
 * Turn a date/timestamp into HTML form elements
 *
 * @param mixed $time
 * @param string $name to prefix elements with
 * @param string $class name to give elements
 * @return string
 */
public static function date_time($ts = NULL, $name = 'datetime', $class = 'datetime')
{
	require_once('time.php');
	$ts=new Time($ts);$t=$ts->getArray($ts);$e[]=self::month_select($t['month'],$name);foreach(Lang::get('time_units')as$k=>$v)$e[]=html::tag('input',0,array('name'=>"{$name}[$k]",'type'=>'text','value'=>isset($t[$k])?$t[$k]:0,'class'=>$k));return vsprintf(Lang::get('html_datetime'),$e);
}

/**
 * Turn a date/timestamp into HTML form elements (*modified for PHP < 5.3.0 @JRS)
 * NOT REALLY NEEDED WITH HTML5 TYPES
 * @usage with Form->fields declare as "datetime_manual"
 * @see form.tpl.php
 *
 * @param mixed $time
 * @param string $name to prefix elements with
 * @param string $attributes of additional tag settings
 * @return string
 */
public static function datetime($ts = NULL, $name = 'datetime', $attributes = array() )
{
	$ts = date_parse($ts);$e[]=self::month_select($t['month'],$name);foreach(Lang::get('time_units')as$k=>$v)$e[]=html::tag('input',0,array('name'=>"{$name}[$k]",'type'=>'text','value'=>isset($t[$k])?$t[$k]:0,'class'=>$k));return vsprintf(Lang::get('html_datetime'),$e);
}

/**
 * Create a select box for months based on the user language file
 *
 * @param int $month current selected month
 * @param string $name to prefix elements with
 * @param string $class name to give element
 * @return string
 */
public static function month_select($month = 1, $name = 'datetime', $class = 'month')
{
	return html::select("{$name}[month]",Lang::get('html_months'),$month,array('class'=>$class));
}

/**
 * Return a script tag
 * @param string $source either the source file, or the source code itself (depending on $hasCode)
 * @param string $domain {optional} if given, use external domain instead of site domain
 * @param bool $hasCode {default:false} whether or not this is inline code
 */
public static function script($source, $domain = false, $hasCode = false){
	//print inline tag and code
	if($hasCode){
		return self::tag('script', "/* <![CDATA[ */\n".$source."\n/* ]]> */\n", array('type'=>'text/javascript"'));
	}
	//otherwise return a script tag with source
	return self::tag('script', '', array('type'=>'text/javascript', 'src'=>self::script_source($source, $domain)));
}//---	function script

/**
 * Concatenates a script (js, css) source link path and domain (if given)
 * @param string $source either the source file, or the source code itself (depending on $hasCode)
 * @param string $domain {optional} if given, use external domain instead of site domain
 */
public static function script_source($source, $domain = false){
	return ($domain?'//'.$domain.'/'.$source:site_url($source));
}//---	function script_source

/**
 * Return a style tag
 * @param string $source either the source file, or the source code itself (depending on $hasCode)
 * @param string $domain {optional} if given, use external domain instead of site domain
 * @param string $media {default:all} stylesheet media attribute
 * @param bool $hasCode {default:false} whether or not this is inline code
 */
public static function style($source, $domain = false, $media = 'all', $hasCode = false){
	//print inline tag and code
	if($hasCode){
		return self::tag('style', $source, array('type'=>'text/css"'));
	}
	//otherwise return a script tag with source
	return self::tag('link', 0, array('type'=>'text/javascript', 'src'=>self::script_source($source, $domain), 'media'=>$media));
}//---	function style



}///----	class html

// END
