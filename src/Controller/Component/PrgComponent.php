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
namespace Search\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\Controller;
use Cake\Event\Event;
use Cake\Routing\Router;
use Cake\Utility\Hash;

/**
 * Post-Redirect-Get: Transfers POST Requests to GET Requests
 *
 */
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
	public $actions = [];

/**
 * Enables encoding on all presetVar fields
 *
 * @var bool
 */
	public $encode = false;

/**
 * If the current request is an actual search (at least one search value present)
 *
 * @var bool
 */
	public $isSearch = false;

/**
 * @var \Cake\Controller\Controller
 */
	public $controller;

/**
 * Parsed params of current request
 *
 * @var array
 */
	protected $_parsedParams = [];

/**
 * Default options
 *
 * @var array
 */
	protected $_defaultConfig = [
		'commonProcess' => [
			'formName' => null,
			'keepPassed' => true,
			'action' => null,
			'tableMethod' => 'validateSearch',
			'allowedParams' => [],
			'filterEmpty' => false,
			'autoProcess' => false
		],
		'presetForm' => [
			'table' => null,
			'formName' => null,
		]
	];

/**
 * Called before the Controller::beforeFilter().
 *
 * @param Event $event Event object
 *
 * @return void
 */
	public function beforeFilter(Event $event) {
		$this->controller = $this->_registry->getController();

		// fix for not throwing warnings
		if (!isset($this->controller->presetVars)) {
			$this->controller->presetVars = true;
		}

		list(, $table) = pluginSplit($this->controller->modelClass);
		if (!empty($settings['table'])) {
			$table = $settings['table'];
		}

		if ($this->controller->presetVars === true) {
			// auto-set the presetVars based on search definitions in model
			$this->controller->presetVars = [];
			$filterArgs = [];
			if (!empty($this->controller->$table->filterArgs)) {
				$filterArgs = $this->controller->$table->filterArgs;
			}

			foreach ($filterArgs as $key => $arg) {
				if ($args = $this->_parseFromModel($arg, $key)) {
					$this->controller->presetVars[] = $args;
				}
			}
		}
		foreach ($this->controller->presetVars as $key => $field) {
			if ($field === true) {
				if (isset($this->controller->$table->filterArgs[$key])) {
					$field = $this->_parseFromModel($this->controller->$table->filterArgs[$key], $key);
				} else {
					$field = ['type' => 'value'];
				}
			}
			if (!isset($field['field'])) {
				$field['field'] = $key;
			}
			$this->controller->presetVars[$key] = $field;
		}
		$this->autoProcess();
	}

/**
 * Automatic commmon processing of the request.
 *
 * @return void
 */
	public function autoProcess() {
		$autoProcess = $this->_config['commonProcess']['autoProcess'];
		if ($autoProcess === false) {
			return;
		}
		if ($autoProcess === true) {
			$this->commonProcess();
		}
		if (is_array($autoProcess)) {
			$action = $this->controller->request->action;
			if (isset($autoProcess[$action])) {
				$this->commonProcess($autoProcess[$action][0], $autoProcess[$action][1]);
			}
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
 * @param array $options Preset form options
 *
 * @return void
 */
	public function presetForm($options) {
		if (!is_array($options)) {
			$options = ['table' => $options];
		}
		extract(Hash::merge($this->_config['presetForm'], $options));

		$args = $this->controller->request->query;

		$parsedParams = [];
		$data = [];
		foreach ($this->controller->presetVars as $field) {
			if (!isset($args[$field['field']])) {
				continue;
			}

			if ($field['type'] === 'lookup') {
				$searchModel = $field['table'];
				$this->controller->loadModel($searchModel);
				$result = $this->controller->{$searchModel}->findById($args[$field['field']])->first();
				$parsedParams[$field['field']] = $args[$field['field']];
				$parsedParams[$field['formField']] = $result->{$field['tableField']};
				$data[$field['field']] = $args[$field['field']];
				$data[$field['formField']] = $result->{$field['tableField']};

			} elseif ($field['type'] === 'checkbox') {
				$values = explode('|', $args[$field['field']]);
				$parsedParams[$field['field']] = $values;
				$data[$field['field']] = $values;

			} elseif ($field['type'] === 'value') {
				$parsedParams[$field['field']] = $args[$field['field']];
				$data[$field['field']] = $args[$field['field']];
			}

			if (isset($data[$field['field']]) && $data[$field['field']] !== '') {
				$this->isSearch = true;
			}

			if (isset($data[$field['field']]) && $data[$field['field']] === '' && isset($field['emptyValue'])) {
				$data[$field['field']] = $field['emptyValue'];
			}
		}

		if ($formName) {
			$this->controller->request->data[$formName] = $data;
		} else {
			$this->controller->request->data = $data;
		}

		$this->_parsedParams = $parsedParams;
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
 * @param array &$data Data we are serializing
 *
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

			if (!empty($field['empty']) && isset($data[$field['field']]) && $data[$field['field']] === '') {
				unset($data[$field['field']]);
			}
		}
		return $data;
	}

/**
 * Exclude
 *
 * Removes key/values from $array based on $exclude
 *
 * @param array $array Array of data to be filtered
 * @param array $exclude Array of keys to exclude from other $array
 *
 * @return array
 */
	public function exclude(array $array, array $exclude) {
		$data = [];
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
 * @param string $tableName - Name of the model class being used for the prg form
 * @param array $options Optional parameters:
 *  - string formName - name of the form involved in the prg
 *  - string action - The action to redirect to. Defaults to the current action
 *  - mixed tableMethod - If not false a string that is the table method that will be used to process the data
 *  - array allowedParams - An array of additional top level route params that should be included in the params processed
 *  - array excludedParams - An array of named/query params that should be excluded from the redirect url
 *  - string paramType - 'named' if you want to used named params or 'querystring' is you want to use query string
 *
 * @return void
 */
	public function commonProcess($tableName = null, array $options = []) {
		$defaults = [
			'excludedParams' => ['page'],
		];
		$defaults = Hash::merge($defaults, $this->_config['commonProcess']);
		extract(Hash::merge($defaults, $options));

		if (empty($tableName)) {
			list(, $tableName) = pluginSplit($this->controller->modelClass);
		}

		if (empty($action)) {
			$action = $this->controller->request->params['action'];
		}

		if (!empty($formName) && isset($this->controller->request->data[$formName])) {
			$searchParams = $this->controller->request->data[$formName];
		} elseif (isset($this->controller->request->data[$tableName])) {
			$searchParams = $this->controller->request->data[$tableName];
			if (empty($formName)) {
				$formName = $tableName;
			}
		} else {
			$searchParams = $this->controller->request->data;
		}

		if (!empty($searchParams)) {
			$valid = true;
			if ($tableMethod !== false) {
				$valid = $this->controller->{$tableName}->{$tableMethod}($searchParams);
			}

			if ($valid) {
				$params = $this->controller->request->query;
				if ($keepPassed) {
					$params = array_merge($this->controller->request->params['pass'], $params);
					$params = $this->exclude($params, $excludedParams);
				}

				$this->serializeParams($searchParams);

				$searchParams = array_merge($this->controller->request->query, $searchParams);
				$searchParams = $this->exclude($searchParams, $excludedParams);

				if ($filterEmpty) {
					$searchParams = Hash::filter($searchParams);
				}

				$searchParams = $this->_filter($searchParams);

				$params['?'] = $searchParams;

				$params['action'] = $action;

				foreach ($allowedParams as $key) {
					if (isset($this->controller->request->params[$key])) {
						$params[$key] = $this->controller->request->params[$key];
					}
				}

				$this->controller->redirect($params);
			} else {
				$this->controller->Flash->error(__d('search', 'Please correct the errors below.'));
			}
		} elseif (!empty($this->controller->request->query)) {
			$this->presetForm(['table' => $tableName, 'formName' => $formName]);
		}
	}

/**
 * Filter params based on emptyValue.
 *
 * @param array $params Params
 *
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
 * @param array $arg arguments
 * @param mixed $key Key to use
 *
 * @return array
 */
	protected function _parseFromModel(array $arg, $key = null) {
		if (isset($arg['preset']) && !$arg['preset']) {
			return [];
		}
		if (isset($arg['presetType'])) {
			$arg['type'] = $arg['presetType'];
			unset($arg['presetType']);
		} elseif (!isset($arg['type']) || in_array($arg['type'], ['finder', 'like', 'type'])) {
			$arg['type'] = 'value';
		}

		if (isset($arg['name']) || is_numeric($key)) {
			$field = $arg['name'];
		} else {
			$field = $key;
		}
		$res = ['field' => $field, 'type' => $arg['type']];
		if (!empty($arg['encode'])) {
			$res['encode'] = $arg['encode'];
		}
		$res = array_merge($arg, $res);
		return $res;
	}

}
