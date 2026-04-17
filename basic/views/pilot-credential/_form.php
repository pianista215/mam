<?php

use app\models\PilotCredential;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\PilotCredential $model */
/** @var array $credentialTypes  id => label map, only present on issue */
/** @var bool $showTypeField */
/** @var bool $isRenew  true when called from renew.php */

$isRenew        = $isRenew ?? false;
$lockIssuedDate = $isRenew && $model->isActive();
?>
<div class="pilot-credential-form">
    <?php $form = ActiveForm::begin(); ?>

    <?php if (!empty($showTypeField)): ?>
        <?= $form->field($model, 'credential_type_id')->dropDownList(
            $credentialTypes ?? [],
            ['prompt' => Yii::t('app', 'Select credential type...')]
        ) ?>
    <?php endif; ?>

    <?= $form->field($model, 'status')->radioList(PilotCredential::statusLabels()) ?>

    <?php if ($lockIssuedDate): ?>
        <div class="mb-3">
            <label class="form-label"
                   id="label-issued-date"
                   data-label-active="<?= Html::encode(Yii::t('app', 'Issued Date')) ?>"
                   data-label-student="<?= Html::encode(Yii::t('app', 'Training Start Date')) ?>">
                <?= Html::encode(Yii::t('app', 'Issued Date')) ?>
            </label>
            <p class="form-control-plaintext"><?= Html::encode($model->issued_date) ?></p>
            <?= Html::hiddenInput('PilotCredential[issued_date]', $model->issued_date) ?>
        </div>
    <?php else: ?>
        <?= $form->field($model, 'issued_date', [
            'labelOptions' => [
                'id'                 => 'label-issued-date',
                'data-label-active'  => Yii::t('app', 'Issued Date'),
                'data-label-student' => Yii::t('app', 'Training Start Date'),
            ],
        ])->input('date', ['value' => $model->issued_date ?: date('Y-m-d')]) ?>
    <?php endif; ?>

    <?= $form->field($model, 'expiry_date', [
        'labelOptions' => [
            'id'                 => 'label-expiry-date',
            'data-label-active'  => Yii::t('app', 'Expiry Date'),
            'data-label-student' => Yii::t('app', 'Training End Date'),
        ],
    ])->input('date')->hint(Yii::t('app', 'Leave empty if the credential does not expire.')) ?>

    <?= Html::hiddenInput('PilotCredential[pilot_id]', $model->pilot_id) ?>
    <?= Html::hiddenInput('PilotCredential[issued_by]', Yii::$app->user->id) ?>

    <div class="form-group mt-3">
        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php
    $statusStudentVal = PilotCredential::STATUS_STUDENT;
    $this->registerJs(<<<JS
(function () {
    var STATUS_STUDENT = {$statusStudentVal};
    function updateLabels(val) {
        var isStudent = (parseInt(val, 10) === STATUS_STUDENT);
        ['#label-issued-date', '#label-expiry-date'].forEach(function (sel) {
            var el = document.querySelector(sel);
            if (!el) return;
            el.textContent = isStudent ? el.dataset.labelStudent : el.dataset.labelActive;
        });
    }
    var checked = document.querySelector('input[name="PilotCredential[status]"]:checked');
    if (checked) { updateLabels(checked.value); }
    document.querySelectorAll('input[name="PilotCredential[status]"]').forEach(function (r) {
        r.addEventListener('change', function () { updateLabels(this.value); });
    });
}());
JS
    ); ?>

    <?php ActiveForm::end(); ?>
</div>
