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

App::import('Core', 'Model');
App::import('Model', 'ModelBehavior');

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
class SearchableTestCase extends CakeTestCase { 

/**
 * Fixtures used in the SessionTest
 *
 * @var array
 */
	var $fixtures = array('plugin.search.article', 'plugin.search.tag', 'plugin.search.tagged'); 

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
		$this->assertEqual($result, array());

		$data = array('slug' => 'first_article');
		$result = $this->Article->parseCriteria($data);
		$expected = array('Article.slug' => 'first_article');
		$this->assertEqual($result, $expected);

		$this->Article->filterArgs = array(
			array('name' => 'fakeslug', 'type' => 'value', 'field' => 'Article2.slug'));
		$data = array('fakeslug' => 'first_article');
		$result = $this->Article->parseCriteria($data);
		$expected = array('Article2.slug' => 'first_article');
		$this->assertEqual($result, $expected);

		// Testing http://github.com/CakeDC/Search/issues#issue/3
		$this->Article->filterArgs = array(
			array('name' => 'views', 'type' => 'value'));
		$data = array('views' => '0');
		$result = $this->Article->parseCriteria($data);
		$this->assertEqual($result, array('Article.views' => 0));
		
		$this->Article->filterArgs = array(
			array('name' => 'views', 'type' => 'value'));
		$data = array('views' => 0);
		$result = $this->Article->parseCriteria($data);
		$this->assertEqual($result, array('Article.views' => 0));
		
		$this->Article->filterArgs = array(
			array('name' => 'views', 'type' => 'value'));
		$data = array('views' => '');
		$result = $this->Article->parseCriteria($data);
		$this->assertEqual($result, array());
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
		$this->assertEqual($result, array());

		$data = array('title' => 'First');
		$result = $this->Article->parseCriteria($data);
		$expected = array('Article.title LIKE' => '%First%');
		$this->assertEqual($result, $expected);

		$this->Article->filterArgs = array(
			array('name' => 'faketitle', 'type' => 'like', 'field' => 'Article.title'));
		$data = array('faketitle' => 'First');
		$result = $this->Article->parseCriteria($data);
		$expected = array('Article.title LIKE' => '%First%');
		$this->assertEqual($result, $expected);
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

		$this->Article->filterArgs = array(
			array('name' => 'tags', 'type' => 'subquery', 'method' => 'findByTags', 'field' => 'Article.id'));
			
		$data = array();
		$result = $this->Article->parseCriteria($data);
		$this->assertEqual($result, array());

		$data = array('tags' => 'Cake');
		$result = $this->Article->parseCriteria($data);
		$expected = array(array("Article.id in (SELECT `Tagged`.`foreign_key` FROM `tagged` AS `Tagged` LEFT JOIN `tags` AS `Tag` ON (`Tagged`.`tag_id` = `Tag`.`id`)  WHERE `Tag`.`name` = 'Cake')"));
		$this->assertEqual($result, $expected);
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

		$this->Article->filterArgs = array(
			array('name' => 'tags', 'type' => 'subquery', 'method' => 'findByTags', 'field' => 'Article.id', 'allowEmpty' => true));

		$data = array();
		$result = $this->Article->parseCriteria($data);
		$expected = array(array("Article.id in (SELECT `Tagged`.`foreign_key` FROM `tagged` AS `Tagged` LEFT JOIN `tags` AS `Tag` ON (`Tagged`.`tag_id` = `Tag`.`id`)  WHERE 1 = 1)"));
		$this->assertEqual($result, $expected);
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
		$this->assertEqual($result, array());

		$data = array('filter' => 'ticl');
		$result = $this->Article->parseCriteria($data);
		$expected = array('OR' => array(
		'Article.title LIKE' => '%ticl%',
		'Article.body LIKE' => '%ticl%'));
		$this->assertEqual($result, $expected);
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
		$this->assertEqual($result, array());

		$data = array('filter' => 'views');
		$result = $this->Article->parseCriteria($data);
		$expected = array('Article.views > 10');
		$this->assertEqual($result, $expected);
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
		$this->assertEqual($result, array());

		$data = array('range' => '10');
		$result = $this->Article->parseCriteria($data);
		$expected = array('Article.views BETWEEN ? AND ?' => array(0, 10));
		$this->assertEqual($result, $expected);
		
		$this->Article->filterArgs = array(
			array('name' => 'range', 'type' => 'expression', 'method' => 'testThatInBehaviorMethodNotDefined', 'field' => 'Article.views BETWEEN ? AND ?'));
		$data = array('range' => '10');
		$result = $this->Article->parseCriteria($data);
		$this->assertEqual($result, array());
	}

/**
 * testUnbindAll
 *
 * @return void
 */
	public function testUnbindAll() {
		$this->Article->unbindAllModels();
		$this->assertEqual($this->Article->belongsTo, array());
		$this->assertEqual($this->Article->hasMany, array());
		$this->assertEqual($this->Article->hasAndBelongsToMany, array());
		$this->assertEqual($this->Article->hasOne, array());
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
		$this->assertEqual($this->Article->data, $data);
		
		$this->Article->validateSearch($data);
		$this->assertEqual($this->Article->data, $data);
		
		$data = array('Article' => array('title' => ''));
		$this->Article->validateSearch($data);
		$expected = array('Article' => array());
		$this->assertEqual($this->Article->data, $expected);
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
		$this->assertEqual($result, $expected);
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
		$conditions = array('Article.id' => 1);
		$result = $this->Article->getQuery('all', array('conditions' => $conditions, 'order' => 'title', 'page' => 2, 'limit' => 2, 'fields' => array('id', 'title')));
		$expected = 'SELECT `Article`.`id`, `Article`.`title` FROM `articles` AS `Article`   WHERE `Article`.`id` = 1   ORDER BY `title` ASC  LIMIT 2, 2';
		$this->assertEqual($result, $expected);

		$this->Article->Tagged->Behaviors->attach('Search.Searchable');
		$conditions = array('Tagged.tag_id' => 1);
		$result = $this->Article->Tagged->recursive = -1;
		$result = $this->Article->Tagged->getQuery('first', compact('conditions'));
		$expected = "SELECT `Tagged`.`id`, `Tagged`.`foreign_key`, `Tagged`.`tag_id`, `Tagged`.`model`, `Tagged`.`language`, `Tagged`.`created`, `Tagged`.`modified` FROM `tagged` AS `Tagged`   WHERE `Tagged`.`tag_id` = '1'   ORDER BY `Tagged`.`id` ASC  LIMIT 1";
		$this->assertEqual($result, $expected);
	}
}
