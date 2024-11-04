<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "pilot".
 *
 * @property int $id
 * @property string $license
 * @property string $name
 * @property string $surname
 * @property string $email
 * @property string $registration_date
 * @property string $city
 * @property int $country_id
 * @property string $password
 *
 * @property Country $country
 * @property FlightReport[] $flightReports
 * @property SubmittedFlightplan $submittedFlightplan
 */
class Pilot extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'pilot';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['license', 'name', 'surname', 'email', 'city', 'country_id', 'password'], 'required'],
            [['registration_date'], 'safe'],
            [['country_id'], 'integer'],
            [['license'], 'string', 'max' => 8],
            [['name'], 'string', 'max' => 20],
            [['surname', 'city'], 'string', 'max' => 40],
            [['email'], 'string', 'max' => 80],
            [['password'], 'string', 'max' => 255],
            [['license'], 'unique'],
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
     * Gets query for [[SubmittedFlightplan]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSubmittedFlightplan()
    {
        return $this->hasOne(SubmittedFlightplan::class, ['pilot_id' => 'id']);
    }
}
