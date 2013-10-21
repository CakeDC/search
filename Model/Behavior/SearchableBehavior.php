<?php
/**
 * Copyright 2009-2013, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2009 - 2013, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('ModelBehavior', 'Model');

/**
 * Searchable behavior
 *
 */
class SearchableBehavior extends ModelBehavior {

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
	protected $_defaults = array(
		'wildcardAny' => '*', //on windows/unix/mac/google/... thats the default one
		'wildcardOne' => '?', //on windows/unix/mac thats the default one
		'like' => array('before' => true, 'after' => true),
		'connectorAnd' => null,
		'connectorOr' => null,
	);

/**
 * Configuration of model
 *
 * @param Model $Model
 * @param array $config
 * @return void
 */
	public function setup(Model $Model, $config = array()) {
		$this->_defaults = array_merge($this->_defaults, (array)Configure::read('Search.Searchable'));
		$this->settings[$Model->alias] = array_merge($this->_defaults, $config);
		if (empty($Model->filterArgs)) {
			return;
		}
		foreach ($Model->filterArgs as $key => $val) {
			if (!isset($val['name'])) {
				$Model->filterArgs[$key]['name'] = $key;
			}
			if (!isset($val['field'])) {
				$Model->filterArgs[$key]['field'] = $Model->filterArgs[$key]['name'];
			}
			if (!isset($val['type'])) {
				$Model->filterArgs[$key]['type'] = 'value';
			}
		}
	}

/**
 * parseCriteria
 * parses the GET data and returns the conditions for the find('all')/paginate
 * we are just going to test if the params are legit
 *
 * @param Model $Model
 * @param array $data Criteria of key->value pairs from post/named parameters
 * @return array Array of conditions that express the conditions needed for the search
 */
	public function parseCriteria(Model $Model, $data) {
		$conditions = array();
		foreach ($Model->filterArgs as $field) {
			// If this field was not passed and a default value exists, use that instead.
			if (!array_key_exists($field['name'], $data) && array_key_exists('defaultValue', $field)) {
				$data[$field['name']] = $field['defaultValue'];
			}

			if (in_array($field['type'], array('like'))) {
				$this->_addCondLike($Model, $conditions, $data, $field);
			} elseif (in_array($field['type'], array('value'))) {
				$this->_addCondValue($Model, $conditions, $data, $field);
			} elseif ($field['type'] === 'expression') {
				$this->_addCondExpression($Model, $conditions, $data, $field);
			} elseif ($field['type'] === 'query') {
				$this->_addCondQuery($Model, $conditions, $data, $field);
			} elseif ($field['type'] === 'subquery') {
				$this->_addCondSubquery($Model, $conditions, $data, $field);
			}
		}
		return $conditions;
	}

/**
 * Validate search
 *
 * @param Model $Model
 * @param null $data
 * @return boolean always true
 */
	public function validateSearch(Model $Model, $data = null) {
		if (!empty($data)) {
			$Model->set($data);
		}
		$keys = array_keys($Model->data[$Model->alias]);
		foreach ($keys as $key) {
			if (empty($Model->data[$Model->alias][$key])) {
				unset($Model->data[$Model->alias][$key]);
			}
		}
		return true;
	}

/**
 * filter retrieving variables only that present in  Model::filterArgs
 *
 * @param Model $Model
 * @param array $vars
 * @return array, filtered args
 */
	public function passedArgs(Model $Model, $vars) {
		$result = array();
		foreach ($vars as $var => $val) {
			if (in_array($var, Set::extract($Model->filterArgs, '{n}.name'))) {
				$result[$var] = $val;
			}
		}
		return $result;
	}

/**
 * Generates a query string using the same API Model::find() uses, calling the beforeFind process for the model
 *
 * @param Model $Model
 * @param string $type Type of find operation (all / first / count / neighbors / list / threaded)
 * @param array $query Option fields (conditions / fields / joins / limit / offset / order / page / group / callbacks)
 * @return array Array of records
 * @link http://book.cakephp.org/view/1018/find
 */
	public function getQuery(Model $Model, $type = 'first', $query = array()) {
		$Model->findQueryType = $type;
		$Model->id = $Model->getID();
		$query = $Model->buildQuery($type, $query);
		$this->findQueryType = null;
		return $this->_queryGet($Model, $query);
	}

/**
 * Clear all associations
 *
 * @param Model $Model
 * @param boolean $reset
 * @return void
 */
	public function unbindAllModels(Model $Model, $reset = false) {
		$assocs = array('belongsTo', 'hasOne', 'hasMany', 'hasAndBelongsToMany');
		$unbind = array();
		foreach ($assocs as $assoc) {
			$unbind[$assoc] = array_keys($Model->{$assoc});
		}
		$Model->unbindModel($unbind, $reset);
	}

/**
 * For custom queries inside the model
 * example "makePhoneCondition": $cond = array('OR' => array_merge($this->condLike('cell_number', $filter), $this->condLike('landline_number', $filter, array('before' => false))));
 *
 * @param Model $Model
 * @param $name
 * @param $data
 * @param array $field
 * @return array of conditions
 */
	public function condLike(Model $Model, $name, $data, $field = array()) {
		$conditions = array();
		$field['name'] = $name;
		if (!is_array($data)) {
			$data = array($name => $data);
		}
		if (!isset($field['field'])) {
			$field['field'] = $field['name'];
		}
		return $this->_addCondLike($Model, $conditions, $data, $field);
	}

/**
 * Replace substitutions with original wildcards
 * but first, escape the original wildcards in the text to use them as normal search text
 *
 * @param Model $Model
 * @param $data
 * @param array $options
 * @return string queryLikeString
 */
	public function formatLike(Model $Model, $data, $options = array()) {
		$options = array_merge($this->settings[$Model->alias], $options);
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
 * @param Model $Model Reference to the model
 * @param array $options
 * @return array, [one=>..., any=>...]
 */
	public function getWildcards(Model $Model, $options = array()) {
		$options = array_merge($this->settings[$Model->alias], $options);
		return array('any' => $options['wildcardAny'], 'one' => $options['wildcardOne']);
	}

/**
 * Add Conditions based on fuzzy comparison
 *
 * @param Model $Model Reference to the model
 * @param array $conditions existing Conditions collected for the model
 * @param array $data Array of data used in search query
 * @param array $field Field definition information
 * @return array Conditions
 */
	protected function _addCondLike(Model $Model, &$conditions, $data, $field) {
		if (!is_array($this->settings[$Model->alias]['like'])) {
			$this->settings[$Model->alias]['like'] = array('before' => $this->settings[$Model->alias]['like'], 'after' => $this->settings[$Model->alias]['like']);
		}
		$field = array_merge($this->settings[$Model->alias]['like'], $field);
		if (empty($data[$field['name']])) {
			return $conditions;
		}
		$fieldNames = (array)$field['field'];

		$cond = array();
		foreach ($fieldNames as $fieldName) {
			if (strpos($fieldName, '.') === false) {
				$fieldName = $Model->alias . '.' . $fieldName;
			}

			if ($field['before'] === true) {
				$field['before'] = '%';
			}
			if ($field['after'] === true) {
				$field['after'] = '%';
			}
			//if both before and after are false, LIKE allows custom placeholders, % and _ are always treated as normal chars
			$options = $this->settings[$Model->alias];
			$from = $to = $substFrom = $substTo = array();
			if ($options['wildcardAny'] !== '%' || ($field['before'] !== false || $field['after'] !== false)) {
				$from[] = '%';
				$to[] = '\%';
			}
			if ($options['wildcardOne'] !== '_' || ($field['before'] !== false || $field['after'] !== false)) {
				$from[] = '_';
				$to[] = '\_';
			}
			$value = $data[$field['name']];
			if (!empty($from)) {
				$value = str_replace($from, $to, $value);
			}
			if ($field['before'] === false && $field['after'] === false) {
				if ($options['wildcardAny'] !== '%') {
					$substFrom[] = $options['wildcardAny'];
					$substTo[] = '%';
				}
				if ($options['wildcardOne'] !== '_') {
					$substFrom[] = $options['wildcardOne'];
					$substTo[] = '_';
				}
				$value = str_replace($substFrom, $substTo, $value);
			}

			if (!empty($field['connectorAnd']) || !empty($field['connectorOr'])) {
				$cond[] = $this->_connectedLike($value, $field, $fieldName);
			} else {
				$cond[$fieldName . " LIKE"] = $field['before'] . $value . $field['after'];
			}
		}
		if (count($cond) > 1) {
			if (isset($conditions['OR'])) {
				$conditions[]['OR'] = $cond;
			} else {
				$conditions['OR'] = $cond;
			}
		} else {
			$conditions = array_merge($conditions, $cond);
		}
		return $conditions;
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
 * @param Model $Model Reference to the model
 * @param array $conditions existing Conditions collected for the model
 * @param array $data Array of data used in search query
 * @param array $field Field definition information
 * @return array of conditions
 */
	protected function _addCondValue(Model $Model, &$conditions, $data, $field) {
		$fieldNames = (array)$field['field'];
		$fieldValue = isset($data[$field['name']]) ? $data[$field['name']] : null;

		$cond = array();
		foreach ($fieldNames as $fieldName) {
			if (strpos($fieldName, '.') === false) {
				$fieldName = $Model->alias . '.' . $fieldName;
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
				$schema = $Model->schema($field['name']);
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
			if (isset($conditions['OR'])) {
				$conditions[]['OR'] = $cond;
			} else {
				$conditions['OR'] = $cond;
			}
		} else {
			$conditions = array_merge($conditions, $cond);
		}
		return $conditions;
	}

/**
 * Add Conditions based expressions to search conditions.
 *
 * @param Model $Model  Instance of AppModel
 * @param array $conditions Existing conditions.
 * @param array $data Data for a field.
 * @param array $field Info for field.
 * @return array of conditions modified by this method
 */
	protected function _addCondExpression(Model $Model, &$conditions, $data, $field) {
		$fieldName = $field['field'];

		if ((method_exists($Model, $field['method']) || $this->_checkBehaviorMethods($Model, $field['method'])) && (!empty($field['allowEmpty']) || !empty($data[$field['name']]) || (isset($data[$field['name']]) && (string)$data[$field['name']] !== ''))) {
			$fieldValues = $Model->{$field['method']}($data, $field);
			if (!empty($conditions[$fieldName]) && is_array($conditions[$fieldName])) {
				$conditions[$fieldName] = array_unique(array_merge(array($conditions[$fieldName]), array($fieldValues)));
			} else {
				$conditions[$fieldName] = $fieldValues;
			}
		}
		return $conditions;
	}

/**
 * Add Conditions based query to search conditions.
 *
 * @param Model $Model  Instance of AppModel
 * @param array $conditions Existing conditions.
 * @param array $data Data for a field.
 * @param array $field Info for field.
 * @return array of conditions modified by this method
 */
	protected function _addCondQuery(Model $Model, &$conditions, $data, $field) {
		if ((method_exists($Model, $field['method']) || $this->_checkBehaviorMethods($Model, $field['method'])) && (!empty($field['allowEmpty']) || !empty($data[$field['name']]) || (isset($data[$field['name']]) && (string)$data[$field['name']] !== ''))) {
			$conditionsAdd = $Model->{$field['method']}($data, $field);
			// if our conditions function returns something empty, nothing to merge in
			if (!empty($conditionsAdd)) {
				$conditions = Set::merge($conditions, (array)$conditionsAdd);
			}
		}
		return $conditions;
	}

/**
 * Add Conditions based subquery to search conditions.
 *
 * @param Model $Model  Instance of AppModel
 * @param array $conditions Existing conditions.
 * @param array $data Data for a field.
 * @param array $field Info for field.
 * @return array of conditions modified by this method
 */
	protected function _addCondSubquery(Model $Model, &$conditions, $data, $field) {
		$fieldName = $field['field'];
		if ((method_exists($Model, $field['method']) || $this->_checkBehaviorMethods($Model, $field['method'])) && (!empty($field['allowEmpty']) || !empty($data[$field['name']]) || (isset($data[$field['name']]) && (string)$data[$field['name']] !== ''))) {
			$subquery = $Model->{$field['method']}($data, $field);
			// if our subquery function returns something empty, nothing to merge in
			if (!empty($subquery)) {
				$conditions[] = $Model->getDataSource()->expression("$fieldName in ($subquery)");
			}
		}
		return $conditions;
	}

/**
 * Helper method for getQuery.
 * extension of dbo source method. Create association query.
 *
 * @param Model $Model
 * @param array $queryData
 * @return string
 */
	protected function _queryGet(Model $Model, $queryData = array()) {
		/** @var DboSource $db  */
		$db = $Model->getDataSource();
		$queryData = $this->_scrubQueryData($queryData);
		$recursive = null;
		$byPass = false;
		$null = null;
		$linkedModels = array();

		if (isset($queryData['recursive'])) {
			$recursive = $queryData['recursive'];
		}

		if ($recursive !== null) {
			$_recursive = $Model->recursive;
			$Model->recursive = $recursive;
		}

		if (!empty($queryData['fields'])) {
			$byPass = true;
			$queryData['fields'] = $db->fields($Model, null, $queryData['fields']);
		} else {
			$queryData['fields'] = $db->fields($Model);
		}

		$_associations = $Model->associations();

		if ($Model->recursive == -1) {
			$_associations = array();
		} elseif ($Model->recursive == 0) {
			unset($_associations[2], $_associations[3]);
		}

		foreach ($_associations as $type) {
			foreach ($Model->{$type} as $assoc => $assocData) {
				$linkModel = $Model->{$assoc};
				$external = isset($assocData['external']);

				$linkModel->getDataSource();
				if ($Model->useDbConfig === $linkModel->useDbConfig) {
					if ($byPass) {
						$assocData['fields'] = false;
					}
					if ($db->generateAssociationQuery($Model, $linkModel, $type, $assoc, $assocData, $queryData, $external, $null) === true) {
						$linkedModels[$type . '/' . $assoc] = true;
					}
				}
			}
		}

		return trim($db->generateAssociationQuery($Model, null, null, null, null, $queryData, false, $null));
	}

/**
 * Private helper method to remove query metadata in given data array.
 *
 * @param array $data
 * @return array
 */
	protected function _scrubQueryData($data) {
		static $base = null;
		if ($base === null) {
			$base = array_fill_keys(array('conditions', 'fields', 'joins', 'order', 'limit', 'offset', 'group'), array());
		}
		return (array)$data + $base;
	}

/**
 * Check if model have some method in attached behaviors
 *
 * @param Model $Model
 * @param string $method
 * @return boolean, true if method exists in attached and enabled behaviors
 */
	protected function _checkBehaviorMethods(Model $Model, $method) {
		$behaviors = $Model->Behaviors->enabled();
		$count = count($behaviors);
		$found = false;
		for ($i = 0; $i < $count; $i++) {
			$name = $behaviors[$i];
			$methods = get_class_methods($Model->Behaviors->{$name});
			$check = array_flip($methods);
			$found = isset($check[$method]);
			if ($found) {
				return true;
			}
		}
		return $found;
	}

}
