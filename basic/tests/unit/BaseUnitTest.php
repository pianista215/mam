<?php

namespace tests\unit;

use Yii;

abstract class BaseUnitTest extends \Codeception\Test\Unit
{
    protected function _before()
    {
        parent::_before();
        $this->clearDatabase();
    }

    protected function clearDatabase()
    {
        $db = Yii::$app->db;
        Yii::$app->db->createCommand()->delete('config')->execute();
        Yii::$app->db->createCommand()->delete('acars_file')->execute();
        Yii::$app->db->createCommand()->delete('flight_report')->execute();
        Yii::$app->db->createCommand()->delete('flight')->execute();
        Yii::$app->db->createCommand()->delete('submitted_flight_plan')->execute();
        Yii::$app->db->createCommand()->delete('tour_stage')->execute();
        Yii::$app->db->createCommand()->delete('tour')->execute();
        Yii::$app->db->createCommand()->delete('aircraft')->execute();
        Yii::$app->db->createCommand()->delete('pilot')->execute();
        Yii::$app->db->createCommand()->delete('route')->execute();
        Yii::$app->db->createCommand()->delete('airport')->execute();
        Yii::$app->db->createCommand()->delete('country')->execute();
        Yii::$app->db->createCommand()->delete('aircraft_configuration')->execute();
        Yii::$app->db->createCommand()->delete('aircraft_type')->execute();
        Yii::$app->db->createCommand()->delete('rank')->execute();
        Yii::$app->db->createCommand()->delete('page')->execute();
        Yii::$app->db->createCommand()->delete('page_content')->execute();
        Yii::$app->db->createCommand()->delete('image')->execute();
        Yii::$app->db->createCommand()->delete('flight_phase_type')->execute();
        Yii::$app->db->createCommand()->delete('flight_phase_metric_type')->execute();
        Yii::$app->db->createCommand()->delete('flight_event_attribute')->execute();
        Yii::$app->db->createCommand()->delete('issue_type')->execute();
        Yii::$app->db->createCommand()->delete('statistic_record')->execute();
        Yii::$app->db->createCommand()->delete('statistic_ranking')->execute();
        Yii::$app->db->createCommand()->delete('statistic_aggregate')->execute();
        Yii::$app->db->createCommand()->delete('statistic_period')->execute();
    }

}