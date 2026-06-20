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
 * @property int $crew
 * @property int $mtow
 * @property int $bew
 * @property float|null $fuel_regression_a
 * @property float|null $fuel_regression_b
 * @property int|null $fuel_regression_n
 * @property float|null $fuel_avg_kg_per_min
 * @property string|null $fuel_regression_updated_at
 *
 * @property AircraftType $aircraftType
 * @property Aircraft[] $aircrafts
 */
class AircraftConfiguration extends \yii\db\ActiveRecord
{
    const SCENARIO_ADMIN_FORM = 'admin_form';
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
            [['aircraft_type_id', 'name', 'pax_capacity', 'cargo_capacity', 'crew', 'mtow', 'bew'], 'required'],
            [['aircraft_type_id', 'pax_capacity', 'cargo_capacity'], 'integer', 'min' => 0],
            [['crew', 'mtow', 'bew'], 'integer', 'min' => 1],
            [['mtow'], 'compare', 'compareAttribute' => 'bew', 'operator' => '>', 'type' => 'number'],
            [['cargo_capacity'], 'compare', 'compareAttribute' => 'mtow', 'operator' => '<', 'type' => 'number'],
            [['name'], 'string', 'max' => 20],
            [['name'], 'trim'],
            [['name'], 'unique', 'targetAttribute' => ['aircraft_type_id', 'name']],
            [['aircraft_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => AircraftType::class, 'targetAttribute' => ['aircraft_type_id' => 'id']],
            [['fuel_regression_a', 'fuel_regression_b', 'fuel_avg_kg_per_min'], 'number'],
            [['fuel_regression_n'], 'integer', 'min' => 0],
            [['fuel_regression_updated_at'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['fuel_regression_a', 'fuel_regression_b', 'fuel_regression_n', 'fuel_avg_kg_per_min', 'fuel_regression_updated_at'], 'default', 'value' => null],
        ];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_ADMIN_FORM] = ['aircraft_type_id', 'name', 'pax_capacity', 'cargo_capacity', 'crew', 'mtow', 'bew'];
        return $scenarios;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'aircraft_type_id' => Yii::t('app', 'Aircraft Type'),
            'name' => Yii::t('app', 'Name'),
            'pax_capacity' => Yii::t('app', 'Pax Capacity'),
            'cargo_capacity' => Yii::t('app', 'Cargo Capacity (Kg)'),
            'crew' => Yii::t('app', 'Crew'),
            'mtow' => Yii::t('app', 'MTOW (Kg)'),
            'bew' => Yii::t('app', 'BEW (Kg)'),
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
