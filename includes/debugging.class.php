<?php

/**
 * Custom variant of print_r
 * @param $data the thing to print
 * @param $depth {ignore} used in recursion
 */
function myprint_r($data, $depth = 0){
	# echo str_repeat("\t", $depth);
	echo '{', gettype($data), "}: ";
	if( ! is_scalar( $data ) ) {
		echo "\n";
		foreach( $data as $key => $value ){
			echo str_repeat("\t", $depth+1);
			echo "'$key' => ", myprint_r($value, $depth+1);
		}
	}
	else {
		echo htmlspecialchars($data), "\n";
	}
}//--	fn	myprint_r

if( ! function_exists('mybug_render') ) :
/**
 * Print Debug output - rendering function
 * @param $title {optional} if first argument starts with '--' or '__' or '|', it'll print as a title
 * @param mixed $etc the rest of the things to print
 */
function mybug_render($args, $attr){
	static $debug_counter;
	echo sprintf($attr['#wrapper_open'], ++$debug_counter);
	
	#region ---------------- source -----------------
	/* so we can find where we put the pbug */
	
	echo '<small style="float:right;">';
	$backtrace = debug_backtrace( /* 1, 1 */ );
	// pop the current function off
	array_shift($backtrace);
	if( isset($attr['backtrace']) && 'all' === $attr['backtrace'] ) {
		print_r($backtrace);
	}
	else {
		echo 'LINE['
			, $backtrace[0]['line'], '] of FILE{'
			, $backtrace[0]['file'], '}'
			/*
			, ' -- fn ', $backtrace[0]['function'],'('
			, implode(', ', $backtrace[0]['args']), ')'
			*/
			;
	}
	echo '</small>';
	#endregion ---------------- source -----------------

	// optional title
	if(  -1 === $args[0] ) {
		// get rid of the placeholder
		array_shift($args);
		// now pull off the title
		$title = array_shift($args);
	}
	elseif( is_string($args[0]) && (
			0 === strpos($args[0], '--') || 0 === strpos($args[0], '__')  || 0 === strpos($args[0], '|') )
		) {
		$title = substr( array_shift($args), 2);
	}
	else {
		$title = 'Debug ' . $debug_counter;
	}
	echo sprintf($attr['#title'], $title);
	
	
	foreach($args as $arg) {
		echo $attr['#item_open'];
		if( isset( $attr['style'] ) && 'print_r' == $attr['style'] ){
			print_r($arg);
		}
		elseif( isset( $attr['style'] ) && 'myprint_r' == $attr['style'] ){
			myprint_r($arg);
		}
		else {
			echo var_export($arg);
		}
		echo $attr['#item_close'];
	}
	echo $attr['#wrapper_close'];
}
endif;


if( ! function_exists('pbug') ) :
/**
 * Print Debug output - as pre
 * @param $title {optional} if first argument starts with '--' or '__' or '|', it'll print as a title
 * @param mixed $etc the rest of the things to print
 */
function pbug(){
	$args = func_get_args();
	
	mybug_render($args, array(
		'#wrapper_open' => "\n<div class=\"debug\" id=\"debug-%s\">\n",
		'#wrapper_close' => "\n</div>\n",
		'#title' => "\n	<h2>%s</h2>\n",
		'#item_open' => "\n	<pre>\n",
		'#item_close' => "\n	</pre>\n",
		#'style' => 'print_r' | 'var_export' | 'myprint_r'
		'style' => 'print_r'
	));	
}//--	fn	pbug
endif;



if( ! function_exists('hbug') ) :
/**
 * Print Debug output - in HTML comment
 * @param $title {optional} if first argument starts with '--' or '__' or '|', it'll print as a title
 * @param mixed $etc the rest of the things to print
 */
function hbug(){
	$args = func_get_args();

	mybug_render($args, array(
		'#wrapper_open' => "\n<!-- debug-%s \n",
		'#wrapper_close' => "\n -->\n",
		'#title' => "\n	--- %s ---\n",
		'#item_open' => "\n	------------\n",
		'#item_close' => "\n	------------\n",
		#'style' => 'print_r' | 'var_export' | 'myprint_r'
		'style' => 'myprint_r'
	));
}//--	fn	hbug
endif;


if( ! function_exists('pdump') ) :
/**
 * Print Debug output - in HTML comment
 * @param $title {optional} if first argument starts with '--' or '__' or '|', it'll print as a title
 * @param mixed $etc the rest of the things to print
 */
function pdump(){
	$args = func_get_args();

	mybug_render($args, array(
		'#wrapper_open' => "\n<!-- debug-%s \n",
		'#wrapper_close' => "\n -->\n",
		'#title' => "\n	--- %s ---\n",
		'#item_open' => "\n	------------\n",
		'#item_close' => "\n	------------\n",
		#'style' => 'print_r' | 'var_export' | 'myprint_r'
		'style' => 'var_export'
	));
}//--	fn	pdump
endif;



if( !function_exists('debug_whereat')):
/**
 * Pretty-print debug_backtrace()
 * @param int $limit {optional} when to stop printing - how many recursions up/down
 * @param int $skip {optional} when to start printing - how many calls to skip over
 * @param string $before {optional} extra html to print before the table, inside debug container
 * @param string $after {optional} extra html to print before the table, inside debug container
 *  */
function debug_whereat($limit = false, $skip = false, $before = '', $after = ''){
	static $debug_whereat_counter; if( !$debug_whereat_counter) $debug_whereat_counter = 0;
	$uid = $debug_whereat_counter++;
	?>
	<div class="debug trace">
		<?php echo $before; ?>
	<table>
		<thead><tr>
			<th id="th-index-<?=$uid?>"><i>nth</i></th>
			<th id="th-line-<?=$uid?>">Line</th>
			<th id="th-file-<?=$uid?>">File</th>
			<th id="th-method-<?=$uid?>">Method</th>
		</tr></thead>
		<tbody>
	<?php
	
	$backtrace = debug_backtrace();
	if( $skip ) $backtrace = array_slice($backtrace, $skip);
	
	foreach($backtrace as $index => $trace){
		//force quit
		if($limit !== false && $index == $limit){
			?>
			<tr><td colspan="4"><em>----- FORCE STOP RECURSION -----</em></td></tr>
			<?php
			break;
		}
		
		?>
		<tr class="trace-item">
			<th headers="th-index-<?=$uid?>"><?=$index?></th>
			<td headers="th-line-<?=$uid?>" class="line"><?=$trace['line']?></td>
			<td headers="th-file-<?=$uid?>" class="file"><?=$trace['file']?></td>
			<td headers="th-method-<?=$uid?>" class="method">
				<code><?=$trace['function']?></code>
				<?php
				if(!empty($trace['args'])){
					echo '<br />';
					while(!empty($trace['args'])){
						?>	{<i><?php print_r(array_shift($trace['args']) ); ?></i>}	<?php
					}//	while !empty $trace['args']
				}
				?>
			</td>
		</tr>
		<?php
	}
	?>
	</tbody></table><?php echo $after; ?></div>
	<?php

}//	function debug_whereat
endif; //function_exists debug_whereat

/**
 * Helpful methods for debugging
 * TODO: put other methods in here
 */
class abt_debug {
	
	/**
	 * Get the name of the calling file, from a certain depth
	 * 
	 * Get the name of the preceding files calling this one, for a given depth
	 * 
	 * @param int $depth {default 0} how many files up the tree from the current file to check, +1 (since we want the calling file)
	 * 
	 * @return the name of the calling file
	 */
	static function calling_file($depth = 0){
		$backtrace = debug_backtrace( $depth+1 );
		
		return $backtrace[$depth+1]['file'];
	}//--	fn	calling_file
	
}///---	class	abt_debug




/**
 * Because new WP 3.3 "doing it wrong" warnings don't really tell you where you f*'d up...
 * @param string $function The function that was called.
 * @param string $message A message explaining what has been done incorrectly.
 * @param string $version The version of WordPress where the message was added.
 */
function abt_doing_it_wrong_helper($function, $message, $version){
	$before = <<<EOD
<strong>$function:</strong> v$version
EOD;
	debug_whereat(3, 5, $before);
}
add_action( 'doing_it_wrong_run', 'abt_doing_it_wrong_helper', 10, 3 );
