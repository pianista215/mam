<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "aircraft_configuration".
 *
 * @property int $id
 * @property int $aircraft_type_id
 * @property string $name
 * @property int $pax_capacity
 * @property int $cargo_capacity
 *
 * @property AircraftType $aircraftType
 * @property Aircraft[] $aircrafts
 */
class AircraftConfiguration extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'aircraft_configuration';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['aircraft_type_id', 'name', 'pax_capacity', 'cargo_capacity'], 'required'],
            [['aircraft_type_id', 'pax_capacity', 'cargo_capacity'], 'integer'],
            [['name'], 'string', 'max' => 20],
            [['aircraft_type_id', 'name'], 'unique', 'targetAttribute' => ['aircraft_type_id', 'name']],
            [['aircraft_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => AircraftType::class, 'targetAttribute' => ['aircraft_type_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'aircraft_type_id' => 'Aircraft Type ID',
            'name' => 'Name',
            'pax_capacity' => 'Pax Capacity',
            'cargo_capacity' => 'Cargo Capacity',
        ];
    }

    public function getFullname(){
        return $this->aircraftType->name.' ('.$this->name.')';
    }

    /**
     * Gets query for [[AircraftType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAircraftType()
    {
        return $this->hasOne(AircraftType::class, ['id' => 'aircraft_type_id']);
    }

    /**
     * Gets query for [[Aircrafts]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAircrafts()
    {
        return $this->hasMany(Aircraft::class, ['aircraft_configuration_id' => 'id']);
    }
}
