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

/**
 * Tag Fixture
 *
 * @package search
 * @subpackage search.tests.fixtures
 */ 
class TagFixture extends CakeTestFixture {

/**
 * Name
 *
 * @var string $name
 */
	public $name = 'Tag';

/**
 * Table
 *
 * @var string $table
 */
	public $table = 'tags';

/**
 * Fields
 *
 * @var array $fields
 */
	public $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
		'identifier' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 30, 'key' => 'index'),
		'name' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 30),
		'keyname' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 30),
		'weight' => array('type' => 'integer', 'null' => false, 'default' => 0, 'length' => 2),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
		'modified' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
		'indexes' => array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1),
			'UNIQUE_TAG' => array('column' => array('identifier', 'keyname'), 'unique' => 1)
		)
	);

/**
 * Records
 *
 * @var array $records
 */
	public $records = array(
		array(
			'id'  => 1,
			'identifier'  => null,
			'name'  => 'CakePHP',
			'keyname'  => 'cakephp',
			'weight' => 2,
			'created'  => '2008-06-02 18:18:11',
			'modified'  => '2008-06-02 18:18:37'),
		array(
			'id'  => 2,
			'identifier'  => null,
			'name'  => 'CakeDC',
			'keyname'  => 'cakedc',
			'weight' => 2,
			'created'  => '2008-06-01 18:18:15',
			'modified'  => '2008-06-01 18:18:15'),
		array(
			'id'  => 3,
			'identifier'  => null,
			'name'  => 'CakeDC',
			'keyname'  => 'cakedc',
			'weight' => 2,
			'created'  => '2008-06-01 18:18:15',
			'modified'  => '2008-06-01 18:18:15'));
}
