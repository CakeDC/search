<?php
/**
 * Copyright 2009-2013, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2009 - 2014, Cake Development Corporation (http://cakedc.com)
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Search\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Core\Configure;
use Cake\Utility\Hash;
use Cake\Utility\String;

/**
 * Searchable behavior
 *
 */
class SearchableBehavior extends Behavior {

/**
 * Default settings
 * - wildcardAny: the character used instead of % (% is a normal character then)
 * - wildcardOne: the character used instead of _ (_ is a normal character then)
 * - like: auto add % wildcard to beginning, end or both (both false => user can enter wildcards himself)
 * - connectorAnd: the character between search terms to specify an "and" relationship (binds stronger than or, similar to * and + in math)
 * - connectorOr: the character between search terms to specify an "or" relationship
 *
 * @var array
 */
	protected $_defaultConfig = array(
		'wildcardAny' => '*', //on windows/unix/mac/google/... thats the default one
		'wildcardOne' => '?', //on windows/unix/mac thats the default one
		'like' => array('before' => true, 'after' => true),
		'connectorAnd' => null,
		'connectorOr' => null,
	);

	protected $_table;

/**
 * Configuration of model
 *
 * 
 * @param array $config
 * @return void
 */
	public function __construct(Table $table, array $config = []) {
		$this->_defaultConfig = array_merge($this->_defaultConfig, (array)Configure::read('Search.Searchable'));
		parent::__construct($table, $config);
		$this->_table = $table;
	}

/**
 * Validate search
 *
 * @param \Cake\ORM\Entity  $entity
 *
 * @return boolean always true
 */
	public function validateSearch(\Cake\ORM\Entity $entity) {
		$keys = $entity->visibleProperties();
		foreach ($keys as $key) {
			if (empty($entity->get($key))) {
				$entity->unsetProperty($key);
			}
		}

		return true;
	}

/**
 * Prepares the filter args based on the model information and calls
 * Model::getFilterArgs if present to set up the filterArgs with proper model
 * aliases.
 *
 * 
 * @return boolean|array
 */
	public function setupFilterArgs() {
		if (method_exists($this->_table, 'getFilterArgs')) {
			$this->_table->getFilterArgs();
		}
		if (empty($this->_table->filterArgs)) {
			return false;
		}
		foreach ($this->_table->filterArgs as $key => $val) {
			if (!isset($val['name'])) {
				$this->_table->filterArgs[$key]['name'] = $key;
			}
			if (!isset($val['field'])) {
				$this->_table->filterArgs[$key]['field'] = $this->_table->filterArgs[$key]['name'];
			}
			if (!isset($val['type'])) {
				$this->_table->filterArgs[$key]['type'] = 'value';
			}
		}
		return $this->_table->filterArgs;
	}

/**
 * parseCriteria
 * parses the GET data and returns the conditions for the find('all')/paginate
 * we are just going to test if the params are legit
 *
 * 
 * @param array $data Criteria of key->value pairs from post/named parameters
 * @return array Array of conditions that express the conditions needed for the search
 */
	public function parseQuery($data) {
		$this->setupFilterArgs();
		$query = $this->_table->find('all');

		foreach ($this->_table->filterArgs as $field) {
			// If this field was not passed and a default value exists, use that instead.
			if (!array_key_exists($field['name'], $data) && array_key_exists('defaultValue', $field)) {
				$data[$field['name']] = $field['defaultValue'];
			}

			if (in_array($field['type'], array('like'))) {
				$this->_addCondLike($query, $data, $field);
			} elseif (in_array($field['type'], array('value', 'lookup'))) {
				$this->_addCondValue($query, $data, $field);
			} elseif ($field['type'] === 'query') {
				$this->_addCondQuery($query, $data, $field);
			} elseif ($field['type'] === 'subquery') {
				$this->_addCondSubquery($query, $data, $field);
			}
		}
		return $query;
	}

/**
 * filter retrieving variables only that present in  Model::filterArgs
 *
 * 
 * @param array $vars
 * @return array, filtered args
 */
	public function passedArgs($vars) {
		$this->setupFilterArgs($this->_table);

		$result = array();
		foreach ($vars as $var => $val) {
			if (in_array($var, Hash::extract($this->_table->filterArgs, '{n}.name'))) {
				$result[$var] = $val;
			}
		}
		return $result;
	}

/**
 * For custom queries inside the model
 * example "makePhoneCondition": $cond = array('OR' => array_merge($this->condLike('cell_number', $filter), $this->condLike('landline_number', $filter, array('before' => false))));
 *
 * 
 * @param $name
 * @param $data
 * @param array $field
 * @return array of conditions
 */
	public function condLike($name, $data, $field = array()) {
		$conditions = array();
		$field['name'] = $name;
		if (!is_array($data)) {
			$data = array($name => $data);
		}
		if (!isset($field['field'])) {
			$field['field'] = $field['name'];
		}
		return $this->_addCondLike($this->_table, $conditions, $data, $field);
	}

/**
 * Replace substitutions with original wildcards
 * but first, escape the original wildcards in the text to use them as normal search text
 *
 * 
 * @param $data
 * @param array $options
 * @return string queryLikeString
 */
	public function formatLike($data, $options = array()) {
		$options = array_merge($this->_config, $options);
		$from = $to = $substFrom = $substTo = array();
		if ($options['wildcardAny'] !== '%') {
			$from[] = '%';
			$to[] = '\%';
			$substFrom[] = $options['wildcardAny'];
			$substTo[] = '%';
		}
		if ($options['wildcardOne'] !== '_') {
			$from[] = '_';
			$to[] = '\_';
			$substFrom[] = $options['wildcardOne'];
			$substTo[] = '_';
		}
		if (!empty($from)) {
			// escape first
			$data = str_replace($from, $to, $data);
			// replace wildcards
			$data = str_replace($substFrom, $substTo, $data);
		}
		return $data;
	}

/**
 * Return the current chars for querying LIKE statements on this model
 *
 *  Reference to the model
 * @param array $options
 * @return array, [one=>..., any=>...]
 */
	public function getWildcards($options = array()) {
		$options = array_merge($this->_config, $options);
		return array('any' => $options['wildcardAny'], 'one' => $options['wildcardOne']);
	}

/**
 * Add Conditions based on fuzzy comparison
 *
 *  Reference to the model
 * @param array $conditions existing Conditions collected for the model
 * @param array $data Array of data used in search query
 * @param array $field Field definition information
 * @return array Conditions
 */
	protected function _addCondLike(Query $query, $data, $field) {
		if (!is_array($this->_config['like'])) {
			$this->_config['like'] = array('before' => $this->_config['like'], 'after' => $this->_config['like']);
		}
		$field = array_merge($this->_config['like'], $field);
		if (empty($data[$field['name']])) {
			return $query;
		}
		$fieldNames = (array)$field['field'];

		$cond = array();
		foreach ($fieldNames as $fieldName) {
			if (strpos($fieldName, '.') === false) {
				$fieldName = $this->_table->alias() . '.' . $fieldName;
			}

			if ($field['before'] === true) {
				$field['before'] = '%';
			}
			if ($field['after'] === true) {
				$field['after'] = '%';
			}

			$options = $this->_config;
			$from = $to = $substFrom = $substTo = array();
			if ($options['wildcardAny'] !== '%') {
				$from[] = '%';
				$to[] = '\%';
				$from[] = $options['wildcardAny'];
				$to[] = '%';
			}
			if ($options['wildcardOne'] !== '_') {
				$from[] = '_';
				$to[] = '\_';
				$from[] = $options['wildcardOne'];
				$to[] = '_';
			}
			$value = $data[$field['name']];
			if (!empty($from)) {
				$value = str_replace($from, $to, $value);
			}

			if (!empty($field['connectorAnd']) || !empty($field['connectorOr'])) {
				$cond[] = $this->_connectedLike($value, $field, $fieldName);
			} else {
				$cond[$fieldName . " LIKE"] = $field['before'] . $value . $field['after'];
			}
		}
		if (count($cond) > 1) {
			$cond = array(
				'or' => $cond
			);
		}
		$query->where($cond);

		return $query;
	}

/**
 * Form AND/OR query array using String::tokenize to separate
 * search terms by or/and connectors.
 *
 * @param mixed $value
 * @param array $field
 * @param string $fieldName
 * @return array Conditions
 */
	protected function _connectedLike($value, $field, $fieldName) {
		$or = array();
		$orValues = String::tokenize($value, $field['connectorOr']);
		foreach ($orValues as $orValue) {
			$andValues = String::tokenize($orValue, $field['connectorAnd']);
			$and = array();
			foreach ($andValues as $andValue) {
				$and[] = array($fieldName . " LIKE" => $field['before'] . $andValue . $field['after']);
			}

			$or[] = array('AND' => $and);
		}

		return array('OR' => $or);
	}

/**
 * Add Conditions based on exact comparison
 *
 *  Reference to the model
 * @param \Cake\Orm\Query $query existing Conditions collected for the model
 * @param array $data Array of data used in search query
 * @param array $field Field definition information
 * @return array of conditions
 */
	protected function _addCondValue(Query $query, $data, $field) {
		$fieldNames = (array)$field['field'];
		$fieldValue = isset($data[$field['name']]) ? $data[$field['name']] : null;

		$cond = array();
		foreach ($fieldNames as $fieldName) {
			if (strpos($fieldName, '.') === false) {
				$fieldName = $this->_table->alias() . '.' . $fieldName;
			}
			if (is_array($fieldValue) && empty($fieldValue)) {
				continue;
			}
			if (!is_array($fieldValue) && ($fieldValue === null || $fieldValue === '' && empty($field['allowEmpty']))) {
				continue;
			}

			if (is_array($fieldValue) || !is_array($fieldValue) && (string)$fieldValue !== '') {
				$cond[$fieldName] = $fieldValue;
			} elseif (isset($data[$field['name']]) && !empty($field['allowEmpty'])) {
				$schema = $this->_table->schema()->column($field['name']);
				if (isset($schema) && ($schema['default'] !== null || !empty($schema['null']))) {
					$cond[$fieldName] = $schema['default'];
				} elseif (!empty($fieldValue)) {
					$cond[$fieldName] = $fieldValue;
				} else {
					$cond[$fieldName] = $fieldValue;
				}
			}
		}
		if (count($cond) > 1) {
			$cond = array(
				'or' => $cond
			);
		}
		$query->where($cond);

		return $query;
	}

/**
 * Add Conditions based query to search conditions.
 *
 *   Instance of AppModel
 * @param array $conditions Existing conditions.
 * @param array $data Data for a field.
 * @param array $field Info for field.
 * @return array of conditions modified by this method
 */
	protected function _addCondQuery(Query $query, $data, $field) {
		if ((method_exists($this->_table, $field['method']) || $this->_checkBehaviorMethods($field['method'])) && (!empty($field['allowEmpty']) || !empty($data[$field['name']]) || (isset($data[$field['name']]) && (string)$data[$field['name']] !== ''))) {
			$this->_table->{$field['method']}($query, $data, $field);
		}
		return $query;
	}

/**
 * Add Conditions based subquery to search conditions.
 *
 *   Instance of AppModel
 * @param array $conditions Existing conditions.
 * @param array $data Data for a field.
 * @param array $field Info for field.
 * @return array of conditions modified by this method
 */
	protected function _addCondSubquery($query, $data, $field) {
		$fieldName = $field['field'];
		if ((method_exists($this->_table, $field['method']) || $this->_checkBehaviorMethods($this->_table, $field['method'])) && (!empty($field['allowEmpty']) || !empty($data[$field['name']]) || (isset($data[$field['name']]) && (string)$data[$field['name']] !== ''))) {
			$subquery = $this->_table->{$field['method']}($data, $field);
			// if our subquery function returns something empty, nothing to merge in
			if (!empty($subquery)) {
				$query->where(array(
					$fieldName => $subquery
				));
			}
		}
		return $query;
	}

/**
 * Check if model have some method in attached behaviors
 *
 * 
 * @param string $method
 * @return boolean, true if method exists in attached and enabled behaviors
 */
	protected function _checkBehaviorMethods($method) {
		$behaviors = $this->_table->behaviors();
		$count = count($behaviors);
		$found = false;
		for ($i = 0; $i < $count; $i++) {
			$name = $behaviors[$i];
			$methods = get_class_methods($this->_table->getBehavior($name));
			$check = array_flip($methods);
			$found = isset($check[$method]);
			if ($found) {
				return true;
			}
		}
		return $found;
	}

}
