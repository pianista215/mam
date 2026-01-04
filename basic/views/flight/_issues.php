<?php
use yii\helpers\Html;

/** @var \app\models\FlightReport $report */
?>

<h3><?=Yii::t('app', 'Detected Issues')?></h3>

<?php
$issues = [];
$totalPenalty = 0;
foreach ($report->flightPhases as $phase) {
    foreach ($phase->flightPhaseIssues as $issue) {
        $penalty = $issue->issueType->penalty ?? 0;
        $totalPenalty += $penalty;

        $issues[] = [
            'timestamp' => $issue->timestamp,
            'phase' => $phase->flightPhaseType->lang->name,
            'description' => $issue->description,
            'penalty' => $issue->issueType->penalty ?? '-',
        ];
    }
}
$score = max(0, 100 - $totalPenalty);
?>

<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th><?=Yii::t('app', 'Timestamp')?></th>
            <th><?=Yii::t('app', 'Phase')?></th>
            <th><?=Yii::t('app', 'Description')?></th>
            <th><?=Yii::t('app', 'Penalty')?></th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($issues)): ?>
            <tr>
                <td colspan="4" class="text-center text-muted">
                    <?=Yii::t('app', 'No issues detected for this flight.')?>
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($issues as $issue): ?>
                <tr class="issue-row"
                    data-ts="<?= Html::encode($issue['timestamp']) ?>"
                    style="cursor:pointer">
                    <td><?= Html::encode($issue['timestamp']) ?></td>
                    <td><?= Html::encode($issue['phase']) ?></td>
                    <td><?= Html::encode($issue['description']) ?></td>
                    <td><?= Html::encode($issue['penalty']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        <tr class="table-info">
            <td colspan="3" class="text-end"><strong><?=Yii::t('app', 'Final Score')?></strong></td>
            <td><strong><?= $score ?> / 100</strong></td>
        </tr>
    </tbody>
</table>

<?php
$this->registerJs("
document.querySelectorAll('.issue-row').forEach(row => {
    row.addEventListener('click', () => {
        const ts = row.dataset.ts;
        window.dispatchEvent(new CustomEvent('jumpToTimestamp', {
            detail: { timestamp: ts }
        }));
    });
});
");
?>
