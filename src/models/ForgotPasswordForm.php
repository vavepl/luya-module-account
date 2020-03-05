<?php

namespace luya\account\models;

class ForgotPasswordForm extends \yii\base\Model
{
	private $_user = false;

	public $email;

	public function rules()
	{
		return [
			[['email'], 'required'],
			['email', 'validateEmail'],
		];
	}

	public function validateEmail($attribute)
	{
		if (!$this->hasErrors()) {
			$user = $this->getUser();
			if (!$user) {
				$this->addError($attribute, \Yii::t('accountadmin', 'Incorrect username'));
			}
		}
	}

	public function forgot()
	{
		if ($this->validate()) {
			$user = $this->getUser();

			return $user;
		} else {
			return false;
		}
	}

	public function getUser()
	{
		if ($this->_user === false) {
			$this->_user = User::findByEmail($this->email);
		}

		return $this->_user;
	}
}
