<?php
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var string $email */

$this->title = Yii::t('app', 'Password Reset Email Sent');
?>

<h1><?= Html::encode($this->title) ?></h1>

<p>
    <?=Yii::t('app', 'If there is an account associated with')?>
     <strong><?= Html::encode($email) ?></strong>,
    <?=Yii::t('app', 'a link to reset your password has been sent.')?>
</p>

<p>
    <?=Yii::t('app', 'Please check your inbox and also your spam folder.')?>
</p>

<p>
    <?= Html::a(Yii::t('app', 'Back to Login'), ['site/login'], ['class' => 'btn btn-primary']) ?>
</p>
