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

    public function getDescription()
    {
        $base_description = $this->issueType->lang->description;
        $description = $base_description;
        $issue_code = $this->issueType->code;

        $to_add = null;
        $value = $this->value;

        if($issue_code == 'LandingHardFpm'){
            $to_add = $value . ' fpm';
        } else if($issue_code == 'TaxiOverspeed'){
            $to_add = $value. ' knots';
        } else if($issue_code == 'AppHighVsBelow1000AGL' || $issue_code == 'AppHighVsBelow2000AGL'){
            $parts = explode('|', $value);
            $to_add = $parts[0]. ' fpm '. Yii::t('app','and'). ' '. $parts[1]. 'AGL';
        } else if($issue_code == 'Refueling' || $issue_code == 'ZfwModified'){
            $to_add = $value. ' Kg';
        } else {
            $to_add = $value;
        }

        if ($to_add !== null && $to_add !== '') {
            $description = $description. ': (' . $to_add . ')';
        }

        return $description;
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
