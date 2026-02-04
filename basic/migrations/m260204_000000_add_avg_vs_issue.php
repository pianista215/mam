<?php

use yii\db\Migration;

/**
 * Adds VSLast3Avg event attribute and AppHighVsAvgBelow1000AGL issue type.
 * Updates AppHighVsBelow1000AGL threshold from -1000 to -1500 fpm.
 */
class m260204_000000_add_avg_vs_issue extends Migration
{
    public function safeUp()
    {
        // 1. Add VSLast3Avg event attribute
        $this->insert('flight_event_attribute', [
            'code' => 'VSLast3Avg',
            'name' => 'Sampled VS',
        ]);

        // 2. Add new issue type for stabilized VS
        $this->insert('issue_type', [
            'code' => 'AppHighVsAvgBelow1000AGL',
            'penalty' => 10,
        ]);

        $issueTypeId = $this->db->getLastInsertID();

        // 3. Add translations for new issue
        $this->batchInsert('issue_type_lang', ['issue_type_id', 'language', 'description'], [
            [$issueTypeId, 'en', 'High sampled descent rate (<-1150 fpm) below 1000 AGL'],
            [$issueTypeId, 'es', 'Alta tasa de descenso muestreada (<-1150 fpm) por debajo de 1000 AGL'],
        ]);

        // 4. Update AppHighVsBelow1000AGL description (threshold -1000 → -1500)
        $existingIssueId = (new \yii\db\Query())
            ->select('id')
            ->from('issue_type')
            ->where(['code' => 'AppHighVsBelow1000AGL'])
            ->scalar();

        if ($existingIssueId) {
            $this->update('issue_type_lang',
                ['description' => 'High descent rate (<-1500 fpm) below 1000 AGL'],
                ['issue_type_id' => $existingIssueId, 'language' => 'en']
            );
            $this->update('issue_type_lang',
                ['description' => 'Alta tasa de descenso (<-1500 fpm) por debajo de 1000 AGL'],
                ['issue_type_id' => $existingIssueId, 'language' => 'es']
            );
        }
    }

    public function safeDown()
    {
        // 1. Delete new issue translations and type
        $issueTypeId = (new \yii\db\Query())
            ->select('id')
            ->from('issue_type')
            ->where(['code' => 'AppHighVsAvgBelow1000AGL'])
            ->scalar();

        if ($issueTypeId) {
            $this->delete('issue_type_lang', ['issue_type_id' => $issueTypeId]);
            $this->delete('issue_type', ['id' => $issueTypeId]);
        }

        // 2. Delete new event attribute
        $this->delete('flight_event_attribute', ['code' => 'VSLast3Avg']);

        // 3. Revert AppHighVsBelow1000AGL description
        $existingIssueId = (new \yii\db\Query())
            ->select('id')
            ->from('issue_type')
            ->where(['code' => 'AppHighVsBelow1000AGL'])
            ->scalar();

        if ($existingIssueId) {
            $this->update('issue_type_lang',
                ['description' => 'High descent rate (<-1000 fpm) below 1000 AGL'],
                ['issue_type_id' => $existingIssueId, 'language' => 'en']
            );
            $this->update('issue_type_lang',
                ['description' => 'Alta tasa de descenso (<-1000 fpm) por debajo de 1000 AGL'],
                ['issue_type_id' => $existingIssueId, 'language' => 'es']
            );
        }
    }
}
