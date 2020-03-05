<?php

namespace luya\account\admin\apis;

/**
 * User Company Controller.
 * 
 * File has been created with `crud/create` command. 
 */
class UserCompanyController extends \luya\admin\ngrest\base\Api
{
    /**
     * @var string The path to the model which is the provider for the rules and fields.
     */
    public $modelClass = 'luya\account\models\UserCompany';
}