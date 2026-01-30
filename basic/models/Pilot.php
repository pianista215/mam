<?php

namespace app\models;

use app\config\ConfigHelper as CK;
use app\helpers\CustomRules;
use app\models\traits\ImageRelated;
use app\models\traits\PasswordRulesTrait;
use Yii;

/**
 * This is the model class for table "pilot".
 *
 * @property int $id
 * @property string|null $license
 * @property string $name
 * @property string $surname
 * @property string $email
 * @property string $registration_date
 * @property string $city
 * @property int $country_id
 * @property string $password
 * @property string $date_of_birth
 * @property int|null $vatsim_id
 * @property int|null $ivao_id
 * @property string|null $auth_key
 * @property string|null $access_token
 * @property float|null $hours_flown
 * @property string $location
 * @property string|null $pwd_reset_token
 * @property string|null $pwd_reset_token_created_at
 * @property int|null $rank_id
 *
 * @property Country $country
 * @property FlightReport[] $flightReports
 * @property SubmittedFlightplan $submittedFlightplan
 * @property PilotTourCompletion[] $pilotTourCompletions
 * @property Rank $rank
 * @property Tour[] $tours
 */
class Pilot extends \yii\db\ActiveRecord implements \yii\web\IdentityInterface
{
    use ImageRelated;
    use PasswordRulesTrait;

    public function getImageDescription(): string
    {
        return "pilot: {$this->fullname}";
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'pilot';
    }

    const SCENARIO_REGISTER = 'register';
    const SCENARIO_ACTIVATE = 'activate';
    const SCENARIO_UPDATE = 'update';
    const SCENARIO_MOVE = 'MOVE';


    public function scenarios(){
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_REGISTER] = ['name', 'surname', 'email', 'city', 'country_id', 'password', 'date_of_birth', 'vatsim_id', 'ivao_id'];
        $scenarios[self::SCENARIO_ACTIVATE] = ['license'];
        $scenarios[self::SCENARIO_MOVE] = ['location'];
        return $scenarios;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return array_merge([
            [['name', 'surname', 'email', 'city', 'country_id', 'date_of_birth', 'location'], 'required'],
            [['registration_date', 'date_of_birth', 'pwd_reset_token_created_at'], 'safe'],
            ['date_of_birth', 'date', 'format' => 'php:Y-m-d'],
            [['date_of_birth'], 'compare', 'compareValue' => date('Y-m-d'), 'operator' => '<', 'message' => 'The date of birth must be earlier than today.'],
            [['country_id', 'vatsim_id', 'ivao_id', 'rank_id'], 'integer'],
            [['hours_flown'], 'number'],
            [['license'], 'filter', 'filter' => [CustomRules::class, 'removeSpaces']],
            [['license'], 'string', 'max' => 8],
            ['license', 'required', 'on' => [self::SCENARIO_ACTIVATE, self::SCENARIO_UPDATE]],
            [['name'], 'string', 'max' => 20],
            [['surname', 'city'], 'string', 'max' => 40],
            [['name', 'surname', 'city', 'email'], 'trim'],
            [['email'], 'string', 'max' => 80],
            [['email'], 'email'],
            [['auth_key', 'access_token'], 'string', 'max' => 32],
            [['location'], 'string', 'length' => 4],
            [['location'], 'filter', 'filter' => 'strtoupper'],
            [['pwd_reset_token'], 'string', 'length' => 255],
            [['email'], 'unique'],
            [['license'], 'unique'],
            [['location'], 'exist', 'skipOnError' => true, 'targetClass' => Airport::class, 'targetAttribute' => ['location' => 'icao_code']],
            [['rank_id'], 'exist', 'skipOnError' => true, 'targetClass' => Rank::class, 'targetAttribute' => ['rank_id' => 'id']],
            [['country_id'], 'exist', 'skipOnError' => true, 'targetClass' => Country::class, 'targetAttribute' => ['country_id' => 'id']],
        ], $this->passwordRules());
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'license' => Yii::t('app', 'License'),
            'name' => Yii::t('app', 'Name'),
            'surname' => Yii::t('app', 'Surname'),
            'fullname' => Yii::t('app', 'Name'),
            'email' => Yii::t('app', 'Email'),
            'registration_date' => Yii::t('app', 'Registration Date'),
            'city' => Yii::t('app', 'City'),
            'country_id' => Yii::t('app', 'Country'),
            'password' => Yii::t('app', 'Password'),
            'date_of_birth' => Yii::t('app', 'Date Of Birth'),
            'vatsim_id' => Yii::t('app', 'Vatsim ID'),
            'ivao_id' => Yii::t('app', 'Ivao ID'),
            'auth_key' => Yii::t('app', 'Auth Key'),
            'access_token' => Yii::t('app', 'Access Token'),
            'hours_flown' => Yii::t('app', 'Hours Flown'),
            'location' => Yii::t('app', 'Location'),
            'rank_id' => Yii::t('app', 'Rank'),
        ];
    }

    /**
     * Gets query for [[Country]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCountry()
    {
        return $this->hasOne(Country::class, ['id' => 'country_id']);
    }

    /**
     * Gets query for [[Flights]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFlights()
    {
        return $this->hasMany(Flight::class, ['pilot_id' => 'id']);
    }

    /**
     * Gets query for [[FlightReports]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFlightReports()
    {
        return $this->hasMany(FlightReport::class, ['pilot_id' => 'id']);
    }

    /**
     * Gets query for [[Location0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLocation0()
    {
        return $this->hasOne(Airport::class, ['icao_code' => 'location']);
    }

    /**
     * Gets query for [[SubmittedFlightplan]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSubmittedFlightplan()
    {
        return $this->hasOne(SubmittedFlightplan::class, ['pilot_id' => 'id']);
    }

    /**
     * Gets query for [[PilotTourCompletions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPilotTourCompletions()
    {
        return $this->hasMany(PilotTourCompletion::class, ['pilot_id' => 'id']);
    }

    /**
     * Gets query for [[Rank]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRank()
    {
        return $this->hasOne(Rank::class, ['id' => 'rank_id']);
    }

    /**
     * Gets query for [[Tours]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTours()
    {
        return $this->hasMany(Tour::class, ['id' => 'tour_id'])->viaTable('pilot_tour_completion', ['pilot_id' => 'id']);
    }

    public function getFlightStats(): array
    {
        $rows = Flight::find()
            ->select([
                'flight_type',
                'cnt' => 'COUNT(*)'
            ])
            ->where(['pilot_id' => $this->id])
            ->groupBy('flight_type')
            ->asArray()
            ->all();

        $charter = 0;
        $regular = 0;

        foreach ($rows as $row) {
            if ($row['flight_type'] == Flight::TYPE_CHARTER) {
                $charter = (int) $row['cnt'];
            } else {
                $regular += (int) $row['cnt'];
            }
        }

        $total = $charter + $regular;

        return [
            'total_flights'   => $total,
            'charter_flights' => $charter,
            'regular_flights' => $regular,
            'charter_ratio'   => $total > 0 ? round(($charter / $total) * 100) : 0,
        ];
    }

    public function getCharterRatio(): float
    {
        return ($this->getFlightStats()['charter_ratio'] ?? 0) / 100;
    }

    /**
     * {@inheritdoc}
     */
    public static function findByLicense($license)
    {
        return static::findOne(['license' => $license]);
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['access_token' => $token]);
    }

    /**
     * @return int|string current user ID
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string current user location
     */
    public function getLocation()
    {
        return $this->location;
    }

    public function getSurnameInitials()
    {
        $surname = $this->surname;
        if (!$surname) return null;

        $parts = preg_split('/\s+/', trim($surname));

        $trans = \Transliterator::create('NFD; [:Nonspacing Mark:] Remove; NFC');

        $initials = array_map(function ($p) use ($trans) {

            $letter = mb_substr($p, 0, 1, 'UTF-8');

            $latin = $trans ? $trans->transliterate($letter) : $letter;

            if (!$latin || $latin === '?') {
                return $letter . '.';
            }

            return strtoupper($latin) . '.';
        }, $parts);

        return implode(' ', $initials);
    }

    public function getSecureSurname()
    {
        // In console applications or when user is logged in, show full surname
        if (!Yii::$app->has('user') || !Yii::$app->user->isGuest) {
            return $this->surname;
        } else {
            return $this->getSurnameInitials();
        }
    }

    /**
     * @return string current user fullname
     */
    public function getFullName()
    {
        return $this->name.' '.$this->secureSurname;
    }

    /**
     * @return string|null current user auth key
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * @param string $authKey
     * @return bool|null if auth key is valid for current user
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    public function isPasswordResetTokenExpired()
    {
        if (empty($this->pwd_reset_token_created_at)) {
            return true;
        }

        try {
            $hours = CK::getTokenLifeH() ?? 24;

            $createdAt = new \DateTime($this->pwd_reset_token_created_at);
            $createdAt->add(new \DateInterval('PT' . $hours . 'H'));
            $now = new \DateTime();

            return $now > $createdAt;
        } catch (\Exception $e) {
            Yii::error('Exception checking password reset token expired for pilot '. $this->id. ':' . $this->pwd_reset_token_created_at. ' ' .$e->getMessage());
            return true;
        }
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->getSecurity()->validatePassword($password, $this->password);
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($this->license) {
                $this->license = mb_strtoupper($this->license);
            }
            if ($this->isNewRecord) {
                // TODO: Period of validation and ensure password could be modified later (is only hashed in the first time)
                $this->auth_key = \Yii::$app->security->generateRandomString(32);
                $this->access_token = \Yii::$app->security->generateRandomString(32);
                $this->password = Yii::$app->getSecurity()->generatePasswordHash($this->password);
            }
            return true;
        }
        return false;
    }


}
