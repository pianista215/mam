<?php

use app\helpers\ImageMam;
use app\models\Flight;
use app\models\Image;
use app\rbac\constants\Permissions;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var app\models\Flight $model */
/** @var array|null $loadSheet */
/** @var array $eventMarkers */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Flights'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="flight-view">

    <div class="container mb-3">
        <div class="row">
            <div class="col-md-12">
                <div class="p-3 border rounded bg-light-subtle d-flex justify-content-between align-items-center">
                    <?php if ($model->flight_type === Flight::TYPE_ASSIGNMENT && $model->client !== null): ?>
                    <div class="d-flex align-items-center gap-3">
                        <?= ImageMam::render(Image::TYPE_CLIENT_LOGO, $model->client_id, 0, [
                            'alt' => $model->client->name,
                            'style' => 'width:150px; height:75px;',
                        ]) ?>
                        <div>
                            <div class="fw-semibold fs-5 text-dark"><?= Yii::t('app', 'Assignment') ?></div>
                            <div class="text-muted">
                                <?= Html::encode($model->client->name) ?>
                                (<?= Html::encode($model->departure) ?>-<?= Html::encode($model->arrival) ?>)
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="fw-semibold fs-5 text-dark">
                        <?php if (!empty($model->tourStage)): ?>
                        <?= Html::encode($model->tourStage->fplDescription) ?>
                        <?php elseif($model->flight_type === Flight::TYPE_CHARTER): ?>
                        <?= Yii::t('app', 'Charter flight') . ' '.' ('.$model->departure.'-'.$model->arrival.')' ?>
                        <?php else: ?>
                        <?= Html::encode(Yii::t('app', 'Route').' '.$model->code) ?>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php if (Yii::$app->user->can(Permissions::DELETE_OWN_FLIGHT, ['flight' => $model])): ?>
                    <?= Html::a(Yii::t('app', 'Delete flight'), ['delete', 'id' => $model->id], [
                        'class' => 'btn btn-danger',
                        'data' => [
                            'confirm' => Yii::t('app', 'Are you sure you want to delete this flight? Once deleted, it cannot be recovered.'),
                            'method' => 'post',
                        ],
                    ]) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <h3><?=Yii::t('app', 'Flight plan')?></h1>

    <?= $this->render('_flight_plan', [
        'model'     => $model,
        'aircraft'  => $model->getAircraft()->one(),
        'pilotName' => $model->pilot->fullname,
    ]) ?>

    <?php if ($loadSheet !== null): ?>
    <?= $this->render('@app/views/partials/_load_sheet', [
        'loadSheet' => $loadSheet,
    ]) ?>
    <?php endif; ?>

    <h3 class="mt-4"><?=Yii::t('app', 'Flight data')?></h3>

    <?= $this->render('_flight_data', [
        'model' => $model,
        'aircraft' => $model->getAircraft()->one(),
        'pilotName' => $model->pilot->fullname,
    ]) ?>

    <?php if ($model->isPendingValidation() || $model->isValidated()): ?>
        <?= $this->render('_issues', [
            'report' => $model->flightReport,
        ]) ?>
    <?php endif; ?>

    <?php if ($model->hasAcarsInfo()): ?>
        <?php if (Yii::$app->user->can(Permissions::DOWNLOAD_FLIGHT_KML, ['flight' => $model])): ?>
        <div class="d-flex justify-content-end mt-4 mb-2">
            <button type="button" id="kml-download" class="btn btn-outline-primary"
                    data-url="<?= Html::encode(Url::to(['kml', 'id' => $model->id])) ?>">
                <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                <span class="kml-label"><?= Yii::t('app', 'Download KML (Google Earth)') ?></span>
            </button>
        </div>
        <?php
        $kmlTexts = Json::htmlEncode([
            'idle' => Yii::t('app', 'Download KML (Google Earth)'),
            'busy' => Yii::t('app', 'Generating KML...'),
            'error' => Yii::t('app', 'Could not generate the KML file'),
        ]);
        $this->registerJs(<<<JS
(function () {
    const btn = document.getElementById('kml-download');
    if (!btn) return;
    const texts = $kmlTexts;
    const spinner = btn.querySelector('.spinner-border');
    const label = btn.querySelector('.kml-label');
    const setBusy = function (busy) {
        btn.disabled = busy;
        spinner.classList.toggle('d-none', !busy);
        label.textContent = busy ? texts.busy : texts.idle;
    };
    btn.addEventListener('click', async function () {
        setBusy(true);
        try {
            const response = await fetch(btn.dataset.url, {credentials: 'same-origin'});
            if (!response.ok) throw new Error(response.status);
            const disposition = response.headers.get('Content-Disposition') || '';
            const match = disposition.match(/filename="?([^";]+)"?/);
            const filename = match ? match[1] : 'flight.kml';
            const blob = await response.blob();
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            a.remove();
            setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
        } catch (e) {
            alert(texts.error);
        } finally {
            setBusy(false);
        }
    });
})();
JS);
        ?>
        <?php endif; ?>
        <?= $this->render('_map_altitude', [
            'report' => $model->flightReport,
            'eventMarkers' => $eventMarkers ?? [],
        ]) ?>
    <?php endif; ?>

    <?php if ($model->isPendingValidation() && Yii::$app->user->can(Permissions::VALIDATE_FLIGHT, ['flight' => $model])): ?>
        <?= $this->render('_validation_form', [
            'model' => $model,
            'validatorList' => $validatorList ?? [],
        ]) ?>
    <?php elseif ($model->isValidated()): ?>
        <?= $this->render('_validation_view', [
            'model' => $model,
        ]) ?>
    <?php endif; ?>

</div>
