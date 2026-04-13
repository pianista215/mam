<?php

use app\models\CredentialType;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\CredentialType $model */
/** @var array $credentialTypes             id => label map of available credential types */
/** @var array $aircraftTypes               id => name map of available aircraft types */
/** @var string[] $restrictionAirports      ICAO codes of restricted airports */
/** @var int[] $restrictionAircraftTypeIds  aircraft type IDs involved in restrictions */
/** @var yii\widgets\ActiveForm $form */
?>
<div class="credential-type-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'code')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'type')->dropDownList(
        CredentialType::typeLabels(),
        ['prompt' => '']
    ) ?>

    <?= $form->field($model, 'description')->textarea(['rows' => 4]) ?>

    <hr>

    <?= $form->field($model, 'prerequisiteIds')->checkboxList(
        $credentialTypes,
        ['separator' => '<br>']
    )->label(Yii::t('app', 'Prerequisites')) ?>

    <hr>

    <?= $form->field($model, 'aircraftTypeIds')->checkboxList(
        $aircraftTypes,
        ['separator' => '<br>']
    )->label(Yii::t('app', 'Unlocked Aircraft Types')) ?>

    <hr>

    <div class="mb-3">
        <label class="form-label fw-semibold"><?= Yii::t('app', 'Airport–Aircraft Restrictions') ?></label>
        <p class="text-muted small mb-2"><?= Yii::t('app', 'Aircraft types that require this credential to fly to specific airports. All selected aircraft × all selected airports generate restrictions. If no restriction is defined for a (aircraft, airport) pair, access is free.') ?></p>

        <label class="form-label"><?= Yii::t('app', 'Restricted Airports') ?></label>
        <div id="js-airport-tags" class="d-flex flex-wrap gap-1 mb-2 p-2 border rounded" style="min-height:2.5rem">
            <?php foreach ($restrictionAirports as $icao): ?>
            <span class="badge bg-secondary d-inline-flex align-items-center gap-1 fs-6">
                <?= Html::encode($icao) ?>
                <?= Html::hiddenInput('airportIcaos[]', $icao) ?>
                <button type="button" class="btn-close btn-close-white js-remove-airport" style="font-size:0.65em" aria-label="Remove"></button>
            </span>
            <?php endforeach; ?>
        </div>
        <div class="input-group mb-3" style="max-width:280px">
            <input id="js-icao-input" type="text" class="form-control" maxlength="4"
                   placeholder="VQPR" style="text-transform:uppercase">
            <button type="button" id="js-add-airport" class="btn btn-outline-secondary">
                + <?= Yii::t('app', 'Add') ?>
            </button>
        </div>

        <label class="form-label"><?= Yii::t('app', 'Aircraft types requiring this credential at above airports') ?></label>
        <?php foreach ($aircraftTypes as $atId => $atName): ?>
        <div class="form-check">
            <input class="form-check-input" type="checkbox"
                   name="restrictionAircraftTypeIds[]"
                   value="<?= Html::encode($atId) ?>"
                   id="rat_<?= $atId ?>"
                   <?= in_array($atId, $restrictionAircraftTypeIds) ? 'checked' : '' ?>>
            <label class="form-check-label" for="rat_<?= $atId ?>"><?= Html::encode($atName) ?></label>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="form-group mt-3">
        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

    <?php
    $this->registerJs('
(function () {
    function addAirport(icao) {
        icao = icao.toUpperCase().trim();
        if (icao.length !== 4) return;

        var duplicate = false;
        $("#js-airport-tags input[type=hidden]").each(function () {
            if ($(this).val() === icao) { duplicate = true; }
        });
        if (duplicate) return;

        var hidden = $("<input>").attr({type: "hidden", name: "airportIcaos[]"}).val(icao);
        var close  = $("<button>").attr({type: "button", "aria-label": "Remove"})
                        .addClass("btn-close btn-close-white js-remove-airport")
                        .css("font-size", "0.65em");
        var badge  = $("<span>").addClass("badge bg-secondary d-inline-flex align-items-center gap-1 fs-6")
                        .text(icao)
                        .append(hidden)
                        .append(close);

        $("#js-airport-tags").append(badge);
        $("#js-icao-input").val("");
    }

    $("#js-add-airport").on("click", function () {
        addAirport($("#js-icao-input").val());
    });

    $("#js-icao-input").on("keydown", function (e) {
        if (e.key === "Enter") { e.preventDefault(); addAirport($(this).val()); }
    });

    $(document).on("click", ".js-remove-airport", function () {
        $(this).closest("span.badge").remove();
    });
}());
    ');
    ?>

</div>
