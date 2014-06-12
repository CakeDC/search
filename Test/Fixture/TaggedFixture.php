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
namespace Search\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Tagged Fixture
 */
class TaggedFixture extends TestFixture {

/**
 * Table
 *
 * @var string name$table
 */
	public $table = 'tagged';

/**
 * Fields
 *
 * @var array $fields
 */
	public $fields = array(
		'id' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 36],
		'foreign_key' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 36],
		'tag_id' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 36],
		'model' => ['type' => 'string', 'null' => false, 'default' => null],
		'language' => ['type' => 'string', 'null' => true, 'default' => null, 'length' => 6],
		'created' => ['type' => 'datetime', 'null' => true, 'default' => null],
		'modified' => ['type' => 'datetime', 'null' => true, 'default' => null],
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']], 'UNIQUE_TAGGING' => ['type' => 'unique', 'columns' => ['model', 'foreign_key', 'tag_id', 'language']]]
	);

/**
 * Records
 *
 * @var array $records
 */
	public $records = array(
		array(
			'id' => '49357f3f-c464-461f-86ac-a85d4a35e6b6',
			'foreign_key' => 1,
			'tag_id' => 1, // CakePHP
			'model' => 'Article',
			'language' => 'eng',
			'created' => '2008-12-02 12:32:31 ',
			'modified' => '2008-12-02 12:32:31',
		),
		array(
			'id' => '49357f3f-c66c-4300-a128-a85d4a35e6b6',
			'foreign_key' => 1,
			'tag_id' => 2, // CakeDC
			'model' => 'Article',
			'language' => 'eng',
			'created' => '2008-12-02 12:32:31 ',
			'modified' => '2008-12-02 12:32:31',
		),
		array(
			'id' => '493dac81-1b78-4fa1-a761-43ef4a35e6b2',
			'foreign_key' => 2,
			'tag_id' => 2, // CakeDC
			'model' => 'Article',
			'language' => 'eng',
			'created' => '2008-12-02 12:32:31 ',
			'modified' => '2008-12-02 12:32:31',
		),
	);

}
