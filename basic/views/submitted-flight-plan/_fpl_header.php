<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var object $entity */
/** @var string $title */

?>
    <div class="container mb-3">
        <div class="row">
            <div class="col-md-12">
                <div class="p-3 border rounded bg-light-subtle">
                    <div class="fw-semibold fs-5 text-dark">
                        <?= Html::encode($entity->fplDescription) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>