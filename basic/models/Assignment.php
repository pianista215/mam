<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "assignment".
 *
 * A client job offered to a pilot, to be flown with the aircraft the pilot flew last from its current airport.
 *
 * @property int $id
 * @property int $pilot_id
 * @property int $aircraft_id
 * @property int $client_id
 * @property string $departure
 * @property string $arrival
 * @property int $distance_nm
 * @property string $created_at
 *
 * @property Aircraft $aircraft
 * @property Airport $arrival0
 * @property Client $client
 * @property Airport $departure0
 * @property Pilot $pilot
 * @property SubmittedFlightPlan $submittedFlightPlan
 */
class Assignment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'assignment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['pilot_id', 'aircraft_id', 'client_id', 'departure', 'arrival', 'distance_nm'], 'required'],
            [['pilot_id', 'aircraft_id', 'client_id'], 'integer'],
            [['distance_nm'], 'integer', 'min' => 0],
            [['departure', 'arrival'], 'filter', 'filter' => 'strtoupper'],
            [['departure', 'arrival'], 'string', 'length' => 4],
            [['arrival'], 'compare', 'compareAttribute' => 'departure', 'operator' => '!=='],
            [['pilot_id'], 'exist', 'skipOnError' => true, 'targetClass' => Pilot::class, 'targetAttribute' => ['pilot_id' => 'id']],
            [['aircraft_id'], 'exist', 'skipOnError' => true, 'targetClass' => Aircraft::class, 'targetAttribute' => ['aircraft_id' => 'id']],
            [['client_id'], 'exist', 'skipOnError' => true, 'targetClass' => Client::class, 'targetAttribute' => ['client_id' => 'id']],
            [['departure'], 'exist', 'skipOnError' => true, 'targetClass' => Airport::class, 'targetAttribute' => ['departure' => 'icao_code']],
            [['arrival'], 'exist', 'skipOnError' => true, 'targetClass' => Airport::class, 'targetAttribute' => ['arrival' => 'icao_code']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'pilot_id' => Yii::t('app', 'Pilot'),
            'aircraft_id' => Yii::t('app', 'Aircraft'),
            'client_id' => Yii::t('app', 'Client'),
            'departure' => Yii::t('app', 'Departure'),
            'arrival' => Yii::t('app', 'Arrival'),
            'distance_nm' => Yii::t('app', 'Distance NM'),
            'created_at' => Yii::t('app', 'Created At'),
        ];
    }

    public function getFplDescription()
    {
        return Yii::t('app', 'Assignment') . ': ' . $this->client->name . ' (' . $this->departure . '-' . $this->arrival . ')';
    }

    /**
     * Assignments the pilot can book right now: departing from the pilot's location, with the aircraft parked there
     * and not reserved by any flight plan. Range and credentials are checked when booking.
     * @return Assignment[] sorted by distance
     */
    public static function findAvailableForPilot(Pilot $pilot): array
    {
        if (empty($pilot->location)) {
            return [];
        }

        $reserved = SubmittedFlightPlan::find()
            ->select('aircraft_id')
            ->where('submitted_flight_plan.aircraft_id = assignment.aircraft_id');

        return self::find()
            ->innerJoinWith('aircraft', false)
            ->where([
                'assignment.pilot_id' => $pilot->id,
                'assignment.departure' => $pilot->location,
                'aircraft.location' => $pilot->location,
            ])
            ->andWhere(['not exists', $reserved])
            ->with(['client', 'aircraft.aircraftConfiguration.aircraftType', 'departure0', 'arrival0'])
            ->orderBy(['assignment.distance_nm' => SORT_ASC, 'assignment.id' => SORT_ASC])
            ->all();
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
     * Gets query for [[Arrival0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getArrival0()
    {
        return $this->hasOne(Airport::class, ['icao_code' => 'arrival']);
    }

    /**
     * Gets query for [[Client]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getClient()
    {
        return $this->hasOne(Client::class, ['id' => 'client_id']);
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
     * Gets query for [[Pilot]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPilot()
    {
        return $this->hasOne(Pilot::class, ['id' => 'pilot_id']);
    }

    /**
     * Gets query for [[SubmittedFlightPlan]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSubmittedFlightPlan()
    {
        return $this->hasOne(SubmittedFlightPlan::class, ['assignment_id' => 'id']);
    }
}
