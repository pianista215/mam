<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "statistic_record_type_lang".
 *
 * @property int $id
 * @property int $record_type_id
 * @property string $language
 * @property string $name
 * @property string|null $description
 *
 * @property StatisticRecordType $recordType
 */
class StatisticRecordTypeLang extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'statistic_record_type_lang';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['record_type_id', 'language', 'name'], 'required'],
            [['record_type_id'], 'integer'],
            [['language'], 'string', 'max' => 2],
            [['name'], 'string', 'max' => 100],
            [['description'], 'string', 'max' => 200],
            [['record_type_id', 'language'], 'unique', 'targetAttribute' => ['record_type_id', 'language']],
            [['record_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => StatisticRecordType::class, 'targetAttribute' => ['record_type_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'record_type_id' => 'Record Type ID',
            'language' => 'Language',
            'name' => 'Name',
            'description' => 'Description',
        ];
    }

    /**
     * Gets query for [[RecordType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRecordType()
    {
        return $this->hasOne(StatisticRecordType::class, ['id' => 'record_type_id']);
    }
}
