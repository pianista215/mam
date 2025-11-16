<?php

namespace tests\unit;

use Yii;

abstract class BaseUnitTest extends \Codeception\Test\Unit
{
    protected function _after()
    {
        parent::_after();
        $this->clearDatabase();
    }

    protected function clearDatabase()
    {
        $db = Yii::$app->db;
        Yii::$app->db->createCommand()->delete('config')->execute();
        Yii::$app->db->createCommand()->delete('acars_file')->execute();
        Yii::$app->db->createCommand()->delete('flight_report')->execute();
        Yii::$app->db->createCommand()->delete('flight')->execute();
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
    }

}