<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\AircraftType;

/**
 * AircraftTypeSearch represents the model behind the search form of `app\models\AircraftType`.
 */
class AircraftTypeSearch extends AircraftType
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'max_nm_range', 'pax_capacity', 'cargo_capacity'], 'integer'],
            [['icao_type_code', 'name'], 'safe'],
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
        $query = AircraftType::find();

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
            'max_nm_range' => $this->max_nm_range,
            'pax_capacity' => $this->pax_capacity,
            'cargo_capacity' => $this->cargo_capacity,
        ]);

        $query->andFilterWhere(['like', 'icao_type_code', $this->icao_type_code])
            ->andFilterWhere(['like', 'name', $this->name]);

        return $dataProvider;
    }
}
