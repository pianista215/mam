<?php

use yii\db\Migration;

class m260317_092935_update_taxi_overspeed_description extends Migration
{
    public function safeUp()
    {
        $id = (new \yii\db\Query())->select('id')->from('issue_type')->where(['code' => 'TaxiOverspeed'])->scalar();
        $this->update('issue_type_lang', ['description' => 'Taxi overspeed (>30 knots)'], ['issue_type_id' => $id, 'language' => 'en']);
        $this->update('issue_type_lang', ['description' => 'Taxi overspeed (>30 nudos)'], ['issue_type_id' => $id, 'language' => 'es']);
    }

    public function safeDown()
    {
        $id = (new \yii\db\Query())->select('id')->from('issue_type')->where(['code' => 'TaxiOverspeed'])->scalar();
        $this->update('issue_type_lang', ['description' => 'Taxi overspeed (>25 knots)'], ['issue_type_id' => $id, 'language' => 'en']);
        $this->update('issue_type_lang', ['description' => 'Taxi overspeed (>25 nudos)'], ['issue_type_id' => $id, 'language' => 'es']);
    }
}
