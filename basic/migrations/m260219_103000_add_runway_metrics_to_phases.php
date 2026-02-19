<?php

use yii\db\Migration;

/**
 * Adds TakeoffRunway and TakeoffRunwayRemainingPct metrics to the takeoff phase,
 * and LandingRunway and LandingRunwayTouchdownPct metrics to the final_landing phase.
 */
class m260219_103000_add_runway_metrics_to_phases extends Migration
{
    public function safeUp()
    {
        $takeoffTypeId = (new \yii\db\Query())
            ->select('id')
            ->from('flight_phase_type')
            ->where(['code' => 'takeoff'])
            ->scalar();

        $landingTypeId = (new \yii\db\Query())
            ->select('id')
            ->from('flight_phase_type')
            ->where(['code' => 'final_landing'])
            ->scalar();

        // TakeoffRunway
        $this->insert('flight_phase_metric_type', [
            'flight_phase_type_id' => $takeoffTypeId,
            'code' => 'TakeoffRunway',
        ]);
        $takeoffRunwayTypeId = $this->db->getLastInsertID();
        $this->batchInsert('flight_phase_metric_type_lang', ['flight_phase_metric_type_id', 'language', 'name'], [
            [$takeoffRunwayTypeId, 'en', 'Takeoff Runway'],
            [$takeoffRunwayTypeId, 'es', 'Pista de despegue'],
        ]);

        // TakeoffRunwayRemainingPct
        $this->insert('flight_phase_metric_type', [
            'flight_phase_type_id' => $takeoffTypeId,
            'code' => 'TakeoffRunwayRemainingPct',
        ]);
        $takeoffRunwayRemainingTypeId = $this->db->getLastInsertID();
        $this->batchInsert('flight_phase_metric_type_lang', ['flight_phase_metric_type_id', 'language', 'name'], [
            [$takeoffRunwayRemainingTypeId, 'en', 'Takeoff Runway Remaining (%)'],
            [$takeoffRunwayRemainingTypeId, 'es', 'Pista restante en despegue (%)'],
        ]);

        // LandingRunway
        $this->insert('flight_phase_metric_type', [
            'flight_phase_type_id' => $landingTypeId,
            'code' => 'LandingRunway',
        ]);
        $landingRunwayTypeId = $this->db->getLastInsertID();
        $this->batchInsert('flight_phase_metric_type_lang', ['flight_phase_metric_type_id', 'language', 'name'], [
            [$landingRunwayTypeId, 'en', 'Landing Runway'],
            [$landingRunwayTypeId, 'es', 'Pista de aterrizaje'],
        ]);

        // LandingRunwayTouchdownPct
        $this->insert('flight_phase_metric_type', [
            'flight_phase_type_id' => $landingTypeId,
            'code' => 'LandingRunwayTouchdownPct',
        ]);
        $landingRunwayTouchdownTypeId = $this->db->getLastInsertID();
        $this->batchInsert('flight_phase_metric_type_lang', ['flight_phase_metric_type_id', 'language', 'name'], [
            [$landingRunwayTouchdownTypeId, 'en', 'Touchdown Point on Runway (%)'],
            [$landingRunwayTouchdownTypeId, 'es', 'Punto de touchdown en pista (%)'],
        ]);
    }

    public function safeDown()
    {
        $this->delete('flight_phase_metric_type', ['code' => [
            'TakeoffRunway',
            'TakeoffRunwayRemainingPct',
            'LandingRunway',
            'LandingRunwayTouchdownPct',
        ]]);
    }
}
