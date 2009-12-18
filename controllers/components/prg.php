<?php
/* SVN FILE: $Id: prg.php 1358 2009-10-15 10:49:11Z skie $ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 *
 * Copyright 2007-2008, Cake Development Corporation
 * 							1785 E. Sahara Avenue, Suite 490-423
 * 							Las Vegas, Nevada 89104
 *
 * You may obtain a copy of the License at:
 * License page: http://projects.cakedc.com/licenses/TBD  TBD
 * Copyright page: 
 *
 * @filesource
 * @copyright		Copyright 2007-2008, Cake Development Corporation
 * @link			
 * @package			
 * @subpackage		
 * @since			
 * @version			$Revision: 1358 $
 * @modifiedby		$LastChangedBy: skie $
 * @lastmodified	$Date: 2009-10-15 21:49:11 +1100 (Thu, 15 Oct 2009) $
 * @license			http://projects.cakedc.com/licenses/TBD  TBD
 */
/**
 * Maps POST Requests to GET Requests
 *
 * @package		converge.views
 * @subpackage	converge.views.helpers
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
 * Poplulates controller->data with allowed values from the named/passed params.
 * Fields in $controller::$presetVars that have a type of 'lookup' the foreignKey value will be inserted.
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
					$user = $this->controller->{$searchModel}->findById($args[$field['field']]);
					$data[$model][$field['field']] = $args[$field['field']];
					$data[$model][$field['formField']] = $user[$searchModel][$field['modelField']];
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
 * Restore form params for ckeckboxs and other url encoded params
 * 
 * @param array
 * @access public
 */
	public function fixFormValues(&$data) {
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
 *
 * @todo more detailed description
 * @param array $array Array of data to be filtered
 * @param array $exclude Array of keys to exclude from other $array
 * @return array
 * @access public
 */
	public function exclude($array, $exclude) {
		$data = array();
		foreach ($array as $key => $value) {
			if (!is_numeric($key) && !in_array($key,$exclude)) {
				$data[$key] = $value;
			}
		}
		return $data;
	}
/**
 * Common search method. Handles processes common to all PRG forms.
 *  
 * - Handles validation of post data.
 * - converting post data into named params
 * - Issuing redirect(), and connecting named parameters before redirect
 * - Setting named parameter form data to view
 *  
 *
 * @param string $modelName Name of the model class being used for the prg form
 * @param string $formName Name of the form involved in the prg
 * @param string $action The action to redirect to. Defaults to the current action
 * @param boolean $validate Whether or not the form data should be validated.
 * @return void
 * @access public
 */
	public function commonProcess($modelName = null, $formName = null, $action = null, $validate = true) {
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
			if ($validate) {
				$valid = $this->controller->{$modelName}->validateSearch();
			}
			if ($valid) {
				$params = $this->controller->data[$modelName];
				$params = $this->exclude($params, array());
				$this->fixFormValues($params);
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