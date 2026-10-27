<?php

namespace app\models;

use app\helpers\CustomRules;
use app\models\traits\ImageRelated;
use Yii;

/**
 * This is the model class for table "client".
 *
 * @property int $id
 * @property string $name
 * @property string $headquarters_icao
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Airport $headquarters
 * @property ClientHub[] $clientHubs
 * @property Airport[] $hubs
 * @property Assignment[] $assignments
 * @property Flight[] $flights
 */
class Client extends \yii\db\ActiveRecord
{
    use ImageRelated;

    /**
     * Hub ICAO codes to store on save. Null means "leave hubs untouched".
     * @var string[]|null
     */
    public $hubIcaos = null;

    public function getImageDescription(): string
    {
        return "client: {$this->name}";
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'client';
    }

    /**
     * {@inheritdoc}
     */
    public function transactions()
    {
        // Client row and its hubs are saved atomically
        return [
            self::SCENARIO_DEFAULT => self::OP_INSERT | self::OP_UPDATE,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'headquarters_icao'], 'required'],
            [['name'], 'trim'],
            [['name'], 'string', 'max' => 100],
            [['name'], 'unique'],
            [['headquarters_icao'], 'filter', 'filter' => [CustomRules::class, 'removeSpaces']],
            [['headquarters_icao'], 'filter', 'filter' => 'strtoupper'],
            [['headquarters_icao'], 'string', 'length' => 4],
            [['headquarters_icao'], 'exist', 'targetClass' => Airport::class, 'targetAttribute' => 'icao_code'],
            [['hubIcaos'], 'validateHubIcaos', 'skipOnEmpty' => false],
        ];
    }

    /**
     * Normalizes hub ICAO codes (uppercase, no spaces, no duplicates, headquarters removed)
     * and checks that all of them exist as airports.
     */
    public function validateHubIcaos($attribute, $params)
    {
        if ($this->hubIcaos === null) {
            return;
        }

        if (!is_array($this->hubIcaos)) {
            $this->addError($attribute, Yii::t('app', 'Invalid hubs.'));
            return;
        }

        $icaos = [];
        foreach ($this->hubIcaos as $icao) {
            if (!is_string($icao)) {
                continue;
            }
            $icao = strtoupper(CustomRules::removeSpaces($icao));
            if ($icao !== '' && $icao !== $this->headquarters_icao) {
                $icaos[$icao] = $icao;
            }
        }
        $icaos = array_values($icaos);

        $existing = Airport::find()->select('icao_code')->where(['icao_code' => $icaos])->column();
        $missing = array_diff($icaos, $existing);
        if (!empty($missing)) {
            $this->addError($attribute, Yii::t('app', 'Unknown hub airports: {icaos}', ['icaos' => implode(', ', $missing)]));
            return;
        }

        sort($icaos);
        $this->hubIcaos = $icaos;
    }

    /**
     * {@inheritdoc}
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if ($this->hubIcaos !== null) {
            $this->syncHubs($this->hubIcaos);
        } elseif (isset($changedAttributes['headquarters_icao'])) {
            // New headquarters can't remain as a hub
            ClientHub::deleteAll(['client_id' => $this->id, 'airport_icao' => $this->headquarters_icao]);
        }
    }

    /**
     * Replaces the client hubs with the given (already validated) ICAO codes.
     * @param string[] $icaos
     */
    public function syncHubs(array $icaos): void
    {
        ClientHub::deleteAll(['client_id' => $this->id]);

        $rows = array_map(fn($icao) => [$this->id, $icao], $icaos);
        if (!empty($rows)) {
            Yii::$app->db->createCommand()
                ->batchInsert(ClientHub::tableName(), ['client_id', 'airport_icao'], $rows)
                ->execute();
        }

        unset($this->clientHubs, $this->hubs);
    }

    /**
     * ICAO codes of the current hubs, sorted.
     * @return string[]
     */
    public function getHubIcaoList(): array
    {
        return ClientHub::find()
            ->select('airport_icao')
            ->where(['client_id' => $this->id])
            ->orderBy(['airport_icao' => SORT_ASC])
            ->column();
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Name'),
            'headquarters_icao' => Yii::t('app', 'Headquarters'),
            'hubIcaos' => Yii::t('app', 'Hubs'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * Gets query for [[Headquarters]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getHeadquarters()
    {
        return $this->hasOne(Airport::class, ['icao_code' => 'headquarters_icao']);
    }

    /**
     * Gets query for [[ClientHubs]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getClientHubs()
    {
        return $this->hasMany(ClientHub::class, ['client_id' => 'id']);
    }

    /**
     * Gets query for [[Hubs]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getHubs()
    {
        return $this->hasMany(Airport::class, ['icao_code' => 'airport_icao'])
            ->viaTable('client_hub', ['client_id' => 'id'])
            ->orderBy(['icao_code' => SORT_ASC]);
    }

    /**
     * Gets query for [[Assignments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAssignments()
    {
        return $this->hasMany(Assignment::class, ['client_id' => 'id']);
    }

    /**
     * Gets query for [[Flights]] flown as assignments for this client.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFlights()
    {
        return $this->hasMany(Flight::class, ['client_id' => 'id']);
    }

    /**
     * Whether the client can be deleted: flown flights keep a reference to it and booked assignments can't be removed.
     */
    public function isDeletable(): bool
    {
        return !$this->getFlights()->exists()
            && !$this->getAssignments()->innerJoinWith('submittedFlightPlan', false)->exists();
    }
}
