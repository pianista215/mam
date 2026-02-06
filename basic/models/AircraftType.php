<?php

namespace app\models;

use app\helpers\CustomRules;
use app\models\traits\ImageRelated;
use Yii;

/**
 * This is the model class for table "aircraft_type".
 *
 * @property int $id
 * @property string $icao_type_code
 * @property string $name
 * @property int $max_nm_range
 *
 * @property AircraftConfiguration[] $aircraftConfigurations
 */
class AircraftType extends \yii\db\ActiveRecord
{
    use ImageRelated;

    public function getImageDescription(): string
    {
        return "aircraft type: {$this->name}";
    }

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
            [['icao_type_code', 'name', 'max_nm_range'], 'required'],
            [['icao_type_code'], 'filter', 'filter' => [CustomRules::class, 'removeSpaces']],
            [['max_nm_range'], 'integer'],
            [['icao_type_code'], 'string', 'min' => 2, 'max' => 4],
            [['name'], 'string', 'max' => 60],
            [['name'], 'trim'],
            [['icao_type_code'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'icao_type_code' => Yii::t('app', 'Icao Type Code'),
            'name' => Yii::t('app', 'Name'),
            'max_nm_range' => Yii::t('app', 'Max Range (NM)'),
        ];
    }

    /**
     * Gets query for [[AircraftConfigurations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAircraftConfigurations()
    {
        return $this->hasMany(AircraftConfiguration::class, ['aircraft_type_id' => 'id']);
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($this->icao_type_code) {
                $this->icao_type_code = mb_strtoupper($this->icao_type_code);
            }
            return true;
        }
        return false;
    }
}
