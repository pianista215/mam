<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Pilot $model */

$this->title = 'Thank you for your registration';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="pilot-thankyou-registration">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>The admin has to validate your registration before you can access the company.</p>
    <p>You will receive an email of confirmation when the validation is finished.</p>

</div>
