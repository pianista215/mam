<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\Aircraft $model */

$this->title = Yii::t('app', 'Move Aircraft');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Aircrafts'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->registration, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="aircraft-move">

    <h1><?= Html::encode($this->title) ?></h1>

    <p><?= Yii::t('app', 'Please enter the ICAO code of the airport to move this aircraft.')?></p>

    <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($model, 'location')
            ->textInput([
                'maxlength' => 4,
                'placeholder' => 'Example: LEMD'
            ])
            ->label(Yii::t('app', 'Airport (ICAO)')) ?>

        <div class="form-group">
            <?= Html::submitButton(Yii::t('app', 'Move'), ['class' => 'btn btn-primary']) ?>
        </div>

    <?php ActiveForm::end(); ?>

</div>
