<?php

namespace luya\account\models;

class ChangePasswordForm extends \yii\base\Model
{
	private $_user = false;

	public $email;
	public $oldPassword;
	public $password;
	public $passwordConfirmation;

	public function rules()
	{
		return [
			[['email', 'oldPassword', 'password', 'passwordConfirmation'], 'required'],
			['password', 'validatePassword'],
		];
	}

	public function validatePassword($attribute, $params)
	{
		if (strlen($this->password) < 6) {
			$this->addError($attribute, \Yii::t('accountadmin', 'The password must be min. 6 characters'));
		}
		if ($this->password !== $this->passwordConfirmation) {
			$this->addError($attribute, \Yii::t('accountadmin', 'The password must match the password repetition'));
		}
	}

	public function changePassword()
	{
		$user = $this->getUser();
		if ($user && $this->validate()) {
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

		if($this->_user && $this->_user->verifyPassword($this->oldPassword)){
			return $this->_user;
		} else {
			$this->addError('oldPassword', \Yii::t('accountadmin', 'Current password is incorrect'));
			return false;
		}

	}
}
