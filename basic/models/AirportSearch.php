<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Airport;

/**
 * AirportSearch represents the model behind the search form of `app\models\Airport`.
 */
class AirportSearch extends Airport
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'country_id'], 'integer'],
            [['icao_code', 'name', 'city'], 'safe'],
            [['latitude', 'longitude'], 'number'],
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
        $query = Airport::find();

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
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'country_id' => $this->country_id,
        ]);

        $query->andFilterWhere(['like', 'icao_code', $this->icao_code])
            ->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'city', $this->city]);

        return $dataProvider;
    }
}
