<?php

namespace luya\account\frontend\components;

class User extends \yii\web\User
{
    public $identityClass = '\luya\account\models\User';

    public $loginUrl = ['/'];

    public $identityCookie = ['name' => '_accountIdentity', 'httpOnly' => true];

    public $enableAutoLogin = false;
}
