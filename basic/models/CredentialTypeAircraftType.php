<?php

namespace app\models;

/**
 * This is the model class for table "credential_type_aircraft_type".
 *
 * Links a credential type to the aircraft types it grants permission to fly.
 *
 * @property int $credential_type_id
 * @property int $aircraft_type_id
 *
 * @property CredentialType $credentialType
 * @property AircraftType $aircraftType
 */
class CredentialTypeAircraftType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'credential_type_aircraft_type';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['credential_type_id', 'aircraft_type_id'], 'required'],
            [['credential_type_id', 'aircraft_type_id'], 'integer'],
            [['credential_type_id', 'aircraft_type_id'], 'unique', 'targetAttribute' => ['credential_type_id', 'aircraft_type_id']],
            [['credential_type_id'], 'exist', 'targetClass' => CredentialType::class, 'targetAttribute' => 'id'],
            [['aircraft_type_id'], 'exist', 'targetClass' => AircraftType::class, 'targetAttribute' => 'id'],
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
}
