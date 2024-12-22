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
        Yii::$app->db->createCommand()->delete('aircraft')->execute();
        Yii::$app->db->createCommand()->delete('pilot')->execute();
        Yii::$app->db->createCommand()->delete('route')->execute();
        Yii::$app->db->createCommand()->delete('airport')->execute();
        Yii::$app->db->createCommand()->delete('country')->execute();
        Yii::$app->db->createCommand()->delete('aircraft_configuration')->execute();
        Yii::$app->db->createCommand()->delete('aircraft_type')->execute();
    }

}