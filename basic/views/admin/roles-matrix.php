<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $users app\models\Pilot[] */
/* @var $roles array */
/* @var $matrix array */

$this->title = Yii::t('app', 'Role assignment');
?>

<h1><?= Html::encode($this->title) ?></h1>

<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th><?=Yii::t('app', 'User')?></th>
            <?php foreach ($roles as $role): ?>
                <th><?= Html::encode($role->name) ?></th>
            <?php endforeach; ?>
            <th><?=Yii::t('app', 'Actions')?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
            <?php
                $hasAdminRole = $matrix[$user->id]['admin'] ?? false;
                $rowClass = $hasAdminRole ? 'table-info' : '';
            ?>
            <tr class="<?= $rowClass ?>">
                <td>
                    <?= Html::encode($user->fullname) ?>
                    <?php if ($hasAdminRole): ?>
                        <span class="badge bg-primary ms-1" title="Usuario con rol matrix: tiene todos los permisos">ADMIN</span>
                    <?php endif; ?>
                </td>
                <?php foreach ($roles as $role): ?>
                    <td class="text-center">
                        <?php if ($hasAdminRole): ?>
                            <span class="text-muted">&#10003;</span> <!-- check inactivo -->
                        <?php else: ?>
                            <?= $matrix[$user->id][$role->name]
                                ? '<span class="text-success">&#10003;</span>'
                                : '' ?>
                        <?php endif; ?>
                    </td>
                <?php endforeach; ?>
                <td>
                    <?= Html::a('Modificar', ['admin/edit-roles', 'id' => $user->id], ['class' => 'btn btn-sm btn-primary']) ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
