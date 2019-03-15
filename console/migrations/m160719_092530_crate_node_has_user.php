<?php

use yii\db\Migration;

class m160719_092530_crate_node_has_user extends Migration
{
    public function safeUp()
    {
        $this->createTable('node_has_user', array(
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'node_id' => $this->integer(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull()
        ));

        $this->createIndex(
            'idx-node_has_user-user_id',
            'node_has_user',
            'user_id'
        );
        $this->addForeignKey(
            'fk-node_has_user-user_id',
            'node_has_user',
            'user_id',
            'user',
            'id',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropIndex(
            'idx-node_has_user-user_id',
            'node_has_user'
        );
        $this->dropForeignKey(
            'fk-node_has_user-user_id',
            'node_has_user'
        );
        $this->dropTable('node_has_user');
    }
}
