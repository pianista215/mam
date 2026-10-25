<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\Client $model */
/** @var string[] $hubIcaos  ICAO codes of the client hubs */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="client-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'headquarters_icao')->textInput([
        'maxlength' => 4,
        'placeholder' => 'LEMD',
        'style' => 'text-transform:uppercase; max-width:280px',
    ]) ?>

    <div class="mb-3 <?= $model->hasErrors('hubIcaos') ? 'has-error' : '' ?>">
        <label class="form-label"><?= Html::encode($model->getAttributeLabel('hubIcaos')) ?></label>
        <div id="js-hub-tags" class="d-flex flex-wrap gap-1 mb-2 p-2 border rounded" style="min-height:2.5rem">
            <?php foreach ($hubIcaos as $icao): ?>
            <span class="badge bg-secondary d-inline-flex align-items-center gap-1 fs-6">
                <?= Html::encode($icao) ?>
                <?= Html::hiddenInput('hubIcaos[]', $icao) ?>
                <button type="button" class="btn-close btn-close-white js-remove-hub" style="font-size:0.65em" aria-label="Remove"></button>
            </span>
            <?php endforeach; ?>
        </div>
        <div class="input-group" style="max-width:280px">
            <input id="js-hub-input" type="text" class="form-control" maxlength="4"
                   placeholder="LEBL" style="text-transform:uppercase">
            <button type="button" id="js-add-hub" class="btn btn-outline-secondary">
                + <?= Yii::t('app', 'Add') ?>
            </button>
        </div>
        <div class="form-text"><?= Yii::t('app', 'The headquarters airport is not stored as a hub.') ?></div>
        <?php if ($model->hasErrors('hubIcaos')): ?>
            <div class="invalid-feedback d-block"><?= Html::encode($model->getFirstError('hubIcaos')) ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

    <?php
    $this->registerJs('
(function () {
    function addHub(icao) {
        icao = icao.toUpperCase().trim();
        if (icao.length !== 4) return;

        var duplicate = false;
        $("#js-hub-tags input[type=hidden]").each(function () {
            if ($(this).val() === icao) { duplicate = true; }
        });
        if (duplicate) return;

        var hidden = $("<input>").attr({type: "hidden", name: "hubIcaos[]"}).val(icao);
        var close  = $("<button>").attr({type: "button", "aria-label": "Remove"})
                        .addClass("btn-close btn-close-white js-remove-hub")
                        .css("font-size", "0.65em");
        var badge  = $("<span>").addClass("badge bg-secondary d-inline-flex align-items-center gap-1 fs-6")
                        .text(icao)
                        .append(hidden)
                        .append(close);

        $("#js-hub-tags").append(badge);
        $("#js-hub-input").val("");
    }

    $("#js-add-hub").on("click", function () {
        addHub($("#js-hub-input").val());
    });

    $("#js-hub-input").on("keydown", function (e) {
        if (e.key === "Enter") { e.preventDefault(); addHub($(this).val()); }
    });

    $(document).on("click", ".js-remove-hub", function () {
        $(this).closest("span.badge").remove();
    });
}());
    ');
    ?>

</div>
