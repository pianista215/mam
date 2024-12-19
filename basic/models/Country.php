<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "country".
 *
 * @property int $id
 * @property string $name
 * @property string $iso2_code
 *
 * @property Airport[] $airports
 * @property Pilot[] $pilots
 */
class Country extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'country';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'iso2_code'], 'required'],
            [['name'], 'trim'],
            [['name'], 'string', 'max' => 80],
            [['iso2_code'], 'string', 'length' => 2],
            [['iso2_code'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'iso2_code' => 'Iso2 Code',
        ];
    }

    /**
     * Gets query for [[Airports]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAirports()
    {
        return $this->hasMany(Airport::class, ['country_id' => 'id']);
    }

    /**
     * Gets query for [[Pilots]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPilots()
    {
        return $this->hasMany(Pilot::class, ['country_id' => 'id']);
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($this->iso2_code) {
                $this->iso2_code = mb_strtoupper($this->iso2_code);
            }
            return true;
        }
        return false;
    }
}
