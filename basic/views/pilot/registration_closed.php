<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Pilot $model */

$this->title = 'Registration is closed';
$this->params['breadcrumbs'][] = ['label' => 'Pilots', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="pilot-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <!-- TODO: MAKE THIS MESSAGE CUSTOMIZABLE-->
    <p>At this moment the period of registration is closed. Estimated next time will be bla bla bla bla. Thank you.</p>

</div>
