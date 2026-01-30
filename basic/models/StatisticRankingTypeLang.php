<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "statistic_ranking_type_lang".
 *
 * @property int $id
 * @property int $ranking_type_id
 * @property string $language
 * @property string $name
 * @property string|null $description
 *
 * @property StatisticRankingType $rankingType
 */
class StatisticRankingTypeLang extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'statistic_ranking_type_lang';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['ranking_type_id', 'language', 'name'], 'required'],
            [['ranking_type_id'], 'integer'],
            [['language'], 'string', 'max' => 2],
            [['name'], 'string', 'max' => 100],
            [['description'], 'string', 'max' => 200],
            [['ranking_type_id', 'language'], 'unique', 'targetAttribute' => ['ranking_type_id', 'language']],
            [['ranking_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => StatisticRankingType::class, 'targetAttribute' => ['ranking_type_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'ranking_type_id' => 'Ranking Type ID',
            'language' => 'Language',
            'name' => 'Name',
            'description' => 'Description',
        ];
    }

    /**
     * Gets query for [[RankingType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRankingType()
    {
        return $this->hasOne(StatisticRankingType::class, ['id' => 'ranking_type_id']);
    }
}
