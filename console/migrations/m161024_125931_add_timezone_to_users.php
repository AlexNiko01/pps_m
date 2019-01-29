<?php

use yii\db\Migration;

class m161024_125931_add_timezone_to_users extends Migration
{
    public function safeUp()
    {

        $this->addColumn('user', 'timezone', 'string');
        $this->db->schema->refresh();

    }

    public function safeDown()
    {
        $this->dropColumn('user', 'timezone');
        $this->db->schema->refresh();

    }
}
