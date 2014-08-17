<?php
/**
 * Copyright 2009 - 2014, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2009 - 2014, Cake Development Corporation (http://cakedc.com)
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Search\Test\TestClass\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Query;

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
 * @param Query $query Query to find
 * @param array $options Options
 *
 * @return Query
 */
	public function findMostFilterConditions(Query $query, $options = array()) {
		$data = $options['data'];
		$filter = $data['filter'];
		if (!in_array($filter, array('views', 'comments'))) {
			return array();
		}

		switch ($filter) {
			case 'views':
				$cond = $query->repository()->alias() . '.views > 10';
				break;
			case 'comments':
				$cond = $query->repository()->alias() . '.comments > 10';
				break;
		}

		return $query->where($cond);
	}

}