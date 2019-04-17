<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%post}}`.
 */
class m190417_070938_create_auth_log_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%auth_log}}', [
            'id' => $this->primaryKey(),
            'ip' => $this->string(),
            'user_agent' => $this->string(),
            'attempts' => $this->smallInteger(),
            'block' => $this->boolean(),
            'unblocking_time' => $this->integer(),
            'blocking_quantity' => $this->integer(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%auth_log}}');
    }
}
