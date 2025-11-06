<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "rank".
 *
 * @property int $id
 * @property string $name
 * @property int $position
 *
 * @property Pilot[] $pilots
 */
class Rank extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'rank';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'position'], 'required'],
            [['position'], 'integer'],
            [['name'], 'string', 'max' => 100],
            [['name'], 'unique'],
            [['position'], 'unique'],
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
            'position' => 'Position',
        ];
    }

    /**
     * Gets query for [[Pilots]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPilots()
    {
        return $this->hasMany(Pilot::class, ['rank_id' => 'id']);
    }
}
