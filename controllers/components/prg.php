<?php
/**
 * CakePHP Tags Plugin
 *
 * Copyright 2009 - 2010, Cake Development Corporation
 *                        1785 E. Sahara Avenue, Suite 490-423
 *                        Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright 2009 - 2010, Cake Development Corporation (http://cakedc.com)
 * @link      http://github.com/CakeDC/Search
 * @package   plugins.search
 * @license   MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Post-Redirect-Get: Transfers POST Requests to GET Requests
 *
 * @package		plugins.search
 * @subpackage	plugins.search.controllers.components
 */
class PrgComponent extends Object {
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
 * @access public
 */
	public $actions = array();

/**
 * Intialize Callback
 *
 * @param object Controller object
 * @access public
 */
	public function initialize(&$controller) {
		$this->controller = $controller;
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
 * @param array
 * @access public
 */
	public function presetForm($model) {
		$data = array($model => array());
		$args = $this->controller->passedArgs;

		foreach ($this->controller->presetVars as $field) {
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
 * @access public
 */
	public function serializeParams(&$data) {
		foreach ($this->controller->presetVars as $field) {
			if ($field['type'] == 'checkbox') {
				if (is_array($data[$field['field']])) {
					$values = join('|', $data[$field['field']]);
				} else {
					$values = '';
				}
				$data[$field['field']] = $values;
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
 * @access public
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

 * @param array Array of data to be filtered
 * @param array Array of keys to exclude from other $array
 * @return array
 * @access public
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
 * @param string $modelName Name of the model class being used for the prg form
 * @param array $options Optional parameters:
 *  - string form Name of the form involved in the prg
 *  - string action The action to redirect to. Defaults to the current action
 *  - mixed modelMethod If not false a string that is the model method that will be used to process the data 
 * @return void
 * @access public
 */
	public function commonProcess($modelName = null, $options = array()) {
		$defaults = array(
			'form' => null,
			'keepPassed' => true,
			'action' => null,
			'modelMethod' => 'validateSearch');
		extract(Set::merge($defaults, $options));

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
				$passed = $this->controller->params['pass'];
				$params = $this->controller->data[$modelName];
				$params = $this->exclude($params, array());
				if ($keepPassed) {
					$params = array_merge($passed, $params);
				}
				$this->serializeParams($params);
				$this->connectNamed($params, array());
				$params['action'] = $action;
				$params = array_merge($this->controller->params['named'], $params);
				$this->controller->redirect($params);
			} else {
				$this->controller->Session->setFlash(__('Please correct the errors below.', true));
			}
		}

		if (empty($this->controller->data) && !empty($this->controller->passedArgs)) {
			$this->connectNamed($this->controller->passedArgs, array());
			$this->presetForm($formName);
		}
	}

}
?>