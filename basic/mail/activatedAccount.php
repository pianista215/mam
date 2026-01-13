<?php
use yii\helpers\Html;

/** @var string $license */
/** @var string $name */
?>
<p>Hello <?= Html::encode($name) ?>,</p>

<p>We are pleased to inform you that your account has been successfully activated.</p>

<p><strong>Pilot license:</strong> <?= Html::encode($license) ?></p>

<p>You can now log in and start using the platform.</p>

<p>If you have any questions or need assistance, feel free to reply to this email and our support team will be happy to help you.</p>

<p>Welcome aboard!</p>