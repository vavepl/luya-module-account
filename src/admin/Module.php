<?php

namespace luya\account\admin;

use luya\admin\components\AdminMenuBuilder;

final class Module extends \luya\admin\base\Module
{
    public $apis = [
        'api-account-user' => 'luya\account\admin\apis\UserController',
        'api-account-usercompany' => 'luya\account\admin\apis\UserCompanyController',
    ];

    public function getMenu()
    {
        return (new AdminMenuBuilder($this))
        ->node('Accounts', 'supervisor_account')
            ->group('Preview')
            ->itemApi('Users', 'accountadmin/user/index', 'account_circle', 'api-account-user')
            ->itemApi('Users Company', 'accountadmin/user-company/index', 'account_circle', 'api-account-usercompany', ['hiddenInMenu' => true]);
    }

    public $apiRules = [
        'api-account-user' => [
            'extraPatterns' => [
                'POST register' => 'register',
                'POST login' => 'login',
                'PUT update-self' => 'update-self',
            ],
        ],
    ];

    public function extendPermissionApis()
    {
        return [
            ['api' => 'api-account-usercompany', 'alias' => 'User Company'],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public static function onLoad()
    {
        self::registerTranslation('accountadmin*', static::staticBasePath() . '/messages', [
            'accountadmin' => 'accountadmin.php',
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public static function t($message, array $params = [])
    {
        return parent::baseT('accountadmin', $message, $params);
    }

	public $registerConfirmEmail = false;

	public $validateRegistration = false;

	public $forgotPasswordFormClass = 'luya\account\models\ForgotPasswordForm';

    public $changePasswordFormClass = 'luya\account\models\ChangePasswordForm';

    public $updateUserFormClass = 'luya\account\models\UpdateUserForm';

    public $updateCompanyFormClass = 'luya\account\models\UpdateCompanyForm';

}
