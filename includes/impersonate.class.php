<?php
/**
 * Class to handle admins impersonating users
 * @author adamd
 *
 */
class ABT_Ecom_Impersonate
{
	// Holder for Admin user_info
	private $admin_user;
	// Holder for Host User user_info
	private $host_user;
	// Holder for Messages
	private $message;
	// Holder for Session
	private $session;
	// Holder for Nonce
	private $_wpnonce;

	// isImpersonation Holder
	static $isimp = false;

	function __construct(){
		// Initiate things at the right time
		add_filter( 'init', array(&$this, 'init') );

		// Initiate Abort link from menu
		add_action('admin_bar_menu', array(&$this, 'imp_init_menu'),12);
	}

	public function init(){
		// Make sure the capability is in place
		add_action( 'admin_init', array(&$this, 'init_roles'));

		// Get Session
		if(!session_id()) session_start();
		$this->session = &$_SESSION;

		if(isset($_GET['abort_impersonation'])){
			$this->abortImpersonation();
			wp_redirect(admin_url());
		}

		// Get the current User
		$this->admin_user = wp_get_current_user();

		// Use v() function if it exists
		if(function_exists('v')){
			$host_id = v($_GET['impersonate_user_id'],v($this->session['impersonate_user_id']));
			$this->_wpnonce = v($_GET['_wpnonce'],v($this->session['_wpnonce']));
		} else {
			$host_id = (isset($_GET['impersonate_user_id'])) ? $_GET['impersonate_user_id'] : ( (isset($this->session['impersonate_user_id'])) ? $this->session['impersonate_user_id'] : FALSE);
			$this->_wpnonce = (isset($_GET['_wpnonce'])) ? $_GET['_wpnonce'] : ( (isset($this->session['_wpnonce'])) ? $this->session['_wpnonce'] : FALSE);
		}

		// Attempt to validate host(user) id - if valid, proceed to set information
		if($id = $this->validateHostID($host_id)){
			$this->session['impersonate_user_id'] = $id;
			$this->session['_wpnonce'] = $this->_wpnonce;
			$this->session['real_id'] = $this->admin_user->ID;
			// If you are in the admin section, you don't want to impersonate the user at that moment
			if(!is_admin()){
				$this->host_user = wp_set_current_user($id);
			}
			self::$isimp = $id;
		} else {
			self::$isimp = false;
			return;
		}
	}

	/**
	 * Function to ensure proper permissions exist for admins
	 * Is this even necessary? - Allows for dev to add permissions to other roles...
	 */
	public function init_roles(){
		$role = get_role( 'administrator' ); // gets the administrator role
 		$role->add_cap( 'impersonate_user' ); // Gives the administrator permission to impersonate users
	}

	/**
	 * Function handles adding abort link to menu
	 */
	public function imp_init_menu(){
		global $wp_admin_bar;

		if(false !== self::$isimp){
			$link = admin_url('admin.php?abort_impersonation=true');
			$link = self::imp_nonce_url($link, 'abort_impersonation');

			$user = get_userdata(self::$isimp); // If active, isimp holds id of donor

			$wp_admin_bar->add_menu(
				array(
					'title' 	=> 'Stop Impersonating '.$user->display_name,
					// 'parent' 	=> 'my-account-with-avatar',
					'id' 		=> 'abort-impersonation',
					'href' 		=> $link,
			    )
			);
		}
	}

	/**
	 * Function checks if impersonation id should be impersonated
	 * @param int $id - User ID of Donor to be impersonated
	 */
	private function validateHostID($id){
	    // kill early if current user cannot impersonate donor
		if (! current_user_can('impersonate_donor') ){
			return false;
		}
		// if nonce is set, verify it
		if(isset($this->_wpnonce)){
			if(!wp_verify_nonce($this->_wpnonce, 'impersonate_user_'.$id)){
				return false;
			}
		}
		// no nonce is set, it needs to be.
		else {
		    return false;
		}

		// If ID is false, it hasn't been set, so shouldn't be able to proceed
		if(!$id){
			$this->message = 'No ID set';
			return false;
		}
		// Don't let user impersonate self
		if($id == $this->admin_user->ID){
			$this->message = 'Trying to impersonate self.';
			return false;
		}
		// Get donor data
		$user = get_userdata($id);

		// If user doesn't exist
		if(!$user){
			$this->message = 'User doesnt exist';
			return false;
		}
		// Check if donor is an admin
		/*if($user->user_level == 10){
			$this->message = 'User is admin';
			return false;
		}*/

		// Everything checks out
		return $user->ID;
	}

	/**
	 * Static function to handle creation of the impersonation link
	 * @param (int) $id			ID of user to impersonate
	 * @param (string) $target	Link target (e.g. '_self', '_parent', '_blank')
	 * @param (string) $text	Link Text
	 */
	static function impLink($id,$target='_blank',$text='Impersonate'){
		// Verify user's ability to impersonate users
		if ( current_user_can('impersonate_user') ){
			$url = site_url('donate/?impersonate_user_id='.$id);
			$url = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($url, 'impersonate_user_'.$id) : $url;

			$link = '<a href="'.$url.'" target="'.$target.'" title="'.__($text).'">'.__($text).'</a>';

			return $link;
		}
	}

	/**
	 * Function to stop impersonating donor
	 */
	public function abortImpersonation(){
		if(isset($_SESSION['impersonate_donor_id'])) unset($_SESSION['impersonate_donor_id']);
		if(isset($_SESSION['_wpnonce'])) unset($_SESSION['_wpnonce']);
		if(isset($_SESSION['real_id'])) unset($_SESSION['real_id']);
		$this->is_impersonate = false;
		self::$isimp = false;

		//wp_redirect(admin_url());
	}


	/**
	 * Functions for nonce handling since current user ID changes based on front-end/back-end
	 * when impersonating
	 *
	 */
	function imp_nonce_url( $actionurl, $action = -1 ) {
		$actionurl = str_replace( '&amp;', '&', $actionurl );
		return esc_html( add_query_arg( '_wpnonce', self::imp_create_nonce( $action ), $actionurl ) );
	}

	function imp_create_nonce($action = -1) {
		//$user = wp_get_current_user();
		if(self::$isimp){
			if(is_admin()){
				//$user = wp_get_current_user();
			} else {
				if(isset($_SESSION['real_id']))	{
					$user = get_userdata($_SESSION['real_id'],'OBJECT');
				} else {
					$user = wp_get_current_user();
				}
			}
		} else {
			$user = wp_get_current_user();
		}
		$uid = (int) $user->id;

		$i = wp_nonce_tick();

		return substr(wp_hash($i . $action . $uid, 'nonce'), -12, 10);
	}
}

// Instantiate class
new ABT_Ecom_Impersonate;
?>