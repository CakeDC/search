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

/**
 * Post-Redirect-Get: Transfers POST Requests to GET Requests
 *
 */
App::uses('Component', 'Controller');
App::uses('Hash', 'Utility');

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
 * If the current request is an actual search (at least one search value present)
 *
 * @var boolean
 */
	public $isSearch = false;

/**
 * Parsed params of current request
 *
 * @var array
 */
	protected $_parsedParams = array();

/**
 * Default options
 *
 * @var array
 */
	protected $_defaults = array(
		'callback' => 'initialize',
		'commonProcess' => array(
			'formName' => null,
			'keepPassed' => true,
			'action' => null,
			'modelMethod' => 'validateSearch',
			'allowedParams' => array(),
			'paramType' => 'querystring',
			'filterEmpty' => false
		),
		'presetForm' => array(
			'model' => null,
			'paramType' => 'querystring'
		)
	);

/**
 * Constructor
 *
 * @param ComponentCollection $collection
 * @param array $settings
 */
	public function __construct(ComponentCollection $collection, $settings = array()) {
		$this->_defaults = Hash::merge($this->_defaults, array(
			'commonProcess' => (array)Configure::read('Search.Prg.commonProcess'),
			'presetForm' => (array)Configure::read('Search.Prg.presetForm'),
		), $settings);
	}

/**
 * Called after the Controller::beforeFilter() and before the controller action
 *
 * @param Controller $controller Controller with components to startup
 * @return void
 * @link http://book.cakephp.org/2.0/en/controllers/components.html#Component::startup
 */
	public function startup(Controller $controller) {
		if ($this->_defaults['callback'] === 'startup') {
			$this->init($controller);
		}
	}

/**
 * Called before the Controller::beforeFilter().
 *
 * @param Controller $controller Controller with components to initialize
 * @return void
 */
	public function initialize(Controller $controller) {
		if ($this->_defaults['callback'] === 'initialize') {
			$this->init($controller);
		}
	}

/**
 * Initializes the component based on the controller
 *
 * @param controller $controller
 * @return void
 */
	public function init(Controller $controller) {
		$this->controller = $controller;

		// fix for not throwing warnings
		if (!isset($this->controller->presetVars)) {
			$this->controller->presetVars = true;
		}

		$model = $this->controller->modelClass;
		if (!empty($this->_defaults['presetForm']['model'])) {
			$model = $this->_defaults['presetForm']['model'];
		}

		if ($this->controller->presetVars === true) {
			// auto-set the presetVars based on search definitions in model
			$this->controller->presetVars = array();
			$filterArgs = array();
			if (!empty($this->controller->$model->filterArgs)) {
				$filterArgs = $this->controller->$model->filterArgs;
			}

			foreach ($filterArgs as $key => $arg) {
				if ($args = $this->_parseFromModel($arg, $key)) {
					$this->controller->presetVars[] = $args;
				}
			}
		}
		foreach ($this->controller->presetVars as $key => $field) {
			if ($field === true) {
				if (isset($this->controller->$model->filterArgs[$key])) {
					$field = $this->_parseFromModel($this->controller->$model->filterArgs[$key], $key);
				} else {
					$field = array('type' => 'value');
				}
			}
			if (!isset($field['field'])) {
				$field['field'] = $key;
			}
			$this->controller->presetVars[$key] = $field;
		}
	}

/**
 * Populates controller->request->data with allowed values from the named/passed get params
 *
 * Fields in $controller::$presetVars that have a type of 'lookup' the foreignKey value will be inserted
 *
 * 1) 'lookup'
 *    Is used for autocomplete selectors
 *    For auto-complete we have hidden field with value and autocomplete text box
 *    Component fills text part on id from hidden field
 * 2) 'value'
 *    The value as it is entered in form
 * 3) 'checkbox'
 *    Allows to pass several values internally encoded as string
 *
 * 1 uses field, model, formField, and modelField
 * 2, 3 need only field parameter
 *
 * @param array $options
 * @return void
 */
	public function presetForm($options) {
		if (!is_array($options)) {
			$options = array('model' => $options);
		}
		extract(Hash::merge($this->_defaults['presetForm'], $options));

		if ($paramType === 'named') {
			$args = $this->controller->passedArgs;
		} else {
			$args = $this->controller->request->query;
		}

		$parsedParams = array();
		$data = array($model => array());
		foreach ($this->controller->presetVars as $field) {
			if (!isset($args[$field['field']])) {
				continue;
			}

			if ($paramType === 'named' && ($this->encode || !empty($field['encode']))) {
				// Its important to set it also back to the controllers passed args!
				$fieldContent = str_replace(array('-', '_'), array('/', '='), $args[$field['field']]);
				$args[$field['field']] = base64_decode($fieldContent);
			}
			
			switch ($field['type']) {
				case 'lookup':
					if (!empty($args[$field['field']])) {
						$searchModel = $field['model'];
						$this->controller->loadModel($searchModel);
						$this->controller->{$searchModel}->recursive = -1;
						$result = $this->controller->{$searchModel}->findById($args[$field['field']]);
						$parsedParams[$field['field']] = $args[$field['field']];
						$parsedParams[$field['formField']] = $result[$searchModel][$field['modelField']];
						$data[$model][$field['field']] = $args[$field['field']];
						$data[$model][$field['formField']] = $result[$searchModel][$field['modelField']];
					}
					break;
				
				case 'checkbox':
					$values = explode('|', $args[$field['field']]);
					$parsedParams[$field['field']] = $values;
					$data[$model][$field['field']] = $values;
					break;
				case 'value':
					$parsedParams[$field['field']] = $args[$field['field']];
					$data[$model][$field['field']] = $args[$field['field']];
					break;
			}

			if (isset($data[$model][$field['field']]) && $data[$model][$field['field']] !== '') {
				$this->isSearch = true;
			}

			if (isset($data[$model][$field['field']]) && $data[$model][$field['field']] === '' && isset($field['emptyValue'])) {
				$data[$model][$field['field']] = $field['emptyValue'];
			}
		}

		$this->controller->request->data = $data;
		$this->_parsedParams = $parsedParams;
		// deprecated, don't use controller's parsedData or passedArgs anymore.
		$this->controller->parsedData = $this->_parsedParams;
		foreach ($this->controller->parsedData as $key => $value) {
			$this->controller->passedArgs[$key] = $value;
		}
		$this->controller->set('isSearch', $this->isSearch);
	}

/**
 * Return the parsed params of the current search request
 *
 * @return array Params
 */
	public function parsedParams() {
		return $this->_parsedParams;
	}

/**
 * Restores form params for checkboxes and other url encoded params
 *
 * @param array
 * @return array
 */
	public function serializeParams(array &$data) {
		foreach ($this->controller->presetVars as $field) {
			if ($field['type'] === 'checkbox') {
				if (array_key_exists($field['field'], $data)) {
					$values = join('|', (array)$data[$field['field']]);
				} else {
					$values = '';
				}
				$data[$field['field']] = $values;
			}

			if ($this->_defaults['commonProcess']['paramType'] === 'named' && ($this->encode || !empty($field['encode']))) {
				$fieldContent = $data[$field['field']];
				$tmp = base64_encode($fieldContent);
				// replace chars base64 uses that would mess up the url
				$tmp = str_replace(array('/', '='), array('-', '_'), $tmp);
				$data[$field['field']] = $tmp;
			}
			if (!empty($field['empty']) && isset($data[$field['field']]) && $data[$field['field']] === '') {
				unset($data[$field['field']]);
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
	public function connectNamed($data = null, array $exclude = array()) {
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
	public function exclude(array $array, array $exclude) {
		$data = array();
		foreach ($array as $key => $value) {
			if (is_numeric($key) || !in_array($key, $exclude)) {
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
 *  - array excludedParams - An array of named/query params that should be excluded from the redirect url
 *  - string paramType - 'named' if you want to used named params or 'querystring' is you want to use query string
 * @return void
 */
	public function commonProcess($modelName = null, array $options = array()) {
		$defaults = array(
			'excludedParams' => array('page'),
		);
		$defaults = Hash::merge($defaults, $this->_defaults['commonProcess']);
		extract(Hash::merge($defaults, $options));

		$paramType = strtolower($paramType);

		if (empty($modelName)) {
			$modelName = $this->controller->modelClass;
		}

		if (empty($formName)) {
			$formName = $modelName;
		}

		if (empty($action)) {
			$action = $this->controller->action;
		}

		if (!empty($this->controller->request->data)) {
			$this->controller->{$modelName}->set($this->controller->request->data);
			$valid = true;
			if ($modelMethod !== false) {
				$valid = $this->controller->{$modelName}->{$modelMethod}();
			}

			if ($valid) {
				$params = $this->controller->request->params['named'];
				if ($keepPassed) {
					$params = array_merge($this->controller->request->params['pass'], $params);
				}

				$searchParams = $this->controller->request->data[$modelName];
				$this->serializeParams($searchParams);

				if ($paramType === 'named') {
					$params = array_merge($params, $searchParams);
					$params = $this->exclude($params, $excludedParams);
					if ($filterEmpty) {
						$params = Hash::filter($params);
					}

					$params = $this->_filter($params);

					$this->connectNamed($params, array());

				} else {
					$searchParams = array_merge($this->controller->request->query, $searchParams);
					$searchParams = $this->exclude($searchParams, $excludedParams);
					if ($filterEmpty) {
						$searchParams = Hash::filter($searchParams);
					}

					$searchParams = $this->_filter($searchParams);

					$this->connectNamed($searchParams, array());
					$params['?'] = $searchParams;
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
		} elseif (($paramType === 'named' && !empty($this->controller->passedArgs)) ||
				($paramType === 'querystring' && !empty($this->controller->request->query))
			) {
			$this->connectNamed($this->controller->passedArgs, array());
			$this->presetForm(array('model' => $formName, 'paramType' => $paramType));
		}
	}

/**
 * Filter params based on emptyValue.
 *
 * @param array $params Params
 * @return array Params
 */
	protected function _filter(array $params) {
		foreach ($this->controller->presetVars as $key => $presetVar) {
			$field = $key;
			if (!empty($presetVar['field'])) {
				$field = $presetVar['field'];
			}
			if (!isset($params[$field])) {
				continue;
			}
			if (!isset($presetVar['emptyValue']) || $presetVar['emptyValue'] !== $params[$field]) {
				continue;
			}
			$params[$field] = null;
		}
		return $params;
	}

/**
 * Parse the configs from the Model (to keep things dry)
 *
 * @param array $arg
 * @param mixed $key
 * @return array
 */
	protected function _parseFromModel(array $arg, $key = null) {
		if (isset($arg['preset']) && !$arg['preset']) {
			return array();
		}
		if (isset($arg['presetType'])) {
			$arg['type'] = $arg['presetType'];
			unset($arg['presetType']);
		} elseif (!isset($arg['type']) || in_array($arg['type'], array('expression', 'query', 'subquery', 'like', 'type', 'ilike'))) {
			$arg['type'] = 'value';
		}

		if (isset($arg['name']) || is_numeric($key)) {
			$field = $arg['name'];
		} else {
			$field = $key;
		}
		$res = array('field' => $field, 'type' => $arg['type']);
		if (!empty($arg['encode'])) {
			$res['encode'] = $arg['encode'];
		}
		$res = array_merge($arg, $res);
		return $res;
	}

}
