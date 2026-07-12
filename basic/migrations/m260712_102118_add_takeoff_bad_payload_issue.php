<?php

use yii\db\Migration;

/**
 * Adds the TakeoffWithBadPayload issue type, reported by mam-analyzer when the
 * payload derived from ZFW (zfw_kg - oew_kg) doesn't match the flight's expected payload.
 */
class m260712_102118_add_takeoff_bad_payload_issue extends Migration
{
    public function safeUp()
    {
        $this->insert('issue_type', [
            'code' => 'TakeoffWithBadPayload',
            'penalty' => 20,
        ]);
        $issueTypeId = $this->db->getLastInsertID();
        $this->batchInsert('issue_type_lang', ['issue_type_id', 'language', 'description'], [
            [$issueTypeId, 'en', "The aircraft's estimated payload doesn't match the load sheet"],
            [$issueTypeId, 'es', 'El payload estimado del avión no coincide con el de la hoja de carga'],
        ]);
    }

    public function safeDown()
    {
        $issueTypeId = (new \yii\db\Query())
            ->select('id')
            ->from('issue_type')
            ->where(['code' => 'TakeoffWithBadPayload'])
            ->scalar();

        if ($issueTypeId) {
            $this->delete('issue_type_lang', ['issue_type_id' => $issueTypeId]);
            $this->delete('issue_type', ['id' => $issueTypeId]);
        }
    }
}
