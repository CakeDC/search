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
 * Post-Redirect-Get: Transfers POST Requests to GET Requests
 *
 * @package		plugins.search
 * @subpackage	plugins.search.controllers.components
 */
App::uses('Component', 'Controller');
App::uses('Set', 'Utility');

class PrgComponent extends Component {

/**
 * Actions used to fetch the post data
 *
 * Maps the action that takes the post data and processes it by using this
 * component and maps it to another action that is accessed by a redirect which
 * has the post data attached as get data now
 *
 * array('search' => 'results');
 * array('search' => array('controller' => 'results');
 *
 * @var array actions
 */
	public $actions = array();

/**
 * Enables encoding on all presetVar fields
 *
 * @var boolean
 */
	public $encode = false;

/**
 * Default options
 *
 * @var array
 */
	protected $_defaults = array(
		'commonProcess' => array(
			'formName' => null,
			'keepPassed' => true,
			'action' => null,
			'modelMethod' => 'validateSearch',
			'allowedParams' => array(),
			'paramType' => 'named',
			'filterEmpty' => false
		),
		'presetForm' => array(
			'model' => null,
			'paramType' => 'named'
		)
	);

/**
 * Constructor
 *
 * @param object Controller object
 */
	public function __construct(ComponentCollection $collection, $settings) {
		$this->controller = $collection->getController();
		$this->_defaults = Set::merge($this->_defaults, $settings);
	}

/**
 * Poplulates controller->data with allowed values from the named/passed get params
 *
 * Fields in $controller::$presetVars that have a type of 'lookup' the foreignKey value will be inserted
 *
 * 1) 'lookup'
 *    Is used for autocomplete selectors
 *    For autocomplete we have hidden field with value and autocomplete text box
 *    Component fills text part on id from hidden field
 * 2) 'value'
 *    The value as it is entered in form
 * 3) 'checkbox'
 *    Allows to pass several values internaly encoded as string
 *
 * 1 use field, model, formField, and modelField
 * 2, 3 need only field parameter
 *
 * @param array $options
 */
	public function presetForm($options) {
		if (!is_array($options)) {
			$options = array('model' => $options);
		}
		extract(Set::merge($this->_defaults['presetForm'], $options));

		$data = array($model => array());
		if ($paramType == 'named') {
			$args = $this->controller->passedArgs;
		} else {
			$args = $this->controller->request->query;
		}
		foreach ($this->controller->presetVars as $field) {
			if ($this->encode == true || isset($field['encode']) && $field['encode'] == true) {
				// Its important to set it also back to the controllers passed args!
				if (isset($args[$field['field']])) {
					$val = $args[$field['field']];
					$this->controller->passedArgs[$field['field']] = $args[$field['field']] = base64_decode(str_pad(strtr($val, '-_', '+/'), strlen($val) % 4, '=', STR_PAD_RIGHT));
				}
			}

			if ($field['type'] == 'lookup') {
				if (isset($args[$field['field']])) {
					$searchModel = $field['model'];
					$this->controller->loadModel($searchModel);
					$this->controller->{$searchModel}->recursive = -1;
					$result = $this->controller->{$searchModel}->findById($args[$field['field']]);
					$data[$model][$field['field']] = $args[$field['field']];
					$data[$model][$field['formField']] = $result[$searchModel][$field['modelField']];
				}
			}

			if ($field['type'] == 'checkbox') {
				if (isset($args[$field['field']])) {
					$values = split('\|', $args[$field['field']]);
					$data[$model][$field['field']] = $values;
				}
			}

			if ($field['type'] == 'value') {
				if (isset($args[$field['field']])) {
					$data[$model][$field['field']] = $args[$field['field']];
				}
			}
		}

		$this->controller->data = $data;
		$this->controller->parsedData = $data;
	}

/**
 * Restores form params for checkboxs and other url encoded params
 *
 * @param array
 */
	public function serializeParams(&$data) {
		foreach ($this->controller->presetVars as $field) {
			if ($field['type'] == 'checkbox') {
				if (array_key_exists($field['field'], $data)) {
					$values = join('|', (array)$data[$field['field']]);
				} else {
					$values = '';
				}
				$data[$field['field']] = $values;
			}

			if (($this->encode == true || isset($field['encode']) && $field['encode'] == true) && isset($data[$field['field']])) {
				$data[$field['field']] = rtrim(strtr(base64_encode($data[$field['field']]), '+/', '-_'), '=');
			}
		}
		return $data;
	}

/**
 * Connect named arguments
 *
 * @param array $data
 * @param array $exclude
 * @return void
 */
	public function connectNamed($data = null, $exclude = array()) {
		if (!isset($data)) {
			$data = $this->controller->passedArgs;
		}

		if (!is_array($data)) {
			return;
		}

		foreach ($data as $key => $value) {
			if (!is_numeric($key) && !in_array($key, $exclude)) {
				Router::connectNamed(array($key));
			}
		}
	}

/**
 * Exclude
 *
 * Removes key/values from $array based on $exclude
 *
 * @param array Array of data to be filtered
 * @param array Array of keys to exclude from other $array
 * @return array
 */
	public function exclude($array, $exclude) {
		$data = array();
		foreach ($array as $key => $value) {
			if (!is_numeric($key) && !in_array($key, $exclude)) {
				$data[$key] = $value;
			}
		}
		return $data;
	}

/**
 * Common search method
 *
 * Handles processes common to all PRG forms
 *
 * - Handles validation of post data
 * - converting post data into named params
 * - Issuing redirect(), and connecting named parameters before redirect
 * - Setting named parameter form data to view
 *
 * @param string $modelName - Name of the model class being used for the prg form
 * @param array $options Optional parameters:
 *  - string formName - name of the form involved in the prg
 *  - string action - The action to redirect to. Defaults to the current action
 *  - mixed modelMethod - If not false a string that is the model method that will be used to process the data
 *  - array allowedParams - An array of additional top level route params that should be included in the params processed
 *  - string paramType - 'named' if you want to used named params or 'querystring' is you want to use query string
 * @return void
 */
	public function commonProcess($modelName = null, $options = array()) {
		extract(Set::merge($this->_defaults['commonProcess'], $options));

		if (empty($modelName)) {
			$modelName = $this->controller->modelClass;
		}

		if (empty($formName)) {
			$formName = $modelName;
		}

		if (empty($action)) {
			$action = $this->controller->action;
		}

		if (!empty($this->controller->data)) {
			$this->controller->{$modelName}->data = $this->controller->data;
			$valid = true;
			if ($modelMethod !== false) {
				$valid = $this->controller->{$modelName}->{$modelMethod}();
			}

			if ($valid) {
				$params = $this->controller->request->params['named'];
				if ($keepPassed) {
					$params = array_merge($this->controller->request->params['pass'], $params);
				}

				$searchParams = $this->controller->data[$modelName];
				$searchParams = $this->exclude($searchParams, array());
				if ($filterEmpty) {
					$searchParams = Set::filter($searchParams);
				}

				$this->serializeParams($searchParams);

				if ($paramType == 'named') {
					$params = array_merge($params, $searchParams);
					$this->connectNamed($params, array());
				} else {
					$this->connectNamed($params, array());
					$params['?'] = array_merge($this->controller->request->query, $searchParams);
				}

				$params['action'] = $action;

				foreach ($allowedParams as $key) {
					if (isset($this->controller->request->params[$key])) {
						$params[$key] = $this->controller->request->params[$key];
					}
				}

				$this->controller->redirect($params);
			} else {
				$this->controller->Session->setFlash(__d('search', 'Please correct the errors below.'));
			}
		} elseif (($paramType == 'named' && !empty($this->controller->passedArgs)) ||
				($paramType == 'querystring' && !empty($this->controller->request->query))
			) {
			$this->connectNamed($this->controller->passedArgs, array());
			$this->presetForm(array('model' => $formName, 'paramType' => $paramType));
		}
	}
}
