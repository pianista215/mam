<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "flight_phase_issue".
 *
 * @property int $id
 * @property int $phase_id
 * @property int $issue_type_id
 * @property string $timestamp
 * @property string|null $value
 *
 * @property IssueType $issueType
 * @property FlightPhase $phase
 */
class FlightPhaseIssue extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'flight_phase_issue';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['phase_id', 'issue_type_id', 'timestamp'], 'required'],
            [['phase_id', 'issue_type_id'], 'integer'],
            [['timestamp'], 'safe'],
            [['value'], 'string', 'max' => 100],
            [['phase_id'], 'exist', 'skipOnError' => true, 'targetClass' => FlightPhase::class, 'targetAttribute' => ['phase_id' => 'id']],
            [['issue_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => IssueType::class, 'targetAttribute' => ['issue_type_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'phase_id' => 'Phase ID',
            'issue_type_id' => 'Issue Type ID',
            'timestamp' => 'Timestamp',
            'value' => 'Value',
        ];
    }

    /**
     * Gets query for [[IssueType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIssueType()
    {
        return $this->hasOne(IssueType::class, ['id' => 'issue_type_id']);
    }

    /**
     * Gets query for [[Phase]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPhase()
    {
        return $this->hasOne(FlightPhase::class, ['id' => 'phase_id']);
    }
}
