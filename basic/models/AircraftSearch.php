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
            [['id', 'aircraft_type_id'], 'integer'],
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
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'aircraft_type_id' => $this->aircraft_type_id,
            'hours_flown' => $this->hours_flown,
        ]);

        $query->andFilterWhere(['like', 'registration', $this->registration])
            ->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'location', $this->location]);

        return $dataProvider;
    }
}