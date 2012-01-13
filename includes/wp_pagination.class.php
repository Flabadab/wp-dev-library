<?php
if( ! class_exists('WP_Pagination')):
wp_library_include('Singleton.class.php', true, true);

/**
 * Wordpress Helper - Pagination simplification
 * @author jeremys
 * 
 * 
 * @usage:
 * 
 * $pagination = WP_Pagination::instance();	//prepare for pagination
 * query_posts( $pagination->query('', 3) );
 * while ( have_posts() ) : the_post(); ?> ... <?php endwhile;
 * echo $pagination->links(array('prev_text'=>__('[--PREV]'), 'next_text'=>__('[NEXT--]')));
 *
 */
class WP_Pagination extends Singleton {
	
	#region ---------- FACTORY --------------
	
	/**
	 * Return singleton instance
	 * Workaround for PHP < 5.3.0
	 */
	static public function instance(){
		return parent::instance(__CLASS__);
	}//--	fn	instance
	
	#endregion ---------- FACTORY --------------
	
	
private $current = 0;
/**
 * Get the current wordpress page
 */
public function current(){
	if($this->current !== 0) return $this->current;
	//optional paging
	global $wp_query;
	$this->current = $wp_query->query_vars['paged'];
	if(!($this->current > 1)){ $this->current = 1; }
	return $this->current;
}//----	fn currentPage


private $links;
/**
 * Wordpress Helper Fn - pagination_links wrapper
 * @see http://codex.wordpress.org/Function_Reference/paginate_links
 * 
 * @param $args {optional} override any of the normal WP pagination options
 * @return	the pagination links in a DIV
 */
public function links( $args = array() ){
	if(!empty($this->links)) return $this->links;
	
	
	global $wp_query, $wp_rewrite;
	$wp_query->query_vars['paged'] > 1 ? $current = $wp_query->query_vars['paged'] : $current = 1;
	
	$pagination_options = array(
		'base' => @add_query_arg('page','%#%'),
		'format' => '',
		'total' => $wp_query->max_num_pages,
		'current' => $this->current(),
		'show_all' => true,
		'type' => 'plain'
		);
	
	if( $wp_rewrite->using_permalinks() )
	$pagination_options['base'] = user_trailingslashit( trailingslashit( remove_query_arg( 's', get_pagenum_link( 1 ) ) ) . 'page/%#%/', 'paged' );

	if( !empty($wp_query->query_vars['s']) )
		$pagination_options['add_args'] = array( 's' => get_query_var( 's' ) );
	
	//override any default settings
	$args = array_merge($pagination_options, $args);
	
	$this->links = '<div class="links pagination-links">'. paginate_links( $args ). '</div>';
	return $this->links;
}//----	fn links

/**
 * Clear cached pagination strings, etc
 */
public function reset(){
	$this->current = 0;
	$this->links = null;
}//--	fn	reset

/**
 * Return a paginated query string (for query_posts, WP_Query, etc)
 * @param string $query the original query string
 * @param int $per_page {optional, 10} how many per page to return
 */
public function query($query = null, $per_page = 10){
	return ( ! empty($query) ? "$query&" : '') . "posts_per_page=$per_page&paged=".$this->current();
}//--	fn	query

}//----	class wp_pagination
endif;// if class_exists
?>