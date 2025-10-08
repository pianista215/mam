<?php
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var string $email */

$this->title = 'Password Reset Email Sent';
?>

<h1><?= Html::encode($this->title) ?></h1>

<p>
    If there is an account associated with <strong><?= Html::encode($email) ?></strong>,
    a link to reset your password has been sent.
</p>

<p>
    Please check your inbox and also your spam folder.
</p>

<p>
    <?= Html::a('Back to Login', ['site/login'], ['class' => 'btn btn-primary']) ?>
</p>
