<?php

use yii\helpers\Markdown;

/** @var yii\web\View $this */
/** @var app\models\PageContent $content */

$this->title = $content->title;
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="registration-closed">
    <div class="page-content">
        <h1><?= $this->title ?></h1>
        <?= Markdown::process($content->content_md, 'gfm') ?>
    </div>
</div>
