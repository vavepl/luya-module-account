<?php

namespace luya\account\admin\apis;

use app\helpers\CorsCustom;
use luya\account\models\User;
use luya\admin\components\Auth;
use luya\helpers\ArrayHelper;
use luya\helpers\Json;
use luya\helpers\RestHelper;
use luya\helpers\Url;
use sizeg\jwt\JwtHttpBearerAuth;
use Yii;
use yii\filters\auth\CompositeAuth;
use yii\filters\Cors;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\Response;

class UserController extends \luya\admin\ngrest\base\Api
{
    public $modelClass = 'luya\account\models\User';

    /**
     * @var array Define methods which does not require authentification
     */
    public $authOptional = ['login', 'register', 'forgot-password'];

    /**
     * @return array
     */
    public function behaviors()
    {
	    $behaviors = parent::behaviors();

	    // remove authentication filter
	    $auth = $behaviors['authenticator'];
	    unset($behaviors['authenticator']);

	    // add CORS filter
	    $behaviors['corsFilter'] = [
		    'class' => CorsCustom::class,
		    'cors' => [
			    'Origin' => CorsCustom::allowedDomains(),
			    'Access-Control-Allow-Credentials' => true,
			    'Access-Control-Request-Method'    => ['POST', 'GET', 'PUT', 'OPTIONS'],
			    'Access-Control-Allow-Headers' => ['X-Requested-With','content-type', 'api-cart', 'Authorization'],
		    ],
	    ];

	    $behaviors['verbs'] = [
		    'class' => VerbFilter::class,
		    'actions' => [
			    'login' => ['POST'],
			    'register' => ['POST'],
			    'forgot-password' => ['POST'],
                'change-password' => ['POST'],
                'update-self' => ['PUT'],
                'update-self-company' => ['PUT'],
			    'me' => ['GET'],
		    ],
	    ];

	    // re-add authentication filter
	    $behaviors['authenticator'] = $auth;
	    // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
	    $behaviors['authenticator']['except'] = ['options'];

	    return $behaviors;
    }

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        parent::init();

	    $this->addActionPermission(Auth::CAN_VIEW,'me');
	    $this->addActionPermission(Auth::CAN_VIEW,'change-password');
        $this->addActionPermission(Auth::CAN_VIEW,'update-self');//todo change for only this user
        $this->addActionPermission(Auth::CAN_VIEW,'update-self-company');//todo change for only this user
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
            if ($user && $user->verifyPassword($model->password)) {
                if ($user->updateAttributes(['auth_token' => Yii::$app->jwt->generateToken($user)])) {
                    return $this->asJson([
                        'data' => ['token' => (string)$user->getJwtToken()],
                        'status' => 200
                    ]);
                }
            } else {
                $model->addError('email', 'Unable to find the given email or password is wrong.');
            }
        }

        return $this->asJson([
            'data' => RestHelper::sendModelError($model),
            'status' => 400
        ]);
    }

	/**
	 * Allow users to signup which will create a new user.
	 *
	 * > No authentification needed.
	 *
	 * @return Response
	 * @throws \luya\Exception
	 * @throws \yii\base\Exception
	 * @throws \Throwable
	 */
    public function actionRegister()
    {
	    /**
	     * @var User $model
	     */
        $model = new User();
        $model->scenario = User::SCENARIO_REGISTER;

        if ($model->load(Yii::$app->request->post(), '') && $model->save()) {
	        if ($this->module->registerConfirmEmail) {
		        // send mail
		        $hashKey = Yii::$app->security->generateRandomString();
		        $model->verification_hash = $hashKey;
		        $model->update(false);
		        $mail = $this->renderPartial('mail/_validationlink.php', [
			        'hashKey' => $hashKey,
			        'user' => $model,
			        'link' => Url::to(['/account/register/activate', 'hash' => $hashKey], true),
		        ]);
		        Yii::$app->mail->compose('Rejestracja', $mail)->address($model->email)->send();
	        } else {
		        $model->is_mail_verified = 1;
		        $model->is_active = 1;
		        $model->update(false);
	        }
            return $this->asJson([
                'data' => ["success"],
                'code' => 200
            ]);
        }

        return $this->asJson([
            'data' => RestHelper::sendModelError($model),
            'code' => 400
        ]);
    }

	public function actionForgotPassword()
	{
		$model = Yii::createObject(['class' => $this->module->forgotPasswordFormClass]);

		if (Yii::$app->request->post()) {
			$model->load(Yii::$app->request->post(), '');
			/** @var User $user */
			if (($user = $model->forgot()) !== false) {
				$user->password = Yii::$app->security->generateRandomString(6);
				$user->encodePassword();
				$user->update(false);
				$mail = $this->renderPartial('mail/_forgot.php', [
					'password' => $user->plainPassword,
					'user' => $user
				]);
				Yii::$app->mail->compose('Reset hasła', $mail)->address($model->email)->send();
				return $this->asJson([
					'data' => ["success"],
					'code' => 200
				]);
			}
		}

		return $this->asJson([
			'data' => RestHelper::sendModelError($model),
			'code' => 400
		]);
	}

	//todo change password

    public function actionChangePassword()
    {
        $model = Yii::createObject(['class' => $this->module->changePasswordFormClass]);

        if (Yii::$app->request->post()) {
            $model->load(Yii::$app->request->post(), '');
            /** @var User $user */
            $user = $model->changePassword();
            if ($user !== false) {
                $user->scenario = User::SCENARIO_CHANGE_PASSWORD;
                $user->password = $model->password;
                $user->encodePassword();
                $user->update(false);
                return $this->asJson([
                    'data' => ["success"],
                    'code' => 200
                ]);
            }
        }

        return $this->asJson([
            'data' => RestHelper::sendModelError($model),
            'code' => 400
        ]);
    }

    public function actionUpdateSelf()
    {
        $model = Yii::createObject(['class' => $this->module->updateUserFormClass]);

        if (Yii::$app->request->post()) {
            $model->load(Yii::$app->request->post(), '');
            if ($model->update()) {
                return $this->asJson([
                    'data' => ["success"],
                    'code' => 200
                ]);
            }
        }

        return $this->asJson([
            'data' => RestHelper::sendModelError($model),
            'code' => 400
        ]);
    }

    public function actionUpdateSelfCompany()
    {
        $model = Yii::createObject(['class' => $this->module->updateCompanyFormClass]);

        if (Yii::$app->request->post()) {
            $model->load(Yii::$app->request->post(), '');
            if ($model->update()) {
                return $this->asJson([
                    'data' => ["success"],
                    'code' => 200
                ]);
            }
        }

        return $this->asJson([
            'data' => RestHelper::sendModelError($model),
            'code' => 400
        ]);
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

	//todo order history
}
