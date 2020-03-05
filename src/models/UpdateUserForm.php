<?php

namespace luya\account\models;

use Yii;

class UpdateUserForm extends \yii\base\Model
{
    private $_model;

    protected $modelClass = 'luya\account\models\User';

    protected $modelScenario = 'update';

    public $firstname;

    public $lastname;

    public $gender;

    public $telephone;

    public $street;

    public $zip;

    public $city;

    public $country;

    public $subscription_newsletter = 0;

    public $subscription_medianews = 0;

    public function rules()
    {
        return [
            [['firstname', 'lastname', 'gender', 'street', 'telephone', 'zip', 'city', 'country'], 'required'],
            [['subscription_medianews', 'subscription_newsletter'], 'safe'],
        ];
    }

    public function getModel()
    {
        if ($this->_model === null) {
            $this->_model = Yii::$app->jwt->identity;
        }

        return $this->_model;
    }

    public function update()
    {
        if ($this->validate()) {
            $model = $this->getModel();
            $model->scenario = $this->modelScenario;
            $model->attributes  = $this->attributes;
            //$model->setAttributes($this->getAttributes());
            if ($model->validate()) {
                if ($model->save()) {
                    return $model;
                }
            } else {
                $this->addErrors($model->getErrors());
            }
        }

        return false;
    }
}
