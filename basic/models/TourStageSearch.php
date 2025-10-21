<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\TourStage;

/**
 * TourStageSearch represents the model behind the search form of `app\models\TourStage`.
 */
class TourStageSearch extends TourStage
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'tour_id', 'distance_nm'], 'integer'],
            [['departure', 'arrival', 'description'], 'safe'],
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
        $query = TourStage::find();

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
            'tour_id' => $this->tour_id,
            'distance_nm' => $this->distance_nm,
        ]);

        $query->andFilterWhere(['like', 'departure', $this->departure])
            ->andFilterWhere(['like', 'arrival', $this->arrival])
            ->andFilterWhere(['like', 'description', $this->description]);

        return $dataProvider;
    }
}
