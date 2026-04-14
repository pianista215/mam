<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "pilot_credential".
 *
 * History model: each row is an issuance event. The current record for a given
 * (pilot_id, credential_type_id) is the one where superseded_at IS NULL.
 * When renewing or changing status, close the previous record (set superseded_at = NOW())
 * and insert a new one, within a transaction.
 *
 * A credential is considered valid when:
 *   status = STATUS_ACTIVE AND superseded_at IS NULL
 *   AND (expiry_date IS NULL OR expiry_date >= TODAY)
 *
 * @property int $id
 * @property int $pilot_id
 * @property int $credential_type_id
 * @property int $status
 * @property string $issued_date
 * @property string|null $expiry_date
 * @property string|null $superseded_at
 * @property string $created_at
 * @property string|null $notes
 * @property int|null $issued_by
 *
 * @property Pilot $pilot
 * @property CredentialType $credentialType
 * @property Pilot $issuer
 */
class PilotCredential extends \yii\db\ActiveRecord
{
    const STATUS_STUDENT = 1;
    const STATUS_ACTIVE  = 2;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'pilot_credential';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['pilot_id', 'credential_type_id', 'status', 'issued_date'], 'required'],
            [['pilot_id', 'credential_type_id', 'issued_by'], 'integer'],
            [['status'], 'integer'],
            [['status'], 'in', 'range' => [self::STATUS_STUDENT, self::STATUS_ACTIVE]],
            [['issued_date', 'expiry_date'], 'date', 'format' => 'php:Y-m-d'],
            [['superseded_at'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['notes'], 'string', 'max' => 255],
            [['pilot_id'], 'exist', 'targetClass' => Pilot::class, 'targetAttribute' => 'id'],
            [['credential_type_id'], 'exist', 'targetClass' => CredentialType::class, 'targetAttribute' => 'id'],
            [['issued_by'], 'exist', 'targetClass' => Pilot::class, 'targetAttribute' => 'id'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'                 => Yii::t('app', 'ID'),
            'pilot_id'           => Yii::t('app', 'Pilot'),
            'credential_type_id' => Yii::t('app', 'Credential Type'),
            'status'             => Yii::t('app', 'Status'),
            'issued_date'        => Yii::t('app', 'Issued Date'),
            'expiry_date'        => Yii::t('app', 'Expiry Date'),
            'superseded_at'      => Yii::t('app', 'Superseded At'),
            'created_at'         => Yii::t('app', 'Created At'),
            'notes'              => Yii::t('app', 'Notes'),
            'issued_by'          => Yii::t('app', 'Issued By'),
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_STUDENT => Yii::t('app', 'Student'),
            self::STATUS_ACTIVE  => Yii::t('app', 'Active'),
        ];
    }

    public function getStatusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? '';
    }

    public function isStudent(): bool
    {
        return $this->status === self::STATUS_STUDENT;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Whether this credential record is currently valid (active, not expired, not superseded).
     */
    public function isValid(): bool
    {
        return $this->isActive()
            && $this->superseded_at === null
            && ($this->expiry_date === null || $this->expiry_date >= date('Y-m-d'));
    }

    /**
     * Whether this is the current record for this pilot+credential (not yet superseded).
     */
    public function isCurrent(): bool
    {
        return $this->superseded_at === null;
    }

    public function getPilot()
    {
        return $this->hasOne(Pilot::class, ['id' => 'pilot_id']);
    }

    public function getCredentialType()
    {
        return $this->hasOne(CredentialType::class, ['id' => 'credential_type_id']);
    }

    public function getIssuer()
    {
        return $this->hasOne(Pilot::class, ['id' => 'issued_by']);
    }

    /**
     * Returns all superseded (historical) records for the same pilot + credential type pair,
     * ordered by supersession date descending (most recent first).
     *
     * @return \yii\db\ActiveQuery
     */
    public function getHistory()
    {
        return static::find()
            ->where(['pilot_id' => $this->pilot_id, 'credential_type_id' => $this->credential_type_id])
            ->andWhere(['IS NOT', 'superseded_at', null])
            ->orderBy(['superseded_at' => SORT_DESC]);
    }
}
