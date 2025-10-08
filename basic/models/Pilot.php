<?php

namespace app\models;

use app\helpers\CustomRules;
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
 *
 * @property Country $country
 * @property FlightReport[] $flightReports
 * @property SubmittedFlightplan $submittedFlightplan
 */
class Pilot extends \yii\db\ActiveRecord implements \yii\web\IdentityInterface
{
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
    const SCENARIO_PASSWORD_CHANGE_REQUEST = 'passwordChangeRequest';


    public function scenarios(){
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_REGISTER] = ['name', 'surname', 'email', 'city', 'country_id', 'password', 'date_of_birth', 'vatsim_id', 'ivao_id'];
        $scenarios[self::SCENARIO_ACTIVATE] = ['license'];
        $scenarios[self::SCENARIO_MOVE] = ['location'];
        $scenarios[self::SCENARIO_PASSWORD_CHANGE_REQUEST] = ['pwd_reset_token', 'pwd_reset_token_created_at'];
        return $scenarios;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'surname', 'email', 'city', 'country_id', 'password', 'date_of_birth', 'location'], 'required'],
            [['registration_date', 'date_of_birth', 'pwd_reset_token_created_at'], 'safe'],
            ['date_of_birth', 'date', 'format' => 'php:Y-m-d'],
            [['date_of_birth'], 'compare', 'compareValue' => date('Y-m-d'), 'operator' => '<', 'message' => 'The date of birth must be earlier than today.'],
            [['country_id', 'vatsim_id', 'ivao_id'], 'integer'],
            [['hours_flown'], 'number'],
            [['license'], 'filter', 'filter' => [CustomRules::class, 'removeSpaces']],
            [['license'], 'string', 'max' => 8],
            ['license', 'required', 'on' => [self::SCENARIO_ACTIVATE, self::SCENARIO_UPDATE]],
            [['name'], 'string', 'max' => 20],
            [['surname', 'city'], 'string', 'max' => 40],
            [['name', 'surname', 'city', 'email'], 'trim'],
            [['email'], 'string', 'max' => 80],
            [['email'], 'email'],
            [['password'], 'string', 'max' => 255],
            [['password'], 'string', 'min' => 8],
            [['auth_key', 'access_token'], 'string', 'max' => 32],
            [['password'], 'match', 'pattern'=>'/\d/', 'message' => 'Password must contain at least one numeric digit.'],
            [['password'], 'match', 'pattern'=>'/[a-zA-Z]/', 'message' => 'Password must contain at least one letter.'],
            [['location'], 'string', 'length' => 4],
            [['pwd_reset_token'], 'string', 'length' => 255],
            [['email'], 'unique'],
            [['license'], 'unique'],
            [['location'], 'exist', 'skipOnError' => true, 'targetClass' => Airport::class, 'targetAttribute' => ['location' => 'icao_code']],
            [['country_id'], 'exist', 'skipOnError' => true, 'targetClass' => Country::class, 'targetAttribute' => ['country_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'license' => 'License',
            'name' => 'Name',
            'surname' => 'Surname',
            'email' => 'Email',
            'registration_date' => 'Registration Date',
            'city' => 'City',
            'country_id' => 'Country ID',
            'password' => 'Password',
            'date_of_birth' => 'Date Of Birth',
            'vatsim_id' => 'Vatsim ID',
            'ivao_id' => 'Ivao ID',
            'auth_key' => 'Auth Key',
            'access_token' => 'Access Token',
            'hours_flown' => 'Hours Flown',
            'location' => 'Location',
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

    /**
     * @return string current user fullname
     */
    public function getFullName()
    {
        return $this->name.' '.$this->surname;
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
