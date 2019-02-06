<?php

use yii\db\Migration;

/**
 * Handles the creation of table `card_token`.
 */
class m180403_110810_create_card_token_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->createTable('card_token', [
            'id' => $this->primaryKey(),
            'brand_id' => $this->integer(10)->notNull(),
            'buyer_id' => $this->string(32)->notNull(),
            'number' => $this->string(20)->notNull(),
            'token' => $this->string(64)->notNull(),
            'created_at' => $this->timestamp(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->dropTable('card_token');
    }
}
