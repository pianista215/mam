<?php

use app\models\PilotCredential;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\PilotCredential $model */
/** @var array $credentialTypes  id => label map, only present on issue */
/** @var bool $showTypeField */
/** @var bool $isRenew  true when called from renew.php */

$isRenew            = $isRenew ?? false;
$lockIssuedDate     = $isRenew && $model->isActive();
$studentOnlyTypeIds = $studentOnlyTypeIds ?? [];
?>
<div class="pilot-credential-form">
    <?php $form = ActiveForm::begin(); ?>

    <?php if (!empty($showTypeField)): ?>
        <?= $form->field($model, 'credential_type_id')->dropDownList(
            $credentialTypes ?? [],
            ['prompt' => Yii::t('app', 'Select credential type...')]
        ) ?>
        <?php if (!empty($studentOnlyTypeIds)): ?>
        <div id="student-only-notice" class="alert alert-warning py-2" style="display:none">
            <?= Html::encode(Yii::t('app', 'This credential can only be issued as Student because all prerequisites are held as Student.')) ?>
        </div>
        <?php endif; ?>
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

    <?php
    $noExpiry    = ($model->expiry_date === null && !$model->isNewRecord);
    $expiryValue = $model->expiry_date ?: date('Y-12-31');
    ?>
    <div class="mb-3">
        <label class="form-label"
               id="label-expiry-date"
               data-label-active="<?= Html::encode(Yii::t('app', 'Expiry Date')) ?>"
               data-label-student="<?= Html::encode(Yii::t('app', 'Training End Date')) ?>">
            <?= Html::encode(Yii::t('app', 'Expiry Date')) ?>
        </label>
        <div class="form-check mb-2">
            <input type="checkbox" id="no-expiry-checkbox" class="form-check-input"
                   <?= $noExpiry ? 'checked' : '' ?>>
            <label class="form-check-label" for="no-expiry-checkbox">
                <?= Html::encode(Yii::t('app', 'Does not expire')) ?>
            </label>
        </div>
        <div id="expiry-date-wrapper"<?= $noExpiry ? ' style="display:none"' : '' ?>>
            <input type="date"
                   id="pilotcredential-expiry_date"
                   name="PilotCredential[expiry_date]"
                   value="<?= Html::encode($expiryValue) ?>"
                   class="form-control<?= $model->hasErrors('expiry_date') ? ' is-invalid' : '' ?>"
                   <?= $noExpiry ? 'disabled' : '' ?>>
        </div>
        <input type="hidden" id="expiry-date-clear" name="PilotCredential[expiry_date]" value=""
               <?= $noExpiry ? '' : 'disabled' ?>>
        <?php if ($model->hasErrors('expiry_date')): ?>
            <div class="invalid-feedback d-block">
                <?= Html::encode(implode(', ', $model->getErrors('expiry_date'))) ?>
            </div>
        <?php endif; ?>
    </div>

    <?= Html::hiddenInput('PilotCredential[pilot_id]', $model->pilot_id) ?>
    <?= Html::hiddenInput('PilotCredential[issued_by]', Yii::$app->user->id) ?>

    <div class="form-group mt-3">
        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php
    $statusStudentVal   = PilotCredential::STATUS_STUDENT;
    $statusActiveVal    = PilotCredential::STATUS_ACTIVE;
    $studentOnlyJson    = json_encode(array_values($studentOnlyTypeIds));
    $this->registerJs(<<<JS
(function () {
    var STATUS_STUDENT = {$statusStudentVal};
    var STATUS_ACTIVE  = {$statusActiveVal};
    var STUDENT_ONLY   = {$studentOnlyJson};
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

    var noExpiryCb  = document.getElementById('no-expiry-checkbox');
    var expiryWrap  = document.getElementById('expiry-date-wrapper');
    var expiryInput = expiryWrap ? expiryWrap.querySelector('input[type="date"]') : null;
    var expiryClear = document.getElementById('expiry-date-clear');
    function syncNoExpiry() {
        var off = noExpiryCb && noExpiryCb.checked;
        if (expiryWrap)  expiryWrap.style.display = off ? 'none' : '';
        if (expiryInput) expiryInput.disabled      = !!off;
        if (expiryClear) expiryClear.disabled       = !off;
    }
    if (noExpiryCb) {
        noExpiryCb.addEventListener('change', syncNoExpiry);
        syncNoExpiry();
    }

    var typeSelect   = document.getElementById('pilotcredential-credential_type_id');
    var radioActive  = document.querySelector('input[name="PilotCredential[status]"][value="' + STATUS_ACTIVE + '"]');
    var radioStudent = document.querySelector('input[name="PilotCredential[status]"][value="' + STATUS_STUDENT + '"]');
    var studentNotice = document.getElementById('student-only-notice');
    function syncStudentOnly(typeId) {
        var isStudentOnly = STUDENT_ONLY.indexOf(parseInt(typeId, 10)) !== -1;
        if (radioActive)   radioActive.disabled = isStudentOnly;
        if (isStudentOnly && radioStudent) { radioStudent.checked = true; updateLabels(STATUS_STUDENT); }
        if (studentNotice) studentNotice.style.display = isStudentOnly ? '' : 'none';
    }
    if (typeSelect) {
        typeSelect.addEventListener('change', function () { syncStudentOnly(this.value); });
        syncStudentOnly(typeSelect.value);
    }
}());
JS
    ); ?>

    <?php ActiveForm::end(); ?>
</div>
