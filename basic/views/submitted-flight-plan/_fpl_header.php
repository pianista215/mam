<?php

use app\helpers\ImageMam;
use app\models\Assignment;
use app\models\Image;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var object $entity */
/** @var string $title */

?>
    <div class="container mb-3">
        <div class="row">
            <div class="col-md-12">
                <?php if ($entity instanceof Assignment): ?>
                <div class="p-3 border rounded bg-light-subtle d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-semibold fs-5 text-dark"><?= Yii::t('app', 'Assignment') ?></div>
                        <div class="text-muted">
                            <?= Html::encode($entity->client->name) ?>
                            (<?= Html::encode($entity->departure) ?>-<?= Html::encode($entity->arrival) ?>)
                        </div>
                    </div>
                    <?= ImageMam::render(Image::TYPE_CLIENT_LOGO, $entity->client_id, 0, [
                        'alt' => $entity->client->name,
                        'style' => 'width:150px; height:75px;',
                    ]) ?>
                </div>
                <?php else: ?>
                <div class="p-3 border rounded bg-light-subtle">
                    <div class="fw-semibold fs-5 text-dark">
                        <?= Html::encode($entity->fplDescription) ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
