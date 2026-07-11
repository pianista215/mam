<?php

use yii\db\Migration;

class m260711_175733_add_operations_mail_config extends Migration
{
    public function up()
    {
        $this->insert('config', ['key' => 'operations_mail', 'value' => 'operations@mamairlines.com']);
    }

    public function down()
    {
        $this->delete('config', ['key' => 'operations_mail']);
    }
}
