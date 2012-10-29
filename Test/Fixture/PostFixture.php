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
 * Post Fixture
 *
 * @package search
 * @subpackage search.tests.fixtures
 */
class PostFixture extends CakeTestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'title' => array('type' => 'string', 'null' => false),
		'slug' => array('type' => 'string', 'null' => false),
		'views' => array('type' => 'integer', 'null' => false),
		'comments' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => 10),
		'created' => 'datetime',
		'updated' => 'datetime',
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('id' => 1, 'title' => 'First Post', 'slug' => 'first_post', 'views' => 2, 'comments' => 1, 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'),
		array('id' => 2, 'title' => 'Second Post', 'slug' => 'second_post', 'views' => 1, 'comments' => 2, 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'),
		array('id' => 3, 'title' => 'Third Post', 'slug' => 'third_post', 'views' => 2, 'comments' => 3, 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'),
	);

}
