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
use Cake\ORM\Behavior;
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
 * Find by tags
 *
 * @param array $data
 * @return array
 */
	public function findByTags($data = array()) {
		$query = $this->Tagged->find('all');
		$conditions = array();
		if (!empty($data['tags'])) {
			$conditions = array('Tags.name' => $data['tags']);
		}
		$query
			->where($conditions)
			->select('foreign_key')
			->contain('Tags');
		return $query;
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
				return array(0, 10);
			case '100':
				return array(11, 100);
			case '1000':
				return array(101, 1000);
			default:
				return array(0, 0);
		}
	}

/**
 * orConditions
 *
 * @param array $data
 * @return array
 */
	public function orConditions(Query $query, $data = array()) {
		$filter = $data['filter'];
		$cond = array(
			'OR' => array(
				$this->alias() . '.title LIKE' => '%' . $filter . '%',
				$this->alias() . '.body LIKE' => '%' . $filter . '%',
			));
		$query->orWhere($cond);
		return $query;
	}

	public function or2Conditions(Query $query, $data = array()) {
		$filter = $data['filter2'];
		$cond = array(
			'OR' => array(
				$this->alias() . '.field1 LIKE' => '%' . $filter . '%',
				$this->alias() . '.field2 LIKE' => '%' . $filter . '%',
			));
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
	public $fixtures = array(
		'plugin.search.article',
		'plugin.search.tag',
		'plugin.search.tagged',
		'core.user'
	);

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
		$expected = array('any' => '*', 'one' => '?');
		$this->assertSame($expected, $result);

		$this->Articles->behaviors()->Searchable->config('wildcardAny', false);
		$this->Articles->behaviors()->Searchable->config('wildcardOne', false);
		$result = $this->Articles->getWildcards();
		$expected = array('any' => false, 'one' => false);
		$this->assertSame($expected, $result);

		$this->Articles->behaviors()->Searchable->config('wildcardAny', '%');
		$this->Articles->behaviors()->Searchable->config('wildcardOne', '_');
		$result = $this->Articles->getWildcards();
		$expected = array('any' => '%', 'one' => '_');
		$this->assertSame($expected, $result);
	}

/**
 * Test 'value' filter type
 *
 * @return void
 * @link http://github.com/CakeDC/Search/issues#issue/3
 */
	public function testValueCondition() {
		$this->Articles->filterArgs = array(
			array('name' => 'slug', 'type' => 'value'));
		$data = array();
		$result = $this->Articles->parseQuery($data);
		$expected = $this->Articles->find('all')->where(array());
		$this->assertEquals($expected, $result);

		$data = array('slug' => 'first_article');
		$result = $this->Articles->parseQuery($data);
		$expected = $this->Articles->find('all')->where(array('Articles.slug' => 'first_article'));
		$this->assertEquals($expected, $result);

		$this->Articles->filterArgs = array(
			array('name' => 'fakeslug', 'type' => 'value', 'field' => 'Article2.slug'));
		$data = array('fakeslug' => 'first_article');
		$result = $this->Articles->parseQuery($data);
		$expected = $this->Articles->find('all')->where(array('Article2.slug' => 'first_article'));
		$this->assertEquals($expected, $result);

		// Testing http://github.com/CakeDC/Search/issues#issue/3
		$this->Articles->filterArgs = array(
			array('name' => 'views', 'type' => 'value'));
		$data = array('views' => '0');
		$result = $this->Articles->parseQuery($data);
		$expected = $this->Articles->find('all')->where(array('Articles.views' => 0));
		$this->assertEquals($expected, $result);

		$this->Articles->filterArgs = array(
			array('name' => 'views', 'type' => 'value'));
		$data = array('views' => 0);
		$result = $this->Articles->parseQuery($data);
		$expected = $this->Articles->find('all')->where(array('Articles.views' => 0));
		$this->assertEquals($expected, $result);

		$this->Articles->filterArgs = array(
			array('name' => 'views', 'type' => 'value'));
		$data = array('views' => '');
		$result = $this->Articles->parseQuery($data);
		$expected = $this->Articles->find('all')->where(array());
		$this->assertEquals($expected, $result);

		// Multiple fields + cross model searches
		$this->Articles->filterArgs = array(
			'faketitle' => array('type' => 'value', 'field' => array('title', 'User.name'))
		);
		$data = array('faketitle' => 'First');
		$result = $this->Articles->parseQuery($data);
		$expected = $this->Articles->find('all')->where(array('OR' => array('Articles.title' => 'First', 'User.name' => 'First')));
		$this->assertEquals($expected, $result);

		// Multiple select dropdown
		$this->Articles->filterArgs = array(
			'fakesource' => array('type' => 'value')
		);
		$data = array('fakesource' => array(5, 9));
		$result = $this->Articles->parseQuery($data);
		$expected = $this->Articles->find('all')->where(array('Articles.fakesource' => array(5, 9)));
		$this->assertEquals($expected, $result);
	}

/**
 * Test 'like' filter type
 *
 * @return void
 */
	public function testLikeCondition() {
		$this->Articles->filterArgs = array(
			array('name' => 'title', 'type' => 'like'));

		$data = array();
		$result = $this->Articles->parseQuery($data);
		$expected = $this->Articles->find('all');
		$this->assertEquals($expected, $result);

		$data = array('title' => 'First');
		$result = $this->Articles->parseQuery($data);
		$expected = array('Articles.title LIKE' => '%First%');
		$expected = $this->Articles->find('all')->where($expected);
		$this->assertEquals($expected, $result);

		$this->Articles->filterArgs = array(
			array('name' => 'faketitle', 'type' => 'like', 'field' => 'Articles.title'));

		$data = array('faketitle' => 'First');
		$result = $this->Articles->parseQuery($data);
		$expected = array('Articles.title LIKE' => '%First%');
		$expected = $this->Articles->find('all')->where($expected);
		$this->assertEquals($expected, $result);

		// Wildcards should be treated as normal text
		$this->Articles->filterArgs = array(
			array('name' => 'faketitle', 'type' => 'like', 'field' => 'Articles.title')
		);
		$data = array('faketitle' => '%First_');
		$result = $this->Articles->parseQuery($data);
		$expected = array('Articles.title LIKE' => '%\%First\_%');
		$expected = $this->Articles->find('all')->where($expected);
		$this->assertEquals($expected, $result);

		// Working with like settings
		$this->Articles->behaviors()->Searchable->config('like.before', false);
		$result = $this->Articles->parseQuery($data);
		$expected = array('Articles.title LIKE' => '\%First\_%');
		$expected = $this->Articles->find('all')->where($expected);
		$this->assertEquals($expected, $result);

		$this->Articles->behaviors()->Searchable->config('like.after', false);
		$result = $this->Articles->parseQuery($data);
		$expected = array('Articles.title LIKE' => '\%First\_');
		$expected = $this->Articles->find('all')->where($expected);
		$this->assertEquals($expected, $result);

		// Now custom like should be possible
		$data = array('faketitle' => '*First?');
		$this->Articles->behaviors()->Searchable->config('like.after', false);
		$result = $this->Articles->parseQuery($data);
		$expected = array('Articles.title LIKE' => '%First_');
		$expected = $this->Articles->find('all')->where($expected);
		$this->assertEquals($expected, $result);

		$data = array('faketitle' => 'F?rst');
		$this->Articles->behaviors()->Searchable->config('like.before', true);
		$this->Articles->behaviors()->Searchable->config('like.after', true);
		$result = $this->Articles->parseQuery($data);
		$expected = array('Articles.title LIKE' => '%F_rst%');
		$expected = $this->Articles->find('all')->where($expected);
		$this->assertEquals($expected, $result);

		$data = array('faketitle' => 'F*t');
		$this->Articles->behaviors()->Searchable->config('like.before', true);
		$this->Articles->behaviors()->Searchable->config('like.after', true);
		$result = $this->Articles->parseQuery($data);
		$expected = array('Articles.title LIKE' => '%F%t%');
		$expected = $this->Articles->find('all')->where($expected);
		$this->assertEquals($expected, $result);

		// now we try the default wildcards % and _
		$data = array('faketitle' => '*First?');
		$this->Articles->behaviors()->Searchable->config('like.before', false);
		$this->Articles->behaviors()->Searchable->config('like.after', false);
		$this->Articles->behaviors()->Searchable->config('wildcardAny', '%');
		$this->Articles->behaviors()->Searchable->config('wildcardOne', '_');
		$result = $this->Articles->parseQuery($data);
		$expected = array('Articles.title LIKE' => '*First?');
		$expected = $this->Articles->find('all')->where($expected);
		$this->assertEquals($expected, $result);

		// Now it is possible and makes sense to allow wildcards in between (custom wildcard use case)
		$data = array('faketitle' => '%Fi_st_');
		$result = $this->Articles->parseQuery($data);
		$expected = array('Articles.title LIKE' => '%Fi_st_');
		$expected = $this->Articles->find('all')->where($expected);
		$this->assertEquals($expected, $result);

		// Shortcut disable/enable like before/after
		$data = array('faketitle' => '%First_');
		$this->Articles->behaviors()->Searchable->config('like', false);
		$result = $this->Articles->parseQuery($data);
		$expected = array('Articles.title LIKE' => '%First_');
		$expected = $this->Articles->find('all')->where($expected);
		$this->assertEquals($expected, $result);

		// Multiple OR fields per field
		$this->Articles->filterArgs = array(
			array('name' => 'faketitle', 'type' => 'like', 'field' => array('title', 'descr'))
		);

		$data = array('faketitle' => 'First');
		$this->Articles->behaviors()->Searchable->config('like', true);
		$result = $this->Articles->parseQuery($data);
		$expected = array(
			'OR' => array(
				'Articles.title LIKE' => '%First%',
				'Articles.descr LIKE' => '%First%'
			)
		);
		$expected = $this->Articles->find('all')->where($expected);
		$this->assertEquals($expected, $result);

		// Set before => false dynamically
		$this->Articles->filterArgs = array(
			array('name' => 'faketitle',
				'type' => 'like',
				'field' => array('title', 'descr'),
				'before' => false
			)
		);
		$data = array('faketitle' => 'First');
		$result = $this->Articles->parseQuery($data);
		$expected = array(
			'OR' => array(
				'Articles.title LIKE' => 'First%',
				'Articles.descr LIKE' => 'First%'
			)
		);
		$expected = $this->Articles->find('all')->where($expected);
		$this->assertEquals($expected, $result);

		// Manually define the before/after type
		$this->Articles->filterArgs = array(
			array('name' => 'faketitle', 'type' => 'like', 'field' => array('title'),
				'before' => '_', 'after' => '_')
		);
		$data = array('faketitle' => 'First');
		$result = $this->Articles->parseQuery($data);
		$expected = array('Articles.title LIKE' => '_First_');
		$expected = $this->Articles->find('all')->where($expected);
		$this->assertEquals($expected, $result);

		// Cross model searches + named keys (shorthand)
		$this->Articles->belongsTo('Users');
		$this->Articles->filterArgs = array(
			'faketitle' => array('type' => 'like', 'field' => array('title', 'Users.name'),
				'before' => false, 'after' => true)
		);
		$data = array('faketitle' => 'First');
		$result = $this->Articles->parseQuery($data);
		$expected = array('OR' => array('Articles.title LIKE' => 'First%', 'Users.name LIKE' => 'First%'));
		$expected = $this->Articles->find('all')->where($expected);
		$this->assertEquals($expected, $result);

		// With already existing or conditions + named keys (shorthand)
		$this->Articles->filterArgs = array(
			'faketitle' => array('type' => 'like', 'field' => array('title', 'Users.name'),
				'before' => false, 'after' => true),
			'otherfaketitle' => array('type' => 'like', 'field' => array('descr', 'comment'),
				'before' => false, 'after' => true)
		);

		$data = array('faketitle' => 'First', 'otherfaketitle' => 'Second');
		$result = $this->Articles->parseQuery($data);
		$expected = $this->Articles->find('all')
			->where(array('OR' => array(
				'Articles.title LIKE' => 'First%',
				'Users.name LIKE' => 'First%'
			)))
			->where(array(
				'OR' => array(
					'Articles.descr LIKE' => 'Second%',
					'Articles.comment LIKE' => 'Second%')
			));
		$this->assertEquals($expected, $result);

		// Wildcards and and/or connectors
		$this->Articles->removeBehavior('Searchable');
		$this->Articles->addBehavior('Search.Searchable');
		$this->Articles->filterArgs = array(
			array('name' => 'faketitle', 'type' => 'like', 'field' => 'Articles.title',
				'connectorAnd' => '+', 'connectorOr' => ',', 'before' => true, 'after' => true)
		);
		$data = array('faketitle' => 'First%+Second%, Third%');
		$result = $this->Articles->parseQuery($data);
		$expected = array(
			0 => array(
				'OR' => array(
					array('AND' => array(
						array('Articles.title LIKE' => '%First\%%'),
						array('Articles.title LIKE' => '%Second\%%'),
					)),
					array('AND' => array(
						array('Articles.title LIKE' => '%Third\%%')
					)),
				)
			)
		);
		$expected = $this->Articles->find('all')->where($expected);
		$this->assertEquals($expected, $result);
	}

/**
 * Test 'subquery' filter type
 *
 * @return void
 */
	public function testSubQueryCondition() {
		$this->Articles->filterArgs = array(
			array('name' => 'tags', 'type' => 'subquery', 'method' => 'findByTags', 'field' => 'Articles.id')
		);

		$data = array();
		$result = $this->Articles->parseQuery($data);
		$expected = $this->Articles->find('all');
		$this->assertEquals($expected, $result);

		$data = array('tags' => 'Cake');
		$result = $this->Articles->parseQuery($data);
		$expected = $this->Articles->find('all');
		$subQuery = $this->Articles->findByTags($data);
		$expected->where(array('Articles.id' => $subQuery));
		$this->assertEquals($expected, $result);
	}

/**
 * Test 'subquery' filter type when 'allowEmpty' = true
 *
 * @return void
 */
	public function testSubQueryEmptyCondition() {
		$this->Articles->filterArgs = array(
			'tags' => array('type' => 'subquery', 'method' => 'findByTags',
				'field' => 'Articles.id', 'allowEmpty' => true
			)
		);

		$data = array();
		$result = $this->Articles->parseQuery($data);
		$expected = $this->Articles->find('all');
		$subQuery = $this->Articles->findByTags($data);
		$expected->where(array('Articles.id' => $subQuery));

		$this->assertEquals($expected, $result);
	}

/**
 * Test 'query' filter type with one orConditions method
 *
 * Uses ``Article::orConditions()``.
 *
 * @return void
 */
	public function testQueryOneOrConditions() {
		$this->Articles->filterArgs = array(
			array('name' => 'filter', 'type' => 'query', 'method' => 'orConditions'));

		$data = array();
		$result = $this->Articles->parseQuery($data);
		$expected = $this->Articles->find('all');
		$this->assertEquals($expected, $result);

		$data = array('filter' => 'ticl');
		$result = $this->Articles->parseQuery($data);
		$expected = array('OR' => array(
		'Articles.title LIKE' => '%ticl%',
		'Articles.body LIKE' => '%ticl%'));
		$expected = $this->Articles->find('all')->orWhere($expected);
		$this->assertEquals($expected, $result);
	}

/**
 * Test 'query' filter type with two orConditions methods
 *
 * Uses ``Article::orConditions()`` and ``Article::or2Conditions()``.
 *
 * @return void
 */
	public function testQueryOrTwoOrConditions() {
		$this->Articles->filterArgs = array(
			array('name' => 'filter', 'type' => 'query', 'method' => 'orConditions'),
			array('name' => 'filter2', 'type' => 'query', 'method' => 'or2Conditions'));

		$data = array();
		$result = $this->Articles->parseQuery($data);
		$expected = $this->Articles->find('all');
		$this->assertEquals($expected, $result);

		$data = array('filter' => 'ticl', 'filter2' => 'test');
		$result = $this->Articles->parseQuery($data);
		$expected = $this->Articles->find('all')
			->orWhere(array(
				'OR' => array(
					'Articles.title LIKE' => '%ticl%',
					'Articles.body LIKE' => '%ticl%'
				)
			))
			->orWhere(array(
				'OR' => array(
					'Articles.field1 LIKE' => '%test%',
					'Articles.field2 LIKE' => '%test%'
				)
			));
		$this->assertEquals($expected, $result);
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
		$this->Articles->filterArgs = array(
			array('name' => 'filter', 'type' => 'query', 'method' => 'mostFilterConditions'));

		$data = array();
		$result = $this->Articles->parseQuery($data);
		$expected = $this->Articles->find('all');
		$this->assertEquals($expected, $result);

		$data = array('filter' => 'views');
		$result = $this->Articles->parseQuery($data);
		$expected = array('Articles.views > 10');
		$expected = $this->Articles->find('all')->where($expected);
		$this->assertEquals($expected, $result);
	}

/**
 * Test 'query' filter type with 'defaultValue' set
 *
 * @return void
 */
	public function testDefaultValue() {
		$this->Articles->filterArgs = array(
			'faketitle' => array('type' => 'value', 'defaultValue' => '100')
		);

		$data = array();
		$result = $this->Articles->parseQuery($data);
		$expected = array('Articles.faketitle' => 100);
		$expected = $this->Articles->find('all')->where($expected);
		$this->assertEquals($expected, $result);
	}

/**
 * Test validateSearch()
 *
 * @return void
 */
	public function testValidateSearch() {
		$this->Articles->filterArgs = array();
		$data = array('title' => 'Last Article');
		$entity = $this->Articles->newEntity($data);
		$this->Articles->validateSearch($entity);
		$this->assertEquals($data, $entity->toArray());

		$data = array('title' => '');
		$entity = $this->Articles->newEntity($data);
		$this->Articles->validateSearch($entity);
		$this->assertEquals(array(), $entity->toArray());
	}

/**
 * Test passedArgs()
 *
 * @return void
 */
	public function testPassedArgs() {
		$this->Articles->filterArgs = array(
			array('name' => 'slug', 'type' => 'value'));
		$data = array('slug' => 'first_article', 'filter' => 'myfilter');
		$result = $this->Articles->passedArgs($data);
		$expected = array('slug' => 'first_article');
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
		$this->Articles->filterArgs = array(
			'title' => array(
				'name' => 'title',
				'type' => 'like',
				'field' => 'Articles.title',
				'allowEmpty' => true
			),
			'author' => array(
				'name' => 'author',
				'type' => 'value',
				'field' => 'Articles.author',
				'allowEmpty' => true
			),
			'created' => array(
				'name' => 'created',
				'type' => 'value',
				'field' => 'Articles.created',
				'allowEmpty' => true
			),
			'slug' => array(
				'name' => 'slug',
				'type' => 'value',
				'field' => 'Articles.slug',
				'allowEmpty' => true
			),
		);
		$data = array('title' => 'first', 'author' => '', 'created' => '', 'slug' => null);
		$expected = array(
			'Articles.title LIKE' => '%first%',
			'Articles.author' => '',
			'Articles.created' => null,
		);
		$result = $this->Articles->parseQuery($data);
		$expected = $this->Articles->find('all')->where($expected);
		$this->assertEquals($expected, $result);
	}

}
