<?php
class AllSearchPluginTest extends PHPUnit_Framework_TestSuite {

/**
 * Suite define the tests for this suite
 *
 * @return void
 */
	public static function suite() {
		$suite = new PHPUnit_Framework_TestSuite('All Search Plugin Tests');

		$basePath = CakePlugin::path('Search') . DS . 'Test' . DS . 'Case' . DS;
		// components
		$suite->addTestFile($basePath . 'Controller' . DS . 'Component' . DS . 'PrgComponentTest.php');

		// behaviors
		$suite->addTestFile($basePath . 'Model' . DS . 'Behavior' . DS . 'SearchableBehaviorTest.php');

		return $suite;
	}

}