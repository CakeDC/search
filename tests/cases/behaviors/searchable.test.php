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
 * Searchable behavior tests
 *
 * @package		plugins.search
 * @subpackage	plugins.search.tests.cases.behaviors
 */

App::import('Core', 'Model');

class FilterBehavior extends ModelBehavior {
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

class Tag extends CakeTestModel {
}

class Tagged extends CakeTestModel {
	public $useTable = 'tagged';
	public $belongsTo = array('Tag');
}

class Article extends CakeTestModel {
	public $actsAs = array('Search.Searchable');
	
	public $hasAndBelongsToMany = array('Tag' => array('with' => 'Tagged'));
	
	public function findByTags($data = array()) {
		$this->Tagged->Behaviors->attach('Containable', array('autoFields' => false));
		$this->Tagged->Behaviors->attach('Search.Searchable');
		$query = $this->Tagged->getQuery('all', array(
			'conditions' => array('Tag.name'  => $data['tags']),
			'fields' => array('foreign_key'),
			'contain' => array('Tag')
		));
		return $query;
	}
	
/**
 * Makes an array of range numbers that matches the ones on the interface.
 *
 * @return void
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
}

class SearchableTestCase extends CakeTestCase { 

/**
 * Fixtures used in the SessionTest
 *
 * @var array
 * @access public
 */
	var $fixtures = array('plugin.search.article', 'plugin.search.tag', 'plugin.search.tagged'); 

/**
 * startTest
 *
 * @return void
 * @access public
 */
	public function startTest() {
		$this->Article = ClassRegistry::init('Article');
	}

/**
 * endTest
 *
 * @return void
 * @access public
 */
	public function endTest() {
		unset($this->Article);
	}

/**
 * test value condition
 *
 * @access public
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
	}
 
/**
 * test like condition
 *
 * @access public
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
 * test subquery condition
 *
 * @access public
 */ 
	public function testSubQueryCondition() {
		$this->Article->filterArgs = array(
			array('name' => 'tags', 'type' => 'subquery', 'method' => 'findByTags', 'field' => 'Article.id'));
			
		$data = array();
		$result = $this->Article->parseCriteria($data);
		$this->assertEqual($result, array());
			
		$data = array('tags' => 'Cake');
		$result = $this->Article->parseCriteria($data);
		$expected = array(array("Article.id in (SELECT `Tagged`.`foreign_key` FROM `tagged` AS `Tagged` LEFT JOIN `tags` AS `Tag` ON (`Tagged`.`tag_id` = `Tag`.`id`)  WHERE `Tag`.`name` = 'Cake'   )"));
		$this->assertEqual($result, $expected);
	}
 
/**
 * test query condition
 *
 * @access public
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
 * test expression condition
 *
 * @access public
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
 * test unbind all
 *
 * @access public
 */ 
	public function testUnbindAll() {
		$this->Article->unbindAllModels();
		$this->assertEqual($this->Article->belongsTo, array());
		$this->assertEqual($this->Article->hasMany, array());
		$this->assertEqual($this->Article->hasAndBelongsToMany, array());
		$this->assertEqual($this->Article->hasOne, array());
	}

/**
 * test validate search
 *
 * @access public
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
 * test passed args
 *
 * @access public
 */ 
	public function testPassedArgs() {
		$this->Article->filterArgs = array(
			array('name' => 'slug', 'type' => 'value'));
		$data = array('slug' => 'first_article', 'filter' => 'myfilter');
		$result = $this->Article->passedArgs($data);
		$expected = array('slug' => 'first_article');
		$this->assertEqual($result, $expected);
	}

	public function testGetQuery() {
		$conditions = array('Article.id' => 1);
		$result = $this->Article->getQuery($conditions, array('id', 'title'));
		$expected = 'SELECT `Article`.`id`, `Article`.`title` FROM `articles` AS `Article`   WHERE `Article`.`id` = 1    LIMIT 1';
		$this->assertEqual($result, $expected);

		$result = $this->Article->getQuery('all', array('conditions' => $conditions, 'order' => 'title', 'page' => 2, 'limit' => 2, 'fields' => array('id', 'title')));
		$expected = 'SELECT `Article`.`id`, `Article`.`title` FROM `articles` AS `Article`   WHERE `Article`.`id` = 1   ORDER BY `title` ASC  LIMIT 2, 2';
		$this->assertEqual($result, $expected);

		$this->Article->Tagged->Behaviors->attach('Search.Searchable');
		$conditions = array('Tagged.tag_id' => 1);
		$result = $this->Article->Tagged->recursive = -1;
		$result = $this->Article->Tagged->getQuery($conditions);
		$expected = "SELECT `Tagged`.`id`, `Tagged`.`foreign_key`, `Tagged`.`tag_id`, `Tagged`.`model`, `Tagged`.`language`, `Tagged`.`created`, `Tagged`.`modified` FROM `tagged` AS `Tagged`   WHERE `Tagged`.`tag_id` = '1'    LIMIT 1";
		$this->assertEqual($result, $expected);
	}
}
?>