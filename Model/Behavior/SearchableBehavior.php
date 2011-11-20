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
 * Searchable behavior
 *
 * @package		plugins.search
 * @subpackage	plugins.search.models.behaviors
 */
class SearchableBehavior extends ModelBehavior {

/**
 * settings indexed by model name.
 *
 * @var array
 */
	public $settings = array();

/**
 * Default settings
 * - wildcards: set to false if you dont want to allow wildcards in like statements
 * - wildcardAny: the character used instead of % (% is a normal character then)
 * - wildcardOne: the character used instead of _ (_ is a normal character then)
 * - like (can be used if wildcards is disabled - auto add % wildcard to beginning, end or both)
 *
 * @var string
 */
	protected $_defaults = array(
		'wildcards' => true,
		'wildcardAny' => '*', //on windows/unix/mac thats the default one
		'wildcardOne' => '?', //on windows/unix/mac thats the default one
		'like' => array('before'=>true, 'after'=>true)
	);

/**
 * Configuration of model
 *
 * @param AppModel $Model
 * @param array $config
 */
	public function setup(Model $Model, $config = array()) {
		$this->settings[$Model->alias] = array_merge($this->_defaults, $config);
		if (!isset($Model->filterArgs)) {
			return;
		}
		foreach ($Model->filterArgs as $key => $val) {
			if (!isset($val['name'])) {
				$Model->filterArgs[$key]['name'] = $key;
			}
		}
	}

/**
 * parseCriteria
 * parses the GET data and returns the conditions for the find('all')/paginate
 * we are just going to test if the params are legit
 *
 * @param array $data Criteria of key->value pairs from post/named parameters
 * @return array Array of conditions that express the conditions needed for the search.
 */
	public function parseCriteria(Model $Model, $data) {
		$conditions = array();
		foreach ($Model->filterArgs as $field) {
			if (in_array($field['type'], array('string', 'like'))) {
				$this->_addCondLike($Model, $conditions, $data, $field);
			} elseif (in_array($field['type'], array('int', 'value'))) {
				$this->_addCondValue($Model, $conditions, $data, $field);
			} elseif ($field['type'] == 'expression') {
				$this->_addCondExpression($Model, $conditions, $data, $field);
			} elseif ($field['type'] == 'query') {
				$this->_addCondQuery($Model, $conditions, $data, $field);
			} elseif ($field['type'] == 'subquery') {
				$this->_addCondSubquery($Model, $conditions, $data, $field);
			}
		}
		return $conditions;
	}

/**
 * Validate search
 *
 * @param object Model
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
 * @param object Model
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
 * 
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
		return $this->__queryGet($Model, $query);
	}

/**
 * Clear all associations
 *
 * @param AppModel $Model
 * @param bool $reset
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
	 * for custom queries inside the model
	 * example "makePhoneCondition": $cond = array('OR' => array_merge($this->condLike('cell_number', $filter), $this->condLike('landline_number', $filter, array('before'=>false))));
	 * 2011-07-06 ms
	 */
	public function condLike(Model $Model, $name, $data, $field = array()) {
		$conditions = array();
		$field['name'] = $name;
		if (!is_array($data)) {
			$data = array($name=>$data);
		}
		return $this->_addCondLike($Model, $conditions, $data, $field);
	}
	
	/**
	 * replace substitions with original wildcards
	 * but first, escape the original wildcards in the text to use them as normal search text
	 * @param Model $Model
	 * @param string $queryLikeString
	 * @return string $queryLikeString
	 */
	public function formatLike(Model $Model, $data, $options = array()) {
		$options = am($this->settings[$Model->alias], $options);
		if (!$options['wildcards']) {
			return $data;
		}
		$from = $to = $substFrom = $substTo = array();
		if ($options['wildcardAny'] != '%') {
			$from[] = '%';
			$to[] = '\%';
			$substFrom[] = $options['wildcardAny'];
			$substTo[] = '%';
		}
		if ($options['wildcardOne'] != '_') {
			$from[] = '_';
			$to[] = '\_';
			$substFrom[] = $options['wildcardOne'];
			$substTo[] = '_';
		}
		if (!empty($from)) {	
			/* escape first */
			$data = str_replace($from, $to, $data);
			/* replace wildcards */
			$data = str_replace($substFrom, $substTo, $data);
		}
		return $data;
	}
	
	/**
	 * return the current chars for querying LIKE statements on this model
	 * @param Model $Model Reference to the model
	 * @return array, [one=>..., any=>...]
	 */
	public function getLikeQueryChars(Model $Model, $options) {
		$options = am($this->settings[$Model->alias], $options);
		return array('one' => $options['wildcardOne'], 'any' => $options['wildcardAny']);
	}

/**
 * Add Conditions based on fuzzy comparison
 *
 * @param AppModel $Model Reference to the model
 * @param array $conditions existing Conditions collected for the model
 * @param array $data Array of data used in search query
 * @param array $field Field definition information
 * @return array of conditions.
 */
	protected function _addCondLike(Model $Model, &$conditions, $data, $field) {
		$fieldName = $field['name'];
		if (isset($field['field'])) {
			$fieldName = $field['field'];
		}
		if (strpos($fieldName, '.') === false) {
			$fieldName = $Model->alias . '.' . $fieldName;
		}
		$field = array_merge($this->settings[$Model->alias]['like'], $field);
		if (empty($data[$field['name']])) {
			return $conditions;
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
		if ($options['wildcardAny'] != '%' || ($field['before'] !== false || $field['after'] !== false)) {
			$from[] = '%';
			$to[] = '\%';
		}
		if ($options['wildcardOne'] != '_' || ($field['before'] !== false || $field['after'] !== false)) {
			$from[] = '_';
			$to[] = '\_';
		}
		if (!empty($from)) {	
			$data[$field['name']] = str_replace($from, $to, $data[$field['name']]);
		}
		if ($field['before'] === false && $field['after'] === false) {
			if ($options['wildcardAny'] != '%') {
				$substFrom[] = $options['wildcardAny'];
				$substTo[] = '%';
			}
			if ($options['wildcardOne'] != '_') {
				$substFrom[] = $options['wildcardOne'];
				$substTo[] = '_';
			}				
			$data[$field['name']] = str_replace($substFrom, $substTo, $data[$field['name']]);
		}
		
		$conditions[$fieldName . " LIKE"] = $field['before'] . $data[$field['name']] . $field['after'];
		return $conditions;
	}

/**
 * Add Conditions based on exact comparison
 *
 * @param AppModel $Model Reference to the model
 * @param array $conditions existing Conditions collected for the model
 * @param array $data Array of data used in search query
 * @param array $field Field definition information
 * @return array of conditions.
 */
	protected function _addCondValue(Model $Model, &$conditions, $data, $field) {
		$fieldName = $field['name'];
		if (isset($field['field'])) {
			$fieldName = $field['field'];
		}
		if (strpos($fieldName, '.') === false) {
			$fieldName = $Model->alias . '.' . $fieldName;
		}
		if (!empty($data[$field['name']]) || (isset($data[$field['name']]) && ($data[$field['name']] === 0 || $data[$field['name']] === '0'))) {
			$conditions[$fieldName] = $data[$field['name']];
		}
		return $conditions;
	}

/**
 * Add Conditions based query to search conditions.
 *
 * @param Object $Model  Instance of AppModel
 * @param array $conditions Existing conditions.
 * @param array $data Data for a field.
 * @param array $field Info for field.
 * @return array of conditions modified by this method.
 */
	protected function _addCondQuery(Model $Model, &$conditions, $data, $field) {
		if ((method_exists($Model, $field['method']) || $this->__checkBehaviorMethods($Model, $field['method'])) && (!empty($data[$field['name']]) || (isset($data[$field['name']]) && ($data[$field['name']] === 0 || $data[$field['name']] === '0')))) {
			$conditionsAdd = $Model->{$field['method']}($data, $field);
			$conditions = array_merge($conditions, (array)$conditionsAdd);
		}
		return $conditions;
	}

/**
 * Add Conditions based expressions to search conditions.
 *
 * @param Object $Model  Instance of AppModel
 * @param array $conditions Existing conditions.
 * @param array $data Data for a field.
 * @param array $field Info for field.
 * @return array of conditions modified by this method.
 */
	protected function _addCondExpression(Model $Model, &$conditions, $data, $field) {
		$fieldName = $field['field'];
		if ((method_exists($Model, $field['method']) || $this->__checkBehaviorMethods($Model, $field['method'])) && (!empty($data[$field['name']]) || (isset($data[$field['name']]) && ($data[$field['name']] === 0 || $data[$field['name']] === '0')))) {
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
 * Add Conditions based subquery to search conditions.
 *
 * @param Object $Model  Instance of AppModel
 * @param array $conditions Existing conditions.
 * @param array $data Data for a field.
 * @param array $field Info for field.
 * @return array of conditions modified by this method.
 */
	protected function _addCondSubquery(Model $Model, &$conditions, $data, $field) {
		$fieldName = $field['field'];
		if ((method_exists($Model, $field['method']) || $this->__checkBehaviorMethods($Model, $field['method'])) && (!empty($data[$field['name']]) || (isset($data[$field['name']]) && ($data[$field['name']] === 0 || $data[$field['name']] === '0')))) {
			$subquery = $Model->{$field['method']}($data, $field);
			$conditions[] = array("$fieldName in ($subquery)");
		}
		return $conditions;
	}

/**
 * Helper method for getQuery.
 * extension of dbosource method. Create association query.
 *
 * @param AppModel $Model
 * @param array $queryData
 * @param integer $recursive
 */
	private function __queryGet(Model $Model, $queryData = array()) {
		/** @var DboSource $db  */
		$db = $Model->getDataSource();
		$queryData = $this->_scrubQueryData($queryData);
		$recursive = null;
		$byPass = false;
		$null = null;
		$array = array();
		$linkedModels = array();

		if (isset($queryData['recursive'])) {
			$recursive = $queryData['recursive'];
		}

		if (!is_null($recursive)) {
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
					if (true === $db->generateAssociationQuery($Model, $linkModel, $type, $assoc, $assocData, $queryData, $external, $null)) {
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
	private function __checkBehaviorMethods(Model $Model, $method) {
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