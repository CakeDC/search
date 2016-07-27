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
	public $components = ['Search.Prg'];

/**
 * beforeFilter
 *
 * @return void
 */
	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$this->Prg->actions = [
			'search' => [
				'controller' => 'Posts',
				'action' => 'result'
			]
		];
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
		$this->redirectUrl = Router::url($url);
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
	public $components = [
		'Search.Prg' => [
			'commonProcess' => [
				'form' => 'Post',
				'tableMethod' => false,
				'allowedParams' => ['lang']]],
		'Session'
	];
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
	public $fixtures = ['plugin.search.posts'];

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
		$this->Controller->startupProcess();
		$this->Controller->Posts = new Posts([
			'alias' => 'Post',
			'table' => 'posts',
			'connection' => $this->connection
		]);
		$this->Controller->request->params = [
			'named' => [],
			'pass' => [],
			'url' => [],
			'action' => 'search'
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
		$this->Controller->presetVars = [];
		$this->Controller->action = 'search';
		$this->Controller->request->data = [
			'title' => 'test'
		];

		$this->Controller->Prg->commonProcess('Posts');
		$expected = [
			'?' => ['title' => 'test'],
			'action' => 'search'
		];
		$this->assertEquals(Router::url($expected), $this->Controller->redirectUrl);

		$this->Controller->request->params = array_merge(
			$this->Controller->request->params,
			[
				'lang' => 'en',
			]
		);
		$this->Controller->Prg->commonProcess('Posts', ['allowedParams' => ['lang']]);
		$expected = [
			'?' => ['title' => 'test'],
			'action' => 'search',
			'lang' => 'en'
		];
		$this->assertEquals(Router::url($expected), $this->Controller->redirectUrl);

		$this->Controller->presetVars = [
			['field' => 'title', 'type' => 'value']
		];
		$this->Controller->Prg->commonProcess('Posts', ['paramType' => 'querystring']);
		$expected = ['action' => 'search', '?' => ['title' => 'test']];
		$this->assertEquals(Router::url($expected), $this->Controller->redirectUrl);
	}

/**
 * Test presetForm()
 *
 * @return void
 */
	public function testPresetForm() {
		$this->Controller->presetVars = [
			[
				'field' => 'title',
				'type' => 'value'
			],
			[
				'field' => 'checkbox',
				'type' => 'checkbox'
			],
			[
				'field' => 'lookup',
				'type' => 'lookup',
				'formField' => 'lookup_input',
				'tableField' => 'title',
				'table' => 'Posts'
			]
		];
		$this->Controller->request->query = [
			'title' => 'test',
			'checkbox' => 'test|test2|test3',
			'lookup' => '1'
		];
		$this->Controller->beforeFilter(new Event('Controller.beforeFilter'));

		$this->Controller->Prg->presetForm('Posts');
		$expected = [
			'title' => 'test',
			'checkbox' => [
				0 => 'test',
				1 => 'test2',
				2 => 'test3'],
			'lookup' => 1,
			'lookup_input' => 'First Post'
		];
		$this->assertEquals($expected, $this->Controller->request->data);

		$this->assertTrue($this->Controller->Prg->isSearch);
	}

/**
 * Test presetForm() when passed args are empty
 *
 * @return void
 */
	public function testPresetFormEmpty() {
		$this->Controller->presetVars = [
			[
				'field' => 'title',
				'type' => 'value'
			],
			[
				'field' => 'checkbox',
				'type' => 'checkbox'
			],
			[
				'field' => 'lookup',
				'type' => 'lookup',
				'formField' => 'lookup_input',
				'modelField' => 'title',
				'model' => 'Post'
			]
		];
		$this->Controller->request->query = [
			'page' => '2'
		];
		$this->Controller->beforeFilter(new Event('Controller.beforeFilter'));

		$this->Controller->Prg->presetForm('Post');
		$expected = [];
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
		$this->Controller->presetVars = [
			[
				'field' => 'views',
				'type' => 'value'
			]
		];
		$this->Controller->request->query = [
			'views' => '0'
		];
		$this->Controller->beforeFilter(new Event('Controller.beforeFilter'));

		$this->Controller->Prg->presetForm('Posts');
		$expected = [
			'views' => '0'
		];
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
		$this->Controller->presetVars = [
			[
				'field' => 'options',
				'type' => 'checkbox'
			]
		];
		$testData = [
			'options' => [
				0 => 'test1', 1 => 'test2', 2 => 'test3'
			]
		];
		$result = $this->Controller->Prg->serializeParams($testData);
		$this->assertEquals(['options' => 'test1|test2|test3'], $result);

		$testData = ['options' => ''];

		$result = $this->Controller->Prg->serializeParams($testData);
		$this->assertEquals(['options' => ''], $result);

		$testData = [];
		$result = $this->Controller->Prg->serializeParams($testData);
		$this->assertEquals(['options' => ''], $result);
	}

/**
 * Test exclude()
 *
 * @return void
 */
	public function testExclude() {
		$this->Controller->request->query = [];

		$array = ['foo' => 'test', 'bar' => 'test', 'test' => 'test'];
		$exclude = ['bar', 'test'];
		$result = $this->Controller->Prg->exclude($array, $exclude);
		$this->assertEquals(['foo' => 'test'], $result);

		$array = ['foo' => 'test', 'bar' => 'test', 'test' => 'test',
			0 => 'passed', 1 => 'passed_again'
		];
		$exclude = ['bar', 'test'];
		$result = $this->Controller->Prg->exclude($array, $exclude);
		$this->assertEquals(['foo' => 'test', 0 => 'passed', 1 => 'passed_again'], $result);
	}

/**
 * Test commonProcess()
 *
 * @return void
 */
	public function testCommonProcess() {
		$this->Controller->request->query = [];
		$this->Controller->presetVars = [];
		$this->Controller->action = 'search';
		$this->Controller->request->data = [
			'title' => 'test'
		];
		$this->Controller->Prg->commonProcess('Posts', [
				'tableMethod' => false
			]
		);
		$expected = [
			'?' => ['title' => 'test'],
			'action' => 'search'
		];
		$this->assertEquals(Router::url($expected), $this->Controller->redirectUrl);

		$this->Controller->Prg->commonProcess(null, [
				'tableMethod' => false
			]
		);
		$expected = [
			'?' => ['title' => 'test'],
			'action' => 'search'
		];
		$this->assertEquals(Router::url($expected), $this->Controller->redirectUrl);

		$this->Controller->Posts->filterArgs = [
			['name' => 'title', 'type' => 'value']
		];
		$this->Controller->Prg->commonProcess('Posts');
		$expected = [
			'?' => ['title' => 'test'],
			'action' => 'search'
		];
		$this->assertEquals(Router::url($expected), $this->Controller->redirectUrl);

		$this->Controller->request->data = [
			'PostForm' => [
				'title' => 'test'
			]
		];
		$this->Controller->Posts->filterArgs = [
			['name' => 'title', 'type' => 'value']
		];
		$this->Controller->Prg->commonProcess('Posts', [
			'formName' => 'PostForm'
		]);
		$expected = [
			'?' => ['title' => 'test'],
			'action' => 'search'
		];
		$this->assertEquals(Router::url($expected), $this->Controller->redirectUrl);

		$this->Controller->request->data = [
			'PostForm' => [
				'title' => 'new_title'
			]
		];
		$this->Controller->request->query = [
			'title' => 'old_title'
		];
		$this->Controller->Posts->filterArgs = [
			['name' => 'title', 'type' => 'value']
		];
		$this->Controller->Prg->commonProcess('Posts', [
			'formName' => 'PostForm'
		]);
		$expected = [
			'?' => ['title' => 'new_title'],
			'action' => 'search'
		];
		$this->assertEquals(Router::url($expected), $this->Controller->redirectUrl);

	}

/**
 * Test commonProcess() with presetVars not empty
 *
 * Fixing warning when checking undefined $presetVar['name'].
 *
 * @return void
 */
	public function testCommonProcessWithPresetVarsNotEmpty() {
		$this->Controller->request->query = [];
		$this->Controller->presetVars = ['title' => ['type' => 'value']];

		$this->Controller->action = 'search';
		$this->Controller->request->data = [
			'title' => 'test'
		];
		$this->Controller->Prg->commonProcess('Posts');
		$expected = [
			'?' => ['title' => 'test'],
			'action' => 'search'
		];
		$this->assertEquals(Router::url($expected), $this->Controller->redirectUrl);
	}

/**
 * Test commonProcess() with 'allowedParams' set
 *
 * @return void
 */
	public function testCommonProcessAllowedParams() {
		$this->Controller->request->query = array_merge(
			$this->Controller->request->query,
			[
				'lang' => 'en',
			]
		);
		$this->Controller->presetVars = [];
		$this->Controller->action = 'search';
		$this->Controller->request->data = [
			'title' => 'test'
		];
		$this->Controller->Prg->commonProcess('Posts', [
				'tableMethod' => false,
				'allowedParams' => ['lang']
			]
		);
		$expected = [
			'lang' => 'en',
			'?' => ['lang' => 'en', 'title' => 'test'],
			'action' => 'search'
		];
		$this->assertEquals(Router::url($expected), $this->Controller->redirectUrl);
	}

/**
 * Test commonProcess() when resetting 'query'
 *
 * @return void
 */
	public function testCommonProcessResetQuery() {
		$this->Controller->request->query = array_merge(
			$this->Controller->request->query,
			[
				'page' => 2, 'sort' => 'name', 'direction' => 'asc',
				'lang' => 'en',
			]
		);
		$this->Controller->presetVars = [];
		$this->Controller->action = 'search';
		$this->Controller->request->data = [
			'title' => 'test',
			'foo' => '',
			'bar' => ''
		];
		$this->Controller->Prg->commonProcess('Posts', [
				'tableMethod' => false,
				'allowedParams' => ['lang']
			]
		);
		$expected = [
			'sort' => 'name',
			'direction' => 'asc',
			'lang' => 'en',
			'?' => [
				'sort' => 'name',
				'direction' => 'asc',
				'lang' => 'en',
				'title' => 'test',
				'foo' => '',
				'bar' => '',
			],
			'action' => 'search',
		];
		$this->assertEquals(Router::url($expected), $this->Controller->redirectUrl);
	}

/**
 * Test commonProcess() when 'filterEmpty' = true
 *
 * @return void
 */
	public function testCommonProcessFilterEmpty() {
		$this->Controller->request->query = array_merge(
			$this->Controller->request->query,
			[
				'lang' => 'en',
			]
		);
		$this->Controller->presetVars = [];
		$this->Controller->action = 'search';
		$this->Controller->request->data = [
			'title' => 'test',
			'foo' => '',
			'bar' => ''
		];
		$this->Controller->Prg->commonProcess('Posts', [
				'tableMethod' => false,
				'filterEmpty' => true,
				'allowedParams' => ['lang']
			]
		);
		$expected = [
			'lang' => 'en',
			'?' => [
				'lang' => 'en',
				'title' => 'test',
			],
			'action' => 'search'
		];
		$this->assertEquals(Router::url($expected), $this->Controller->redirectUrl);
	}

/**
 * Test commonProcess() with special characters
 *
 * @return void
 */
	public function testCommonProcessSpecialChars() {
		$this->Controller->request->query += [
			'lang' => 'en',
		];
		$this->Controller->presetVars = [];
		$this->Controller->action = 'search';
		$this->Controller->request->data = [
			'title' => 'test/slashes?!',
			'foo' => '',
			'bar' => ''
		];
		$this->Controller->Prg->commonProcess('Posts', [
				'tableMethod' => false,
				'filterEmpty' => true,
				'allowedParams' => ['lang']
			]
		);
		$expected = [
			'lang' => 'en',
			'?' => [
				'lang' => 'en',
				'title' => 'test/slashes?!',
			],
			'action' => 'search',
		];
		$this->assertEquals(Router::url($expected), $this->Controller->redirectUrl);
	}

/**
 * Test commonProcess() with GET
 *
 * @return void
 */
	public function testCommonProcessGet() {
		$this->Controller->action = 'search';
		$this->Controller->presetVars = [
			['field' => 'title', 'type' => 'value']
		];
		$this->Controller->request->data = [];
		$this->Controller->Posts->filterArgs = [
			['name' => 'title', 'type' => 'value']
		];
		$this->Controller->request->query = ['title' => 'test'];
		$this->Controller->Prg->commonProcess('Posts');

		$this->assertTrue($this->Controller->Prg->isSearch);
		$expected = ['title' => 'test'];
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
		$this->Controller->presetVars = [
			['field' => 'title', 'type' => 'value']
		];
		$this->Controller->request->data = [];
		$this->Controller->Posts->filterArgs = [
			['name' => 'title', 'type' => 'value']
		];
		$this->Controller->request->query = ['title' => 'test'];
		$this->Controller->Prg->commonProcess('Posts', [
			'formName' => 'PostForm'
		]);

		$this->assertTrue($this->Controller->Prg->isSearch);
		$expected = ['PostForm' => ['title' => 'test']];
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
		$this->Controller->presetVars = [
			'title' => ['type' => 'value']
		];
		$this->Controller->Posts->filterArgs = [
			'title' => ['type' => 'value']
		];

		$this->Controller->Prg->__construct($this->Controller->components(), []);
		$this->Controller->Prg->beforeFilter(new Event('Controller.initialize', $this->Controller));
		$this->Controller->request->data = [];

		$this->Controller->request->query = ['title' => 'test'];
		$this->Controller->Prg->commonProcess('Posts');
		$expected = ['title' => 'test'];
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
		$this->Controller->presetVars = [
			'title' => true
		];
		$this->Controller->Posts->filterArgs = [
			'title' => ['type' => 'value']
		];

		$this->Controller->Prg->__construct($this->Controller->components(), []);
		$this->Controller->Prg->beforeFilter(new Event('Controller.initialize', $this->Controller));
		$this->Controller->request->data = [];

		$this->Controller->request->query = ['title' => 'test'];
		$this->Controller->Prg->commonProcess('Post');
		$expected = ['title' => 'test'];
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
			[
				'named' => [],
				'category_id' => '0',
			]
		);
		$this->Controller->presetVars = [
			[
				'field' => 'category_id',
				'name' => 'category_id',
				'type' => 'value',
				'allowEmpty' => true,
				'emptyValue' => '0',
			],
			[
				'field' => 'checkbox',
				'name' => 'checkbox',
				'type' => 'checkbox'
			],
		];
		$this->Controller->action = 'search';
		$this->Controller->request->data = [
			'category_id' => '0',
			'foo' => ''
		];
		$this->Controller->Prg->commonProcess('Posts', [
				'tableMethod' => false,
				'filterEmpty' => true
			]
		);
		$expected = [
			'action' => 'search',
			'?' => ['category_id' => null]
		];
		$this->assertEquals(Router::url($expected), $this->Controller->redirectUrl);
	}

/**
 * Test presetForm() with empty value
 *
 * @return void
 */
	public function testPresetFormWithEmptyValue() {
		$this->Controller->presetVars = [
			[
				'field' => 'category_id',
				'type' => 'value',
				'allowEmpty' => true,
				'emptyValue' => '0',
			],
			[
				'field' => 'checkbox',
				'type' => 'checkbox',
				'allowEmpty' => true,
			],
		];
		$this->Controller->request->query = [
			'category_id' => '',
		];
		$this->Controller->beforeFilter(new Event('Controller.beforeFilter'));

		$this->Controller->Prg->encode = true;
		$this->Controller->Prg->presetForm(['table' => 'Posts']);
		$expected = [
			'category_id' => '0'
		];
		$this->assertEquals($expected, $this->Controller->request->data);
		$this->assertFalse($this->Controller->Prg->isSearch);

		$expected = [
			'category_id' => ''
		];
		$this->assertEquals($expected, $this->Controller->Prg->parsedParams());
	}

}
