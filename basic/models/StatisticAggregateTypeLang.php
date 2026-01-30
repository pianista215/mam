<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "statistic_aggregate_type_lang".
 *
 * @property int $id
 * @property int $aggregate_type_id
 * @property string $language
 * @property string $name
 * @property string|null $description
 *
 * @property StatisticAggregateType $aggregateType
 */
class StatisticAggregateTypeLang extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'statistic_aggregate_type_lang';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['aggregate_type_id', 'language', 'name'], 'required'],
            [['aggregate_type_id'], 'integer'],
            [['language'], 'string', 'max' => 2],
            [['name'], 'string', 'max' => 100],
            [['description'], 'string', 'max' => 200],
            [['aggregate_type_id', 'language'], 'unique', 'targetAttribute' => ['aggregate_type_id', 'language']],
            [['aggregate_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => StatisticAggregateType::class, 'targetAttribute' => ['aggregate_type_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'aggregate_type_id' => 'Aggregate Type ID',
            'language' => 'Language',
            'name' => 'Name',
            'description' => 'Description',
        ];
    }

    /**
     * Gets query for [[AggregateType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAggregateType()
    {
        return $this->hasOne(StatisticAggregateType::class, ['id' => 'aggregate_type_id']);
    }
}
