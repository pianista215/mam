<?php

use app\rbac\constants\Permissions;
use app\rbac\constants\Roles;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $users app\models\Pilot[] */
/* @var $roles array */
/* @var $matrix array */

$this->title = Yii::t('app', 'Role assignment');
$canAssignAdmin = Yii::$app->user->can(Permissions::ASSIGN_ADMIN);
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
                $isAdmin = $matrix[$user->id][Roles::ADMIN] ?? false;
                $rowClass = $isAdmin ? 'table-info' : '';
                $canEdit = !$isAdmin || $canAssignAdmin;
            ?>
            <tr class="<?= $rowClass ?>">
                <td>
                    <?= Html::encode($user->fullname) ?>
                    <?php if ($isAdmin): ?>
                        <span class="badge bg-primary ms-1">ADMIN</span>
                    <?php endif; ?>
                </td>

                <?php foreach ($roles as $role): ?>
                    <td class="text-center">
                        <?= $matrix[$user->id][$role->name] ? '<span class="text-success">&#10003;</span>' : '' ?>
                    </td>
                <?php endforeach; ?>

                <td>
                    <?php if ($canEdit): ?>
                        <?= Html::a(Yii::t('app','Edit'), ['admin/edit-roles','id'=>$user->id], ['class'=>'btn btn-sm btn-primary']) ?>
                    <?php else: ?>
                        <span class="text-muted" title="<?=Yii::t('app','Only superadmins can edit admin users')?>">🔒</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
