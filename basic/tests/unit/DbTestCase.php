<?php

namespace tests\unit;

use Yii;

abstract class DbTestCase extends \Codeception\Test\Unit
{
    protected function _after()
    {
        parent::_after();
        // Limpiar la base de datos (si es necesario)
        $this->clearDatabase();
    }

    /**
     * Limpia las tablas de la base de datos.
     */
    protected function clearDatabase()
    {
        $db = Yii::$app->db;
        Yii::$app->db->createCommand()->delete('pilot')->execute();
        Yii::$app->db->createCommand()->delete('airport')->execute();
        Yii::$app->db->createCommand()->delete('country')->execute();
    }

}