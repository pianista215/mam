<?php

use yii\db\Migration;

class m260712_172223_add_discord_webhook_config extends Migration
{
    public function up()
    {
        $this->insert('config', ['key' => 'discord_webhook_url', 'value' => '']);
    }

    public function down()
    {
        $this->delete('config', ['key' => 'discord_webhook_url']);
    }
}
