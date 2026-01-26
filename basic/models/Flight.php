<?php

namespace app\models;

use app\config\ConfigHelper as CK;
use app\helpers\LoggerTrait;
use Yii;

/**
 * This is the model class for table "flight".
 *
 * @property int $id
 * @property int $pilot_id
 * @property int $aircraft_id
 * @property string $code
 * @property string $departure
 * @property string $arrival
 * @property string $alternative1_icao
 * @property string|null $alternative2_icao
 * @property string $flight_rules
 * @property string $cruise_speed_value
 * @property string $cruise_speed_unit
 * @property string $flight_level_value
 * @property string $flight_level_unit
 * @property string $route
 * @property string $estimated_time
 * @property string $other_information
 * @property string $endurance_time
 * @property string $report_tool
 * @property string $status
 * @property string $creation_date
 * @property string|null $network
 * @property string|null $validator_comments
 * @property int|null $validator_id
 * @property string|null $validation_date
 * @property int|null $tour_stage_id
 * @property string $flight_type
 *
 * @property Aircraft $aircraft
 * @property Airport $alternative1Icao
 * @property Airport $alternative2Icao
 * @property Airport $arrival0
 * @property Airport $departure0
 * @property FlightReport $flightReport
 * @property Pilot $pilot
 * @property TourStage $tourStage
 * @property Pilot $validator
 */
class Flight extends \yii\db\ActiveRecord
{
    use LoggerTrait;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'flight';
    }

    const SCENARIO_VALIDATE = 'validate';

    public function scenarios(){
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_VALIDATE] = ['validator_comments'];
        return $scenarios;
    }

    // Status
    const STATUS_CREATED            = 'C';
    const STATUS_SUBMITTED          = 'S';
    const STATUS_PENDING_VALIDATION = 'V';
    const STATUS_FINISHED           = 'F';
    const STATUS_REJECTED           = 'R';

    // Flight type
    const TYPE_ROUTE   = 'R';
    const TYPE_TOUR    = 'T';
    const TYPE_CHARTER = 'C';

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['pilot_id', 'aircraft_id', 'code', 'departure', 'arrival', 'alternative1_icao', 'flight_rules', 'cruise_speed_value', 'cruise_speed_unit', 'flight_level_unit', 'route', 'estimated_time', 'other_information', 'endurance_time', 'report_tool'], 'required'],
            [['pilot_id', 'aircraft_id', 'validator_id', 'tour_stage_id'], 'integer'],
            [['creation_date', 'validation_date'], 'safe'],
            [['code'], 'string', 'max' => 10],
            [['departure', 'arrival', 'alternative1_icao', 'alternative2_icao', 'cruise_speed_value', 'flight_level_value', 'estimated_time', 'endurance_time'], 'string', 'max' => 4],
            [['cruise_speed_unit', 'status', 'flight_rules', 'flight_type'], 'string', 'max' => 1],
            [['flight_level_unit'], 'string', 'max' => 3],
            [['route', 'other_information', 'validator_comments'], 'string', 'max' => 400],
            [['report_tool'], 'string', 'max' => 20],
            [['network'], 'string', 'max' => 50],
            [['status'], 'in', 'range' => [
                self::STATUS_CREATED,
                self::STATUS_SUBMITTED,
                self::STATUS_PENDING_VALIDATION,
                self::STATUS_FINISHED,
                self::STATUS_REJECTED,
            ]],
            [['flight_type'], 'in', 'range' => [
                self::TYPE_ROUTE,
                self::TYPE_TOUR,
                self::TYPE_CHARTER,
            ]],
            [['aircraft_id'], 'exist', 'skipOnError' => true, 'targetClass' => Aircraft::class, 'targetAttribute' => ['aircraft_id' => 'id']],
            [['alternative1_icao'], 'exist', 'skipOnError' => true, 'targetClass' => Airport::class, 'targetAttribute' => ['alternative1_icao' => 'icao_code']],
            [['alternative2_icao'], 'exist', 'skipOnError' => true, 'targetClass' => Airport::class, 'targetAttribute' => ['alternative2_icao' => 'icao_code']],
            [['arrival'], 'exist', 'skipOnError' => true, 'targetClass' => Airport::class, 'targetAttribute' => ['arrival' => 'icao_code']],
            [['departure'], 'exist', 'skipOnError' => true, 'targetClass' => Airport::class, 'targetAttribute' => ['departure' => 'icao_code']],
            [['pilot_id'], 'exist', 'skipOnError' => true, 'targetClass' => Pilot::class, 'targetAttribute' => ['pilot_id' => 'id']],
            [['validator_id'], 'exist', 'skipOnError' => true, 'targetClass' => Pilot::class, 'targetAttribute' => ['validator_id' => 'id']],
            [['tour_stage_id'], 'exist', 'skipOnError' => true, 'targetClass' => TourStage::class, 'targetAttribute' => ['tour_stage_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        // TODO: Use attribute labels in all the forms instead of string literals
        return [
            'id' => 'ID',
            'pilot_id' => 'Pilot ID',
            'aircraft_id' => 'Aircraft ID',
            'code' => 'Code',
            'departure' => Yii::t('app', 'Departure'),
            'arrival' => Yii::t('app', 'Arrival'),
            'alternative1_icao' => 'Alternative1 Icao',
            'alternative2_icao' => 'Alternative2 Icao',
            'flight_rules' => 'Flight Rules',
            'cruise_speed_value' => 'Cruise Speed Value',
            'cruise_speed_unit' => 'Cruise Speed Unit',
            'flight_level_value' => 'Flight Level Value',
            'flight_level_unit' => 'Flight Level Unit',
            'route' => 'Route',
            'estimated_time' => 'Estimated Time',
            'other_information' => 'Other Information',
            'endurance_time' => 'Endurance Time',
            'report_tool' => 'Report Tool',
            'status' => Yii::t('app', 'Status'),
            'fullStatus' => Yii::t('app', 'Status'),
            'creation_date' => Yii::t('app', 'Creation Date'),
            'network' => 'Network',
            'validator_comments' => Yii::t('app', 'Validator Comments'),
            'validator_id' => 'Validator ID',
            'validation_date' => 'Validation Date',
            'tour_stage_id' => 'Tour Stage ID',
            'flight_type' => 'Flight Type',
        ];
    }

    public function getFullStatus(){
        $list = [
            self::STATUS_CREATED => Yii::t('app', 'Created. Basic information received. Awaiting ACARS files to be uploaded.'),
            self::STATUS_SUBMITTED => Yii::t('app', 'ACARS files received. Awaiting processing.'),
            self::STATUS_PENDING_VALIDATION => Yii::t('app', 'Pending validation.'),
            self::STATUS_FINISHED => Yii::t('app', 'Finished'),
            self::STATUS_REJECTED => Yii::t('app', 'Rejected')
        ];

        return $list[$this->status];
    }

    public function isProcessed(){
        return $this->status === self::STATUS_PENDING_VALIDATION || $this->status === self::STATUS_FINISHED || $this->status === self::STATUS_REJECTED;
    }

    public function hasAcarsInfo(){
        return !empty($this->flightReport->flightPhases);
    }

    public function isValidated(){
        return $this->status === self::STATUS_FINISHED || $this->status === self::STATUS_REJECTED;
    }

    public function isPendingValidation(){
        if ($this->status === self::STATUS_PENDING_VALIDATION) {
            return true;
        }

        if ($this->status === self::STATUS_CREATED && $this->creation_date) {
            $creation = new \DateTimeImmutable($this->creation_date);
            // TODO: Make this configurable from config param
            return $creation->modify('+72 hours') < new \DateTimeImmutable();
        }

        return false;
    }

    /**
     * Gets query for [[Aircraft]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAircraft()
    {
        return $this->hasOne(Aircraft::class, ['id' => 'aircraft_id']);
    }

    /**
     * Gets query for [[Alternative1Icao]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAlternative1Icao()
    {
        return $this->hasOne(Airport::class, ['icao_code' => 'alternative1_icao']);
    }

    /**
     * Gets query for [[Alternative2Icao]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAlternative2Icao()
    {
        return $this->hasOne(Airport::class, ['icao_code' => 'alternative2_icao']);
    }

    /**
     * Gets query for [[Arrival0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getArrival0()
    {
        return $this->hasOne(Airport::class, ['icao_code' => 'arrival']);
    }

    /**
     * Gets query for [[Departure0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDeparture0()
    {
        return $this->hasOne(Airport::class, ['icao_code' => 'departure']);
    }

    /**
     * Gets query for [[FlightReport]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFlightReport()
    {
        return $this->hasOne(FlightReport::class, ['flight_id' => 'id']);
    }

    /**
     * Gets query for [[Pilot]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPilot()
    {
        return $this->hasOne(Pilot::class, ['id' => 'pilot_id']);
    }

   /**
    * Gets query for [[Validator]].
    *
    * @return \yii\db\ActiveQuery
    */
   public function getValidator()
   {
       return $this->hasOne(Pilot::class, ['id' => 'validator_id']);
   }
   
   /**
    * Gets query for [[TourStage]].
    *
    * @return \yii\db\ActiveQuery
    */
   public function getTourStage()
   {
       return $this->hasOne(TourStage::class, ['id' => 'tour_stage_id']);
   }

    /**
     * Deletes the flight and its associated ACARS files.
     * Files are deleted AFTER the database transaction commits for safety.
     *
     * @return bool Whether the deletion was successful
     * @throws \Throwable If an error occurs during deletion
     */
    public function deleteWithAcarsFiles(): bool
    {
        // Collect file paths and directory before deletion
        $filePaths = [];
        $reportDir = null;

        if ($this->flightReport) {
            $reportDir = CK::getChunksStoragePath() . DIRECTORY_SEPARATOR . $this->flightReport->id;
            foreach ($this->flightReport->acarsFiles as $acarsFile) {
                $filePaths[] = $acarsFile->getPath();
            }
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            // Suppress file deletion in AcarsFile::afterDelete()
            AcarsFile::$skipFileDelete = true;

            // Delete flight (cascade deletes flight_report and acars_file records)
            $result = $this->delete();

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            AcarsFile::$skipFileDelete = false;
            throw $e;
        }

        AcarsFile::$skipFileDelete = false;

        // Delete physical files AFTER successful commit
        foreach ($filePaths as $path) {
            if (file_exists($path)) {
                if (!@unlink($path)) {
                    $this->logWarn('Failed to delete ACARS file', ['path' => $path]);
                }
            }
        }

        // Try to remove the directory if empty
        if ($reportDir && is_dir($reportDir)) {
            $files = @scandir($reportDir);
            if ($files !== false && count($files) === 2) { // Only . and ..
                if (!@rmdir($reportDir)) {
                    $this->logWarn('Failed to remove empty ACARS directory', ['dir' => $reportDir]);
                }
            }
        }

        return $result !== false;
    }
}
