<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "credential_type_airport_aircraft".
 *
 * A row means: having credential_type_id allows flying aircraft_type_id to airport_icao.
 *
 * Query logic for FPL: if any row exists for a (aircraft_type_id, airport_icao) pair,
 * the pilot must hold at least one of the associated credentials (OR across credentials).
 * If no row exists for the pair, there is no restriction.
 *
 * @property int $credential_type_id
 * @property int $aircraft_type_id
 * @property string $airport_icao
 *
 * @property CredentialType $credentialType
 * @property AircraftType $aircraftType
 * @property Airport $airport
 */
class CredentialTypeAirportAircraft extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'credential_type_airport_aircraft';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['credential_type_id', 'aircraft_type_id', 'airport_icao'], 'required'],
            [['credential_type_id', 'aircraft_type_id'], 'integer'],
            [['airport_icao'], 'string', 'length' => 4],
            [['airport_icao'], 'filter', 'filter' => 'strtoupper'],
            [['credential_type_id', 'aircraft_type_id', 'airport_icao'], 'unique', 'targetAttribute' => ['credential_type_id', 'aircraft_type_id', 'airport_icao']],
            [['credential_type_id'], 'exist', 'targetClass' => CredentialType::class, 'targetAttribute' => 'id'],
            [['aircraft_type_id'], 'exist', 'targetClass' => AircraftType::class, 'targetAttribute' => 'id'],
            [['airport_icao'], 'exist', 'targetClass' => Airport::class, 'targetAttribute' => 'icao_code'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'credential_type_id' => Yii::t('app', 'Credential Type'),
            'aircraft_type_id'   => Yii::t('app', 'Aircraft Type'),
            'airport_icao'       => Yii::t('app', 'Airport'),
        ];
    }

    public function getCredentialType()
    {
        return $this->hasOne(CredentialType::class, ['id' => 'credential_type_id']);
    }

    public function getAircraftType()
    {
        return $this->hasOne(AircraftType::class, ['id' => 'aircraft_type_id']);
    }

    public function getAirport()
    {
        return $this->hasOne(Airport::class, ['icao_code' => 'airport_icao']);
    }
}
