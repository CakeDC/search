<?php
/**
 * Copyright (c) Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Development Corporation (http://cakedc.com)
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * AllSearchPluginTest test suite
 */
class AllSearchPluginTest extends PHPUnit_Framework_TestSuite {

/**
 * Compile test suite with all tests
 *
 * @return CakeTestSuite The compiled test suite.
 */
	public static function suite() {
		$Suite = new CakeTestSuite('All Plugin tests');
		$path = dirname(__FILE__);
		$Suite->addTestDirectory($path . DS . 'Controller' . DS . 'Component');
		$Suite->addTestDirectory($path . DS . 'Model' . DS . 'Behavior');
		return $Suite;
	}

}
