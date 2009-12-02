<?php
/**
 * YUI Helper class test
 * 
 * Right now, only tests against yui v2.8
 * 
 * @package cake.plugins.yui
 * @author Mitchell Amihod
 **/
App::import('Helper', 'Yui.Yui');

class YuiTestCase extends CakeTestCase {

	function setUp() {
		ClassRegistry::flush();
	}

	function startTest() {
		$this->yui = new YuiHelper();
	}

	function endTest() {
		unset($this->yui);
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 **/
	function testCss() {
		Configure::write('Yui.debug', 0);
		$this->yui->reset();
		$this->yui->load(array('reset'));
		
		$expected = '<link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/combo?2.8.0/build/reset/reset-min.css" />';
		$result = $this->yui->css();
		$this->assertEqual($result, $expected);
	}

	/**
	 * Test header script generation
	 *
	 * @return void
	 **/
	function testHeadJs() {
		Configure::write('Yui.debug', 0);
		$expected = '<script type="text/javascript" src="http://yui.yahooapis.com/combo?2.8.0/build/yahoo/yahoo-min.js&2.8.0/build/dom/dom-min.js&2.8.0/build/event/event-min.js&2.8.0/build/selector/selector-min.js"></script>';
		$result = $this->yui->headJs();
		$this->assertEqual($result, $expected);
	}


	/**
	 * Test footer script. 
	 *
	 * @return void
	 **/
	function testFootJs() {
		Configure::write('Yui.debug', 0);
		
		$this->yui->reset();
		//When nothing is loaded, this should be equiv as headJs()
		$this->yui->load();
		$expected = '<script type="text/javascript" src="http://yui.yahooapis.com/combo?2.8.0/build/yahoo/yahoo-min.js&2.8.0/build/dom/dom-min.js&2.8.0/build/event/event-min.js&2.8.0/build/selector/selector-min.js"></script>';
		$result = $this->yui->footJs();
		$this->assertEqual($result, $expected);
	}

	/**
	 * Test debug settings using headJs call. 
	 *
	 * @return void
	 **/
	function testDebugSettings() {
		
		//Remove the loader, start again...
		$this->yui->reset();		
		Configure::write('Yui.debug', 1);//With DEBUG ON, but still minned
		$expected = '<script type="text/javascript" src="http://yui.yahooapis.com/2.8.0/build/yahoo/yahoo-min.js"></script>' . "\n"
			. '<script type="text/javascript" src="http://yui.yahooapis.com/2.8.0/build/dom/dom-min.js"></script>' ."\n"
			. '<script type="text/javascript" src="http://yui.yahooapis.com/2.8.0/build/event/event-min.js"></script>' . "\n"
			. '<script type="text/javascript" src="http://yui.yahooapis.com/2.8.0/build/selector/selector-min.js"></script>' . "\n";
		$result = $this->yui->headJs();
		$this->assertEqual($result, $expected);

		$this->yui->reset();		
		Configure::write('Yui.debug', 2);//non-min
		$expected = '<script type="text/javascript" src="http://yui.yahooapis.com/2.8.0/build/yahoo/yahoo.js"></script>' . "\n"
			. '<script type="text/javascript" src="http://yui.yahooapis.com/2.8.0/build/dom/dom.js"></script>' ."\n"
			. '<script type="text/javascript" src="http://yui.yahooapis.com/2.8.0/build/event/event.js"></script>' . "\n"
			. '<script type="text/javascript" src="http://yui.yahooapis.com/2.8.0/build/selector/selector.js"></script>' . "\n";
		$result = $this->yui->headJs();
		$this->assertEqual($result, $expected);

		$this->yui->reset();		
		Configure::write('Yui.debug', 3);//-debug
		$expected = '<script type="text/javascript" src="http://yui.yahooapis.com/2.8.0/build/yahoo/yahoo-debug.js"></script>' . "\n"
			. '<script type="text/javascript" src="http://yui.yahooapis.com/2.8.0/build/dom/dom-debug.js"></script>' ."\n"
			. '<script type="text/javascript" src="http://yui.yahooapis.com/2.8.0/build/event/event-debug.js"></script>' . "\n"
			. '<script type="text/javascript" src="http://yui.yahooapis.com/2.8.0/build/selector/selector-debug.js"></script>' . "\n";
		$result = $this->yui->headJs();
		$this->assertEqual($result, $expected);

		Configure::write('Yui.debug', 0);

	}

	/**
	 * Check loader class loaded
	 * 
	 * @access public
	 * @return void
	 **/
	function testLoaderExists() {
		$this->assertTrue(class_exists('YAHOO_util_Loader'));
	}

}
