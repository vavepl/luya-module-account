<?php

namespace luya\account\models;

use Yii;
use luya\admin\ngrest\base\NgRestModel;

/**
 * User Company.
 * 
 * File has been created with `crud/create` command. 
 *
 * @property integer $id
 * @property integer $account_user_id
 * @property string $name
 * @property string $nip
 * @property string $street
 * @property string $zip
 * @property string $city
 * @property string $country
 */
class UserCompany extends NgRestModel
{
    const SCENARIO_UPDATE = "update";

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%account_user_company}}';
    }

    /**
     * @inheritdoc
     */
    public static function ngRestApiEndpoint()
    {
        return 'api-account-usercompany';
    }

    public function fields()
    {
        $fields = parent::fields();
        // remove fields that contain sensitive information
        unset($fields['account_user_id']);
        return $fields;
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();

        $scenarios[self::SCENARIO_UPDATE] = ['name', 'nip', 'street', 'zip', 'city', 'country'];

        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['account_user_id'], 'integer'],
            [['account_user_id'], 'unique'],
            [['name'], 'string', 'max' => 255],
            [['nip', 'zip'], 'string', 'max' => 20],
            [['street'], 'string', 'max' => 120],
            [['city', 'country'], 'string', 'max' => 80],
        ];
    }

    public function ngRestConfig($config)
    {
        $config->list->field('name', \Yii::t('accountadmin', 'Name'))->text();
        $config->list->field('nip', \Yii::t('accountadmin', 'Nip'))->text();
        $config->list->field('street', \Yii::t('accountadmin', 'Street'))->text();
        $config->list->field('zip', \Yii::t('accountadmin', 'Zip'))->text();

        $config->update->copyFrom('list');
        $config->update->field('city', \Yii::t('accountadmin', 'City'))->text();
        $config->update->field('country', \Yii::t('accountadmin', 'Country'))->text();
        return $config;
    }

    public function ngRestRelations()
    {
        return [
            ['label' => 'User', 'targetModel' => User::class, 'dataProvider' => $this->getUser()],
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'account_user_id']);
    }
}
