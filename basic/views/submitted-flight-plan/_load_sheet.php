<?php

use yii\helpers\Html;

/**
 * @var yii\web\View  $this
 * @var int           $paxAdults
 * @var int           $paxChildren
 * @var int           $cargoBags
 * @var int           $cargoPaidKg
 * @var int           $crew
 * @var int           $adultW
 * @var int           $childW
 * @var int           $bagW
 */

$crewKg      = $crew * $adultW;
$adultsKg    = $paxAdults * $adultW;
$childrenKg  = $paxChildren * $childW;
$paxTotal    = $paxAdults + $paxChildren;
$paxKg       = $adultsKg + $childrenKg;
$bagsKg      = $cargoBags * $bagW;
$cargoKg     = $bagsKg + $cargoPaidKg;
$totalPayload = $crewKg + $paxKg + $cargoKg;
$pob         = $paxTotal + $crew;

$fmt = fn(int $n) => number_format($n, 0, '.', ',') . ' Kg';
?>
<div class="card mt-3">
    <div class="card-header text-white text-center fw-bold py-2" style="background-color: var(--brand);">
        <?= Html::encode(Yii::t('app', 'Load Sheet')) ?>
    </div>
    <div class="card-body p-0">
        <table class="table table-bordered table-sm mb-0">
            <thead class="text-white text-center small text-uppercase" style="background-color: var(--brand-dark);">

                <tr>
                    <th style="width:46%"><?= Yii::t('app', 'Item') ?></th>
                    <th style="width:14%" class="text-center"><?= Yii::t('app', 'Count') ?></th>
                    <th style="width:20%" class="text-end"><?= Yii::t('app', 'Unit weight') ?></th>
                    <th style="width:20%" class="text-end"><?= Yii::t('app', 'Total') ?></th>
                </tr>
            </thead>
            <tbody class="font-monospace small">

                <tr class="table-secondary">
                    <td colspan="4" class="fw-semibold text-uppercase py-1 px-2" style="border-top: 2px solid #6c757d;"><?= Yii::t('app', 'Crew') ?></td>
                </tr>
                <tr>
                    <td class="ps-3"><?= Yii::t('app', 'Crew') ?></td>
                    <td class="text-center"><?= $crew ?></td>
                    <td class="text-end"><?= $adultW ?> Kg</td>
                    <td class="text-end fw-semibold"><?= $fmt($crewKg) ?></td>
                </tr>

                <tr class="table-secondary">
                    <td colspan="4" class="fw-semibold text-uppercase py-1 px-2" style="border-top: 2px solid #6c757d;"><?= Yii::t('app', 'Passengers') ?></td>
                </tr>
                <tr>
                    <td class="ps-3"><?= Yii::t('app', 'Adults') ?></td>
                    <td class="text-center"><?= $paxAdults ?></td>
                    <td class="text-end"><?= $adultW ?> Kg</td>
                    <td class="text-end"><?= $fmt($adultsKg) ?></td>
                </tr>
                <tr>
                    <td class="ps-3"><?= Yii::t('app', 'Children') ?></td>
                    <td class="text-center"><?= $paxChildren ?></td>
                    <td class="text-end"><?= $childW ?> Kg</td>
                    <td class="text-end"><?= $fmt($childrenKg) ?></td>
                </tr>
                <tr class="fw-bold" style="--bs-table-bg: #e9ecef;">
                    <td><?= Yii::t('app', 'Total PAX') ?></td>
                    <td class="text-center"><?= $paxTotal ?></td>
                    <td class="text-end"></td>
                    <td class="text-end"><?= $fmt($paxKg) ?></td>
                </tr>

                <tr class="table-secondary">
                    <td colspan="4" class="fw-semibold text-uppercase py-1 px-2" style="border-top: 2px solid #6c757d;"><?= Yii::t('app', 'Hold / Cargo') ?></td>
                </tr>
                <tr>
                    <td class="ps-3"><?= Yii::t('app', 'Checked bags') ?></td>
                    <td class="text-center"><?= $cargoBags ?></td>
                    <td class="text-end"><?= $bagW ?> Kg</td>
                    <td class="text-end"><?= $fmt($bagsKg) ?></td>
                </tr>
                <tr>
                    <td class="ps-3"><?= Yii::t('app', 'Paid cargo') ?></td>
                    <td class="text-center">—</td>
                    <td class="text-end">—</td>
                    <td class="text-end"><?= $fmt($cargoPaidKg) ?></td>
                </tr>
                <tr class="fw-bold" style="--bs-table-bg: #e9ecef;">
                    <td><?= Yii::t('app', 'Total hold') ?></td>
                    <td class="text-center"></td>
                    <td class="text-end"></td>
                    <td class="text-end"><?= $fmt($cargoKg) ?></td>
                </tr>

                <tr class="fw-bold" style="--bs-table-bg: var(--brand); --bs-table-color: #fff;">
                    <td><?= Yii::t('app', 'Payload') ?></td>
                    <td class="text-center fw-normal"><?= $pob ?> <?= Yii::t('app', 'POB') ?></td>
                    <td class="text-end"></td>
                    <td class="text-end"><?= $fmt($totalPayload) ?></td>
                </tr>

            </tbody>
        </table>
    </div>
</div>
