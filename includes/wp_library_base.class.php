<?php

/**
 * Base class for library extensions
 * other WP-Library classes can inherit this to get some juicy functions
 */
class WP_Library_Base {

/**
 * Set a hook of the same name for a method from this class
 * 	Note that actions and filters are the same (internally to WP)
 * 
 * @param string $hook_name the name of both the WP hook (filter or action) and the corresponding internal class method
 * @param int $priority {optional, 10} the hook priority
 * @param int $params_allowed {optional, 2} how many parameters to pass through the hook
 */
public function add_hook($hook_name, $priority = 10, $params_allowed = 2) {
	return add_filter($hook_name, array(&$this, $hook_name), $priority, $params_allowed);
}//--	fn	hook

/**
 * Set a hook for a method from this class
 * 	Note that actions and filters are the same (internally to WP)
 * 
 * @param string $hook_name the name of the WP hook (filter or action)
 * @param string $callback_name the name of the corresponding internal class method to use on callback
 * @param int $priority {optional, 10} the hook priority
 * @param int $params_allowed {optional, 2} how many parameters to pass through the hook
 */
public function add_hook_with($hook_name, $callback_name, $priority = 10, $params_allowed = 2) {
	return add_filter($hook_name, array(&$this, $callback_name), $priority, $params_allowed);
}//--	fn	hook


}///---	class	WP_Library_Base

?>