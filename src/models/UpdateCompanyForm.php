<?php

namespace luya\account\models;

use Yii;

class UpdateCompanyForm extends \yii\base\Model
{
    private $_model;

    protected $modelClass = 'luya\account\models\UserCompany';

    protected $modelScenario = 'update';

    public $name;

    public $nip;

    public $street;

    public $zip;

    public $city;

    public $country;

    public function rules()
    {
        return [
            [['name', 'nip', 'street', 'zip', 'city', 'country'], 'string'],
        ];
    }

    public function getModel()
    {
        if ($this->_model === null) {
            $this->_model = Yii::$app->jwt->identity->getCompany()->one();
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
