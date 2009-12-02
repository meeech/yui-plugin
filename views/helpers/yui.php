<?php
App::import('Vendor', 'Yui.phploader/phploader/loader.php');

class YuiHelper extends AppHelper {

	//This is what we want avaliable right away.
	var $_headModules = array(
			'yahoo', 'dom', 'event', 'selector'
		);

	var $_modules = array();

	//Some sane Loader defaults
	//maps to http://developer.yahoo.com/yui/phploader/#metainfo
	var $_loaderOptions = array(
		'base' => null,
		'filter'=>null,

		//Rollup/combine. Overridden if debug = 2
		'allowRollups'=>false,
		'combine' => true,

		'loadOptional'=>null,
		'comboBase'=>null
		);

	/**
	 * Which version of yui to load.
	 *
	 * @var string
	 **/
	public $version = '2.8.0';

	/**
	 * Loader Instance
	 *
	 * @var object
	 **/
	protected $_loader = null;

	function __construct($options = array()) {
		//You can set this in the core.php, or just set it here.
		if(!Configure::read('Yui.debug')) {
			// @todo integrate logger debug output YAHOO.widget.Logger.enableBrowserConsole()
			Configure::write('Yui.debug', 0);
		}

		return parent::__construct($options);
	}

	/**
	 * Used to reset the loader.
	 *
	 * @return void
	 **/
	function reset() {
		$this->_loader = null;
	}

	//
	/**
	 * Change options if debug is enabled. (ie; no combine)
	 *  based on Configure::write('Yui.debug', 1);
	 *  0 = combo, no rollup (default)
	 * 	1 = no combo, no rollup, still -min
	 *  2= no combo, no rollup, raw filter (no min).
	 *  3= no combo, no rollup, -debug
	 *
	 * @return void
	 **/
	function _debugSettings() {

		if( 0 < Configure::read('Yui.debug')) {
			$this->_loaderOptions['allowRollups'] = false;
			$this->_loaderOptions['combine'] = false;
		}

		if( 2 == Configure::read('Yui.debug')) {
			$this->_loaderOptions['filter'] = YUI_RAW;
		}
		elseif( 3 == Configure::read('Yui.debug')) {
			$this->_loaderOptions['filter'] = YUI_DEBUG;
			$this->_headModules[] = 'logger';
		}
	}

	/**
	 * Initialize the loader
	 *
	 * @return void
	 **/
	function _init() {
		$this->_debugSettings();

		$this->_loader = new YAHOO_util_Loader($this->version);

		foreach ($this->_loaderOptions as $key => $value) {
			if(isset($this->_loaderOptions[$key])) { //so we gloss over nulls...
				$this->_loader->$key = $value;
			}
		}

	}

	/**
	 * Set up the queue up the modules to load.
	 *
	 * @return void
	 **/
	function load($modules = array(), $loaderOptions = array()) {
		if(is_string($modules)) {
			$modules = array($modules);
		}

		$this->_modules = array_merge($this->_modules, $modules);
		$this->_loaderOptions = array_merge($this->_loaderOptions, $loaderOptions);

	}

	/**
	 * General rule, we want the bare min scripts in the head.
	 * for yui 2 this generally consists of yahoo, dom, event.
	 *
	 * @return string
	 **/
	function headJs() {

		if(!isset($this->_loader)) { $this->_init(); }
		call_user_func_array(array($this->_loader, 'load'), $this->_headModules);
		$scripts = $this->_loader->script();
		return $this->output($scripts);
	}

	/**
	 * Output the CSS tags for the headers.
	 *
	 * @return string
	 **/
	function css() {
		if(!isset($this->_loader)) { $this->_init(); }

		call_user_func_array(array($this->_loader, 'load'), $this->_modules);
		return $this->output($this->_loader->css());
	}

	/**
	 * Output the JS tags for the footer.
	 *
	 * @return string
	 **/
	function footJs() {
		if(!isset($this->_loader)) { $this->_init(); }

		//so if headJs never called, it will still include them.
		//If it's already been included, should ignore them.
		//(Currently there's a bug in combine where they won't be ignored.  - bug opened with Y!)
		$modules = array_merge($this->_modules, $this->_headModules);
		call_user_func_array(array($this->_loader, 'load'), $modules);
		return $this->output($this->_loader->script());
	}

}


