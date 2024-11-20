<?php

use app\models\Pilot;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var app\models\PilotSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Pilots';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="pilot-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php if(Yii::$app->user->can('userCrud')) : ?>
    <p>
        <?= Html::a('Create Pilot', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php endif; ?>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            //'id',
            'license',
            'name',
            'surname',
            'email:email',
            //'registration_date',
            //'city',
            //'country_id',
            //'password',
            //'date_of_birth',
            //'vatsim_id',
            //'ivao_id',
            'hours_flown',
            [
                'class' => ActionColumn::className(),
                'visibleButtons'=>[
                    'delete'=> function($model){
                        return Yii::$app->user->can('userCrud');
                    },
                    'update'=> function($model){
                        return Yii::$app->user->can('userCrud');
                    },
                ],
                'urlCreator' => function ($action, Pilot $model, $key, $index, $column) {
                    return Url::toRoute([$action, 'id' => $model->id]);
                 },
            ],
        ],
    ]); ?>


</div>
