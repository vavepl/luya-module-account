<?php

use yii\db\Migration;

/**
 * Class m200222_193914_account_user_add_telephone
 */
class m200222_193914_account_user_add_telephone extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%account_user}}', 'telephone', $this->string(20));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%account_user}}', 'telephone');
    }

}
