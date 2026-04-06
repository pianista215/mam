<?php

use yii\db\Migration;

/**
 * Adds AppHighVsBelow500AGL and AppHighVsAvgBelow500AGL issue types.
 */
class m260406_194526_add_500agl_issues extends Migration
{
    public function safeUp()
    {
        // 1. Add AppHighVsBelow500AGL
        $this->insert('issue_type', [
            'code' => 'AppHighVsBelow500AGL',
            'penalty' => 10,
        ]);
        $issueTypeId = $this->db->getLastInsertID();
        $this->batchInsert('issue_type_lang', ['issue_type_id', 'language', 'description'], [
            [$issueTypeId, 'en', 'High descent rate (<{limit} fpm) below 500 AGL'],
            [$issueTypeId, 'es', 'Alta tasa de descenso (<{limit} fpm) por debajo de 500 AGL'],
        ]);

        // 2. Add AppHighVsAvgBelow500AGL
        $this->insert('issue_type', [
            'code' => 'AppHighVsAvgBelow500AGL',
            'penalty' => 10,
        ]);
        $issueTypeId = $this->db->getLastInsertID();
        $this->batchInsert('issue_type_lang', ['issue_type_id', 'language', 'description'], [
            [$issueTypeId, 'en', 'High sampled descent rate (<{limit} fpm) below 500 AGL'],
            [$issueTypeId, 'es', 'Alta tasa de descenso muestreada (<{limit} fpm) por debajo de 500 AGL'],
        ]);
    }

    public function safeDown()
    {
        foreach (['AppHighVsBelow500AGL', 'AppHighVsAvgBelow500AGL'] as $code) {
            $issueTypeId = (new \yii\db\Query())
                ->select('id')
                ->from('issue_type')
                ->where(['code' => $code])
                ->scalar();

            if ($issueTypeId) {
                $this->delete('issue_type_lang', ['issue_type_id' => $issueTypeId]);
                $this->delete('issue_type', ['id' => $issueTypeId]);
            }
        }
    }
}
