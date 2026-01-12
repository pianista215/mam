<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\components\Roles;
use app\components\Permissions;

/* @var $formModel app\models\forms\AssignRolesForm */
/* @var $user app\models\Pilot */
/* @var $roles array */

$this->title = Yii::t('app', 'Roles of user') . ': '. $user->fullname;

$hasAdmin = in_array('admin', $formModel->roles, true);
$canAssignAdmin = Yii::$app->user->can('assignAdmin');
?>

<h2 class="mb-4">
    <?= Yii::t('app', 'Roles of user'). ': ' ?>
    <span class="text-primary"><?= Html::encode($user->fullname) ?></span>
</h2>

<div class="card shadow-sm">
    <div class="card-body">

        <?php $form = ActiveForm::begin(['id' => 'roles-form']); ?>

        <!-- Permite enviar array vacío -->
        <input type="hidden" name="AssignRolesForm[roles]" value="">

        <?= $form->errorSummary($formModel) ?>

        <?php foreach ($roles as $role): ?>
            <?php if ($role->name !== 'admin' || $canAssignAdmin): ?>
                <?php
                    $isAdmin = $role->name === 'admin';
                    $checked = in_array($role->name, $formModel->roles, true);
                    $disabled = $isAdmin && !$canAssignAdmin;
                ?>

                <div class="mb-2">
                    <div class="form-check d-flex align-items-start <?= $isAdmin ? 'border rounded p-3 border-primary bg-light' : '' ?>">
                        <input
                            class="form-check-input mt-1 role-checkbox"
                            type="checkbox"
                            name="AssignRolesForm[roles][]"
                            value="<?= $role->name ?>"
                            id="role-<?= $role->name ?>"
                            <?= $checked ? 'checked' : '' ?>
                            <?= $disabled ? 'disabled' : '' ?>
                        >
                        <label class="form-check-label ms-2" for="role-<?= $role->name ?>">
                            <strong><?= Html::encode($role->name) ?></strong>

                            <?php if ($isAdmin): ?>
                                <span class="badge bg-primary ms-2">FULL ACCESS</span>
                                <div class="small text-muted mt-1">
                                    <?= Yii::t('app', 'This role grants full access. All other roles will be disabled.') ?>
                                </div>
                            <?php endif; ?>
                        </label>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <div class="mt-4">
            <?= Html::submitButton(Yii::t('app', 'Save'), ['class'=>'btn btn-primary']) ?>
            <?= Html::a(Yii::t('app', 'Cancel'), ['roles-matrix'], ['class'=>'btn btn-secondary ms-2']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>

<?php if ($canAssignAdmin): ?>
<?php
$js = <<<JS
function toggleRolesByAdmin() {
    const admin = document.getElementById('role-admin');
    if (!admin) return;

    const roles = document.querySelectorAll('.role-checkbox');

    roles.forEach(cb => {
        if (cb.id !== 'role-admin') {
            if (admin.checked) {
                cb.checked = true;
                cb.disabled = true;
            } else {
                cb.disabled = false;
            }
        }
    });
}
document.getElementById('role-admin')?.addEventListener('change', toggleRolesByAdmin);
toggleRolesByAdmin();
JS;

$this->registerJs($js);
?>
<?php endif; ?>
