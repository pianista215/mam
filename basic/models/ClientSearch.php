<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * ClientSearch represents the model behind the search form of `app\models\Client`.
 */
class ClientSearch extends Client
{
    /** @var string|null ICAO code (or part of it) of any of the client's hubs */
    public $hub_icao;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'headquarters_icao', 'hub_icao'], 'safe'],
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
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'hub_icao' => Yii::t('app', 'Hubs'),
        ]);
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
        $query = Client::find()->with(['clientHubs']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['name' => SORT_ASC],
                'attributes' => ['name', 'headquarters_icao'],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere(['like', 'client.name', $this->name]);
        $query->andFilterWhere(['like', 'client.headquarters_icao', $this->headquarters_icao]);

        if ($this->hub_icao !== null && $this->hub_icao !== '') {
            // EXISTS instead of JOIN so clients with several matching hubs aren't duplicated
            $query->andWhere(['exists', ClientHub::find()
                ->where('client_hub.client_id = client.id')
                ->andWhere(['like', 'client_hub.airport_icao', $this->hub_icao])]);
        }

        return $dataProvider;
    }
}
