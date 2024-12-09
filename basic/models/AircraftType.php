<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "aircraft_type".
 *
 * @property int $id
 * @property string $icao_type_code
 * @property string $name
 * @property int $max_nm_range
 * @property int $pax_capacity
 * @property int $cargo_capacity
 *
 * @property Aircraft[] $aircrafts
 */
class AircraftType extends \yii\db\ActiveRecord
{
    // TODO: CONSIDER DIFFERENT CONFIGURATIONS (737 carguero y 737 normal)
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'aircraft_type';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['icao_type_code', 'name', 'max_nm_range', 'pax_capacity', 'cargo_capacity'], 'required'],
            [['max_nm_range', 'pax_capacity', 'cargo_capacity'], 'integer'],
            [['icao_type_code'], 'string', 'length' => 4],
            [['name'], 'string', 'max' => 60],
            [['icao_type_code'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'icao_type_code' => 'Icao Type Code',
            'name' => 'Name',
            'max_nm_range' => 'Max Nm Range',
            'pax_capacity' => 'Pax Capacity',
            'cargo_capacity' => 'Cargo Capacity',
        ];
    }

    /**
     * Gets query for [[Aircrafts]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAircrafts()
    {
        return $this->hasMany(Aircraft::class, ['aircraft_type_id' => 'id']);
    }
}
