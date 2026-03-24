<?php

use yii\db\Migration;

/**
 * Updates AppHighVsBelow2000AGL descriptions to use {limit} placeholder.
 */
class m260324_093000_add_limit_placeholder_to_2000agl_issue extends Migration
{
    public function safeUp()
    {
        $issueTypeId = (new \yii\db\Query())
            ->select('id')
            ->from('issue_type')
            ->where(['code' => 'AppHighVsBelow2000AGL'])
            ->scalar();

        if ($issueTypeId) {
            $this->update('issue_type_lang',
                ['description' => 'High descent rate ({limit} fpm) below 2000 AGL'],
                ['issue_type_id' => $issueTypeId, 'language' => 'en']
            );
            $this->update('issue_type_lang',
                ['description' => 'Alta tasa de descenso ({limit} fpm) por debajo de 2000 AGL'],
                ['issue_type_id' => $issueTypeId, 'language' => 'es']
            );
        }
    }

    public function safeDown()
    {
        $issueTypeId = (new \yii\db\Query())
            ->select('id')
            ->from('issue_type')
            ->where(['code' => 'AppHighVsBelow2000AGL'])
            ->scalar();

        if ($issueTypeId) {
            $this->update('issue_type_lang',
                ['description' => 'High descent rate (<-2000 fpm) below 2000 AGL'],
                ['issue_type_id' => $issueTypeId, 'language' => 'en']
            );
            $this->update('issue_type_lang',
                ['description' => 'Alta tasa de descenso (<-2000 fpm) por debajo de 2000 AGL'],
                ['issue_type_id' => $issueTypeId, 'language' => 'es']
            );
        }
    }
}
