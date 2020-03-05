<?php

namespace luya\account\models;

use Lcobucci\JWT\Token;
use luya\admin\base\JwtIdentityInterface;
use luya\helpers\ArrayHelper;
use Yii;
use luya\admin\ngrest\base\NgRestModel;
use yii\web\IdentityInterface;

class User extends NgRestModel implements JwtIdentityInterface, IdentityInterface
{
    public $password_confirm;

    public $plainPassword;

	const SCENARIO_REGISTER = "register";
	const SCENARIO_CHANGE_PASSWORD = "changePassword";
    const SCENARIO_LOGIN = "login";
    const SCENARIO_UPDATE = "update";

    public function init()
    {
        parent::init();
        $this->on(self::EVENT_BEFORE_INSERT, [$this, 'encodePassword']);
        $this->on(self::EVENT_AFTER_INSERT, [$this, 'createCompany']);
    }
    
    public static function tableName()
    {
        return 'account_user';
    }

    public static function findByEmail($email)
    {
        return static::findOne(['email' => $email]);
    }

    public function fields()
    {
	    $fields = parent::fields();
        $fields['company'] = 'company';
        // remove fields that contain sensitive information
	    unset($fields['password'], $fields['password_confirm'], $fields['password_salt'], $fields['password_hash'], $fields['auth_token'], $fields['verification_hash']);
	    return $fields;
    }


    public function rules()
    {
        return [
            [['email', 'password'], 'required', 'on' => 'login'],
            [['email', 'password', 'password_confirm'], 'required', 'on' => 'register'],
            [['email'], 'email', 'on' => 'register'],
            [['email'], 'trim', 'on' => 'register'],
            [['email'], 'validateUserExists', 'on' => 'register'],
            [['password'], 'validatePassword', 'on' => 'register'],
        ];
    }

    public function scenarios()
    {
	    $scenarios = parent::scenarios();

	    $scenarios[self::SCENARIO_REGISTER] = ['firstname', 'lastname', 'email', 'telephone', 'password', 'password_confirm', 'gender', 'street', 'zip', 'city', 'country', 'subscription_newsletter', 'subscription_medianews'];
	    $scenarios[self::SCENARIO_LOGIN] = ['email', 'password'];
	    $scenarios[self::SCENARIO_UPDATE] = ['firstname', 'lastname', 'telephone', 'street', 'zip', 'city', 'country', 'subscription_newsletter', 'subscription_medianews'];
	    $scenarios[self::SCENARIO_CHANGE_PASSWORD] = ['password', 'password_confirm'];

	    $scenarios[self::SCENARIO_RESTCREATE] = $scenarios[self::SCENARIO_REGISTER];
	    $scenarios[self::SCENARIO_RESTUPDATE] = $scenarios[self::SCENARIO_UPDATE];

	    return $scenarios;
    }

    public function validateUserExists($attribute, $params)
    {
        $exists = self::findByEmail($this->email);
        if (!empty($exists)) {
            $this->addError($attribute, \Yii::t('accountadmin', 'This user already exists'));
        }
    }

    public function validatePassword($attribute, $params)
    {
        if (strlen($this->password) < 6) {
            $this->addError($attribute, \Yii::t('accountadmin', 'The password must be min. 6 characters'));
        }
        if ($this->password !== $this->password_confirm) {
            $this->addError($attribute, \Yii::t('accountadmin', 'The password must match the password repetition'));
        }
    }

    public function verifyPassword($password)
    {
        return Yii::$app->security->validatePassword($password.$this->password_salt, $this->password);
    }

    public function encodePassword()
    {
        $this->plainPassword = $this->password;

        // create random string for password salting
        $this->password_salt = Yii::$app->getSecurity()->generateRandomString();
        // store the password
        $this->password = Yii::$app->getSecurity()->generatePasswordHash($this->password.$this->password_salt);
        $this->password_confirm = $this->password;
    }

    public function createCompany()
    {
        $company = new UserCompany();
        $company->setAttribute('account_user_id', $this->getId());

        $company->save(false);
    }

    /* NgRest */
    
    public static function ngRestApiEndpoint()
    {
        return 'api-account-user';
    }
    
    public function ngRestConfig($config)
    {
        $config->list->field("firstname", \Yii::t('accountadmin', 'First name'))->text();
        $config->list->field("lastname", \Yii::t('accountadmin', 'Last name'))->text();
        $config->list->field("email", \Yii::t('accountadmin', 'E-mail'))->text();
        $config->list->field("telephone", \Yii::t('accountadmin', 'Telephone'))->text();
        $config->list->field("subscription_newsletter", \Yii::t('accountadmin', 'Newsletter'))->toggleStatus();
        $config->list->field("subscription_medianews", \Yii::t('accountadmin', 'News'))->toggleStatus();
        $config->list->field("is_mail_verified", \Yii::t('accountadmin', 'Email verified'))->toggleStatus();
        $config->list->field("is_active", \Yii::t('accountadmin', 'Enabled'))->toggleStatus();

        $config->update->copyFrom('list');
        $config->update->field('street', \Yii::t('accountadmin', 'Street'))->text();
        $config->update->field('zip', \Yii::t('accountadmin', 'Zip'))->text();
        $config->update->field('city', \Yii::t('accountadmin', 'City'))->text();
        $config->update->field('country', \Yii::t('accountadmin', 'Country'))->text();
        return $config;
    }

    /**
     * @return int|string current user ID
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string current user auth key
     */
    public function getAuthKey()
    {
        return false;
    }

    /**
     * @return string current user auth key
     */
    public function getJwtToken()
    {
        return $this->auth_token;
    }


	public static function findIdentityByVerificationHash($verificationHash)
	{
		return self::findOne(['verification_hash' => $verificationHash]);
	}

    /**
     * @param string $authKey
     *
     * @return bool if auth key is valid for current user
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    public function ngRestRelations()
    {
        return [
            ['label' => 'Company', 'targetModel' => UserCompany::class, 'dataProvider' => $this->getCompany()],
        ];
    }

    public function getCompany()
    {
        return $this->hasOne(UserCompany::class, ['account_user_id' => 'id']);
    }

    /* JwtIdentityInterface */

    /**
     * Ensure the user login by token.
     *
     * The user id to get the given user is commonly stored as `uid` claim. Therefore
     * in order to get the user id use getClaim:
     *
     * ```php
     * public staitc function loginByJwtToken(Token $token)
     * {
     *     // get the user id
     *     $userId = $token->getClaim('uid');
     *
     *     return User::findOne($userId);
     * }
     * ```
     *
     * Depending on your setup you also might to store the jwt token while authentication. Then you can
     * retrieve the jwt token by calling `__toString()` method.
     *
     * ```php
     * public staitc function loginByJwtToken(Token $token)
     * {
     *     // get the user id
     *     $userId = $token->getClaim('uid');
     *     // get the jwt token
     *     $jwtToken = $token->__toString();
     *
     *     return User::findOne(['id' => $userId, 'jwt_access_token' => $jwtToken]);
     * }
     * ```
     *
     * Return false if no user is found or login is incorrect.
     *
     * @see Discussion regarding storing the jwt token: https://stackoverflow.com/a/42765870/4611030
     * @param Token $token
     * @return self|boolean Return the user object which implements JwtIdentityInterface or false if not found and login is invalid.
     */
    public static function loginByJwtToken(Token $token)
    {
        $userId = $token->getClaim('uid');
        $jwtToken = $token->__toString();

        return self::findOne(['id' => $userId, 'auth_token' => $jwtToken]);
    }

    /**
     * Finds an identity by the given ID.
     * @param string|int $id the ID to be looked for
     * @return IdentityInterface|null the identity object that matches the given ID.
     * Null should be returned if such an identity cannot be found
     * or the identity is not in an active state (disabled, deleted, etc.)
     */
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    /**
     * Finds an identity by the given token.
     * @param mixed $token the token to be looked for
     * @param mixed $type the type of the token. The value of this parameter depends on the implementation.
     * For example, [[\yii\filters\auth\HttpBearerAuth]] will set this parameter to be `yii\filters\auth\HttpBearerAuth`.
     * @return IdentityInterface|null the identity object that matches the given token.
     * Null should be returned if such an identity cannot be found
     * or the identity is not in an active state (disabled, deleted, etc.)
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return self::loginByJwtToken($token);
    }
}
