<?php

namespace account\controllers;

use Yii;
use account\models\LoginForm;
use luya\helpers\Url;

class DefaultController extends \account\base\Controller
{
    public function getRules()
    {
        return [
            [
                'allow' => true,
                'actions' => ['index'],
                'roles' => ['?', '@'],
            ], [
                'allow' => true,
                'actions' => ['lostpass'],
                'roles' => ['?'],
            ],
        ];
    }

    /**
     * @param $_GET['redirect'] should be urlencoded
     * @param $_POST['LoginForm'] data to login
     */
    public function actionIndex()
    {
        if (!$this->module->getUserIdentity()->isGuest) {
            return $this->redirect(Url::to('account/settings/index'));
        }

        $model = new LoginForm();
        // see if values are sent via post
        if (isset($_POST['LoginForm'])) {
            $model->attributes = $_POST['LoginForm'];
            if (($userObject = $model->login()) !== false) {
                if ($this->module->getUserIdentity()->login($userObject)) {
                    $redirect = Yii::$app->request->get('redirect', false);
                    if (!$redirect) {
                        $redirect = Url::to('account/settings/index');
                    }

                    return $this->redirect($redirect);
                }
            }
        }

        return $this->render('index', ['model' => $model]);
    }

    public function actionLostpass()
    {
        return 'lost password form';
    }
}
