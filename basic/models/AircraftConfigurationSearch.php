<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\AircraftConfiguration;

/**
 * AircraftConfigurationSearch represents the model behind the search form of `app\models\AircraftConfiguration`.
 */
class AircraftConfigurationSearch extends AircraftConfiguration
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'aircraft_type_id', 'pax_capacity', 'cargo_capacity', 'crew', 'mtow', 'oew'], 'integer'],
            [['name'], 'safe'],
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
        $query = AircraftConfiguration::find()->joinWith(['aircraftType']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'attributes' => [
                    'name' => [
                        'asc'  => ['aircraft_configuration.name' => SORT_ASC],
                        'desc' => ['aircraft_configuration.name' => SORT_DESC],
                    ],
                    'pax_capacity',
                    'cargo_capacity',
                    'crew',
                    'oew',
                    'mtow',
                    'aircraftType' => [
                        'asc'  => ['aircraft_type.name' => SORT_ASC],
                        'desc' => ['aircraft_type.name' => SORT_DESC],
                    ],
                ],
            ],
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
            'pax_capacity' => $this->pax_capacity,
            'cargo_capacity' => $this->cargo_capacity,
            'crew' => $this->crew,
            'mtow' => $this->mtow,
            'oew' => $this->oew,
        ]);

        $query->andFilterWhere(['like', 'aircraft_configuration.name', $this->name]);

        return $dataProvider;
    }
}
