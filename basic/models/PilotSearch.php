<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Pilot;

/**
 * PilotSearch represents the model behind the search form of `app\models\Pilot`.
 */
class PilotSearch extends Pilot
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        // TODO: CHECK IF IT'S DANGEROUS HAVING THE PASSWORD HERE
        return [
            [['id', 'country_id', 'vatsim_id', 'ivao_id'], 'integer'],
            [['license', 'name', 'surname', 'email', 'registration_date', 'city', 'password', 'date_of_birth', 'auth_key', 'access_token', 'location'], 'safe'],
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
        $query = Pilot::find();

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
            'registration_date' => $this->registration_date,
            'country_id' => $this->country_id,
            'date_of_birth' => $this->date_of_birth,
            'vatsim_id' => $this->vatsim_id,
            'ivao_id' => $this->ivao_id,
            'hours_flown' => $this->hours_flown,
        ]);

        // TODO: CHECK IF IT'S DANGEROUS HAVING THE PASSWORD HERE

        $query->andFilterWhere(['like', 'license', $this->license])
            ->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'surname', $this->surname])
            ->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'city', $this->city])
            ->andFilterWhere(['like', 'password', $this->password])
            ->andFilterWhere(['like', 'auth_key', $this->auth_key])
            ->andFilterWhere(['like', 'access_token', $this->access_token])
            ->andFilterWhere(['like', 'location', $this->location]);

        return $dataProvider;
    }
}
