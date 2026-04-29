<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Aircraft;

/**
 * AircraftSearch represents the model behind the search form of `app\models\Aircraft`.
 */
class AircraftSearch extends Aircraft
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'aircraft_configuration_id'], 'integer'],
            [['registration', 'name', 'location'], 'safe'],
            [['hours_flown'], 'number'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }


    public function searchAvailableAircraftsInLocationWithRange($location, $distance, int $pilotId, string $arrivalIcao)
    {
        $subquery = SubmittedFlightPlan::find()->select('aircraft_id');

        $query = Aircraft::find()->joinWith(['aircraftConfiguration'])->joinWith('aircraftConfiguration.aircraftType');
        $query->orderBy(['aircraft_type.name' => SORT_ASC, 'registration' => SORT_ASC]);

        $this->location = $location;

        $dataProvider = new ActiveDataProvider([
           'query' => $query->where(['NOT IN', 'aircraft.id', $subquery]),
        ]);

        if (!$this->validate()) {
            // Don't return nothing if validation fails
            $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere(['location' => $this->location]);
        $query->andFilterWhere(['>=', 'aircraft_type.max_nm_range', $distance]);

        $this->applyCredentialFilter($query, $pilotId, $arrivalIcao);

        return $dataProvider;
    }

    /**
     * Restricts the aircraft query to types the pilot is allowed to fly.
     * Any credential (active or student) grants access — access is removed only by revoking
     * (deleting the row). Aircraft types with no mapping in credential_type_aircraft_type
     * are hidden from everyone.
     * Airport restrictions add a second layer: if credential_type_airport_aircraft
     * has entries for the arrival airport, only pilots with a matching credential can fly
     * those aircraft types there (types without entries are unrestricted).
     */
    private function applyCredentialFilter(\yii\db\ActiveQuery $query, int $pilotId, string $arrivalIcao): void
    {
        $validCredTypeIds = PilotCredential::find()
            ->select('credential_type_id')
            ->where(['pilot_id' => $pilotId])
            ->column();

        $allowedTypeIds = empty($validCredTypeIds) ? [] :
            CredentialTypeAircraftType::find()
                ->select('aircraft_type_id')->distinct()
                ->where(['credential_type_id' => $validCredTypeIds])
                ->column();
        $query->andWhere(['in', 'aircraft_type.id', $allowedTypeIds]);

        $airportTypeIds = CredentialTypeAirportAircraft::find()
            ->select('aircraft_type_id')->distinct()
            ->where(['airport_icao' => strtoupper($arrivalIcao)])
            ->column();
        if (!empty($airportTypeIds)) {
            $allowedAirportTypeIds = empty($validCredTypeIds) ? [] :
                CredentialTypeAirportAircraft::find()
                    ->select('aircraft_type_id')->distinct()
                    ->where(['airport_icao' => strtoupper($arrivalIcao), 'credential_type_id' => $validCredTypeIds])
                    ->column();
            $query->andWhere(['or',
                ['not in', 'aircraft_type.id', $airportTypeIds],
                ['in', 'aircraft_type.id', $allowedAirportTypeIds],
            ]);
        }
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Aircraft::find()->joinWith(['aircraftConfiguration', 'aircraftConfiguration.aircraftType']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'  => [
                'attributes' => [
                    'registration',
                    'name',
                    'location',
                    'hours_flown',
                    'aircraftConfiguration' => [
                        'asc'  => ['aircraft_type.name' => SORT_ASC,  'aircraft_configuration.name' => SORT_ASC],
                        'desc' => ['aircraft_type.name' => SORT_DESC, 'aircraft_configuration.name' => SORT_DESC],
                        'label' => 'Aircraft Configuration',
                    ],
                ],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // Don't return nothing if validation fails
            $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'aircraft_configuration_id' => $this->aircraft_configuration_id,
            'hours_flown' => $this->hours_flown,
        ]);

        $query->andFilterWhere(['like', 'registration', $this->registration])
            ->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'location', $this->location]);

        return $dataProvider;
    }
}
