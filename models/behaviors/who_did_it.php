<?php

/**
 * WhoDidIt Model Behavior for CakePHP
 *
 * Handles created_by, modified_by fields for a given Model, if they exist in the Model DB table.
 * It's similar to the created, modified automagic, but it stores the logged User id
 * in the models that actsAs = array('WhoDidIt')
 *
 * This is useful to track who created records, and the last user that has changed them
 *
 * 2010-09-03: Modified to allow specifying models for auto-bind associations as suggested in branch
 * @see http://github.com/danfreak/4cakephp/issues#issue/1
 *
 * 2010-09-12: Added bindWhoDidIt and unbindWhoDidIt methods to enable on-the-fly binding of Creator and Modifier models.
 * @author vincentm8
 *
 * Based on:
 * @package behaviors
 * @author Daniel Vecchiato
 * @version 1.2
 * @date 01/03/2009
 * @copyright http://www.4webby.com
 * @licence MIT
 * @repository  https://github.com/danfreak/4cakephp/tree
 */
class WhoDidItBehavior extends ModelBehavior {

	/**
	 * Default settings for a model that has this behavior attached.
	 *
	 * @var array
	 * @access protected
	 */
	protected $_defaults = array(
		'auth_session' => 'Auth', //name of Auth session key
		'user_model' => 'User', //name of User model
		'created_by_field' => 'created_by', //the name of the "created_by" field in DB (default 'created_by')
		'modified_by_field' => 'modified_by', //the name of the "modified_by" field in DB (default 'modified_by')
		'auto_bind' => true, //automatically bind the model to the User model (default true)
		'auto_bind_models' => array(
			'CreatorModel' => 'CreatedBy',
			'ModifierModel' => 'ModifiedBy'
		)
	);

	/**
	 * Initiate WhoMadeIt Behavior
	 *
	 * @param object $model
	 * @param array $config  behavior settings you would like to override
	 * @return void
	 * @access public
	 */
	function setup(&$model, $config = array()) {
		//assigne default settings
		$this->settings[$model->alias] = $this->_defaults;

		//merge custom config with default settings
		$this->settings[$model->alias] = array_merge($this->settings[$model->alias], (array) $config);

		//configure model binding
		$this->settings[$model->alias]['has_created_by'] = $model->hasField($this->settings[$model->alias]['created_by_field']);
		$this->settings[$model->alias]['has_modified_by'] = $model->hasField($this->settings[$model->alias]['modified_by_field']);

		$this->bindWhoDidIt($model);
	}

	/**
	 * Before save callback
	 *
	 * @param object $model Model using this behavior
	 * @return boolean True if the operation should continue, false if it should abort
	 * @access public
	 */
	function beforeSave(&$model) {
		if ($this->settings[$model->alias]['has_created_by'] || $this->settings[$model->alias]['has_modified_by']) {
			$AuthSession = $this->settings[$model->alias]['auth_session'];
			$UserSession = $this->settings[$model->alias]['user_model'];
			$userId = Set::extract($_SESSION, $AuthSession . '.' . $UserSession . '.' . 'id');
			if ($userId) {
				$data = array($this->settings[$model->alias]['modified_by_field'] => $userId);
				if (!$model->exists()) {
					$data[$this->settings[$model->alias]['created_by_field']] = $userId;
				}
				$model->set($data);
			}
		}
		return true;
	}

	/**
	 * Handles model binding to the defined User model according to the auto_bind settings
	 */
	function bindWhoDidIt(&$model, $bindNow = false) {
		if (empty($this->settings[$model->alias])) {
			return;
		}
		if ($this->settings[$model->alias]['auto_bind'] || $bindNow) {
			if ($this->settings[$model->alias]['has_created_by']) {
				$commonBelongsTo = array(
					// $this->settings[$model->alias]['auto_bind_models']['CreatorModel'] => array(
					'Creator' => array(
						'className' => $this->settings[$model->alias]['user_model'],
						'foreignKey' => $this->settings[$model->alias]['created_by_field']
					)
				);
				$model->bindModel(array('belongsTo' => $commonBelongsTo), false);
			}

			if ($this->settings[$model->alias]['has_modified_by']) {
				$commonBelongsTo = array(
					// $this->settings[$model->alias]['auto_bind_models']['ModifierModel'] => array(
					'Modifier' => array(
						'className' => $this->settings[$model->alias]['user_model'],
						'foreignKey' => $this->settings[$model->alias]['modified_by_field']
					)
				);
				$model->bindModel(array('belongsTo' => $commonBelongsTo), false);
			}
		}
	}

	/**
	 * Unbind associated Creator and Modifier models
	 */
	function unbindWhoDidIt(&$model) {
		$model->unbindModel('Creator');
		$model->unbindModel('Modifier');
	}

}

?>