<?php

use yii\db\Migration;

class m260823_085651_add_bags_ratio_config extends Migration
{
    public function up()
    {
        $this->insert('config', ['key' => 'pax_bags_ratio_min', 'value' => '20']);
        $this->insert('config', ['key' => 'pax_bags_ratio_max', 'value' => '35']);
    }

    public function down()
    {
        $this->delete('config', ['key' => 'pax_bags_ratio_min']);
        $this->delete('config', ['key' => 'pax_bags_ratio_max']);
    }
}
