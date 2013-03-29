<?php
/**
 * Copyright 2009 - 2013, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2009 - 2013, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Controller', 'Controller');
App::uses('Component', 'Search.Prg');
App::uses('Router', 'Routing');

/**
 * Post-Redirect-Get: Transfers POST Requests to GET Requests tests
 *
 * @package search
 * @subpackage search.tests.cases.components
 */
class Post extends CakeTestModel {

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
	public function redirect($url, $status = null, $exit = true) {
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
	public function setUp() {
		parent::setUp();

		$this->Controller = new PostsTestController(new CakeRequest(), new CakeResponse());
		$this->Controller->constructClasses();
		$this->Controller->request->params = array(
			'named' => array(),
			'pass' => array(),
			'url' => array());
		$this->Controller->request->query = array();
	}

/**
 * endTest
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Controller);
		ClassRegistry::flush();

		parent::tearDown();
	}

/**
 * testOptions
 *
 * @return void
 */
	public function testOptions() {
		$this->Controller->presetVars = array();
		$this->Controller->action = 'search';
		$this->Controller->request->data = array(
			'Post' => array(
				'title' => 'test'));

		$this->Controller->Prg->commonProcess('Post');
		$this->assertEquals($this->Controller->redirectUrl, array(
			'title' => 'test',
			'action' => 'search'));

		$this->Controller->request->params = array_merge($this->Controller->request->params, array(
			'lang' => 'en',
			));
		$this->Controller->Prg->commonProcess('Post', array('allowedParams' => array('lang')));
		$this->assertEquals($this->Controller->redirectUrl, array(
			'title' => 'test',
			'action' => 'search',
			'lang' => 'en'));

		$this->Controller->presetVars = array(
			array('field' => 'title', 'type' => 'value'));
		$this->Controller->Prg->commonProcess('Post', array('paramType' => 'querystring'));
		$expected = array('action' => 'search', '?' => array('title' => 'test'));
		$this->assertEquals($expected, $this->Controller->redirectUrl);
	}

/**
 * testPresetForm
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
		$this->Controller->beforeFilter();

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
		$this->assertEquals($this->Controller->request->data, $expected);

		$this->Controller->data = array();
		$this->Controller->passedArgs = array();
		$this->Controller->request->query = array(
			'title' => 'test',
			'checkbox' => 'test|test2|test3',
			'lookup' => '1');
		$this->Controller->beforeFilter();

		$this->Controller->Prg->presetForm(array('model' => 'Post', 'paramType' => 'querystring'));
		$this->assertEquals($expected, $this->Controller->data);
		$this->assertTrue($this->Controller->Prg->isSearch);
	}

/**
 * testPresetFormEmpty
 *
 * @return void
 */
	public function testPresetFormEmpty() {
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
			'page' => '2');
		$this->Controller->beforeFilter();

		$this->Controller->Prg->presetForm('Post');
		$expected = array(
			'Post' => array());
		$this->assertEquals($this->Controller->request->data, $expected);

		$this->Controller->data = array();
		$this->Controller->passedArgs = array();
		$this->Controller->request->query = array(
			'page' => '2');
		$this->Controller->beforeFilter();

		$this->Controller->Prg->presetForm(array('model' => 'Post', 'paramType' => 'querystring'));
		$this->assertEquals($expected, $this->Controller->data);
		$this->assertFalse($this->Controller->Prg->isSearch);
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
		$this->Controller->beforeFilter();

		$this->Controller->Prg->presetForm('Post');
		$expected = array(
			'Post' => array(
				'views' => '0'));
		$this->assertEquals($this->Controller->request->data, $expected);
		$this->assertTrue($this->Controller->Prg->isSearch);
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

		$testData = array(
			'options' => array(
				0 => 'test1', 1 => 'test2', 2 => 'test3'));

		$result = $this->Controller->Prg->serializeParams($testData);
		$this->assertEquals(array('options' => 'test1|test2|test3'), $result);

		$testData = array('options' => '');

		$result = $this->Controller->Prg->serializeParams($testData);
		$this->assertEquals(array('options' => ''), $result);

		$testData = array();
		$result = $this->Controller->Prg->serializeParams($testData);
		$this->assertEquals(array('options' => ''), $result);
	}

/**
 * testConnectNamed
 *
 * @return void
 */
	public function testConnectNamed() {
		$this->Controller->passedArgs = array(
			'title' => 'test');
		$this->assertTrue(is_null($this->Controller->Prg->connectNamed()));
		$this->assertTrue(is_null($this->Controller->Prg->connectNamed(1)));
	}

/**
 * testExclude
 *
 * @return void
 */
	public function testExclude() {
		$this->Controller->request->params['named'] = array();

		$array = array('foo' => 'test', 'bar' => 'test', 'test' => 'test');
		$exclude = array('bar', 'test');
		$result = $this->Controller->Prg->exclude($array, $exclude);
		$this->assertEquals(array('foo' => 'test'), $result);

		$array = array('foo' => 'test', 'bar' => 'test', 'test' => 'test', 0 => 'passed', 1 => 'passed_again');
		$exclude = array('bar', 'test');
		$result = $this->Controller->Prg->exclude($array, $exclude);
		$this->assertEquals(array('foo' => 'test', 0 => 'passed', 1 => 'passed_again'), $result);
	}

/**
 * testCommonProcess
 *
 * @return void
 */
	public function testCommonProcess() {
		$this->Controller->request->params['named'] = array();
		$this->Controller->presetVars = array();
		$this->Controller->action = 'search';
		$this->Controller->request->data = array(
			'Post' => array(
				'title' => 'test'));
		$this->Controller->Prg->commonProcess('Post', array(
			'form' => 'Post',
			'modelMethod' => false));
		$expected = array(
			'title' => 'test',
			'action' => 'search');
		$this->assertEquals($expected, $this->Controller->redirectUrl);

		$this->Controller->Prg->commonProcess(null, array(
			'modelMethod' => false));
		$expected = array(
			'title' => 'test',
			'action' => 'search');
		$this->assertEquals($expected, $this->Controller->redirectUrl);

		$this->Controller->Post->filterArgs = array(
			array('name' => 'title', 'type' => 'value'));
		$this->Controller->Prg->commonProcess('Post');
		$expected = array(
			'title' => 'test',
			'action' => 'search');
		$this->assertEquals($expected, $this->Controller->redirectUrl);
	}

/**
 * testCommonProcessExtraParams
 *
 * @return void
 */
	public function testCommonProcessAllowedParams() {
		$this->Controller->request->params = array_merge($this->Controller->request->params, array(
			'named' => array(),
			'lang' => 'en',
			));

		$this->Controller->presetVars = array();
		$this->Controller->action = 'search';
		$this->Controller->request->data = array(
			'Post' => array(
				'title' => 'test'));

		$this->Controller->Prg->commonProcess('Post', array(
			'form' => 'Post',
			'modelMethod' => false,
			'allowedParams' => array('lang')));
		$expected = array(
			'title' => 'test',
			'action' => 'search',
			'lang' => 'en');
		$this->assertEquals($expected, $this->Controller->redirectUrl);
	}

/**
 * testCommonProcessFilterEmpty
 *
 * @return void
 */
	public function testCommonProcessResetNamed() {
		$this->Controller->request->params = array_merge($this->Controller->request->params, array(
			'named' => array('page' => 2, 'sort' => 'name', 'direction' => 'asc'),
			'lang' => 'en',
			));

		$this->Controller->presetVars = array();
		$this->Controller->action = 'search';
		$this->Controller->request->data = array(
			'Post' => array(
				'title' => 'test',
				'foo' => '',
				'bar' => ''));

		$this->Controller->Prg->commonProcess('Post', array(
			'form' => 'Post',
			'modelMethod' => false,
			'allowedParams' => array('lang')));
		$expected = array(
			'sort' => 'name',
			'direction' => 'asc',
			'title' => 'test',
			'foo' => '',
			'bar' => '',
			'action' => 'search',
			'lang' => 'en');
		$this->assertEquals($expected, $this->Controller->redirectUrl);
	}

/**
 * testCommonProcessFilterEmpty
 *
 * @return void
 */
	public function testCommonProcessFilterEmpty() {
		$this->Controller->request->params = array_merge($this->Controller->request->params, array(
			'named' => array(),
			'lang' => 'en',
			));

		$this->Controller->presetVars = array();
		$this->Controller->action = 'search';
		$this->Controller->request->data = array(
			'Post' => array(
				'title' => 'test',
				'foo' => '',
				'bar' => ''));

		$this->Controller->Prg->commonProcess('Post', array(
			'form' => 'Post',
			'modelMethod' => false,
			'filterEmpty' => true,
			'allowedParams' => array('lang')));
		$expected = array(
			'title' => 'test',
			'action' => 'search',
			'lang' => 'en');
		$this->assertEquals($expected, $this->Controller->redirectUrl);
	}

/**
 * testCommonProcessSpecialChars
 *
 * @return void
 */
	public function testCommonProcessSpecialChars() {
		$this->Controller->request->params = array_merge($this->Controller->request->params, array(
			'named' => array(),
			'lang' => 'en',
			));

		$this->Controller->presetVars = array();
		$this->Controller->action = 'search';
		$this->Controller->request->data = array(
			'Post' => array(
				'title' => 'test/slashes?!',
				'foo' => '',
				'bar' => ''));

		$this->Controller->Prg->commonProcess('Post', array(
			'form' => 'Post',
			'modelMethod' => false,
			'filterEmpty' => true,
			'allowedParams' => array('lang')));
		$expected = array(
			'title' => 'test/slashes?!',
			'action' => 'search',
			'lang' => 'en');
		$this->assertEquals($expected, $this->Controller->redirectUrl);

		$url = Router::url($this->Controller->redirectUrl);
		$expected = '/search/title:test%2Fslashes%3F%21/lang:en';
		$this->assertEquals($expected, $url);
	}

/**
 * testCommonProcessQuerystring
 *
 * @return void
 */
	public function testCommonProcessQuerystring() {
		$this->Controller->request->params = array_merge($this->Controller->request->params, array(
			'named' => array(),
			'lang' => 'en',
			));

		$this->Controller->presetVars = array();
		$this->Controller->action = 'search';
		$this->Controller->request->data = array(
			'Post' => array(
				'title' => 'test',
				'foo' => '',
				'bar' => ''));

		$this->Controller->Prg->commonProcess('Post', array(
			'form' => 'Post',
			'modelMethod' => false,
			'paramType' =>'querystring',
			'allowedParams' => array('lang')));
		$expected = array(
			'?' => array('title' => 'test', 'foo' => '', 'bar' => ''),
			'action' => 'search',
			'lang' => 'en');
		$this->assertEquals($expected, $this->Controller->redirectUrl);
	}

/**
 * testCommonProcessQuerystringSpecialChars
 *
 * @return void
 */
	public function testCommonProcessQuerystringSpecialChars() {
		$this->Controller->request->params = array_merge($this->Controller->request->params, array(
			'named' => array(),
			'lang' => 'en',
			));

		$this->Controller->presetVars = array();
		$this->Controller->action = 'search';
		$this->Controller->request->data = array(
			'Post' => array(
				'title' => 'test/slashes?!',
				'foo' => '',
				'bar' => ''));

		$this->Controller->Prg->commonProcess('Post', array(
			'form' => 'Post',
			'modelMethod' => false,
			'filterEmpty' => true,
			'paramType' =>'querystring',
			'allowedParams' => array('lang')));
		$expected = array(
			'?' => array('title' => 'test/slashes?!'),
			'action' => 'search',
			'lang' => 'en');
		$this->assertEquals($expected, $this->Controller->redirectUrl);

		$url = Router::url($this->Controller->redirectUrl);
		$expected = '/search/lang:en?title=test%2Fslashes%3F%21';
		$this->assertEquals($expected, $url);
	}

/**
 * testCommonProcessQuerystringPagination
 *
 * @return void
 */
	public function testCommonProcessQuerystringPagination() {
		$this->Controller->request->query = array(
			'sort' => 'created',
			'direction' => 'asc',
			'page' => 3,
		);
		$this->Controller->request->params = array_merge($this->Controller->request->params, array(
			'named' => array(),
			'lang' => 'en',
			));

		$this->Controller->presetVars = array();
		$this->Controller->action = 'search';
		$this->Controller->request->data = array(
			'Post' => array(
				'title' => 'test',
				'foo' => '',
				'bar' => ''));

		$this->Controller->Prg->commonProcess('Post', array(
			'form' => 'Post',
			'modelMethod' => false,
			'paramType' =>'querystring',
			'allowedParams' => array('lang')));
		$expected = array(
			'?' => array('title' => 'test', 'foo' => '', 'bar' => '', 'sort' => 'created', 'direction' => 'asc'),
			'action' => 'search',
			'lang' => 'en');
		$this->assertEquals($expected, $this->Controller->redirectUrl);
	}

/**
 * testCommonProcessQuerystringFilterEmpty
 *
 * @return void
 */
	public function testCommonProcessQuerystringFilterEmpty() {
		$this->Controller->request->params = array_merge($this->Controller->request->params, array(
			'named' => array(),
			'lang' => 'en',
			));

		$this->Controller->presetVars = array();
		$this->Controller->action = 'search';
		$this->Controller->request->data = array(
			'Post' => array(
				'title' => 'test',
				'foo' => '',
				'bar' => ''));

		$this->Controller->Prg->commonProcess('Post', array(
			'form' => 'Post',
			'modelMethod' => false,
			'filterEmpty' => true,
			'paramType' =>'querystring',
			'allowedParams' => array('lang')));
		$expected = array(
			'?' => array('title' => 'test'),
			'action' => 'search',
			'lang' => 'en');
		$this->assertEquals($expected, $this->Controller->redirectUrl);
	}

/**
 * testCommonProcessGet
 *
 * @return void
 */
	public function testCommonProcessGet() {
		$this->Controller->action = 'search';
		$this->Controller->presetVars = array(
			array('field' => 'title', 'type' => 'value'));
		$this->Controller->request->data = array();
		$this->Controller->Post->filterArgs = array(
			array('name' => 'title', 'type' => 'value'));
		$this->Controller->request->params['named'] = array('title' => 'test');
		$this->Controller->passedArgs = array_merge($this->Controller->request->params['named'], $this->Controller->request->params['pass']);
		$this->Controller->Prg->commonProcess('Post');
		$this->assertEquals($this->Controller->request->data, array('Post' => array('title' => 'test')));
		$this->assertTrue($this->Controller->Prg->isSearch);
	}

	public function testCommonProcessGetWithStringKeys() {
		$this->Controller->action = 'search';
		$this->Controller->presetVars = array(
			'title' => array('type' => 'value'));
		$this->Controller->Post->filterArgs = array(
			'title' => array('type' => 'value'));

		$this->Controller->Prg->__construct($this->Controller->Components, array());
		$this->Controller->request->data = array();

		$this->Controller->request->params['named'] = array('title' => 'test');
		$this->Controller->passedArgs = array_merge($this->Controller->request->params['named'], $this->Controller->request->params['pass']);
		$this->Controller->Prg->commonProcess('Post');
		$this->assertEquals(array('Post' => array('title' => 'test')), $this->Controller->request->data);
	}

	public function testCommonProcessGetWithStringKeysShort() {
		$this->Controller->action = 'search';
		$this->Controller->presetVars = array(
			'title' => true);
		$this->Controller->Post->filterArgs = array(
			'title' => array('type' => 'value'));

		$this->Controller->Prg->__construct($this->Controller->Components, array());
		$this->Controller->request->data = array();

		$this->Controller->request->params['named'] = array('title' => 'test');
		$this->Controller->passedArgs = array_merge($this->Controller->request->params['named'], $this->Controller->request->params['pass']);
		$this->Controller->Prg->commonProcess('Post');
		$this->assertEquals(array('Post' => array('title' => 'test')), $this->Controller->request->data);
	}

/**
 * testSerializeParamsWithEncoding
 *
 * @return void
 */
	public function testSerializeParamsWithEncoding() {
		$this->Controller->action = 'search';
		$this->Controller->presetVars = array(
			array('field' => 'title', 'type' => 'value', 'encode' => true));
		$this->Controller->request->data = array();
		$this->Controller->Post->filterArgs = array(
			array('name' => 'title', 'type' => 'value'));

		$this->Controller->Prg->encode = true;
		$test = array('title' => 'Something new');
		$result = $this->Controller->Prg->serializeParams($test);
		$this->assertEquals($this->_urlEncode('Something new'), $result['title']);

		$test = array('title' => 'ef?');
		$result = $this->Controller->Prg->serializeParams($test);
		$this->assertEquals($this->_urlEncode('ef?'), $result['title']);
	}

/**
 * replace the base64encoded values that could harm the url (/ and =) with harmless characters
 *
 * @return string
 */
	protected function _urlEncode($str) {
		return str_replace(array('/', '='), array('-', '_'), base64_encode($str));
	}

/**
 * testSerializeParamsWithEncoding
 *
 * @return void
 */
	public function testSerializeParamsWithEncodingAndSpace() {
		$this->Controller->action = 'search';
		$this->Controller->presetVars = array(
			array('field' => 'title', 'type' => 'value', 'encode' => true));
		$this->Controller->request->data = array();
		$this->Controller->Post->filterArgs = array(
			array('name' => 'title', 'type' => 'value'));

		$this->Controller->Prg->encode = true;
		$testData = $test = array('title' => 'Something new');
		$result = $this->Controller->Prg->serializeParams($test);
		$this->assertEquals($this->_urlEncode('Something new'), $result['title']);

		$this->Controller->passedArgs = $result;
		$this->Controller->Prg->presetForm('Post');
		$this->assertEquals(array('Post' => $testData), $this->Controller->request->data);
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
			'title' => $this->_urlEncode('test'),
			'checkbox' => $this->_urlEncode('test|test2|test3'),
			'lookup' => $this->_urlEncode('1'));

		$this->Controller->beforeFilter();

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
		$this->assertEquals($this->Controller->request->data, $expected);
		$this->assertTrue($this->Controller->Prg->isSearch);
	}

/**
 * testCommonProcessGetWithEmptyValue
 *
 * @return void
 */
	public function testCommonProcessGetWithEmptyValue() {
		$this->Controller->request->params = array_merge($this->Controller->request->params, array(
			'named' => array(),
			'category_id' => '0',
			));

		$this->Controller->presetVars = array(
			array(
				'field' => 'category_id',
				'name' => 'category_id',
				'type' => 'value',
				'allowEmpty' => true,
				'emptyValue' => '0',
			),
			array(
				'field' => 'checkbox',
				'name' => 'checkbox',
				'type' => 'checkbox'
			),
		);
		$this->Controller->action = 'search';
		$this->Controller->request->data = array(
			'Post' => array(
				'category_id' => '0',
				'foo' => ''));

		$this->Controller->Prg->commonProcess('Post', array(
			'form' => 'Post',
			'modelMethod' => false,
			'filterEmpty' => true));
		$expected = array(
			'action' => 'search',
			'category_id' => '');
		$this->assertEquals($expected, $this->Controller->redirectUrl);
	}

/**
 * testPresetFormWithEmptyValue
 *
 * @return void
 */
	public function testPresetFormWithEmptyValue() {
		$this->Controller->presetVars = array(
			array(
				'field' => 'category_id',
				'type' => 'value',
				'allowEmpty' => true,
				'emptyValue' => '0',
			),
			array(
				'field' => 'checkbox',
				'type' => 'checkbox'
			),
		);
		$this->Controller->passedArgs = array(
			'category_id' => '',
			'checkbox' => $this->_urlEncode('test|test2|test3'),
		);
		$this->Controller->beforeFilter();

		$this->Controller->Prg->encode = true;
		$this->Controller->Prg->presetForm(array('model' => 'Post'));
		$expected = array(
			'Post' => array(
				'category_id' => '0',
				'checkbox' => array(
					0 => 'test',
					1 => 'test2',
					2 => 'test3')));
		$this->assertEquals($this->Controller->request->data, $expected);
		$this->assertTrue($this->Controller->Prg->isSearch);
	}

}
