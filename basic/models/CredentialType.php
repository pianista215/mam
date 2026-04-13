<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "credential_type".
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property int $type
 * @property string|null $description
 *
 * @property CredentialTypePrerequisite[] $prerequisites
 * @property CredentialTypePrerequisite[] $dependents
 * @property CredentialType[] $parentCredentialTypes
 * @property CredentialType[] $childCredentialTypes
 * @property AircraftType[] $aircraftTypes
 * @property PilotCredential[] $pilotCredentials
 */
class CredentialType extends \yii\db\ActiveRecord
{
    const TYPE_LICENSE       = 1;
    const TYPE_RATING        = 2;
    const TYPE_CERTIFICATION = 3;

    /** @var int[] IDs of parent credential types (prerequisites), managed via junction table */
    public $prerequisiteIds = [];

    /** @var int[] IDs of aircraft types this credential unlocks, managed via junction table */
    public $aircraftTypeIds = [];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'credential_type';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['code', 'name', 'type'], 'required'],
            [['type'], 'integer'],
            [['type'], 'in', 'range' => [self::TYPE_LICENSE, self::TYPE_RATING, self::TYPE_CERTIFICATION]],
            [['description'], 'string'],
            [['code'], 'string', 'max' => 30],
            [['name'], 'string', 'max' => 100],
            [['name'], 'trim'],
            [['code'], 'trim'],
            [['code'], 'unique'],
            [['prerequisiteIds', 'aircraftTypeIds'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'            => Yii::t('app', 'ID'),
            'code'          => Yii::t('app', 'Code'),
            'name'          => Yii::t('app', 'Name'),
            'type'          => Yii::t('app', 'Type'),
            'description'   => Yii::t('app', 'Description'),
        ];
    }

    public static function typeLabels(): array
    {
        return [
            self::TYPE_LICENSE       => Yii::t('app', 'License'),
            self::TYPE_RATING        => Yii::t('app', 'Rating'),
            self::TYPE_CERTIFICATION => Yii::t('app', 'Certification'),
        ];
    }

    public function getTypeLabel(): string
    {
        return self::typeLabels()[$this->type] ?? '';
    }

    public function isLicense(): bool
    {
        return $this->type === self::TYPE_LICENSE;
    }

    public function isRating(): bool
    {
        return $this->type === self::TYPE_RATING;
    }

    public function isCertification(): bool
    {
        return $this->type === self::TYPE_CERTIFICATION;
    }

    /**
     * Gets the prerequisite junction records (this credential as child).
     */
    public function getPrerequisites()
    {
        return $this->hasMany(CredentialTypePrerequisite::class, ['child_id' => 'id']);
    }

    /**
     * Gets the credential types that are prerequisites of this one.
     */
    public function getParentCredentialTypes()
    {
        return $this->hasMany(CredentialType::class, ['id' => 'parent_id'])
            ->via('prerequisites');
    }

    /**
     * Gets the junction records where this credential is a parent (unlocks other credentials).
     */
    public function getDependents()
    {
        return $this->hasMany(CredentialTypePrerequisite::class, ['parent_id' => 'id']);
    }

    /**
     * Gets the credential types that require this one as prerequisite.
     */
    public function getChildCredentialTypes()
    {
        return $this->hasMany(CredentialType::class, ['id' => 'child_id'])
            ->via('dependents');
    }

    /**
     * Gets aircraft types this credential unlocks for flying.
     */
    public function getAircraftTypes()
    {
        return $this->hasMany(AircraftType::class, ['id' => 'aircraft_type_id'])
            ->viaTable('credential_type_aircraft_type', ['credential_type_id' => 'id']);
    }

    /**
     * Gets airport-aircraft restrictions defined by this credential type.
     */
    public function getAirportAircraftRestrictions()
    {
        return $this->hasMany(CredentialTypeAirportAircraft::class, ['credential_type_id' => 'id'])
            ->orderBy(['airport_icao' => SORT_ASC, 'aircraft_type_id' => SORT_ASC]);
    }

    /**
     * Gets pilot credentials of this type.
     */
    public function getPilotCredentials()
    {
        return $this->hasMany(PilotCredential::class, ['credential_type_id' => 'id']);
    }
}
