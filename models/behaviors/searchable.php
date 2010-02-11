<?php

class SearchableBehavior extends ModelBehavior {
/**
 * settings indexed by model name.
 *
 * @var array
 * @access private
 */
	public $settings = array();

/**
 * Default settings
 *
 * @var string
 **/
	protected $_defaults = array();

/**
 * Configuration of model
 *
 * @param AppModel $model
 * @param array $config
 */
	public function setup(Model $model, $config = array()) {
		$this->settings[$model->alias] = array_merge($this->_defaults, $config);
	}

/**
 * Helper method to build search conditions
 *
 * @param AppModel $model Reference to the model
 * @param array $conditions existing Conditions collected for the model
 * @param array $data Array of data used in search query
 * @param array $field Field definition information
 * @param string $modelName Name of model (defaults to current model name)
 * @return array of conditions.
 * @access public
 */
	public function addCondStr(Model $model, &$conditions, $data, $field, $modelName = '') {
		if ($modelName == '') {
			$modelName = $model->name;
		}
		if (isset($field['model'])) {
			$modelName = $field['model'];
		}
		$fieldName = $field['name'];
		if (isset($field['realname'])) {
			$fieldName = $field['realname'];
		}
		if (!empty($data[$field['name']])) {
			$conditions[$modelName.".$fieldName LIKE"] = "%" . $data[$field['name']] . "%";
		}
		return $conditions;
	}

/**
 * Helper method to build search conditions
 *
 * @param AppModel $model Reference to the model
 * @param array $conditions existing Conditions collected for the model
 * @param array $data Array of data used in search query
 * @param array $field Field definition information
 * @param string $modelName Name of model (defaults to current model name)
 * @return array of conditions.
 * @access public
 */
	public function addCondInt(Model $model, &$conditions, $data, $field, $modelName = '') {
		if ($modelName == '') {
			$modelName = $model->name;
		}
		if (isset($field['model'])) {
			$modelName = $field['model'];
		}
		$fieldName = $field['name'];
		if (isset($field['realname'])) {
			$fieldName = $field['realname'];
		}
		if (!empty($data[$field['name']]) || (isset($data[$field['name']]) && (int)$data[$field['name']] === 0)) {
			$conditions[$modelName.".$fieldName"] = $data[$field['name']];
		}
		return $conditions;
	}

/**
 * Add Conditions based query to search conditions.
 *
 * @param Object $model  Instance of AppModel
 * @param array $conditions Existing conditions.
 * @param array $data Data for a field.
 * @param array $field Info for field.
 * @return array of conditions modified by this method.
 **/
	public function addCondQuery(Model $model, &$conditions, $data, $field, $modelName = '') {
		if ($modelName == '') {
			$modelName = $model->name;
		}
		if (isset($field['model'])) {
			$modelName = $field['model'];
		}
		if ((method_exists($model, $field['method']) || $this->__checkBehaviorMethods($model, $field['method'])) && !empty($data[$field['name']])) {
			$conditionsAdd = $model->{$field['method']}($data);
			$conditions = array_merge($conditions, (array)$conditionsAdd);
		}
		return $conditions;
	}

/**
 * Add Conditions based expressions to search conditions.
 *
 * @param array $field Info for field.
 * @return array of conditions modified by this method.
 **/
	private function __checkBehaviorMethods(AppModel $Model, $method) {
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

/**
 * Add Conditions based expressions to search conditions.
 *
 * @param Object $model  Instance of AppModel
 * @param array $conditions Existing conditions.
 * @param array $data Data for a field.
 * @param array $field Info for field.
 * @return array of conditions modified by this method.
 **/
	public function addCondExpression(Model $model, &$conditions, $data, $field, $modelName = '') {
		if ($modelName == '') {
			$modelName = $model->name;
		}
		if (isset($field['model'])) {
			$modelName = $field['model'];
		}
		$fieldName = $field['field'];
		if ((method_exists($model, $field['method']) || $this->__checkBehaviorMethods($model, $field['method'])) && !empty($data[$field['name']])) {
			$fieldValues = $model->{$field['method']}($data);
			if (!empty($conditions[$fieldName]) && is_array($conditions[$fieldName])) {
				$conditions[$fieldName] = array_unique(array_merge($conditions[$fieldName], (array)$fieldValues));
			} else {
				$conditions[$fieldName] = $fieldValues;
			}
		}
		return $conditions;
	}

/**
 * Add Conditions based subquery to search conditions.
 *
 * @param Object $model  Instance of AppModel
 * @param array $conditions Existing conditions.
 * @param array $data Data for a field.
 * @param array $field Info for field.
 * @return array of conditions modified by this method.
 **/
	public function addCondSubquery(Model $model, &$conditions, $data, $field, $modelName = '') {
		if ($modelName == '') {
			$modelName = $model->name;
		}
		if (isset($field['model'])) {
			$modelName = $field['model'];
		}
		$fieldName = $field['field'];
		if (method_exists($model, $field['method']) && !empty($data[$field['name']])) {
			$subquery = $model->{$field['method']}($data);
			$conditions[] = array("$fieldName in ($subquery)");
		}
		return $conditions;
	}

/**
 * Validate search
 *
 * @param object Model
 * @return boolean always true
 */
	public function validateSearch(Model $model) {
		$keys = array_keys($model->data[$model->name]);
		foreach ($keys as $key) {
			if (empty($model->data[$model->name][$key])) {
				unset($model->data[$model->name][$key]);
			}
		}
		return true;
	}

/**
 * filter retrieving variables only that present in  Model::filterArgs
 *
 * @param
 * @param
 * @return
 * @access public
 */
	public function passedArgs(Model $model, $vars) {
		$result = array();
		foreach ($vars as $var=>$val) {
			if (in_array($var, Set::extract($model->filterArgs, '{n}.name'))) {
				$result[$var] = $val;
			}
		}
		return $result;
	}

/**
 * Method to generated DML SQL queries using find* style.
 *
 * Specifying 'fields' for new-notation 'list':
 *  - If no fields are specified, then 'id' is used for key and 'model->displayField' is used for value.
 *  - If a single field is specified, 'id' is used for key and specified field is used for value.
 *  - If three fields are specified, they are used (in order) for key, value and group.
 *  - Otherwise, first and second fields are used for key and value.
 *
 * @param array $conditions SQL conditions array, or type of find operation (all / first / count / neighbors / list / threaded)
 * @param mixed $fields Either a single string of a field name, or an array of field names, or options for matching
 * @param string $order SQL ORDER BY conditions (e.g. "price DESC" or "name ASC")
 * @param integer $recursive The number of levels deep to fetch associated records
 * @return string SQL query string.
 * @access public
 * @link http://book.cakephp.org/view/449/find
 */
	public function getQuery(Model $model, $conditions = null, $fields = array(), $order = null, $recursive = null) {
		if (!is_string($conditions) || (is_string($conditions) && !array_key_exists($conditions, $model->_findMethods))) {
			$type = 'first';
			$query = compact('conditions', 'fields', 'order', 'recursive');
		} else {
			list($type, $query) = array($conditions, $fields);
		}

		$db =& ConnectionManager::getDataSource($model->useDbConfig);
		$model->findQueryType = $type;
		$model->id = $model->getID();

		$query = array_merge(
			array(
				'conditions' => null, 'fields' => null, 'joins' => array(), 'limit' => null,
				'offset' => null, 'order' => null, 'page' => null, 'group' => null, 'callbacks' => true
			),
			(array)$query
		);

		if ($type != 'all') {
			if ($model->_findMethods[$type] === true) {
				$query = $model->{'_find' . ucfirst($type)}('before', $query);
			}
		}

		if (!is_numeric($query['page']) || intval($query['page']) < 1) {
			$query['page'] = 1;
		}
		if ($query['page'] > 1 && !empty($query['limit'])) {
			$query['offset'] = ($query['page'] - 1) * $query['limit'];
		}
		if ($query['order'] === null && $model->order !== null) {
			$query['order'] = $model->order;
		}
		$query['order'] = array($query['order']);


		if ($query['callbacks'] === true || $query['callbacks'] === 'before') {
			$return = $model->Behaviors->trigger($model, 'beforeFind', array($query), array(
				'break' => true, 'breakOn' => false, 'modParams' => true
			));
			$query = (is_array($return)) ? $return : $query;

			if ($return === false) {
				return null;
			}

			$return = $model->beforeFind($query);
			$query = (is_array($return)) ? $return : $query;

			if ($return === false) {
				return null;
			}
		}
		return $this->__queryGet($model, $query, $recursive);
	}

/**
 * parseCriteria
 * parses the GET data and returns the conditions for the find('all')/paginate
 * we are just going to test if the params are legit
 *
 * @param array $data Criteria of key->value pairs from post/named parameters
 * @return array Array of conditions that express the conditions needed for the search.
 */
	public function parseCriteria(Model $model, $data) {
		$conditions = array();
		foreach ($model->filterArgs as $field) {
			if (isset($field['model'])) {
				$modelName = $field['model'];
			} else {
				$modelName = '';
			}
			if (in_array($field['type'], array('string', 'like'))) {
				$this->addCondStr($model, $conditions, $data, $field);
			} elseif (in_array($field['type'], array('int', 'value'))) {
				$this->addCondInt($model, $conditions, $data, $field);
			} elseif ($field['type'] == 'expression') {
				$this->addCondExpression($model, $conditions, $data, $field);
			} elseif ($field['type'] == 'query') {
				$this->addCondQuery($model, $conditions, $data, $field);
			} elseif ($field['type'] == 'subquery') {
				$this->addCondSubquery($model, $conditions, $data, $field);
			}
		}
		return $conditions;
	}

/**
 * Helper method for getQuery.
 * extension of dbosource method. Create association query.
 *
 * @param AppModel $model
 * @param array $queryData
 * @param integer $recursive
 */
	public function __queryGet(Model $model, $queryData = array(), $recursive = null) {
		$db =& ConnectionManager::getDataSource($model->useDbConfig);
		$db->__scrubQueryData($queryData);
		$null = null;
		$array = array();
		$linkedModels = array();
		$db->__bypass = false;
		$db->__booleans = array();

		if ($recursive === null && isset($queryData['recursive'])) {
			$recursive = $queryData['recursive'];
		}

		if (!is_null($recursive)) {
			$_recursive = $model->recursive;
			$model->recursive = $recursive;
		}

		if (!empty($queryData['fields'])) {
			$db->__bypass = true;
			$queryData['fields'] = $db->fields($model, null, $queryData['fields']);
		} else {
			$queryData['fields'] = $db->fields($model);
		}

		foreach ($model->__associations as $type) {
			foreach ($model->{$type} as $assoc => $assocData) {
				if ($model->recursive > -1) {
					$linkModel =& $model->{$assoc};

					$external = isset($assocData['external']);
					if ($model->alias == $linkModel->alias && $type != 'hasAndBelongsToMany' && $type != 'hasMany') {
						if (true === $db->generateSelfAssociationQuery($model, $linkModel, $type, $assoc, $assocData, $queryData, $external, $null)) {
							$linkedModels[] = $type . '/' . $assoc;
						}
					} else {
						if ($model->useDbConfig == $linkModel->useDbConfig) {
							if (true === $db->generateAssociationQuery($model, $linkModel, $type, $assoc, $assocData, $queryData, $external, $null)) {
								$linkedModels[] = $type . '/' . $assoc;
							}
						}
					}
				}
			}
		}
		return $db->generateAssociationQuery($model, $null, null, null, null, $queryData, false, $null);
	}

/**
 * Clear all associations
 *
 * @param AppModel $model
 * @param bool $reset
 */
	public function unbindAllModels(Model $model, $reset = false) {
		$assocs = array('belongsTo', 'hasOne', 'hasMany', 'hasAndBelongsToMany');
		$unbind = array();
		foreach ($assocs as $assoc) {
		  $unbind[$assoc] = array_keys($model->{$assoc});
		}
		$model->unbindModel($unbind, $reset);
	}

}
?>