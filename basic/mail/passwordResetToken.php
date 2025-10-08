<?php
use yii\helpers\Html;

/** @var string $name */
/** @var string $token */

// construct the absolute link to the reset-password endpoint
$resetLink = Yii::$app->urlManager->createAbsoluteUrl([
    'pilot/reset-password',
    'token' => $token,
]);
?>
<p>Hello <?= Html::encode($name) ?>,</p>

<p>You have requested to reset your password. To proceed, please click the following link:</p>

<p><?= Html::a('Reset Password', $resetLink) ?></p>

<p>Or copy and paste this URL into your browser:</p>

<p><code><?= Html::encode($resetLink) ?></code></p>

<p>If you did not request this change, simply ignore this email.</p>
