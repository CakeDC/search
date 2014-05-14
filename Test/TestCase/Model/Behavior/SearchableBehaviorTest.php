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
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;

/**
 * FilterBehavior class
 *
 * Contains a filter condition for the query test
 * testQueryWithBehaviorCallCondition.
 */
class FilterBehavior extends Behavior {

/**
 * mostFilterConditions
 *
 * @param array $data
 * @return array
 */
	public function mostFilterConditions($data = array()) {
		$filter = $data['filter'];
		if (!in_array($filter, array('views', 'comments'))) {
			return array();
		}
		switch ($filter) {
			case 'views':
				$cond = $Model->alias . '.views > 10';
				break;
			case 'comments':
				$cond = $Model->alias . '.comments > 10';
				break;
		}
		return (array)$cond;
	}

}

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
		$this->belongsToMany('Tags', [
			'with' => 'Tagged'
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
		$this->Tagged->Behaviors->attach('Containable', array('autoFields' => false));
		$this->Tagged->Behaviors->attach('Search.Searchable');
		$conditions = array();
		if (!empty($data['tags'])) {
			$conditions = array('Tag.name' => $data['tags']);
		}
		$this->Tagged->order = null;
		$query = $this->Tagged->getQuery('all', array(
			'conditions' => $conditions,
			'fields' => array('foreign_key'),
			'contain' => array('Tag')
		));
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
	public function orConditions($data = array()) {
		$filter = $data['filter'];
		$cond = array(
			'OR' => array(
				$this->alias() . '.title LIKE' => '%' . $filter . '%',
				$this->alias() . '.body LIKE' => '%' . $filter . '%',
			));
		return $cond;
	}

	public function or2Conditions($data = array()) {
		$filter = $data['filter2'];
		$cond = array(
			'OR' => array(
				$this->alias() . '.field1 LIKE' => '%' . $filter . '%',
				$this->alias() . '.field2 LIKE' => '%' . $filter . '%',
			));
		return $cond;
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

		$this->Articles->Behaviors->Searchable->settings['Article']['wildcardAny'] = false;
		$this->Articles->Behaviors->Searchable->settings['Article']['wildcardOne'] = false;
		$result = $this->Articles->getWildcards();
		$expected = array('any' => false, 'one' => false);
		$this->assertSame($expected, $result);

//		$this->Articles->Behaviors->Searchable->settings['Article']['wildcardAny'] = '%';
//		$this->Articles->Behaviors->Searchable->settings['Article']['wildcardOne'] = '_';
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
		$this->Articles->addBehavior('Search.Searchable');
		$data = array();
		$result = $this->Articles->parseCriteria($data);
		$this->assertEquals(array(), $result);

		$data = array('slug' => 'first_article');
		$result = $this->Articles->parseCriteria($data);
		$expected = array('Article.slug' => 'first_article');
		$this->assertEquals($expected, $result);

		$this->Articles->filterArgs = array(
			array('name' => 'fakeslug', 'type' => 'value', 'field' => 'Article2.slug'));
		$this->Articles->addBehavior('Search.Searchable');
		$data = array('fakeslug' => 'first_article');
		$result = $this->Articles->parseCriteria($data);
		$expected = array('Article2.slug' => 'first_article');
		$this->assertEquals($expected, $result);

		// Testing http://github.com/CakeDC/Search/issues#issue/3
		$this->Articles->filterArgs = array(
			array('name' => 'views', 'type' => 'value'));
		$this->Articles->addBehavior('Search.Searchable');
		$data = array('views' => '0');
		$result = $this->Articles->parseCriteria($data);
		$this->assertEquals(array('Article.views' => 0), $result);

		$this->Articles->filterArgs = array(
			array('name' => 'views', 'type' => 'value'));
		$this->Articles->addBehavior('Search.Searchable');
		$data = array('views' => 0);
		$result = $this->Articles->parseCriteria($data);
		$this->assertEquals(array('Article.views' => 0), $result);

		$this->Articles->filterArgs = array(
			array('name' => 'views', 'type' => 'value'));
		$this->Articles->addBehavior('Search.Searchable');
		$data = array('views' => '');
		$result = $this->Articles->parseCriteria($data);
		$this->assertEquals(array(), $result);

		// Multiple fields + cross model searches
		$this->Articles->filterArgs = array(
			'faketitle' => array('type' => 'value', 'field' => array('title', 'User.name'))
		);
		$this->Articles->addBehavior('Search.Searchable');
		$data = array('faketitle' => 'First');
		$result = $this->Articles->parseCriteria($data);
		$expected = array('OR' => array('Article.title' => 'First', 'User.name' => 'First'));
		$this->assertEquals($expected, $result);

		// Multiple select dropdown
		$this->Articles->filterArgs = array(
			'fakesource' => array('type' => 'value')
		);
		$this->Articles->addBehavior('Search.Searchable');
		$data = array('fakesource' => array(5, 9));
		$result = $this->Articles->parseCriteria($data);
		$expected = array('Article.fakesource' => array(5, 9));
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
		$this->Articles->addBehavior('Search.Searchable');

		$data = array();
		$result = $this->Articles->parseCriteria($data);
		$this->assertEquals(array(), $result);

		$data = array('title' => 'First');
		$result = $this->Articles->parseCriteria($data);
		$expected = array('Article.title LIKE' => '%First%');
		$this->assertEquals($expected, $result);

		$this->Articles->filterArgs = array(
			array('name' => 'faketitle', 'type' => 'like', 'field' => 'Article.title'));
		$this->Articles->addBehavior('Search.Searchable');

		$data = array('faketitle' => 'First');
		$result = $this->Articles->parseCriteria($data);
		$expected = array('Article.title LIKE' => '%First%');
		$this->assertEquals($expected, $result);

		// Wildcards should be treated as normal text
		$this->Articles->filterArgs = array(
			array('name' => 'faketitle', 'type' => 'like', 'field' => 'Article.title')
		);
		$this->Articles->addBehavior('Search.Searchable');
		$data = array('faketitle' => '%First_');
		$result = $this->Articles->parseCriteria($data);
		$expected = array('Article.title LIKE' => '%\%First\_%');
		$this->assertEquals($expected, $result);

		// Working with like settings
		$this->Articles->Behaviors->Searchable->settings['Article']['like']['before'] = false;
		$result = $this->Articles->parseCriteria($data);
		$expected = array('Article.title LIKE' => '\%First\_%');
		$this->assertEquals($expected, $result);

		$this->Articles->Behaviors->Searchable->settings['Article']['like']['after'] = false;
		$result = $this->Articles->parseCriteria($data);
		$expected = array('Article.title LIKE' => '\%First\_');
		$this->assertEquals($expected, $result);

		// Now custom like should be possible
		$data = array('faketitle' => '*First?');
		$this->Articles->Behaviors->Searchable->settings['Article']['like']['after'] = false;
		$result = $this->Articles->parseCriteria($data);
		$expected = array('Article.title LIKE' => '%First_');
		$this->assertEquals($expected, $result);

		$data = array('faketitle' => 'F?rst');
		$this->Articles->Behaviors->Searchable->settings['Article']['like']['before'] = true;
		$this->Articles->Behaviors->Searchable->settings['Article']['like']['after'] = true;
		$result = $this->Articles->parseCriteria($data);
		$expected = array('Article.title LIKE' => '%F_rst%');
		$this->assertEquals($expected, $result);

		$data = array('faketitle' => 'F*t');
		$this->Articles->Behaviors->Searchable->settings['Article']['like']['before'] = true;
		$this->Articles->Behaviors->Searchable->settings['Article']['like']['after'] = true;
		$result = $this->Articles->parseCriteria($data);
		$expected = array('Article.title LIKE' => '%F%t%');
		$this->assertEquals($expected, $result);

		// now we try the default wildcards % and _
		$data = array('faketitle' => '*First?');
		$this->Articles->Behaviors->Searchable->settings['Article']['like']['before'] = false;
		$this->Articles->Behaviors->Searchable->settings['Article']['like']['after'] = false;
		$this->Articles->Behaviors->Searchable->settings['Article']['wildcardAny'] = '%';
		$this->Articles->Behaviors->Searchable->settings['Article']['wildcardOne'] = '_';
		$result = $this->Articles->parseCriteria($data);
		$expected = array('Article.title LIKE' => '*First?');
		$this->assertEquals($expected, $result);

		// Now it is possible and makes sense to allow wildcards in between (custom wildcard use case)
		$data = array('faketitle' => '%Fi_st_');
		$result = $this->Articles->parseCriteria($data);
		$expected = array('Article.title LIKE' => '%Fi_st_');
		$this->assertEquals($expected, $result);

		// Shortcut disable/enable like before/after
		$data = array('faketitle' => '%First_');
		$this->Articles->Behaviors->Searchable->settings['Article']['like'] = false;
		$result = $this->Articles->parseCriteria($data);
		$expected = array('Article.title LIKE' => '%First_');
		$this->assertEquals($expected, $result);

		// Multiple OR fields per field
		$this->Articles->filterArgs = array(
			array('name' => 'faketitle', 'type' => 'like', 'field' => array('title', 'descr'))
		);
		$this->Articles->addBehavior('Search.Searchable');
		$data = array('faketitle' => 'First');
		$this->Articles->Behaviors->Searchable->settings['Article']['like'] = true;
		$result = $this->Articles->parseCriteria($data);
		$expected = array('OR' => array('Article.title LIKE' => '%First%',
			'Article.descr LIKE' => '%First%')
		);
		$this->assertEquals($expected, $result);

		// Set before => false dynamically
		$this->Articles->filterArgs = array(
			array('name' => 'faketitle',
				'type' => 'like',
				'field' => array('title', 'descr'),
				'before' => false
			)
		);
		$this->Articles->addBehavior('Search.Searchable');
		$data = array('faketitle' => 'First');
		$result = $this->Articles->parseCriteria($data);
		$expected = array('OR' => array('Article.title LIKE' => 'First%',
			'Article.descr LIKE' => 'First%')
		);
		$this->assertEquals($expected, $result);

		// Manually define the before/after type
		$this->Articles->filterArgs = array(
			array('name' => 'faketitle', 'type' => 'like', 'field' => array('title'),
				'before' => '_', 'after' => '_')
		);
		$this->Articles->addBehavior('Search.Searchable');
		$data = array('faketitle' => 'First');
		$result = $this->Articles->parseCriteria($data);
		$expected = array('Article.title LIKE' => '_First_');
		$this->assertEquals($expected, $result);

		// Cross model searches + named keys (shorthand)
		$this->Articles->bindModel(array('belongsTo' => array('User')));
		$this->Articles->filterArgs = array(
			'faketitle' => array('type' => 'like', 'field' => array('title', 'User.name'),
				'before' => false, 'after' => true)
		);
		$this->Articles->addBehavior('Search.Searchable');
		$data = array('faketitle' => 'First');
		$result = $this->Articles->parseCriteria($data);
		$expected = array('OR' => array('Article.title LIKE' => 'First%', 'User.name LIKE' => 'First%'));
		$this->assertEquals($expected, $result);

		// With already existing or conditions + named keys (shorthand)
		$this->Articles->filterArgs = array(
			'faketitle' => array('type' => 'like', 'field' => array('title', 'User.name'),
				'before' => false, 'after' => true),
			'otherfaketitle' => array('type' => 'like', 'field' => array('descr', 'comment'),
				'before' => false, 'after' => true)
		);
		$this->Articles->addBehavior('Search.Searchable');

		$data = array('faketitle' => 'First', 'otherfaketitle' => 'Second');
		$result = $this->Articles->parseCriteria($data);
		$expected = array(
			'OR' => array('Article.title LIKE' => 'First%', 'User.name LIKE' => 'First%'),
			array('OR' => array(
				'Article.descr LIKE' => 'Second%',
				'Article.comment LIKE' => 'Second%')
			)
		);
		$this->assertEquals($expected, $result);

		// Wildcards and and/or connectors
		$this->Articles->Behaviors->unload('Search.Searchable');
		$this->Articles->filterArgs = array(
			array('name' => 'faketitle', 'type' => 'like', 'field' => 'Article.title',
				'connectorAnd' => '+', 'connectorOr' => ',', 'before' => true, 'after' => true)
		);
		$this->Articles->addBehavior('Search.Searchable');
		$data = array('faketitle' => 'First%+Second%, Third%');
		$result = $this->Articles->parseCriteria($data);
		$expected = array(0 => array('OR' => array(
			array('AND' => array(
				array('Article.title LIKE' => '%First\%%'),
				array('Article.title LIKE' => '%Second\%%'),
			)),
			array('AND' => array(
				array('Article.title LIKE' => '%Third\%%')
			)),
		)));
		$this->assertEquals($expected, $result);
	}

/**
 * Test 'subquery' filter type
 *
 * @return void
 */
	public function testSubQueryCondition() {
		if ($this->connection->config()['driver'] !== 'Cake\Database\Driver\Mysql') {
			$this->markTestSkipped('Test requires mysql db.');
		}
		$database = $this->connection->config()['database'];

		$this->Articles->filterArgs = array(
			array('name' => 'tags', 'type' => 'subquery', 'method' => 'findByTags', 'field' => 'Article.id')
		);

		$data = array();
		$result = $this->Articles->parseCriteria($data);
		$this->assertEquals(array(), $result);

		$data = array('tags' => 'Cake');
		$result = $this->Articles->parseCriteria($data);
		$expression = $this->Articles->getDatasource()->expression(
			'Article.id in (SELECT `Tagged`.`foreign_key` FROM `' .
			$database . '`.`' . $this->Articles->tablePrefix . 'tagged` AS `Tagged` LEFT JOIN `' .
			$database . '`.`' . $this->Articles->tablePrefix .
			'tags` AS `Tag` ON (`Tagged`.`tag_id` = `Tag`.`id`)  WHERE `Tag`.`name` = \'Cake\')'
		);
		$expected = array($expression);
		$this->assertEquals($expected, $result);
	}

/**
 * Test 'subquery' filter type when 'allowEmpty' = true
 *
 * @return void
 */
	public function testSubQueryEmptyCondition() {
		if ($this->connection->config()['driver'] !== 'Cake\Database\Driver\Mysql') {
			$this->markTestSkipped('Test requires mysql db.');
		}
		$database = $this->connection->config()['database'];

		// Old syntax
		$this->Articles->filterArgs = array(
			array('name' => 'tags', 'type' => 'subquery', 'method' => 'findByTags',
				'field' => 'Article.id', 'allowEmpty' => true
			)
		);

		$data = array('tags' => 'Cake');
		$this->Articles->parseCriteria($data);
		$expression = $this->Articles->getDatasource()->expression(
			'Article.id in (SELECT `Tagged`.`foreign_key` FROM `' .
			$database . '`.`' . $this->Articles->tablePrefix . 'tagged` AS `Tagged` LEFT JOIN `' .
			$database . '`.`' . $this->Articles->tablePrefix .
			'tags` AS `Tag` ON (`Tagged`.`tag_id` = `Tag`.`id`)  WHERE `Tag`.`name` = \'Cake\')'
		);
		$expected = array($expression);

		// New syntax
		$this->Articles->filterArgs = array(
			'tags' => array('type' => 'subquery', 'method' => 'findByTags',
				'field' => 'Article.id', 'allowEmpty' => true
			)
		);
		$this->Articles->addBehavior('Search.Searchable');

		$result = $this->Articles->parseCriteria($data);
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
		$result = $this->Articles->parseCriteria($data);
		$this->assertEquals(array(), $result);

		$data = array('filter' => 'ticl');
		$result = $this->Articles->parseCriteria($data);
		$expected = array('OR' => array(
		'Article.title LIKE' => '%ticl%',
		'Article.body LIKE' => '%ticl%'));
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
		$result = $this->Articles->parseCriteria($data);
		$this->assertEquals(array(), $result);

		$data = array('filter' => 'ticl', 'filter2' => 'test');
		$result = $this->Articles->parseCriteria($data);
		$expected = array('OR' => array(
			'Article.title LIKE' => '%ticl%',
			'Article.body LIKE' => '%ticl%',
			'Article.field1 LIKE' => '%test%',
			'Article.field2 LIKE' => '%test%'));
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
		$this->Articles->addBehavior('Filter');
		$this->Articles->filterArgs = array(
			array('name' => 'filter', 'type' => 'query', 'method' => 'mostFilterConditions'));

		$data = array();
		$result = $this->Articles->parseCriteria($data);
		$this->assertEquals(array(), $result);

		$data = array('filter' => 'views');
		$result = $this->Articles->parseCriteria($data);
		$expected = array('Article.views > 10');
		$this->assertEquals($expected, $result);
	}

/**
 * Test 'expression' filter type
 *
 * Uses ``Article::makeRangeCondition()`` and
 * a non-existent one.
 *
 * @return void
 */
	public function testExpressionCallCondition() {
		$this->Articles->filterArgs = array(
			array('name' => 'range', 'type' => 'expression', 'method' => 'makeRangeCondition',
				'field' => 'Article.views BETWEEN ? AND ?')
		);
		$data = array();
		$result = $this->Articles->parseCriteria($data);
		$this->assertEquals(array(), $result);

		$data = array('range' => '10');
		$result = $this->Articles->parseCriteria($data);
		$expected = array('Article.views BETWEEN ? AND ?' => array(0, 10));
		$this->assertEquals($expected, $result);

		$this->Articles->filterArgs = array(
			array('name' => 'range', 'type' => 'expression', 'method' => 'testThatInBehaviorMethodNotDefined',
				'field' => 'Article.views BETWEEN ? AND ?')
		);
		$data = array('range' => '10');
		$result = $this->Articles->parseCriteria($data);
		$this->assertEquals(array(), $result);
	}

/**
 * Test 'query' filter type with 'defaultValue' set
 *
 * @return void
 */
	public function testDefaultValue() {
		$this->Articles->filterArgs = array(
			'range' => array('type' => 'expression', 'defaultValue' => '100', 'method' => 'makeRangeCondition',
				'field' => 'Article.views BETWEEN ? AND ?')
		);
		$this->Articles->addBehavior('Search.Searchable');

		$data = array();
		$result = $this->Articles->parseCriteria($data);
		$expected = array(
			'Article.views BETWEEN ? AND ?' => array(11, 100));
		$this->assertEquals($expected, $result);
	}

/**
 * Test validateSearch()
 *
 * @return void
 */
	public function testValidateSearch() {
		$this->Articles->filterArgs = array();
		$data = array('Article' => array('title' => 'Last Article'));
		$this->Articles->set($data);
		$this->Articles->validateSearch();
		$this->assertEquals($data, $this->Articles->data);

		$this->Articles->validateSearch($data);
		$this->assertEquals($data, $this->Articles->data);

		$data = array('Article' => array('title' => ''));
		$this->Articles->validateSearch($data);
		$expected = array('Article' => array());
		$this->assertEquals($expected, $this->Articles->data);
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
 * Test getQuery()
 *
 * @return void
 */
	public function testGetQuery() {
		if ($this->connection->config()['driver'] !== 'Cake\Database\Driver\Mysql') {
			$this->markTestSkipped('Test requires mysql db.');
		}
		$database = $this->connection->config()['database'];

		$conditions = array('Article.id' => 1);
		$result = $this->Articles->getQuery('all', array(
			'conditions' => $conditions,
			'order' => 'title',
			'page' => 2,
			'limit' => 2,
			'fields' => array('id', 'title')
		));
		$expected = 'SELECT `Article`.`id`, `Article`.`title` FROM `' .
			$database . '`.`' . $this->Articles->tablePrefix .
			'articles` AS `Article`   WHERE `Article`.`id` = 1   ORDER BY `title` ASC  LIMIT 2, 2';
		$this->assertEquals($expected, $result);

		$this->Articles->Tagged->Behaviors->attach('Search.Searchable');
		$conditions = array('Tagged.tag_id' => 1);
		$this->Articles->Tagged->recursive = -1;
		$order = array('Tagged.id' => 'ASC');
		$result = $this->Articles->Tagged->getQuery('first', compact('conditions', 'order'));
		$expected = 'SELECT `Tagged`.`id`, `Tagged`.`foreign_key`, `Tagged`.`tag_id`, ' .
			'`Tagged`.`model`, `Tagged`.`language`, `Tagged`.`created`, `Tagged`.`modified` ' .
			'FROM `' . $database . '`.`' . $this->Articles->tablePrefix .
			'tagged` AS `Tagged`   WHERE `Tagged`.`tag_id` = \'1\'   ORDER BY `Tagged`.`id` ASC  LIMIT 1';
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
				'field' => 'Article.title',
				'allowEmpty' => true
			),
			'author' => array(
				'name' => 'author',
				'type' => 'value',
				'field' => 'Article.author',
				'allowEmpty' => true
			),
			'created' => array(
				'name' => 'created',
				'type' => 'value',
				'field' => 'Article.created',
				'allowEmpty' => true
			),
			'slug' => array(
				'name' => 'slug',
				'type' => 'value',
				'field' => 'Article.slug',
				'allowEmpty' => true
			),
		);
		$data = array('title' => 'first', 'author' => '', 'created' => '', 'slug' => null);
		$expected = array(
			'Article.title LIKE' => '%first%',
			'Article.author' => '',
			'Article.created' => null,
		);
		$result = $this->Articles->parseCriteria($data);
		$this->assertSame($expected, $result);
	}

}
