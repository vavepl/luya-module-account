<?php

namespace luya\account\frontend;

use Yii;

final class Module extends \luya\base\Module
{
    public $urlRules = [
        //['pattern' => 'account/login', 'route' => 'account/default/index'],
        ['pattern' => 'account/registration', 'route' => 'account/register/index'],
	    //['pattern' => 'account/my-profil', 'route' => 'account/settings/index'],
        /*['pattern' => 'account/lost-password', 'route' => 'account/default/lostpass', 'composition' => [
            'de' => 'account/passwort-vergessen',
            'en' => 'account/lost-password',
        ]],*/
        /*['pattern' => 'account/login', 'route' => 'account/rest-user/login'],
        ['pattern' => 'account/me', 'route' => 'account/rest-user/me'],*/

    ];

	public $userIdentity = '\luya\account\frontend\components\User';

    /**
     * @var string defined your custom RegisterForm validation model must impelement `account\RegisterInterface`.
     */
	public $registerFormClass = 'luya\account\models\RegisterForm';

	public $forgotPasswordFormClass = 'luya\account\models\ForgotPasswordForm';

    /**
     * @var boolean Whether the email must be confirmet on registration with an activation link (double opt-in) or not.
     */
    public $registerConfirmEmail = false;
    
    /**
     * @var boolean Whether each registration must be activated/validated by the page administrator in the administration area or not.
     */
    public $validateRegistration = false;
    
    public function getUserIdentity()
    {
        return Yii::createObject(['class' => $this->userIdentity]);
    }
}
