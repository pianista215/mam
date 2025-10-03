<?php
use yii\helpers\Html;

/** @var \app\models\FlightReport $report */
?>

<h3>Detected Issues</h3>

<?php
$issues = [];
$totalPenalty = 0;
foreach ($report->flightPhases as $phase) {
    foreach ($phase->flightPhaseIssues as $issue) {
        $penalty = $issue->issueType->penalty ?? 0;
        $totalPenalty += $penalty;
        $issues[] = [
            'timestamp' => $issue->timestamp,
            'phase' => $phase->flightPhaseType->name ?? '-',
            'description' => $issue->issueType->description ?? '-',
            'penalty' => $issue->issueType->penalty ?? '-',
        ];
    }
}
$score = max(0, 100 - $totalPenalty);
?>

<?php if (empty($issues)): ?>
    <p>No issues detected for this flight.</p>
<?php else: ?>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Timestamp</th>
                <th>Phase</th>
                <th>Description</th>
                <th>Penalty</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($issues as $issue): ?>
                <tr>
                    <td><?= Yii::$app->formatter->asDatetime($issue['timestamp']) ?></td>
                    <td><?= Html::encode($issue['phase']) ?></td>
                    <td><?= Html::encode($issue['description']) ?></td>
                    <td><?= Html::encode($issue['penalty']) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="table-info">
                <td colspan="3" class="text-end"><strong>Final Score</strong></td>
                <td><strong><?= $score ?> / 100</strong></td>
            </tr>
        </tbody>
    </table>
<?php endif; ?>
