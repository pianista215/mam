<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\SubmittedFlightPlan;

/**
 * SubmittedFlightPlanSearch represents the model behind the search form of `app\models\SubmittedFlightPlan`.
 */
class SubmittedFlightPlanSearch extends SubmittedFlightPlan
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'aircraft_id', 'route_id', 'pilot_id'], 'integer'],
            [['flight_rules', 'alternative1_icao', 'alternative2_icao', 'cruise_speed', 'flight_level', 'route', 'estimated_time', 'other_information', 'endurance_time'], 'safe'],
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

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = SubmittedFlightPlan::find();

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
            'aircraft_id' => $this->aircraft_id,
            'route_id' => $this->route_id,
            'pilot_id' => $this->pilot_id,
        ]);

        $query->andFilterWhere(['like', 'flight_rules', $this->flight_rules])
            ->andFilterWhere(['like', 'alternative1_icao', $this->alternative1_icao])
            ->andFilterWhere(['like', 'alternative2_icao', $this->alternative2_icao])
            ->andFilterWhere(['like', 'cruise_speed', $this->cruise_speed])
            ->andFilterWhere(['like', 'flight_level', $this->flight_level])
            ->andFilterWhere(['like', 'route', $this->route])
            ->andFilterWhere(['like', 'estimated_time', $this->estimated_time])
            ->andFilterWhere(['like', 'other_information', $this->other_information])
            ->andFilterWhere(['like', 'endurance_time', $this->endurance_time]);

        return $dataProvider;
    }
}
