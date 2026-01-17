<?php

use app\models\Page;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var string $title */
$this->title = $title;

$this->registerCssFile('https://unpkg.com/easymde@2.20.0/dist/easymde.min.css');
$this->registerJsFile('https://unpkg.com/easymde@2.20.0/dist/easymde.min.js', ['depends' => \yii\web\JqueryAsset::class]);
?>

<h1><?= Html::encode($this->title) ?></h1>

<?php $form = ActiveForm::begin(); ?>

<?php if ($page->type === Page::TYPE_SITE): ?>
    <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>
<?php endif; ?>

<?= $form->field($model, 'content_md')->textarea(['id' => 'editor', 'rows' => 20]) ?>

<div class="form-group">
    <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
</div>

<?php ActiveForm::end(); ?>

<script>
document.addEventListener("DOMContentLoaded", function () {

    const textarea = document.getElementById("editor");

    if (!textarea) return;

    if (textarea.dataset.easymdeInitialized) {
        return;
    }

    textarea.dataset.easymdeInitialized = "1";

    const easyMDE = new EasyMDE({
        element: textarea,
        spellChecker: false,
        sideBySideFullscreen: false,
        autofocus: false,
        status: false,
    });

    easyMDE.value(textarea.value);
    easyMDE.toggleSideBySide();
});

</script>
