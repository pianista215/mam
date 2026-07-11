<?php

use yii\db\Migration;

class m260711_175734_add_occupancy_ratio_config extends Migration
{
    public function up()
    {
        $this->insert('config', ['key' => 'pax_occ_cargo_min', 'value' => '60']);
        $this->insert('config', ['key' => 'pax_occ_cargo_max', 'value' => '90']);
        $this->insert('config', ['key' => 'pax_occ_high_min',  'value' => '50']);
        $this->insert('config', ['key' => 'pax_occ_high_max',  'value' => '85']);
        $this->insert('config', ['key' => 'pax_occ_low_min',   'value' => '40']);
        $this->insert('config', ['key' => 'pax_occ_low_max',   'value' => '80']);
    }

    public function down()
    {
        $this->delete('config', ['key' => 'pax_occ_cargo_min']);
        $this->delete('config', ['key' => 'pax_occ_cargo_max']);
        $this->delete('config', ['key' => 'pax_occ_high_min']);
        $this->delete('config', ['key' => 'pax_occ_high_max']);
        $this->delete('config', ['key' => 'pax_occ_low_min']);
        $this->delete('config', ['key' => 'pax_occ_low_max']);
    }
}
