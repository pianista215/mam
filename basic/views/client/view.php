<?php

use app\helpers\ImageMam;
use app\models\Image;
use app\rbac\constants\Permissions;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Client $model */
/** @var app\models\Airport|null $headquarters */
/** @var app\models\Airport[] $hubs */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Clients'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);

$this->registerCss(<<<CSS
.client-hero {
    position: relative;
    padding: 2.5rem 1rem 1.75rem;
    text-align: center;
}
.client-hero-actions {
    position: absolute;
    top: .75rem;
    right: .75rem;
}
.client-hero-logo {
    display: inline-block;
    padding: 1rem;
    background: #fff;
    border-radius: .5rem;
    box-shadow: 0 1px 4px rgba(0, 0, 0, .08);
}
.client-hero-name {
    margin: 1.25rem 0 .25rem;
    font-size: .9rem;
    font-weight: 600;
    letter-spacing: .12em;
    text-transform: uppercase;
    color: var(--bs-secondary-color, #6c757d);
}
.client-hero-hq {
    font-size: .85rem;
    color: var(--bs-secondary-color, #6c757d);
}
@media (max-width: 575.98px) {
    .client-hero { padding-top: 3.5rem; }
}
CSS);
?>
<div class="client-view container py-3">

    <div class="card shadow-sm mb-4">
        <div class="client-hero bg-body-tertiary rounded">

            <?php if (Yii::$app->user->can(Permissions::CLIENT_CRUD)): ?>
                <div class="client-hero-actions">
                    <?= Html::a(Yii::t('app', 'Update'), ['update', 'id' => $model->id], ['class' => 'btn btn-sm btn-outline-primary me-1']) ?>
                    <?= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->id], [
                        'class' => 'btn btn-sm btn-outline-danger',
                        'data' => [
                            'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                            'method' => 'post',
                        ],
                    ]) ?>
                </div>
            <?php endif; ?>

            <div class="client-hero-logo">
                <?= ImageMam::render(Image::TYPE_CLIENT_LOGO, $model->id, 0, ['alt' => $model->name]) ?>
            </div>

            <h1 class="client-hero-name"><?= Html::encode($model->name) ?></h1>

            <div class="client-hero-hq">
                <?= Yii::t('app', 'Headquarters') ?>:
                <?php if ($headquarters !== null): ?>
                    <?= Html::a(
                        Html::encode($headquarters->icao_code . ' - ' . $headquarters->name),
                        ['airport/view', 'id' => $headquarters->id],
                        ['class' => 'link-secondary text-decoration-none fw-semibold']
                    ) ?>
                <?php else: ?>
                    <?= Html::encode($model->headquarters_icao) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <h5 class="mb-3"><?= Yii::t('app', 'Hubs') ?></h5>
    <?php if (empty($hubs)): ?>
        <p class="text-muted fst-italic"><?= Yii::t('app', 'This client has no hubs.') ?></p>
    <?php else: ?>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($hubs as $hub): ?>
                <?= Html::a(
                    Html::encode($hub->icao_code . ' - ' . $hub->name),
                    ['airport/view', 'id' => $hub->id],
                    ['class' => 'btn btn-sm btn-outline-secondary']
                ) ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>
