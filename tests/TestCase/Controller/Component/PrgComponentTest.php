<?php
/**
 * Copyright 2009 - 2014, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2009 - 2014, Cake Development Corporation (http://cakedc.com)
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Search\Test\TestCase\Controller\Component;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;

/**
 * Post-Redirect-Get: Transfers POST Requests to GET Requests tests
 *
 */
class Posts extends Table {

	public function initialize(array $config) {
		$this->addBehavior('Search.Searchable');
	}

}

/**
 * Posts Test Controller
 */
class PostsTestController extends Controller {

	public $modelClass = 'Posts';

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
	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$this->Prg->actions = array(
			'search' => array(
				'controller' => 'Posts',
				'action' => 'result'
			)
		);
	}

/**
 * Overwrite redirect
 *
 * @param string $url The URL to redirect to
 * @param string $status Not used
 * @param bool|string $exit Not used
 * @return void
 */
	public function redirect($url, $status = null, $exit = true) {
		$this->redirectUrl = $url;
	}

}

/**
 * Posts Options Test Controller
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
				'tableMethod' => false,
				'allowedParams' => array('lang'))),
		'Session'
	);
}

/**
 * PRG Component Test
 */
class PrgComponentTest extends TestCase {

/**
 * Load relevant fixtures
 *
 * @var array
 */
	public $fixtures = array('plugin.search.post');

/**
 * @var PostTestController
 */
	public $Controller;

/**
 * Setup test controller
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->connection = ConnectionManager::get('test');

		Configure::delete('Search');

		$this->Controller = new PostsTestController(new Request(), new Response());
		$this->Controller->constructClasses();
		$this->Controller->startupProcess();
		$this->Controller->Posts = new Posts([
			'alias' => 'Post',
			'table' => 'posts',
			'connection' => $this->connection
		]);
		$this->Controller->request->params = [
			'named' => [],
			'pass' => [],
			'url' => []
		];
		$this->Controller->request->query = [];

		Router::scope('/', function(RouteBuilder $routes) {
			$routes->connect('/:controller/:action');
		});
	}

/**
 * Release test controller
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Controller);

		parent::tearDown();
	}

/**
 * Test options
 *
 * @return void
 */
	public function testOptions() {
		$this->Controller->presetVars = array();
		$this->Controller->action = 'search';
		$this->Controller->request->data = array(
			'title' => 'test'
		);

		$this->Controller->Prg->commonProcess('Posts');
		$expected = array(
			'?' => ['title' => 'test'],
			'action' => 'search'
		);
		$this->assertEquals($expected, $this->Controller->redirectUrl);

		$this->Controller->request->params = array_merge(
			$this->Controller->request->params,
			array(
				'lang' => 'en',
			)
		);
		$this->Controller->Prg->commonProcess('Posts', array('allowedParams' => array('lang')));
		$expected = array(
			'?' => ['title' => 'test'],
			'action' => 'search',
			'lang' => 'en'
		);
		$this->assertEquals($expected, $this->Controller->redirectUrl);

		$this->Controller->presetVars = array(
			array('field' => 'title', 'type' => 'value')
		);
		$this->Controller->Prg->commonProcess('Posts', array('paramType' => 'querystring'));
		$expected = array('action' => 'search', '?' => array('title' => 'test'));
		$this->assertEquals($expected, $this->Controller->redirectUrl);
	}

/**
 * Test presetForm()
 *
 * @return void
 */
	public function testPresetForm() {
		$this->Controller->presetVars = array(
			array(
				'field' => 'title',
				'type' => 'value'
			),
			array(
				'field' => 'checkbox',
				'type' => 'checkbox'
			),
			array(
				'field' => 'lookup',
				'type' => 'lookup',
				'formField' => 'lookup_input',
				'tableField' => 'title',
				'table' => 'Posts'
			)
		);
		$this->Controller->request->query = array(
			'title' => 'test',
			'checkbox' => 'test|test2|test3',
			'lookup' => '1'
		);
		$this->Controller->beforeFilter(new Event('Controller.beforeFilter'));

		$this->Controller->Prg->presetForm('Posts');
		$expected = array(
			'title' => 'test',
			'checkbox' => array(
				0 => 'test',
				1 => 'test2',
				2 => 'test3'),
			'lookup' => 1,
			'lookup_input' => 'First Post'
		);
		$this->assertEquals($expected, $this->Controller->request->data);

		$this->assertTrue($this->Controller->Prg->isSearch);
	}

/**
 * Test presetForm() when passed args are empty
 *
 * @return void
 */
	public function testPresetFormEmpty() {
		$this->Controller->presetVars = array(
			array(
				'field' => 'title',
				'type' => 'value'
			),
			array(
				'field' => 'checkbox',
				'type' => 'checkbox'
			),
			array(
				'field' => 'lookup',
				'type' => 'lookup',
				'formField' => 'lookup_input',
				'modelField' => 'title',
				'model' => 'Post'
			)
		);
		$this->Controller->request->query = array(
			'page' => '2'
		);
		$this->Controller->beforeFilter(new Event('Controller.beforeFilter'));

		$this->Controller->Prg->presetForm('Post');
		$expected = array();
		$this->assertEquals($expected, $this->Controller->request->data);

		$this->assertFalse($this->Controller->Prg->isSearch);
	}

/**
 * Test search on integer when zero is entered
 *
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
				'type' => 'value'
			)
		);
		$this->Controller->request->query = array(
			'views' => '0'
		);
		$this->Controller->beforeFilter(new Event('Controller.beforeFilter'));

		$this->Controller->Prg->presetForm('Posts');
		$expected = array(
			'views' => '0'
		);
		$this->assertEquals($expected, $this->Controller->request->data);
		$this->assertTrue($this->Controller->Prg->isSearch);
		$this->assertEquals($expected, $this->Controller->Prg->parsedParams());
	}

/**
 * Test serializeParams()
 *
 * @return void
 */
	public function testSerializeParams() {
		$this->Controller->presetVars = array(
			array(
				'field' => 'options',
				'type' => 'checkbox'
			)
		);
		$testData = array(
			'options' => array(
				0 => 'test1', 1 => 'test2', 2 => 'test3'
			)
		);
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
 * Test exclude()
 *
 * @return void
 */
	public function testExclude() {
		$this->Controller->request->query = array();

		$array = array('foo' => 'test', 'bar' => 'test', 'test' => 'test');
		$exclude = array('bar', 'test');
		$result = $this->Controller->Prg->exclude($array, $exclude);
		$this->assertEquals(array('foo' => 'test'), $result);

		$array = array('foo' => 'test', 'bar' => 'test', 'test' => 'test',
			0 => 'passed', 1 => 'passed_again'
		);
		$exclude = array('bar', 'test');
		$result = $this->Controller->Prg->exclude($array, $exclude);
		$this->assertEquals(array('foo' => 'test', 0 => 'passed', 1 => 'passed_again'), $result);
	}

/**
 * Test commonProcess()
 *
 * @return void
 */
	public function testCommonProcess() {
		$this->Controller->request->query = array();
		$this->Controller->presetVars = array();
		$this->Controller->action = 'search';
		$this->Controller->request->data = array(
			'title' => 'test'
		);
		$this->Controller->Prg->commonProcess('Posts', array(
				'tableMethod' => false
			)
		);
		$expected = array(
			'?' => array('title' => 'test'),
			'action' => 'search'
		);
		$this->assertEquals($expected, $this->Controller->redirectUrl);

		$this->Controller->Prg->commonProcess(null, array(
				'tableMethod' => false
			)
		);
		$expected = array(
			'?' => array('title' => 'test'),
			'action' => 'search'
		);
		$this->assertEquals($expected, $this->Controller->redirectUrl);

		$this->Controller->Posts->filterArgs = array(
			array('name' => 'title', 'type' => 'value')
		);
		$this->Controller->Prg->commonProcess('Posts');
		$expected = array(
			'?' => array('title' => 'test'),
			'action' => 'search'
		);
		$this->assertEquals($expected, $this->Controller->redirectUrl);

		$this->Controller->request->data = array(
			'PostForm' => array(
				'title' => 'test'
			)
		);
		$this->Controller->Posts->filterArgs = array(
			array('name' => 'title', 'type' => 'value')
		);
		$this->Controller->Prg->commonProcess('Posts', array(
			'formName' => 'PostForm'
		));
		$expected = array(
			'?' => array('title' => 'test'),
			'action' => 'search'
		);
		$this->assertEquals($expected, $this->Controller->redirectUrl);

	}

/**
 * Test commonProcess() with presetVars not empty
 *
 * Fixing warning when checking undefined $presetVar['name'].
 *
 * @return void
 */
	public function testCommonProcessWithPresetVarsNotEmpty() {
		$this->Controller->request->query = array();
		$this->Controller->presetVars = array('title' => array('type' => 'value'));

		$this->Controller->action = 'search';
		$this->Controller->request->data = array(
			'title' => 'test'
		);
		$this->Controller->Prg->commonProcess('Posts');
		$expected = array(
			'?' => array('title' => 'test'),
			'action' => 'search'
		);
		$this->assertEquals($expected, $this->Controller->redirectUrl);
	}

/**
 * Test commonProcess() with 'allowedParams' set
 *
 * @return void
 */
	public function testCommonProcessAllowedParams() {
		$this->Controller->request->query = array_merge(
			$this->Controller->request->query,
			array(
				'lang' => 'en',
			)
		);
		$this->Controller->presetVars = array();
		$this->Controller->action = 'search';
		$this->Controller->request->data = array(
			'title' => 'test'
		);
		$this->Controller->Prg->commonProcess('Posts', array(
				'tableMethod' => false,
				'allowedParams' => array('lang')
			)
		);
		$expected = array(
			'lang' => 'en',
			'?' => array('lang' => 'en', 'title' => 'test'),
			'action' => 'search'
		);
		$this->assertEquals($expected, $this->Controller->redirectUrl);
	}

/**
 * Test commonProcess() when resetting 'query'
 *
 * @return void
 */
	public function testCommonProcessResetQuery() {
		$this->Controller->request->query = array_merge(
			$this->Controller->request->query,
			array(
				'page' => 2, 'sort' => 'name', 'direction' => 'asc',
				'lang' => 'en',
			)
		);
		$this->Controller->presetVars = array();
		$this->Controller->action = 'search';
		$this->Controller->request->data = array(
			'title' => 'test',
			'foo' => '',
			'bar' => ''
		);
		$this->Controller->Prg->commonProcess('Posts', array(
				'tableMethod' => false,
				'allowedParams' => array('lang')
			)
		);
		$expected = array(
			'page' => 2,
			'sort' => 'name',
			'direction' => 'asc',
			'lang' => 'en',
			'?' => array(
				'sort' => 'name',
				'direction' => 'asc',
				'lang' => 'en',
				'title' => 'test',
				'foo' => '',
				'bar' => '',
			),
			'action' => 'search',
		);
		$this->assertEquals($expected, $this->Controller->redirectUrl);
	}

/**
 * Test commonProcess() when 'filterEmpty' = true
 *
 * @return void
 */
	public function testCommonProcessFilterEmpty() {
		$this->Controller->request->query = array_merge(
			$this->Controller->request->query,
			array(
				'lang' => 'en',
			)
		);
		$this->Controller->presetVars = array();
		$this->Controller->action = 'search';
		$this->Controller->request->data = array(
			'title' => 'test',
			'foo' => '',
			'bar' => ''
		);
		$this->Controller->Prg->commonProcess('Posts', array(
				'tableMethod' => false,
				'filterEmpty' => true,
				'allowedParams' => array('lang')
			)
		);
		$expected = array(
			'lang' => 'en',
			'?' => array(
				'lang' => 'en',
				'title' => 'test',
			),
			'action' => 'search'
		);
		$this->assertEquals($expected, $this->Controller->redirectUrl);
	}

/**
 * Test commonProcess() with special characters
 *
 * @return void
 */
	public function testCommonProcessSpecialChars() {
		$this->Controller->request->query = array_merge(
			$this->Controller->request->query,
			array(
				'lang' => 'en',
			)
		);
		$this->Controller->presetVars = array();
		$this->Controller->action = 'search';
		$this->Controller->request->data = array(
			'title' => 'test/slashes?!',
			'foo' => '',
			'bar' => ''
		);
		$this->Controller->Prg->commonProcess('Posts', array(
				'tableMethod' => false,
				'filterEmpty' => true,
				'allowedParams' => array('lang')
			)
		);
		$expected = array(
			'lang' => 'en',
			'?' => array(
				'lang' => 'en',
				'title' => 'test/slashes?!',
			),
			'action' => 'search',
		);
		$this->assertEquals($expected, $this->Controller->redirectUrl);

		$url = Router::url($this->Controller->redirectUrl);
		$expected = '/search?lang=en&title=test%2Fslashes%3F%21';
		$this->assertEquals($expected, $url);
	}

/**
 * Test commonProcess() with GET
 *
 * @return void
 */
	public function testCommonProcessGet() {
		$this->Controller->action = 'search';
		$this->Controller->presetVars = array(
			array('field' => 'title', 'type' => 'value')
		);
		$this->Controller->request->data = array();
		$this->Controller->Posts->filterArgs = array(
			array('name' => 'title', 'type' => 'value')
		);
		$this->Controller->request->query = array('title' => 'test');
		$this->Controller->Prg->commonProcess('Posts');

		$this->assertTrue($this->Controller->Prg->isSearch);
		$expected = array('title' => 'test');
		$this->assertEquals($expected, $this->Controller->Prg->parsedParams());
		$this->assertEquals($expected, $this->Controller->request->data);
	}

/**
 * Test commonProcess() with GET and a formName
 *
 * @return void
 */
	public function testCommonProcessGetWithFormName() {
		$this->Controller->action = 'search';
		$this->Controller->presetVars = array(
			array('field' => 'title', 'type' => 'value')
		);
		$this->Controller->request->data = array();
		$this->Controller->Posts->filterArgs = array(
			array('name' => 'title', 'type' => 'value')
		);
		$this->Controller->request->query = array('title' => 'test');
		$this->Controller->Prg->commonProcess('Posts', [
			'formName' => 'PostForm'
		]);

		$this->assertTrue($this->Controller->Prg->isSearch);
		$expected = array('PostForm' => array('title' => 'test'));
		$this->assertEquals($expected['PostForm'], $this->Controller->Prg->parsedParams());
		$this->assertEquals($expected, $this->Controller->request->data);
	}

/**
 * Test commonProcess() with GET and string keys
 *
 * @return void
 */
	public function testCommonProcessGetWithStringKeys() {
		$this->Controller->action = 'search';
		$this->Controller->presetVars = array(
			'title' => array('type' => 'value')
		);
		$this->Controller->Posts->filterArgs = array(
			'title' => array('type' => 'value')
		);

		$this->Controller->Prg->__construct($this->Controller->components(), array());
		$this->Controller->Prg->initialize(new Event('Controller.initialize', $this->Controller));
		$this->Controller->request->data = array();

		$this->Controller->request->query = array('title' => 'test');
		$this->Controller->Prg->commonProcess('Posts');
		$expected = array('title' => 'test');
		$this->assertEquals($expected, $this->Controller->Prg->parsedParams());
		$this->assertEquals($expected, $this->Controller->request->data);
	}

/**
 * Test commonProcess() with GET and string keys (short notation)
 *
 * @return void
 */
	public function testCommonProcessGetWithStringKeysShort() {
		$this->Controller->action = 'search';
		$this->Controller->presetVars = array(
			'title' => true
		);
		$this->Controller->Posts->filterArgs = array(
			'title' => array('type' => 'value')
		);

		$this->Controller->Prg->__construct($this->Controller->components(), array());
		$this->Controller->Prg->initialize(new Event('Controller.initialize', $this->Controller));
		$this->Controller->request->data = array();

		$this->Controller->request->query = array('title' => 'test');
		$this->Controller->Prg->commonProcess('Post');
		$expected = array('title' => 'test');
		$this->assertEquals($expected, $this->Controller->Prg->parsedParams());
		$this->assertEquals($expected, $this->Controller->request->data);
	}

/**
 * Test commonProcess() with empty value
 *
 * @return void
 */
	public function testCommonProcessGetWithEmptyValue() {
		$this->Controller->request->params = array_merge(
			$this->Controller->request->params,
			array(
				'named' => array(),
				'category_id' => '0',
			)
		);
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
			'category_id' => '0',
			'foo' => ''
		);
		$this->Controller->Prg->commonProcess('Posts', array(
				'tableMethod' => false,
				'filterEmpty' => true
			)
		);
		$expected = array(
			'action' => 'search',
			'?' => array('category_id' => null)
		);
		$this->assertEquals($expected, $this->Controller->redirectUrl);
	}

/**
 * Test presetForm() with empty value
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
				'type' => 'checkbox',
				'allowEmpty' => true,
			),
		);
		$this->Controller->request->query = array(
			'category_id' => '',
		);
		$this->Controller->beforeFilter(new Event('Controller.beforeFilter'));

		$this->Controller->Prg->encode = true;
		$this->Controller->Prg->presetForm(array('table' => 'Posts'));
		$expected = array(
			'category_id' => '0'
		);
		$this->assertEquals($expected, $this->Controller->request->data);
		$this->assertFalse($this->Controller->Prg->isSearch);

		$expected = array(
			'category_id' => ''
		);
		$this->assertEquals($expected, $this->Controller->Prg->parsedParams());
	}

}
