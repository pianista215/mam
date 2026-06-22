<?php

use yii\db\Migration;

class m260621_100823_add_payload_weight_config extends Migration
{
    public function up()
    {
        $this->insert('config', ['key' => 'pax_adult_weight_kg',    'value' => '84']);
        $this->insert('config', ['key' => 'pax_child_weight_kg',    'value' => '35']);
        $this->insert('config', ['key' => 'pax_checked_baggage_kg', 'value' => '13']);
    }

    public function down()
    {
        $this->delete('config', ['key' => 'pax_adult_weight_kg']);
        $this->delete('config', ['key' => 'pax_child_weight_kg']);
        $this->delete('config', ['key' => 'pax_checked_baggage_kg']);
    }
}
