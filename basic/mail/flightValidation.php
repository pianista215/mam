<?php
use yii\helpers\Html;

/** @var string $pilotName */
/** @var string $flightCode */
/** @var string $flightDate */
/** @var string $departure */
/** @var string $arrival */
/** @var string|null $comments */
/** @var bool $isRejected */
?>
<p><?= Yii::t('app', 'Hello {name},', ['name' => Html::encode($pilotName)]) ?></p>

<?php if ($isRejected): ?>
<p><?= Yii::t('app', 'Your flight {code} of {date} ({departure} → {arrival}) has been rejected.', [
    'code' => Html::encode($flightCode),
    'date' => Html::encode($flightDate),
    'departure' => Html::encode($departure),
    'arrival' => Html::encode($arrival),
]) ?></p>
<?php else: ?>
<p><?= Yii::t('app', 'Your flight {code} of {date} ({departure} → {arrival}) has been validated. The validator has left the following comments:', [
    'code' => Html::encode($flightCode),
    'date' => Html::encode($flightDate),
    'departure' => Html::encode($departure),
    'arrival' => Html::encode($arrival),
]) ?></p>
<?php endif; ?>

<?php if (!empty($comments)): ?>
<blockquote style="border-left: 4px solid #dee2e6; margin: 10px 0; padding: 10px 20px; color: #495057;">
    <?= nl2br(Html::encode($comments)) ?>
</blockquote>
<?php endif; ?>

<p><?= Yii::t('app', 'If you have any questions, please contact the operations team.') ?></p>

<p><?= Yii::t('app', 'Best regards') ?></p>
