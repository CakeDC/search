<?php
/**
 * Copyright 2009-2010, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2009-2010, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::import('Controller', 'Controller', false);
App::import('Component', 'Search.Prg');

/**
 * Post-Redirect-Get: Transfers POST Requests to GET Requests tests
 *
 * @package search
 * @subpackage search.tests.cases.components
 */
class Post extends CakeTestModel {

/**
 * Name
 *
 * @var string
 */
	public $name = 'Post';

/**
 * Behaviors
 *
 * @var array
 */
	public $actsAs = array('Search.Searchable');
}

/**
 * PostsTest Controller
 *
 * @package search
 * @subpackage search.tests.cases.components
 */
class PostsTestController extends Controller {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'PostsTest';

/**
 * Models to use
 *
 * @var array
 */
	public $uses = array('Post');

/**
 * Components
 *
 * @var array
 */
	public $components = array('Search.Prg', 'Session');

/**
 * beforeFilter
 *
 * @return void
 */
	public function beforeFilter() {
		parent::beforeFilter();
		$this->Prg->actions = array(
			'search' => array(
				'controller' => 'Posts',
				'action' => 'result'));
	}

/**
 * Overwrite redirect
 *
 * @param string $url 
 * @param string $status 
 * @param string $exit 
 * @return void
 */
	public function redirect($url, $status = NULL, $exit = true) {
		$this->redirectUrl = $url;
	}
}

/**
 * Posts Options Test Controller
 *
 * @package search
 * @subpackage search.tests.cases.components
 */
class PostOptionsTestController extends PostsTestController {

/**
 * Components
 *
 * @var array
 */
	public $components = array(
		'Search.Prg' => array(
			'commonProcess' => array(
				'form' => 'Post',
				'modelMethod' => false,
				'allowedParams' => array('lang'))),
		'Session'
	);
}

/**
 * PRG Component Test
 *
 * @package search
 * @subpackage search.tests.cases.components
 */
class PrgComponentTest extends CakeTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array('plugin.search.post');

/**
 * startTest
 *
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
 * endTest
 *
 * @return void
 */
	function endTest() {
		unset($this->Controller);
		ClassRegistry::flush();
	}

/**
 * testOptions
 *
 * @return void
 */
	public function testOptions() {
		$this->Controller = new PostOptionsTestController();
		$this->Controller->constructClasses();
		$this->Controller->params = array(
			'named' => array(),
			'pass' => array(),
			'url' => array());
		$this->Controller->Component->init($this->Controller);
		$this->Controller->Component->initialize($this->Controller);
		$this->Controller->presetVars = array();
		$this->Controller->action = 'search';
		$this->Controller->data = array(
			'Post' => array(
				'title' => 'test'));

		$this->Controller->Prg->commonProcess('Post');
		$this->assertEqual($this->Controller->redirectUrl, array(
			'title' => 'test',
			'action' => 'search'));

		$this->Controller->params = array_merge($this->Controller->params, array(
			'lang' => 'en',
			));
		$this->Controller->Prg->commonProcess('Post');
		$this->assertEqual($this->Controller->redirectUrl, array(
			'title' => 'test',
			'action' => 'search',
			'lang' => 'en'));
	}

/**
 * test
 *
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
 * This test checks that the search on an integer type field in the database
 * works correctly when a 0 (zero) is entered in the form.
 *
 * @return void
 * @link http://github.com/CakeDC/Search/issues#issue/3
 */
	public function testPresetFormWithIntegerField() {
		$this->Controller->presetVars = array(
			array(
				'field' => 'views',
				'type' => 'value'));
		$this->Controller->passedArgs = array(
			'views' => '0');
		$this->Controller->Component->init($this->Controller);
		$this->Controller->Component->initialize($this->Controller);
		$this->Controller->beforeFilter();
		ClassRegistry::addObject('view', new View($this->Controller));

		$this->Controller->Prg->presetForm('Post');
		$expected = array(
			'Post' => array(
				'views' => '0'));
		$this->assertEqual($this->Controller->data, $expected);
	}

/**
 * testFixFormValues
 *
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

		$testData = array();
		$result = $this->Controller->Prg->serializeParams($testData);
		$this->assertEqual($result, array('options' => ''));
	}

/**
 * testConnectNamed
 *
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

/**
 * testCommonProcessExtraParams
 *
 * @return void
 */
	public function testCommonProcessAllowedParams() {
		$this->Controller->params = array_merge($this->Controller->params, array(
			'named' => array(),
			'lang' => 'en',
			));
		$this->Controller->Component->init($this->Controller);
		$this->Controller->Component->initialize($this->Controller);
		$this->Controller->presetVars = array();
		$this->Controller->action = 'search';
		$this->Controller->data = array(
			'Post' => array(
				'title' => 'test'));

		$this->Controller->Prg->commonProcess('Post', array(
			'form' => 'Post',
			'modelMethod' => false,
			'allowedParams' => array('lang')));
		$this->assertEqual($this->Controller->redirectUrl, array(
			'title' => 'test',
			'action' => 'search',
			'lang' => 'en'));
	}

/**
 * testCommonProcessGet
 *
 * @return void
 */
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

/**
 * testSerializeParamsWithEncoding
 *
 * @return void
 */
	public function testSerializeParamsWithEncoding() {
		$this->Controller->Component->init($this->Controller);
		$this->Controller->Component->initialize($this->Controller);
		$this->Controller->action = 'search';
		$this->Controller->presetVars = array(
			array('field' => 'title', 'type' => 'value', 'encode' => true));
		$this->Controller->data = array();
		$this->Controller->Post->filterArgs = array(
			array('name' => 'title', 'type' => 'value'));

		$this->Controller->Prg->encode = true;
		$test = array('title' => 'Something new');
		$result = $this->Controller->Prg->serializeParams($test);
		$this->assertEqual($result['title'], base64_encode('Something new'));
	}

/**
 * testPresetFormWithEncodedParams
 *
 * @return void
 */
	public function testPresetFormWithEncodedParams() {
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
			'title' => base64_encode('test'),
			'checkbox' => base64_encode('test|test2|test3'),
			'lookup' => base64_encode('1'));

		$this->Controller->Component->init($this->Controller);
		$this->Controller->Component->initialize($this->Controller);
		$this->Controller->beforeFilter();
		ClassRegistry::addObject('view', new View($this->Controller));

		$this->Controller->Prg->encode = true;
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
}
