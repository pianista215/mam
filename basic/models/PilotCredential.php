<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "pilot_credential".
 *
 * One row per (pilot_id, credential_type_id). Revoking deletes the row.
 * Renewing updates the row in place (issued_date, expiry_date, status).
 *
 * A credential is considered valid when:
 *   status = STATUS_ACTIVE AND (expiry_date IS NULL OR expiry_date >= TODAY)
 *
 * @property int $id
 * @property int $pilot_id
 * @property int $credential_type_id
 * @property int $status
 * @property string $issued_date
 * @property string|null $expiry_date
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

    public function getIssuedDateLabel(): string
    {
        return $this->isStudent()
            ? Yii::t('app', 'Training Start Date')
            : Yii::t('app', 'Issued Date');
    }

    public function getExpiryDateLabel(): string
    {
        return $this->isStudent()
            ? Yii::t('app', 'Training End Date')
            : Yii::t('app', 'Expiry Date');
    }

    public function isStudent(): bool
    {
        return (int)$this->status === self::STATUS_STUDENT;
    }

    public function isActive(): bool
    {
        return (int)$this->status === self::STATUS_ACTIVE;
    }

    /**
     * Whether this credential is currently valid (active and not expired).
     */
    public function isValid(): bool
    {
        return $this->isActive()
            && ($this->expiry_date === null || $this->expiry_date >= date('Y-m-d'));
    }

    /**
     * Whether this credential grants access to fly the associated aircraft types.
     * Any existing credential (active or student, regardless of expiry) grants access.
     * Access is only removed when the credential is revoked (row deleted).
     */
    public function grantsAircraftAccess(): bool
    {
        return $this->isActive() || $this->isStudent();
    }

    /**
     * Whether this credential can be renewed.
     * Students can always be issued (promoted to active) regardless of expiry date.
     * Active credentials can only be renewed if they have an expiry date to update.
     */
    public function canRenew(): bool
    {
        if ($this->isStudent()) {
            return true;
        }
        return $this->expiry_date !== null;
    }

    /**
     * Whether this credential can be revoked.
     * A LICENSE cannot be revoked if the pilot holds another LICENSE that is a descendant
     * in the prerequisite DAG (revoking it would leave a higher license without its base).
     */
    public function canRevoke(): bool
    {
        if (!$this->credentialType->isLicense()) {
            return true;
        }
        $descendantIds = $this->credentialType->getDescendantTypeIds();
        if (empty($descendantIds)) {
            return true;
        }
        $childLicenseIds = CredentialType::find()
            ->select('id')
            ->where(['id' => $descendantIds, 'type' => CredentialType::TYPE_LICENSE])
            ->column();
        if (empty($childLicenseIds)) {
            return true;
        }
        return !static::find()
            ->where(['pilot_id' => $this->pilot_id, 'credential_type_id' => $childLicenseIds])
            ->exists();
    }

    /**
     * Returns the display names of credentials that would be cascade-revoked along with this one.
     */
    public function getCascadeCredentialNames(): array
    {
        $descendantIds = $this->credentialType->getDescendantTypeIds();
        if (empty($descendantIds)) {
            return [];
        }
        $dependents = static::find()
            ->with('credentialType')
            ->where(['pilot_id' => $this->pilot_id, 'credential_type_id' => $descendantIds])
            ->all();
        return array_map(
            fn($pc) => $pc->credentialType->code . ' — ' . $pc->credentialType->name,
            $dependents
        );
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
}
