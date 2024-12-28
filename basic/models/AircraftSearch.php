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


    public function searchAvailableAircraftsInLocationWithRange($location, $distance)
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

        $query->andFilterWhere(['location' =>$this->location]);
        $query->andFilterWhere(['>=','aircraft_type.max_nm_range', $distance]);

        return $dataProvider;
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
        $query = Aircraft::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
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
