<?php
/**
 * CakePHP Tags Plugin
 *
 * Copyright 2009 - 2010, Cake Development Corporation
 *                        1785 E. Sahara Avenue, Suite 490-423
 *                        Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright 2009 - 2010, Cake Development Corporation (http://cakedc.com)
 * @link      http://github.com/CakeDC/Search
 * @package   plugins.search
 * @license   MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Post-Redirect-Get: Transfers POST Requests to GET Requests tests
 *
 * @package		plugins.search
 * @subpackage	plugins.search.tests.cases.components
 */

App::import('Controller', 'Controller', false);
App::import('Component', 'Search.Prg');

class Post extends CakeTestModel {
/**
 * 
 */
	public $name = 'Post';
	
	public $actsAs = array('Search.Searchable');
}

class PostsTestController extends Controller {

/**
 * @var string
 * @access public
 */
	public $name = 'PostsTest';

/**
 * @var array
 * @access public
 */
	public $uses = array('Post');

/**
 * @var array
 * @access public
 */
	public $components = array('Search.Prg', 'Session');

/**
 * 
 */
	public function beforeFilter() {
		parent::beforeFilter();
		$this->Prg->actions = array(
			'search' => array(
				'controller' => 'Posts',
				'action' => 'result'));
	}

/**
 * 
 */
	public function redirect($url, $status = NULL, $exit = true) {
		$this->redirectUrl = $url;
	}

}


class PrgComponentTest extends CakeTestCase {
/**
 * Fixtures
 *
 * @var array
 * @access public
 */
	public $fixtures = array(
		'plugin.search.Post');
/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function startTest() {
		$this->Controller = new PostsTestController();
		$this->Controller->constructClasses();
		$this->Controller->params = array(
			'named' => array(),
			'pass' => array(),
			'url' => array()); 
	}

/**
 * tearDown method
 *
 * @access public
 * @return void
 */
	function endTest() {
		unset($this->Controller);
		ClassRegistry::flush();
	}

/**
 * test
 *
 * @access public
 * @return void
 */
	public function testPresetForm() {
		$this->Controller->presetVars = array(
			array(
				'field' => 'title',
				'type' => 'value'),
			array(
				'field' => 'checkbox',
				'type' => 'checkbox'),
			array(
				'field' => 'lookup',
				'type' => 'lookup',
				'formField' => 'lookup_input',
				'modelField' => 'title',
				'model' => 'Post'));
		$this->Controller->passedArgs = array(
			'title' => 'test',
			'checkbox' => 'test|test2|test3',
			'lookup' => '1');
		$this->Controller->Component->init($this->Controller);
		$this->Controller->Component->initialize($this->Controller);
		$this->Controller->beforeFilter();
		ClassRegistry::addObject('view', new View($this->Controller));

		$this->Controller->Prg->presetForm('Post');
		$expected = array(
			'Post' => array(
				'title' => 'test',
				'checkbox' => array(
					0 => 'test',
					1 => 'test2',
					2 => 'test3'),
				'lookup' => 1,
				'lookup_input' => 'First Post'));
		$this->assertEqual($this->Controller->data, $expected);
	}

/**
 * testFixFormValues
 *
 * @access public
 * @return void
 */
	public function testSerializeParams() {
		$this->Controller->presetVars = array(
			array(
				'field' => 'options',
				'type' => 'checkbox'));

		$this->Controller->Component->init($this->Controller);
		$this->Controller->Component->initialize($this->Controller);

		$testData = array(
			'options' => array(
				0 => 'test1', 1 => 'test2', 2 => 'test3'));

		$result = $this->Controller->Prg->serializeParams($testData);
		$this->assertEqual($result, array('options' => 'test1|test2|test3'));

		$testData = array('options' => '');

		$result = $this->Controller->Prg->serializeParams($testData);
		$this->assertEqual($result, array('options' => ''));
	}

/**
 * testConnectNamed
 *
 * @access public
 * @return void
 */
	public function testConnectNamed() {
		$this->Controller->passedArgs = array(
			'title' => 'test');
		$this->Controller->Component->init($this->Controller);
		$this->Controller->Component->initialize($this->Controller);
		$this->assertFalse($this->Controller->Prg->connectNamed());
		$this->assertFalse($this->Controller->Prg->connectNamed(1));
	}

/**
 * testExclude
 *
 * @access public
 * @return void
 */
	public function testExclude() {
		$this->Controller->params['named'] = array();
		$this->Controller->Component->init($this->Controller);
		$this->Controller->Component->initialize($this->Controller);

		$array = array('foo' => 'test', 'bar' => 'test', 'test' => 'test');
		$exclude = array('bar', 'test');
		$this->assertEqual($this->Controller->Prg->exclude($array, $exclude), array('foo' => 'test'));
	}

/**
 * testCommonProcess
 *
 * @access public
 * @return void
 */
	public function testCommonProcess() {
		$this->Controller->params['named'] = array();
		$this->Controller->Component->init($this->Controller);
		$this->Controller->Component->initialize($this->Controller);
		$this->Controller->presetVars = array();
		$this->Controller->action = 'search';
		$this->Controller->data = array(
			'Post' => array(
				'title' => 'test'));
		$this->Controller->Prg->commonProcess('Post', array(
			'form' => 'Post',
			'modelMethod' => false));
		$this->assertEqual($this->Controller->redirectUrl, array(
			'title' => 'test',
			'action' => 'search'));
			
		$this->Controller->Prg->commonProcess(null, array(
			'modelMethod' => false));
		$this->assertEqual($this->Controller->redirectUrl, array(
			'title' => 'test',
			'action' => 'search'));

		$this->Controller->Post->filterArgs = array(
			array('name' => 'title', 'type' => 'value'));
		$this->Controller->Prg->commonProcess('Post');
		$this->assertEqual($this->Controller->redirectUrl, array(
			'title' => 'test',
			'action' => 'search'));
	}

	public function testCommonProcessGet() {
		$this->Controller->Component->init($this->Controller);
		$this->Controller->Component->initialize($this->Controller);
		$this->Controller->action = 'search';
		$this->Controller->presetVars = array(
			array('field' => 'title', 'type' => 'value'));
		$this->Controller->data = array();
		$this->Controller->Post->filterArgs = array(
			array('name' => 'title', 'type' => 'value'));
		$this->Controller->params['named'] = array('title' => 'test');
		$this->Controller->passedArgs = array_merge($this->Controller->params['named'], $this->Controller->params['pass']);
		$this->Controller->Prg->commonProcess('Post');
		$this->assertEqual($this->Controller->data, array('Post' => array('title' => 'test')));			
	}

}
?>