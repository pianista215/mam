<?php

use app\models\Image;
use app\models\Page;
use app\rbac\constants\Permissions;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\Page $page */
/** @var app\models\PageContent $model */
/** @var string $language */
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

<hr>

<h3><?= Yii::t('app', 'Page Images') ?></h3>

<div class="row">
    <?php foreach ($page->images as $image): ?>
        <div class="col-md-3 col-sm-4 col-6 mb-3">
            <div class="card">
                <img src="<?= Html::encode($image->getUrl()) ?>" class="card-img-top" alt="Image <?= $image->element ?>">
                <div class="card-body p-2">
                    <div class="btn-group btn-group-sm w-100" role="group">
                        <?= Html::a(
                            '<i class="fas fa-copy"></i>',
                            '#',
                            [
                                'class' => 'btn btn-outline-secondary copy-url-btn',
                                'title' => Yii::t('app', 'Copy URL'),
                                'data-url' => $image->getUrl(),
                            ]
                        ) ?>
                        <?= Html::a(
                            '<i class="fas fa-edit"></i>',
                            Url::to([
                                'image/upload',
                                'type' => $image->type,
                                'related_id' => $image->related_id,
                                'element' => $image->element,
                                'fromEditor' => true,
                            ]),
                            [
                                'class' => 'btn btn-outline-primary',
                                'title' => Yii::t('app', 'Edit'),
                            ]
                        ) ?>
                        <?php if (Yii::$app->user->can(Permissions::IMAGE_CRUD)): ?>
                            <?= Html::a(
                                '<i class="fas fa-trash"></i>',
                                Url::to([
                                    'image/delete',
                                    'id' => $image->id,
                                    'fromEditor' => true,
                                ]),
                                [
                                    'class' => 'btn btn-outline-danger',
                                    'title' => Yii::t('app', 'Delete'),
                                    'data-method' => 'post',
                                    'data-confirm' => Yii::t('app', 'Are you sure you want to delete this image? This action will not check page contents for references. If this image is used in any page, it may display incorrectly.'),
                                ]
                            ) ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="col-md-3 col-sm-4 col-6 mb-3">
        <div class="card h-100 d-flex justify-content-center align-items-center" style="min-height: 150px; border-style: dashed;">
            <?= Html::a(
                '<i class="fas fa-plus fa-2x"></i><br>' . Yii::t('app', 'Add Image'),
                Url::to([
                    'image/upload',
                    'type' => Image::TYPE_PAGE_IMAGE,
                    'related_id' => $page->id,
                    'element' => $page->getNextImageElement(),
                    'fromEditor' => true,
                ]),
                ['class' => 'text-center text-muted text-decoration-none p-3']
            ) ?>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll('.copy-url-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.dataset.url;
            const fullUrl = window.location.origin + url;
            navigator.clipboard.writeText(fullUrl).then(function() {
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i>';
                setTimeout(function() {
                    btn.innerHTML = originalHtml;
                }, 1500);
            });
        });
    });
});
</script>

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
