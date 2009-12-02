<?php 
/**
 * Plugin for YUI. Going component route so we can just set up variables for output on layout. 
 * Component seems to be the way to go?
 *
 * @package default
 * @author Mitchell Amihod
 **/
class YuiComponent extends Object {


	/**
	 *
	 * @return void
	 **/
	function initialize(&$controller, $settings=array()) {
		$this->_set($settings);
	}
	
/**
 * Allows setting of multiple properties of the object in a single line of code.
 * Override to merge in array settings. 
 *
 * @param array $properties An associative array containing properties and corresponding values.
 * @return void
 * @access protected
 */
	function _set($properties = array()) {
		if (is_array($properties) && !empty($properties)) {
			$vars = get_object_vars($this);
			foreach ($properties as $key => $val) {
				if (array_key_exists($key, $vars) && is_array($val)) {
					$this->{$key} = array_merge($this->{$key}, $val);
				} 
				elseif (array_key_exists($key, $vars)) {
					$this->{$key} = $val;
				}
			}
		}
	}


}
