<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "issue_type".
 *
 * @property int $id
 * @property string $code
 * @property string $description
 * @property int|null $penalty
 *
 * @property FlightPhaseIssue[] $flightPhaseIssues
 */
class IssueType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'issue_type';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['code', 'description'], 'required'],
            [['penalty'], 'integer'],
            [['code'], 'string', 'max' => 80],
            [['description'], 'string', 'max' => 200],
            [['code'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'code' => 'Code',
            'description' => 'Description',
            'penalty' => 'Penalty',
        ];
    }

    /**
     * Gets query for [[FlightPhaseIssues]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFlightPhaseIssues()
    {
        return $this->hasMany(FlightPhaseIssue::class, ['issue_type_id' => 'id']);
    }
}
