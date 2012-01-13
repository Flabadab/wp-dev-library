<style>
.debug section { border-bottom:1px solid #006; margin-bottom:1em; }
</style>
<div class="debug" style="margin: 60px 0;padding:2em;background:#ECF5FA;color:#000;clear:both;">

<section id="debug-usage">
<b>At Start</b>
<pre>
<?php echo number_format(WP_START_MEMORY_USAGE); ?> bytes, <?php echo WP_START_TIME; ?> seconds
</pre>

<b>Memory Usage</b>
<pre>
<?php print number_format(memory_get_usage() - WP_START_MEMORY_USAGE); ?> bytes
<?php print number_format(memory_get_usage()); ?> bytes (process)
<?php print number_format(memory_get_peak_usage(TRUE)); ?> bytes (process peak)
</pre>

<b>Execution Time</b>
<pre><?php print round((microtime(true) - WP_START_TIME), 5); ?> seconds</pre>
</section>

<section id="debug-page">
<b>URL</b>
<?php
if( ! function_exists('url') ):
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
endif;


echo '<pre>',  print_r(url(), true), '</pre>'; ?>
</section>

<section id="debug-database">
<?php
///TODO: get recent WP Queries
global $wpdb;
?>
	<dl class="last-query"><dt>Last Query</dt><dd><?php print_r($wpdb->last_query) ?></dd></dl>
	<dl class="last-result"><dt>Last Result</dt><dd><?php print_r($wpdb->last_result) ?></dd></dl>
	<dl class="col-info"><dt>Last Columns</dt><dd><?php print_r($wpdb->col_info) ?></dd></dl>
	
	<strong>Last Queries</strong>
	<?php if( isset( $wpdb->queries ) && ! empty( $wpdb->queries ) ):?>
	<ol class="last-queries">
	<?php
	foreach( $wpdb->queries as $qid => $query ):
		?>
		<li><pre><?php print_r($query); ?></pre></li>
		<?php
	endforeach;
	?>	
	</ol>
	<?php endif; // if $wpdb->queries ?>
	
<?php

if(class_exists('db', FALSE))
{
	foreach(db::$queries as $type => $queries)
	{
		print '<b>'.$type.' ('. count($queries). ' queries)</b>';
		foreach($queries as $data)
		{
			print '<pre>'. highlight(wordwrap($data[2])."\n/* ".round(($data[0]*1000), 2).'ms - '. round($data[1]/1024,2).'kb'. ' */'). '</pre>';
		}
	}
	
	if(Error::$found)
	{
		print '<b>Last Query Run</b>';
		print '<pre>'. highlight(DB::$last_query). '</pre>';
	}
	
}


function highlight($string)
{
	/*return str_replace(array("&lt;?php", "?&gt;"),'',substr(substr(highlight_string('<?php '.$string.' ?>', TRUE),36),0,-20));*/
	return str_replace(array("&lt;?php", "?&gt;"),'',substr(highlight_string('<?php '.$string.' ?>', TRUE),36));
}
?>
</section>

<section id="debug-session">
<?php if(!empty($_SESSION)) { ?>
<b>Session Data</b>
<?php pdump($_SESSION); ?>
<?php } ?>
</section>

<section id="debug-includes">
<?php $included_files = get_included_files(); ?>
<b><?php print count($included_files); ?> PHP Files Included:</b>
<pre>
<?php print implode("\n", $included_files); ?>
</pre>
</section>

</div>