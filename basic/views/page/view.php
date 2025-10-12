<?php
/** @var yii\web\View $this */
/** @var app\models\Page $page */
/** @var string $title */
/** @var string $content */

$this->title = $title;
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="page-content">
    <h1><?= $this->title ?></h1>
    <div>
        <?= $content ?>
    </div>
</div>
