<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "client_hub".
 *
 * @property int $client_id
 * @property string $airport_icao
 *
 * @property Client $client
 * @property Airport $airport
 */
class ClientHub extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'client_hub';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['client_id', 'airport_icao'], 'required'],
            [['client_id'], 'integer'],
            [['airport_icao'], 'string', 'length' => 4],
            [['client_id', 'airport_icao'], 'unique', 'targetAttribute' => ['client_id', 'airport_icao']],
            [['client_id'], 'exist', 'targetClass' => Client::class, 'targetAttribute' => 'id'],
            [['airport_icao'], 'exist', 'targetClass' => Airport::class, 'targetAttribute' => 'icao_code'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'client_id' => Yii::t('app', 'Client'),
            'airport_icao' => Yii::t('app', 'Airport'),
        ];
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
     * Gets query for [[Airport]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAirport()
    {
        return $this->hasOne(Airport::class, ['icao_code' => 'airport_icao']);
    }
}
