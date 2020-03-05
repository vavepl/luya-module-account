<?php

use yii\db\Migration;

/**
 * Class m200222_194057_account_user_company
 */
class m200222_194057_account_user_company extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropColumn('{{%account_user}}', 'company');
        $this->createTable('{{%account_user_company}}', [
            'id' => $this->primaryKey(),
            'account_user_id' => $this->integer(),
            'name' => $this->string(255),
            'nip' => $this->string(20),
            'street' => $this->string(120),
            'zip' => $this->string(20),
            'city' => $this->string(80),
            'country' => $this->string(80),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%account_user_company}}');
        $this->addColumn('{{%account_user}}', 'company', $this->string(80));
    }
}
