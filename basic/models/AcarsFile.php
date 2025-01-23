<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "acars_file".
 *
 * @property int $chunk_id
 * @property int $flight_report_id
 * @property string $sha256sum
 * @property string|null $upload_date
 *
 * @property FlightReport $flightReport
 */
class AcarsFile extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'acars_file';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['chunk_id', 'flight_report_id', 'sha256sum'], 'required'],
            [['chunk_id', 'flight_report_id'], 'integer'],
            [['upload_date'], 'safe'],
            [['sha256sum'], 'string', 'length' => 44],
            [['chunk_id', 'flight_report_id'], 'unique', 'targetAttribute' => ['chunk_id', 'flight_report_id']],
            [['flight_report_id'], 'exist', 'skipOnError' => true, 'targetClass' => FlightReport::class, 'targetAttribute' => ['flight_report_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'chunk_id' => 'Chunk ID',
            'flight_report_id' => 'Flight Report ID',
            'sha256sum' => 'Sha256sum',
            'upload_date' => 'Upload Date',
        ];
    }

    /**
     * Gets query for [[FlightReport]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFlightReport()
    {
        return $this->hasOne(FlightReport::class, ['id' => 'flight_report_id']);
    }
}
