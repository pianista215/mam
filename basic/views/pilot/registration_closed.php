<?php

use app\helpers\PageContentMam;
use app\models\Page;

/** @var yii\web\View $this */

$this->title = Yii::t('app', 'Registration Closed');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="registration-closed">
    <div class="page-content">
        <h1><?= $this->title ?></h1>
        <?= PageContentMam::render('registration_closed', Page::TYPE_COMPONENT) ?>
    </div>
</div>
