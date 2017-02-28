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
 * Tag Fixture
 */
class ColorsFixture extends TestFixture
{

    /**
     * Fields
     *
     * @var array $fields
     */
    public $fields = [
        'id' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 36],
        'name' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 30],
    ];

}
