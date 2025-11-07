<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Flight;

/**
 * FlightSearch represents the model behind the search form of `app\models\Flight`.
 */
class FlightSearch extends Flight
{

    public $onlyPending = false;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'pilot_id', 'aircraft_id'], 'integer'],
            [['code', 'departure', 'arrival', 'alternative1_icao', 'alternative2_icao', 'cruise_speed_value', 'cruise_speed_unit', 'flight_level_value', 'flight_level_unit', 'route', 'estimated_time', 'other_information', 'endurance_time', 'report_tool', 'status', 'creation_date', 'network', 'flight_rules'], 'safe'],
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
        $query = Flight::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if ($this->onlyPending) {
            $query->andWhere([
                'or',
                ['status' => 'V'],
                [
                    'and',
                    ['status' => 'C'],
                    "creation_date < NOW() - INTERVAL 72 HOUR"
                ]
            ]);
        }

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'pilot_id' => $this->pilot_id,
            'aircraft_id' => $this->aircraft_id,
            'creation_date' => $this->creation_date,
        ]);

        $query->andFilterWhere(['like', 'code', $this->code])
            ->andFilterWhere(['like', 'departure', $this->departure])
            ->andFilterWhere(['like', 'arrival', $this->arrival])
            ->andFilterWhere(['like', 'alternative1_icao', $this->alternative1_icao])
            ->andFilterWhere(['like', 'alternative2_icao', $this->alternative2_icao])
            ->andFilterWhere(['like', 'cruise_speed_value', $this->cruise_speed_value])
            ->andFilterWhere(['like', 'cruise_speed_unit', $this->cruise_speed_unit])
            ->andFilterWhere(['like', 'flight_level_value', $this->flight_level_value])
            ->andFilterWhere(['like', 'flight_level_unit', $this->flight_level_unit])
            ->andFilterWhere(['like', 'route', $this->route])
            ->andFilterWhere(['like', 'estimated_time', $this->estimated_time])
            ->andFilterWhere(['like', 'other_information', $this->other_information])
            ->andFilterWhere(['like', 'endurance_time', $this->endurance_time])
            ->andFilterWhere(['like', 'report_tool', $this->report_tool])
            ->andFilterWhere(['like', 'status', $this->status])
            ->andFilterWhere(['like', 'network', $this->network])
            ->andFilterWhere(['like', 'flight_rules', $this->flight_rules]);

        return $dataProvider;
    }

    public function searchForPilot($pilotId, $params)
    {
        $query = Flight::find()->where(['pilot_id' => $pilotId]);

        $dataProvider = new \yii\data\ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['creation_date' => SORT_DESC]],
            'pagination' => ['pageSize' => 10],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere(['like', 'departure', $this->departure])
              ->andFilterWhere(['like', 'arrival', $this->arrival])
              //->andFilterWhere(['like', 'aircraft', $this->aircraft_id])
              ->andFilterWhere(['>=', 'creation_date', $this->creation_date]);

        return $dataProvider;
    }

}
