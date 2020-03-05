<?php

namespace luya\account\admin\apis;

use luya\account\models\User;
use luya\helpers\RestHelper;
use sizeg\jwt\JwtHttpBearerAuth;
use Yii;
use yii\filters\auth\CompositeAuth;
use yii\web\BadRequestHttpException;

class RestUserController extends \luya\admin\ngrest\base\Api implements \luya\rest\UserBehaviorInterface
{
    public $modelClass = 'luya\account\models\User';

    /**
     * @var array Define methods which does not require authentification
     */
    public $authOptional = ['login', 'register'];

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => CompositeAuth::className(),
            'authMethods' => [
                JwtHttpBearerAuth::class,
            ],
        ];
        return $behaviors;
    }

    /**
     * Returns the class object for the authentication of the rest api. If the return value is false the authentication is disabled for the whole rest controller.
     *
     * return a user object (based on {{yii\web\User}}):
     *
     * ```php
     * return Yii::$app->adminuser;
     * ```
     *
     * return a class string will create a new object from this class string:
     *
     * ```php
     * return \luya\admin\components\AdminUser::class;
     * ```
     *
     * return false will disabled the authentication proccess for this rest controller:
     *
     * ```php
     * return false;
     * ```
     *
     * It can also be an array with configurations:
     *
     * ```php
     * return [
     *     'class' => 'app\models\User',
     *     'property1' => 'value',
     * ];
     * ```
     *
     * @return boolean|string|\yii\web\User If `false` is returned the protection is disabled, if a string is provided this will be threated as className to create the User object.
     */
    public function userAuthClass()
    {
        return User::class;

    }

    /**
     * Make user login and return the user with the fresh generated jwt token which is stored in the user.
     *
     * > No authentification needed.
     */
    public function actionLogin()
    {
        $model = new User();
        $model->scenario = User::SCENARIO_LOGIN;
        if ($model->load(Yii::$app->request->post(), '') && $model->validate()) {
            /** @var User $user */
            $user = User::find()->where(['email' => $model->email])->one();
            if ($user && Yii::$app->security->validatePassword($model->password, $user->password)) {
                if ($user->updateAttributes(['auth_token' => Yii::$app->jwt->generateToken($user)])) {
                    return $this->asJson([
                        'data' => ['token' => (string)$user->getJwtToken()],
                        'code' => 200
                    ]);
                }
            } else {
                $model->addError('email', 'Unable to find the given email or password is wrong.');
            }
        }

        return $this->asJson([
            'data' => RestHelper::sendModelError($model),
            'code' => (new BadRequestHttpException)->getCode()
        ]);
    }

    /**
     * Allow users to signup which will create a new user.
     *
     * > No authentification needed.
     *
     * @return User
     */
    public function actionRegister()
    {
        $model = new User();
        $model->scenario = User::SCENARIO_REGISTER;
        if ($model->load(Yii::$app->request->post(), '') && $model->save()) {
            return $model;
        }

        return $this->sendModelError($model);
    }

    /**
     * Returns the currently logged in jwt authenticated user.
     *
     * > This method requires authentification.
     *
     * @return User
     */
    public function actionMe()
    {
        return Yii::$app->jwt->identity;
    }


}
