<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%payments_systems_statuses}}`.
 */
class m190211_145058_create_payments_systems_statuses_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%payments_systems_statuses}}', [
            'id' => $this->primaryKey(),
            'payment_system_id' => $this->integer(),
            'name' => $this->string(),
            'active' => $this->integer(),
            'deleted' => $this->boolean()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%payments_systems_statuses}}');
    }
}
