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

App::uses('Model', 'Model');
App::uses('ModelBehavior', 'Model');

/**
 * Searchable behavior tests
 *
 * @package search
 * @subpackage search.tests.cases.behaviors
 */
class FilterBehavior extends ModelBehavior {

/**
 * mostFilterConditions
 *
 * @param Model $Model
 * @param string $data
 * @return array
 */
	public function mostFilterConditions(Model $Model, $data = array()) {
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
 * Tag model
 *
 * @package search
 * @subpackage search.tests.cases.behaviors
 */
class Tag extends CakeTestModel {
}

/**
 * Tagged model
 *
 * @package search
 * @subpackage search.tests.cases.behaviors
 */
class Tagged extends CakeTestModel {

/**
 * Table to use
 *
 * @var string
 */
	public $useTable = 'tagged';

/**
 * Belongs To Assocaitions
 *
 * @var array
 */
	public $belongsTo = array('Tag');

}

/**
 * Article model
 *
 * @package search
 * @subpackage search.tests.cases.behaviors
 */
class Article extends CakeTestModel {

/**
 * Behaviors
 *
 * @var array
 */
	public $actsAs = array('Search.Searchable');

/**
 * HABTM associations
 *
 * @var array
 */
	public $hasAndBelongsToMany = array('Tag' => array('with' => 'Tagged'));

/**
 * Find by tags
 *
 * @param string $data
 * @return array
 */
	public function findByTags($data = array()) {
		$this->Tagged->Behaviors->attach('Containable', array('autoFields' => false));
		$this->Tagged->Behaviors->attach('Search.Searchable');
		$conditions = array();
		if (!empty($data['tags'])) {
			$conditions = array('Tag.name'  => $data['tags']);
		}
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
				$this->alias . '.title LIKE' => '%' . $filter . '%',
				$this->alias . '.body LIKE' => '%' . $filter . '%',
			));
		return $cond;
	}

}

/**
 * SearchableTestCase
 *
 * @package search
 * @subpackage search.tests.cases.behaviors
 */
class SearchableTest extends CakeTestCase {

/**
 * Fixtures used in the SessionTest
 *
 * @var array
 */
	public $fixtures = array('plugin.search.article', 'plugin.search.tag', 'plugin.search.tagged', 'core.user');

/**
 * startTest
 *
 * @return void
 */
	public function startTest() {
		$this->Article = ClassRegistry::init('Article');
	}

/**
 * endTest
 *
 * @return void
 */
	public function endTest() {
		unset($this->Article);
	}

/**
 * testGetWildcards
 *
 * @return void
 */
	public function testGetWildcards() {
		$result = $this->Article->getWildcards();
		$expected = array('any' => '*', 'one' => '?');
		$this->assertSame($result, $expected);

		$this->Article->Behaviors->Searchable->settings['Article']['wildcardAny'] = false;
		$this->Article->Behaviors->Searchable->settings['Article']['wildcardOne'] = false;
		$result = $this->Article->getWildcards();
		$expected = array('any' => false, 'one' => false);
		$this->assertSame($result, $expected);

		$this->Article->Behaviors->Searchable->settings['Article']['wildcardAny'] = '%';
		$this->Article->Behaviors->Searchable->settings['Article']['wildcardOne'] = '_';
		$result = $this->Article->getWildcards();
		$expected = array('any' => '%', 'one' => '_');
		$this->assertSame($result, $expected);
	}

/**
 * testValueCondition
 *
 * @return void
 * @link http://github.com/CakeDC/Search/issues#issue/3
 */
	public function testValueCondition() {
		$this->Article->filterArgs = array(
			array('name' => 'slug', 'type' => 'value'));

		$data = array();
		$result = $this->Article->parseCriteria($data);
		$this->assertEquals(array(), $result);

		$data = array('slug' => 'first_article');
		$result = $this->Article->parseCriteria($data);
		$expected = array('Article.slug' => 'first_article');
		$this->assertEquals($expected, $result);

		$this->Article->filterArgs = array(
			array('name' => 'fakeslug', 'type' => 'value', 'field' => 'Article2.slug'));
		$data = array('fakeslug' => 'first_article');
		$result = $this->Article->parseCriteria($data);
		$expected = array('Article2.slug' => 'first_article');
		$this->assertEquals($expected, $result);

		// Testing http://github.com/CakeDC/Search/issues#issue/3
		$this->Article->filterArgs = array(
			array('name' => 'views', 'type' => 'value'));
		$data = array('views' => '0');
		$result = $this->Article->parseCriteria($data);
		$this->assertEquals(array('Article.views' => 0), $result);

		$this->Article->filterArgs = array(
			array('name' => 'views', 'type' => 'value'));
		$data = array('views' => 0);
		$result = $this->Article->parseCriteria($data);
		$this->assertEquals(array('Article.views' => 0), $result);

		$this->Article->filterArgs = array(
			array('name' => 'views', 'type' => 'value'));
		$data = array('views' => '');
		$result = $this->Article->parseCriteria($data);
		$this->assertEquals(array(), $result);
	}

/**
 * testLikeCondition
 *
 * @return void
 */
	public function testLikeCondition() {
		$this->Article->filterArgs = array(
			array('name' => 'title', 'type' => 'like'));

		$data = array();
		$result = $this->Article->parseCriteria($data);
		$this->assertEquals(array(), $result);

		$data = array('title' => 'First');
		$result = $this->Article->parseCriteria($data);
		$expected = array('Article.title LIKE' => '%First%');
		$this->assertEquals($expected, $result);

		$this->Article->filterArgs = array(
			array('name' => 'faketitle', 'type' => 'like', 'field' => 'Article.title'));
		$data = array('faketitle' => 'First');
		$result = $this->Article->parseCriteria($data);
		$expected = array('Article.title LIKE' => '%First%');
		$this->assertEquals($expected, $result);

		// wildcards should be treated as normal text
		$this->Article->filterArgs = array(
			array('name' => 'faketitle', 'type' => 'like', 'field' => 'Article.title')
		);
		$data = array('faketitle' => '%First_');
		$result = $this->Article->parseCriteria($data);
		$expected = array('Article.title LIKE' => '%\%First\_%');
		$this->assertEquals($expected, $result);

		// working with like settings
		//pr($this->Article->Behaviors->Searchable->settings);
		$this->Article->Behaviors->Searchable->settings['Article']['like']['before'] = false;
		$result = $this->Article->parseCriteria($data);
		$expected = array('Article.title LIKE' => '\%First\_%');
		$this->assertEquals($expected, $result);

		$this->Article->Behaviors->Searchable->settings['Article']['like']['after'] = false;
		$result = $this->Article->parseCriteria($data);
		$expected = array('Article.title LIKE' => '\%First\_');
		$this->assertEquals($expected, $result);

		// now custom like should be possible
		$data = array('faketitle' => '*First?');
		$this->Article->Behaviors->Searchable->settings['Article']['like']['after'] = false;
		$result = $this->Article->parseCriteria($data);
		$expected = array('Article.title LIKE' => '%First_');
		$this->assertEquals($expected, $result);

		// now we try the default wildcards % and _
		$data = array('faketitle' => '*First?');
		$this->Article->Behaviors->Searchable->settings['Article']['like']['after'] = false;
		$this->Article->Behaviors->Searchable->settings['Article']['wildcardAny'] = '%';
		$this->Article->Behaviors->Searchable->settings['Article']['wildcardOne'] = '_';
		$result = $this->Article->parseCriteria($data);
		$expected = array('Article.title LIKE' => '*First?');
		$this->assertEquals($expected, $result);

		// now it is possible and makes sense to allow wildcards in between (custom wildcard use case)
		$data = array('faketitle' => '%Fi_st_');
		$result = $this->Article->parseCriteria($data);
		$expected = array('Article.title LIKE' => '%Fi_st_');
		$this->assertEquals($expected, $result);

		// shortcut disable/enable like before/after
		$data = array('faketitle' => '%First_');
		$this->Article->Behaviors->Searchable->settings['Article']['like'] = false;
		$result = $this->Article->parseCriteria($data);
		$expected = array('Article.title LIKE' => '%First_');
		$this->assertEquals($expected, $result);

		$data = array('faketitle' => '%First_');
		$this->Article->Behaviors->Searchable->settings['Article']['like'] = true;
		$result = $this->Article->parseCriteria($data);
		$expected = array('Article.title LIKE' => '%\%First\_%');
		$this->assertEquals($expected, $result);

		// multiple OR fields per field
		$this->Article->filterArgs = array(
			array('name' => 'faketitle', 'type' => 'like', 'field' => array('title', 'descr'))
		);
		$data = array('faketitle' => 'First');

		$result = $this->Article->parseCriteria($data);
		$expected = array('OR' => array('Article.title LIKE' => '%First%', 'Article.descr LIKE' => '%First%'));
		$this->assertEquals($expected, $result);

		// set before => false dynamically
		$this->Article->filterArgs = array(
			array('name' => 'faketitle', 'type' => 'like', 'field' => array('title', 'descr'), 'before' => false)
		);
		$data = array('faketitle' => 'First');
		$result = $this->Article->parseCriteria($data);
		$expected = array('OR' => array('Article.title LIKE' => 'First%', 'Article.descr LIKE' => 'First%'));
		$this->assertEquals($expected, $result);

		// manually define the before/after type
		$this->Article->filterArgs = array(
			array('name' => 'faketitle', 'type' => 'like', 'field' => array('title'), 'before' => '_', 'after' => '_')
		);
		$data = array('faketitle' => 'First');
		$result = $this->Article->parseCriteria($data);
		$expected = array('Article.title LIKE' => '_First_');
		$this->assertEquals($expected, $result);

		// cross model searches + named keys (shorthand)
		$this->Article->bindModel(array('belongsTo' => array('User')));
		$this->Article->filterArgs = array(
			'faketitle' => array('type' => 'like', 'field' => array('title', 'User.name'), 'before' => false, 'after' => true)
		);
		$this->Article->Behaviors->detach('Searchable');
		$this->Article->Behaviors->attach('Search.Searchable');
		$data = array('faketitle' => 'First');
		$result = $this->Article->parseCriteria($data);
		$expected = array('OR' => array('Article.title LIKE' => 'First%', 'User.name LIKE' => 'First%'));
		$this->assertEquals($expected, $result);

		// with already existing or conditions + named keys (shorthand)
		$this->Article->filterArgs = array(
			'faketitle' => array('type' => 'like', 'field' => array('title', 'User.name'), 'before' => false, 'after' => true),
			'otherfaketitle' => array('type' => 'like', 'field' => array('descr', 'comment'), 'before' => false, 'after' => true)
		);
		$this->Article->Behaviors->detach('Searchable');
		$this->Article->Behaviors->attach('Search.Searchable');

		$data = array('faketitle' => 'First', 'otherfaketitle' => 'Second');
		$result = $this->Article->parseCriteria($data);
		$expected = array(
			'OR' => array('Article.title LIKE' => 'First%', 'User.name LIKE' => 'First%'),
			array('OR' => array('Article.descr LIKE' => 'Second%', 'Article.comment LIKE' => 'Second%'))
		);
		$this->assertEquals($expected, $result);

		// wildcards and and/or connectors
		$this->Article->filterArgs = array(
			array('name' => 'faketitle', 'type' => 'like', 'field' => 'Article.title', 'connectorAnd' => '+', 'connectorOr' => ',', 'before' => true, 'after' => true)
		);
		$data = array('faketitle' => 'First%+Second%, Third%');
		$result = $this->Article->parseCriteria($data);
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
 * testSubQueryCondition
 *
 * @return void
 */
	public function testSubQueryCondition() {
		if ($this->skipIf($this->db->config['datasource'] != 'Database/Mysql', 'Test requires mysql db. %s')) {
			return;
		}
		$database = $this->db->config['database'];

		$this->Article->filterArgs = array(
			array('name' => 'tags', 'type' => 'subquery', 'method' => 'findByTags', 'field' => 'Article.id')
		);

		$data = array();
		$result = $this->Article->parseCriteria($data);
		$this->assertEquals(array(), $result);

		$data = array('tags' => 'Cake');
		$result = $this->Article->parseCriteria($data);
		$expected = array(array('Article.id in (SELECT `Tagged`.`foreign_key` FROM `' . $database . '`.`' . $this->Article->tablePrefix . 'tagged` AS `Tagged` LEFT JOIN `' . $database . '`.`' . $this->Article->tablePrefix . 'tags` AS `Tag` ON (`Tagged`.`tag_id` = `Tag`.`id`)  WHERE `Tag`.`name` = \'Cake\')'));
		$this->assertEquals($expected, $result);
	}

/**
 * testSubQueryEmptyCondition
 *
 * @return void
 */
	public function testSubQueryEmptyCondition() {
		if ($this->skipIf($this->db->config['datasource'] != 'Database/Mysql', 'Test requires mysql db. %s')) {
			return;
		}
		$database = $this->db->config['database'];

		$this->Article->filterArgs = array(
			array('name' => 'tags', 'type' => 'subquery', 'method' => 'findByTags', 'field' => 'Article.id')
		);

		$data = array('tags' => 'Cake');
		$result = $this->Article->parseCriteria($data);

		$expected = array(array('Article.id in (SELECT `Tagged`.`foreign_key` FROM `' . $database . '`.`' . $this->Article->tablePrefix . 'tagged` AS `Tagged` LEFT JOIN `' . $database . '`.`' . $this->Article->tablePrefix . 'tags` AS `Tag` ON (`Tagged`.`tag_id` = `Tag`.`id`)  WHERE `Tag`.`name` = \'Cake\')'));

		$this->assertEquals($expected, $result);
	}

/**
 * testQueryOrExample
 *
 * @return void
 */
	public function testQueryOrExample() {
		$this->Article->filterArgs = array(
			array('name' => 'filter', 'type' => 'query', 'method' => 'orConditions'));

		$data = array();
		$result = $this->Article->parseCriteria($data);
		$this->assertEquals(array(), $result);

		$data = array('filter' => 'ticl');
		$result = $this->Article->parseCriteria($data);
		$expected = array('OR' => array(
		'Article.title LIKE' => '%ticl%',
		'Article.body LIKE' => '%ticl%'));
		$this->assertEquals($expected, $result);
	}

/**
 * testQueryWithBehaviorCallCondition
 *
 * @return void
 */
	public function testQueryWithBehaviorCallCondition() {
		$this->Article->Behaviors->attach('Filter');
		$this->Article->filterArgs = array(
			array('name' => 'filter', 'type' => 'query', 'method' => 'mostFilterConditions'));

		$data = array();
		$result = $this->Article->parseCriteria($data);
		$this->assertEquals(array(), $result);

		$data = array('filter' => 'views');
		$result = $this->Article->parseCriteria($data);
		$expected = array('Article.views > 10');
		$this->assertEquals($expected, $result);
	}

/**
 * testExpressionCallCondition
 *
 * @return void
 */
	public function testExpressionCallCondition() {
		$this->Article->filterArgs = array(
			array('name' => 'range', 'type' => 'expression', 'method' => 'makeRangeCondition', 'field' => 'Article.views BETWEEN ? AND ?'));
		$data = array();
		$result = $this->Article->parseCriteria($data);
		$this->assertEquals(array(), $result);

		$data = array('range' => '10');
		$result = $this->Article->parseCriteria($data);
		$expected = array('Article.views BETWEEN ? AND ?' => array(0, 10));
		$this->assertEquals($expected, $result);

		$this->Article->filterArgs = array(
			array('name' => 'range', 'type' => 'expression', 'method' => 'testThatInBehaviorMethodNotDefined', 'field' => 'Article.views BETWEEN ? AND ?'));
		$data = array('range' => '10');
		$result = $this->Article->parseCriteria($data);
		$this->assertEquals(array(), $result);
	}

/**
 * testUnbindAll
 *
 * @return void
 */
	public function testUnbindAll() {
		$this->Article->unbindAllModels();
		$this->assertEquals(array(), $this->Article->belongsTo);
		$this->assertEquals(array(), $this->Article->hasMany);
		$this->assertEquals(array(), $this->Article->hasAndBelongsToMany);
		$this->assertEquals(array(), $this->Article->hasOne);
	}

/**
 * testValidateSearch
 *
 * @return void
 */
	public function testValidateSearch() {
		$this->Article->filterArgs = array();
		$data = array('Article' => array('title' => 'Last Article'));
		$this->Article->set($data);
		$this->Article->validateSearch();
		$this->assertEquals($data, $this->Article->data);

		$this->Article->validateSearch($data);
		$this->assertEquals($data, $this->Article->data);

		$data = array('Article' => array('title' => ''));
		$this->Article->validateSearch($data);
		$expected = array('Article' => array());
		$this->assertEquals($expected, $this->Article->data);
	}

/**
 * testPassedArgs
 *
 * @return void
 */
	public function testPassedArgs() {
		$this->Article->filterArgs = array(
			array('name' => 'slug', 'type' => 'value'));
		$data = array('slug' => 'first_article', 'filter' => 'myfilter');
		$result = $this->Article->passedArgs($data);
		$expected = array('slug' => 'first_article');
		$this->assertEquals($expected, $result);
	}

/**
 * testGetQuery
 *
 * @return void
 */
	public function testGetQuery() {
		if ($this->skipIf($this->db->config['datasource'] != 'Database/Mysql', 'Test requires mysql db. %s')) {
			return;
		}
		$database = $this->db->config['database'];

		$conditions = array('Article.id' => 1);
		$result = $this->Article->getQuery('all', array('conditions' => $conditions, 'order' => 'title', 'page' => 2, 'limit' => 2, 'fields' => array('id', 'title')));
		$expected = 'SELECT `Article`.`id`, `Article`.`title` FROM `' . $database . '`.`' . $this->Article->tablePrefix . 'articles` AS `Article`   WHERE `Article`.`id` = 1   ORDER BY `title` ASC  LIMIT 2, 2';
		$this->assertEquals($expected, $result);
		$this->Article->Tagged->Behaviors->attach('Search.Searchable');
		$conditions = array('Tagged.tag_id' => 1);
		$result = $this->Article->Tagged->recursive = -1;
		$order = array('Tagged.id' => 'ASC');
		$result = $this->Article->Tagged->getQuery('first', compact('conditions', 'order'));
		$expected = 'SELECT `Tagged`.`id`, `Tagged`.`foreign_key`, `Tagged`.`tag_id`, `Tagged`.`model`, `Tagged`.`language`, `Tagged`.`created`, `Tagged`.`modified` FROM `' . $database . '`.`' . $this->Article->tablePrefix . 'tagged` AS `Tagged`   WHERE `Tagged`.`tag_id` = \'1\'   ORDER BY `Tagged`.`id` ASC  LIMIT 1';

		$this->assertEquals($expected, $result);
	}

}
