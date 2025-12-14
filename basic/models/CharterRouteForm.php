<?php
namespace app\models;

use yii\base\Model;
use Yii;

class CharterRouteForm extends Model
{
    public $arrival;

    public function rules()
    {
        return [
            [['arrival'], 'required'],
            [['arrival'], 'string', 'length' => 4],
            [['arrival'], 'filter', 'filter' => 'strtoupper'],
            [['arrival'], 'exist', 'skipOnError' => true, 'targetClass' => Airport::class, 'targetAttribute' => ['arrival' => 'icao_code']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'arrival' => Yii::t('app', 'Arrival airport (ICAO)'),
        ];
    }
}
