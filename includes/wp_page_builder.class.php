<?php
/**
 * Helper class to build, activate, deactivate and remove pages
 * @author adamd
 *
 * 
 */
class WP_Page_Builder
{
	/**
	 * Remember last page id for subsequent actions
	 * @var int
	 */
	private $_last_page_id = NULL;
	/**
	 * Get page id of last page operated on
	 */
	public function last_page_id(){
		return $this->_last_page_id;
	}
	
	function __construct() {
		/* This section intentionally left blank */
	}
	
	/**
	 * Function to build pages
	 * @param array $page contains all page data
	 */
	function build($page)
	{
//		Try to get page by path
		$p = get_page_by_path($page['path']);
		if(!$p) {
			$this->_last_page_id = wp_insert_post($page);
		} else {
			$this->_last_page_id = $p->ID;
			$newdata = array(
				'ID' => $p->ID,
				'post_status' => 'publish'
			);
			wp_update_post($newdata);
		}
		return $this->_last_page_id;
	}
	
	/**
	 * Function to re-activate page (move to 'publish' status)
	 * @param array $page array of data containing page parameters
	 */
	function publish($page){
		$p = get_page_by_path($page['path']);
		if($p){
			$newdata = array();
			$newdata['ID'] = $p->ID;
			$newdata['post_status'] = 'publish';
			wp_update_post($newdata);
			return ($this->_last_page_id = $p->ID);
		}
		return false;
	}
	
	/**
	 * Function to deactivate page (move to trash)
	 * @param array $page array of data containing page parameters
	 */
	function unpublish($page){
		$p = get_page_by_path($page['path']);
		if($p){
			$newdata = array();
			$newdata['ID'] = $p->ID;
			$newdata['post_status'] = 'draft';
			wp_update_post($newdata);
			return ($this->_last_page_id = $p->ID);
		}
		return false;
	}
	
	/**
	 * Function to remove page (force delete)
	 * @param array $page array of data containing page parameters
	 * @param bool $force true == bypass trash and force delete, false === move to trash
	 */
	function remove($page, $force=false){
		$p = get_page_by_path($page['path']);
		$this->_last_page_id = $p->ID;
		wp_delete_post($p->ID, $force);
		return true;
	}
	
	/**
	 * Get/Set meta value for last page we did stuff with
	 * @param $key
	 * @param $value
	 */
	function meta($key, $value = NULL){
		if( NULL === $value ) { return get_post_meta($this->_last_page_id, $key, true); }
		
		update_post_meta($this->_last_page_id, $key, $value);
	}//--	fn	meta
}