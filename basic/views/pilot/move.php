<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\Pilot $model */

$this->title = 'Move pilot';
$this->params['breadcrumbs'][] = ['label' => 'Pilotos', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="pilot-move">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>Please select ICAO code from airport you want to be moved.</p>

    <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($model, 'location')->textInput(['maxlength' => 4, 'placeholder' => 'Ex: LEMD'])->label('Airport (ICAO)') ?>

        <div class="form-group">
            <?= Html::submitButton('Move', ['class' => 'btn btn-primary']) ?>
        </div>

    <?php ActiveForm::end(); ?>

</div>
