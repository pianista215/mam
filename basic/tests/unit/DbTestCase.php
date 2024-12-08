<?php

namespace tests\unit;

use Yii;

abstract class DbTestCase extends \Codeception\Test\Unit
{
    protected function _after()
    {
        parent::_after();
        $this->clearDatabase();
    }

    protected function clearDatabase()
    {
        $db = Yii::$app->db;
        Yii::$app->db->createCommand()->delete('pilot')->execute();
        Yii::$app->db->createCommand()->delete('route')->execute();
        Yii::$app->db->createCommand()->delete('airport')->execute();
        Yii::$app->db->createCommand()->delete('country')->execute();
    }

}