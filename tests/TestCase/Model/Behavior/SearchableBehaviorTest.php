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
namespace Search\Test\TestCase\Model\Behavior;

use Cake\Datasource\ConnectionManager;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;

/**
 * Tag test model
 */
class TagsTable extends Table {
}

/**
 * Tagged test model
 */
class TaggedTable extends Table {

/**
 * Table to use
 *
 * @var string
 */
	public $useTable = 'tagged';

	public function initialize(array $options) {
		$this->belongsTo('Tags');
	}
}

/**
 * Article test model
 *
 * Contains various find and condition methods used by the tests below.
 */
class ArticlesTable extends Table {

	public function initialize(array $options) {
		$this->belongsTo('Tagged');
		$this->belongsToMany('Tags', [
			'through' => $this->association('Tagged')->target()
		]);
		$this->addBehavior('Search.Searchable');
	}

/**
 * Makes an array of range numbers that matches the ones on the interface.
 *
 * @param $data
 * @param null $field
 * @return array
 */
	public function makeRangeCondition($data, $field = null) {
		if (is_string($data)) {
			$input = $data;
		}
		if (is_array($data)) {
			if (!empty($field['name'])) {
				$input = $data[$field['name']];
			} else {
				$input = $data['range'];
			}
		}
		switch ($input) {
			case '10':
				return [0, 10];
			case '100':
				return [11, 100];
			case '1000':
				return [101, 1000];
			default:
				return [0, 0];
		}
	}

/**
 * orConditions
 *
 * @param array $data
 * @return array
 */
	public function findOrConditions(Query $query, $data = []) {
		$filter = $data['filter'];
		$cond = [
			'OR' => [
				$this->alias() . '.title LIKE' => '%' . $filter . '%',
				$this->alias() . '.body LIKE' => '%' . $filter . '%',
			]];
		$query->orWhere($cond);
		return $query;
	}

	public function findOr2Conditions(Query $query, $data = []) {
		$filter = $data['filter2'];
		$cond = [
			'OR' => [
				$this->alias() . '.field1 LIKE' => '%' . $filter . '%',
				$this->alias() . '.field2 LIKE' => '%' . $filter . '%',
			]];
		$query->orWhere($cond);
		return $query;
	}

}

/**
 * SearchableTestCase
 */
class SearchableBehaviorTest extends TestCase {

/**
 * Article test model
 *
 * @var ArticlesTable
 */
	public $Articles;

/**
 * Load relevant fixtures
 *
 * @var array
 */
	public $fixtures = [
		'plugin.search.articles',
		'plugin.search.tags',
		'plugin.search.tagged',
		//'core.users'
	];

/**
 * Load Article test model
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->connection = ConnectionManager::get('test');

		$this->Articles = new ArticlesTable([
			'alias' => 'Articles',
			'table' => 'articles',
			'connection' => $this->connection
		]);
	}

/**
 * Release Article test model
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();

		unset($this->Articles);
	}

/**
 * Test getWildcards()
 *
 * @return void
 */
	public function testGetWildcards() {
		$result = $this->Articles->getWildcards();
		$expected = ['any' => '*', 'one' => '?'];
		$this->assertSame($expected, $result);

		$this->Articles->behaviors()->Searchable->config('wildcardAny', false);
		$this->Articles->behaviors()->Searchable->config('wildcardOne', false);
		$result = $this->Articles->getWildcards();
		$expected = ['any' => false, 'one' => false];
		$this->assertSame($expected, $result);

		$this->Articles->behaviors()->Searchable->config('wildcardAny', '%');
		$this->Articles->behaviors()->Searchable->config('wildcardOne', '_');
		$result = $this->Articles->getWildcards();
		$expected = ['any' => '%', 'one' => '_'];
		$this->assertSame($expected, $result);
	}

/**
 * Test 'value' filter type
 *
 * @return void
 * @link http://github.com/CakeDC/Search/issues#issue/3
 */
	public function testValueCondition() {
		$this->Articles->filterArgs = [
			['name' => 'slug', 'type' => 'value']];
		$data = [];
		$result = $this->Articles->find('searchable', $data);
		$expected = $this->Articles->find('all');
		$this->assertEquals($this->_getWhere($expected), $this->_getWhere($result));

		$data = ['slug' => 'first_article'];
		$result = $this->Articles->find('searchable', $data);
		$expected = $this->Articles->find('all')->where(['Articles.slug' => 'first_article']);
		$this->assertEquals($this->_getWhere($expected), $this->_getWhere($result));

		$this->Articles->filterArgs = [
			['name' => 'fakeslug', 'type' => 'value', 'field' => 'Article2.slug']];
		$data = ['fakeslug' => 'first_article'];
		$result = $this->Articles->find('searchable', $data);
		$expected = $this->Articles->find('all')->where(['Article2.slug' => 'first_article']);
		$this->assertEquals($this->_getWhere($expected), $this->_getWhere($result));

		// Testing http://github.com/CakeDC/Search/issues#issue/3
		$this->Articles->filterArgs = [
			['name' => 'views', 'type' => 'value']];
		$data = ['views' => '0'];
		$result = $this->Articles->find('searchable', $data);
		$expected = $this->Articles->find('all')->where(['Articles.views' => 0]);
		$this->assertEquals($this->_getWhere($expected), $this->_getWhere($result));

		$this->Articles->filterArgs = [
			['name' => 'views', 'type' => 'value']];
		$data = ['views' => 0];
		$result = $this->Articles->find('searchable', $data);
		$expected = $this->Articles->find('all')->where(['Articles.views' => 0]);
		$this->assertEquals($this->_getWhere($expected), $this->_getWhere($result));

		$this->Articles->filterArgs = [
			['name' => 'views', 'type' => 'value']];
		$data = ['views' => ''];
		$result = $this->Articles->find('searchable', $data);
		$expected = $this->Articles->find('all');
		$this->assertEquals($this->_getWhere($expected), $this->_getWhere($result));

		// Multiple fields + cross model searches
		$this->Articles->filterArgs = [
			'faketitle' => ['type' => 'value', 'field' => ['title', 'User.name']]
		];
		$data = ['faketitle' => 'First'];
		$result = $this->Articles->find('searchable', $data);
		$expected = $this->Articles->find('all')->where(['OR' => ['Articles.title' => 'First', 'User.name' => 'First']]);
		$this->assertEquals($this->_getWhere($expected), $this->_getWhere($result));

		// Multiple select dropdown
		$this->Articles->filterArgs = [
			'fakesource' => ['type' => 'value']
		];
		$data = ['fakesource' => [5, 9]];
		$result = $this->Articles->find('searchable', $data);
		$expected = $this->Articles->find('all')->where(['Articles.fakesource' => [5, 9]]);
		$this->assertEquals($this->_getWhere($expected), $this->_getWhere($result));
	}

/**
 * Test 'like' filter type
 *
 * @return void
 */
	public function testLikeCondition() {
		$this->Articles->filterArgs = [
			['name' => 'title', 'type' => 'like']];

		$data = [];
		$result = $this->Articles->find('searchable', $data);
		$expected = $this->Articles->find('all');
		$this->assertEquals($this->_getWhere($expected), $this->_getWhere($result));

		$data = ['title' => 'First'];
		$result = $this->Articles->find('searchable', $data);
		$expected = ['Articles.title LIKE' => '%First%'];
		$expected = $this->Articles->find('all')->where($expected);
		$this->assertEquals($this->_getWhere($expected), $this->_getWhere($result));

		$this->Articles->filterArgs = [
			['name' => 'faketitle', 'type' => 'like', 'field' => 'Articles.title']];

		$data = ['faketitle' => 'First'];
		$result = $this->Articles->find('searchable', $data);
		$expected = ['Articles.title LIKE' => '%First%'];
		$expected = $this->Articles->find('all')->where($expected);
		$this->assertEquals($this->_getWhere($expected), $this->_getWhere($result));

		// Wildcards should be treated as normal text
		$this->Articles->filterArgs = [
			['name' => 'faketitle', 'type' => 'like', 'field' => 'Articles.title']
		];
		$data = ['faketitle' => '%First_'];
		$result = $this->Articles->find('searchable', $data);
		$expected = ['Articles.title LIKE' => '%\%First\_%'];
		$expected = $this->Articles->find('all')->where($expected);
		$this->assertEquals($this->_getWhere($expected), $this->_getWhere($result));

		// Working with like settings
		$this->Articles->behaviors()->Searchable->config('like.before', false);
		$result = $this->Articles->find('searchable', $data);
		$expected = ['Articles.title LIKE' => '\%First\_%'];
		$expected = $this->Articles->find('all')->where($expected);
		$this->assertEquals($this->_getWhere($expected), $this->_getWhere($result));

		$this->Articles->behaviors()->Searchable->config('like.after', false);
		$result = $this->Articles->find('searchable', $data);
		$expected = ['Articles.title LIKE' => '\%First\_'];
		$expected = $this->Articles->find('all')->where($expected);
		$this->assertEquals($this->_getWhere($expected), $this->_getWhere($result));

		// Now custom like should be possible
		$data = ['faketitle' => '*First?'];
		$this->Articles->behaviors()->Searchable->config('like.after', false);
		$result = $this->Articles->find('searchable', $data);
		$expected = ['Articles.title LIKE' => '%First_'];
		$expected = $this->Articles->find('all')->where($expected);
		$this->assertEquals($this->_getWhere($expected), $this->_getWhere($result));

		$data = ['faketitle' => 'F?rst'];
		$this->Articles->behaviors()->Searchable->config('like.before', true);
		$this->Articles->behaviors()->Searchable->config('like.after', true);
		$result = $this->Articles->find('searchable', $data);
		$expected = ['Articles.title LIKE' => '%F_rst%'];
		$expected = $this->Articles->find('all')->where($expected);
		$this->assertEquals($this->_getWhere($expected), $this->_getWhere($result));

		$data = ['faketitle' => 'F*t'];
		$this->Articles->behaviors()->Searchable->config('like.before', true);
		$this->Articles->behaviors()->Searchable->config('like.after', true);
		$result = $this->Articles->find('searchable', $data);
		$expected = ['Articles.title LIKE' => '%F%t%'];
		$expected = $this->Articles->find('all')->where($expected);
		$this->assertEquals($this->_getWhere($expected), $this->_getWhere($result));

		// now we try the default wildcards % and _
		$data = ['faketitle' => '*First?'];
		$this->Articles->behaviors()->Searchable->config('like.before', false);
		$this->Articles->behaviors()->Searchable->config('like.after', false);
		$this->Articles->behaviors()->Searchable->config('wildcardAny', '%');
		$this->Articles->behaviors()->Searchable->config('wildcardOne', '_');
		$result = $this->Articles->find('searchable', $data);
		$expected = ['Articles.title LIKE' => '*First?'];
		$expected = $this->Articles->find('all')->where($expected);
		$this->assertEquals($this->_getWhere($expected), $this->_getWhere($result));

		// Now it is possible and makes sense to allow wildcards in between (custom wildcard use case)
		$data = ['faketitle' => '%Fi_st_'];
		$result = $this->Articles->find('searchable', $data);
		$expected = ['Articles.title LIKE' => '%Fi_st_'];
		$expected = $this->Articles->find('all')->where($expected);
		$this->assertEquals($this->_getWhere($expected), $this->_getWhere($result));

		// Shortcut disable/enable like before/after
		$data = ['faketitle' => '%First_'];
		$this->Articles->behaviors()->Searchable->config('like', false);
		$result = $this->Articles->find('searchable', $data);
		$expected = ['Articles.title LIKE' => '%First_'];
		$expected = $this->Articles->find('all')->where($expected);
		$this->assertEquals($this->_getWhere($expected), $this->_getWhere($result));

		// Multiple OR fields per field
		$this->Articles->filterArgs = [
			['name' => 'faketitle', 'type' => 'like', 'field' => ['title', 'descr']]
		];

		$data = ['faketitle' => 'First'];
		$this->Articles->behaviors()->Searchable->config('like', true);
		$result = $this->Articles->find('searchable', $data);
		$expected = [
			'OR' => [
				'Articles.title LIKE' => '%First%',
				'Articles.descr LIKE' => '%First%'
			]
		];
		$expected = $this->Articles->find('all')->where($expected);
		$this->assertEquals($this->_getWhere($expected), $this->_getWhere($result));

		// Set before => false dynamically
		$this->Articles->filterArgs = [
			['name' => 'faketitle',
				'type' => 'like',
				'field' => ['title', 'descr'],
				'before' => false
			]
		];
		$data = ['faketitle' => 'First'];
		$result = $this->Articles->find('searchable', $data);
		$expected = [
			'OR' => [
				'Articles.title LIKE' => 'First%',
				'Articles.descr LIKE' => 'First%'
			]
		];
		$expected = $this->Articles->find('all')->where($expected);
		$this->assertEquals($this->_getWhere($expected), $this->_getWhere($result));

		// Manually define the before/after type
		$this->Articles->filterArgs = [
			['name' => 'faketitle', 'type' => 'like', 'field' => ['title'],
				'before' => '_', 'after' => '_']
		];
		$data = ['faketitle' => 'First'];
		$result = $this->Articles->find('searchable', $data);
		$expected = ['Articles.title LIKE' => '_First_'];
		$expected = $this->Articles->find('all')->where($expected);
		$this->assertEquals($this->_getWhere($expected), $this->_getWhere($result));

		// Cross model searches + named keys (shorthand)
		$this->Articles->belongsTo('Users');
		$this->Articles->filterArgs = [
			'faketitle' => ['type' => 'like', 'field' => ['title', 'Users.name'],
				'before' => false, 'after' => true]
		];
		$data = ['faketitle' => 'First'];
		$result = $this->Articles->find('searchable', $data);
		$expected = ['OR' => ['Articles.title LIKE' => 'First%', 'Users.name LIKE' => 'First%']];
		$expected = $this->Articles->find('all')->where($expected);
		$this->assertEquals($this->_getWhere($expected), $this->_getWhere($result));

		// With already existing or conditions + named keys (shorthand)
		$this->Articles->filterArgs = [
			'faketitle' => ['type' => 'like', 'field' => ['title', 'Users.name'],
				'before' => false, 'after' => true],
			'otherfaketitle' => ['type' => 'like', 'field' => ['descr', 'comment'],
				'before' => false, 'after' => true]
		];

		$data = ['faketitle' => 'First', 'otherfaketitle' => 'Second'];
		$result = $this->Articles->find('searchable', $data);
		$expected = $this->Articles->find('all')
			->where(['OR' => [
				'Articles.title LIKE' => 'First%',
				'Users.name LIKE' => 'First%'
			]])
			->where([
				'OR' => [
					'Articles.descr LIKE' => 'Second%',
					'Articles.comment LIKE' => 'Second%']
			]);
		$this->assertEquals($this->_getWhere($expected), $this->_getWhere($result));

		// Wildcards and and/or connectors
		$this->Articles->removeBehavior('Searchable');
		$this->Articles->addBehavior('Search.Searchable');
		$this->Articles->filterArgs = [
			['name' => 'faketitle', 'type' => 'like', 'field' => 'Articles.title',
				'connectorAnd' => '+', 'connectorOr' => ',', 'before' => true, 'after' => true]
		];
		$data = ['faketitle' => 'First%+Second%, Third%'];
		$result = $this->Articles->find('searchable', $data);
		$expected = [
			0 => [
				'OR' => [
					['AND' => [
						['Articles.title LIKE' => '%First\%%'],
						['Articles.title LIKE' => '%Second\%%'],
					]],
					['AND' => [
						['Articles.title LIKE' => '%Third\%%']
					]],
				]
			]
		];
		$expected = $this->Articles->find('all')->where($expected);
		$this->assertEquals($this->_getWhere($expected), $this->_getWhere($result));
	}

/**
 * Test 'query' filter type with one orConditions method
 *
 * Uses ``Article::orConditions()``.
 *
 * @return void
 */
	public function testQueryOneOrConditions() {
		$this->Articles->filterArgs = [
			['name' => 'filter', 'type' => 'finder', 'finder' => 'orConditions']];

		$data = [];
		$result = $this->Articles->find('searchable', $data);
		$expected = $this->Articles->find('all');
		$this->assertEquals($this->_getWhere($expected), $this->_getWhere($result));

		$data = ['filter' => 'ticl'];
		$result = $this->Articles->find('searchable', $data);
		$expected = ['OR' => [
		'Articles.title LIKE' => '%ticl%',
		'Articles.body LIKE' => '%ticl%']];
		$expected = $this->Articles->find('all')->orWhere($expected);
		$this->assertEquals($this->_getWhere($expected), $this->_getWhere($result));
	}

/**
 * Test 'query' filter type with two orConditions methods
 *
 * Uses ``Article::orConditions()`` and ``Article::or2Conditions()``.
 *
 * @return void
 */
	public function testQueryOrTwoOrConditions() {
		$this->Articles->filterArgs = [
			['name' => 'filter', 'type' => 'finder', 'finder' => 'orConditions'],
			['name' => 'filter2', 'type' => 'finder', 'finder' => 'or2Conditions']];

		$data = [];
		$result = $this->Articles->find('searchable', $data);
		$expected = $this->Articles->find('all');
		$this->assertEquals($this->_getWhere($expected), $this->_getWhere($result));

		$data = ['filter' => 'ticl', 'filter2' => 'test'];
		$result = $this->Articles->find('searchable', $data);
		$expected = $this->Articles->find('all')
			->orWhere([
				'OR' => [
					'Articles.title LIKE' => '%ticl%',
					'Articles.body LIKE' => '%ticl%'
				]
			])
			->orWhere([
				'OR' => [
					'Articles.field1 LIKE' => '%test%',
					'Articles.field2 LIKE' => '%test%'
				]
			]);
		$this->assertEquals($this->_getWhere($expected), $this->_getWhere($result));
	}

/**
 * Test 'query' filter type with behavior condition method
 *
 * Uses ``FilterBehavior::FilterBehavior::mostFilterConditions()``.
 *
 * @return void
 */
	public function testQueryWithBehaviorCondition() {
		$this->Articles->addBehavior('Filter', [
			'className' => 'Search\Test\TestClass\Model\Behavior\FilterBehavior'
		]);
		$this->Articles->filterArgs = [
			['name' => 'filter', 'type' => 'finder', 'finder' => 'mostFilterConditions']
		];

		$data = [];
		$result = $this->Articles->find('searchable', $data);
		$expected = $this->Articles->find('all');
		$this->assertEquals($this->_getWhere($expected), $this->_getWhere($result));

		$data = ['filter' => 'views'];
		$result = $this->Articles->find('searchable', $data);
		$expected = ['Articles.views > 10'];
		$expected = $this->Articles->find('all')->where($expected);

		$this->assertEquals($this->_getWhere($expected), $this->_getWhere($result));
	}

/**
 * Test 'query' filter type with 'defaultValue' set
 *
 * @return void
 */
	public function testDefaultValue() {
		$this->Articles->filterArgs = [
			'faketitle' => ['type' => 'value', 'defaultValue' => '100']
		];

		$data = [];
		$result = $this->Articles->find('searchable', $data);
		$expected = ['Articles.faketitle' => 100];
		$expected = $this->Articles->find('all')->where($expected);
		$this->assertEquals($expected, $result);
	}

/**
 * Test passedArgs()
 *
 * @return void
 */
	public function testPassedArgs() {
		$this->Articles->filterArgs = [
			['name' => 'slug', 'type' => 'value']];
		$data = ['slug' => 'first_article', 'filter' => 'myfilter'];
		$result = $this->Articles->passedArgs($data);
		$expected = ['slug' => 'first_article'];
		$this->assertEquals($expected, $result);
	}

/**
 * Test whether 'allowEmpty' will be respected
 *
 * @return void
 */
	public function testAllowEmptyWithNullValues() {
		// Author is just empty, created will be mapped against schema default (NULL)
		// and slug omitted as its NULL already
		$this->Articles->filterArgs = [
			'title' => [
				'name' => 'title',
				'type' => 'like',
				'field' => 'Articles.title',
				'allowEmpty' => true
			],
			'author' => [
				'name' => 'author',
				'type' => 'value',
				'field' => 'Articles.author',
				'allowEmpty' => true
			],
			'created' => [
				'name' => 'created',
				'type' => 'value',
				'field' => 'Articles.created',
				'allowEmpty' => true
			],
			'slug' => [
				'name' => 'slug',
				'type' => 'value',
				'field' => 'Articles.slug',
				'allowEmpty' => true
			],
		];
		$data = ['title' => 'first', 'author' => '', 'created' => '', 'slug' => null];
		$expected = [
			'Articles.title LIKE' => '%first%',
			'Articles.author' => '',
			'Articles.created' => null,
		];
		$result = $this->Articles->find('searchable', $data);
		$expected = $this->Articles->find('all')->where($expected);
		$this->assertEquals($this->_getWhere($expected), $this->_getWhere($result));
	}

/**
 * Gets the 'where' part of a query
 *
 * @param Query $query Query to extract the part from
 *
 * @return mixed
 */
	protected function _getWhere(Query $query) {
		$query->traverse(function ($part) use (&$where) {
			$where = $part;
		}, ['where']);
		return $where;
	}

}
