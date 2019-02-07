<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%settings}}`.
 */
class m190204_125826_create_settings_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%settings}}', [
            'id' => $this->primaryKey(),
            'group' => $this->string(255),
            'key' => $this->string(255),
            'value' => $this->string(255),
        ]);
        $this->insert('settings', [
            'group' => 'telegram',
            'key' => 'botToken'
        ]);
        $this->insert('settings', [
            'group' => 'telegram',
            'key' => 'chatId',
        ]);
        $this->insert('settings', [
            'group' => 'rocket_chat',
            'key' => 'rocket_chat_url',
        ]);
        $this->insert('settings', [
            'group' => 'rocket_chat',
            'key' => 'rocket_chat_user',
        ]);
        $this->insert('settings', [
            'group' => 'rocket_chat',
            'key' => 'rocket_chat_password',
        ]);

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('settings', [
            'group' => 'telegram',
            'key' => 'botToken'
        ]);
        $this->delete('settings', [
            'group' => 'telegram',
            'key' => 'chatId',
        ]);
        $this->delete('settings', [
            'group' => 'rocket_chat',
            'key' => 'rocket_chat_url',
        ]);
        $this->delete('settings', [
            'group' => 'rocket_chat',
            'key' => 'rocket_chat_user',
        ]);
        $this->delete('settings', [
            'group' => 'rocket_chat',
            'key' => 'rocket_chat_password',
        ]);
        $this->dropTable('{{%settings}}');
    }
}
