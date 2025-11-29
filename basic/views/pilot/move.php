<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\Pilot $model */

$this->title = Yii::t('app', 'Move Pilot');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Pilots'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="pilot-move">

    <h1><?= Html::encode($this->title) ?></h1>

    <p><?=Yii::t('app', 'Please select ICAO code from airport you want to be moved.')?></p>

    <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($model, 'location')->textInput(['maxlength' => 4, 'placeholder' => Yii::t('app', 'Ex: LEMD')])->label(Yii::t('app', 'Airport (ICAO)')) ?>

        <div class="form-group">
            <?= Html::submitButton(Yii::t('app', 'Move'), ['class' => 'btn btn-primary']) ?>
        </div>

    <?php ActiveForm::end(); ?>

</div>
