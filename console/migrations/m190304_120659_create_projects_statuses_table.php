<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%projects_statuses}}`.
 */
class m190304_120659_create_projects_statuses_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%projects_statuses}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(),
            'domain' => $this->string(),
            'active' => $this->integer(),
            'node_id' => $this->integer(),
            'deleted' => $this->boolean(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%projects_statuses}}');
    }
}
