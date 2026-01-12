<?php

namespace app\models;

use Yii;
use yii\base\Model;

class AssignRolesForm extends Model
{
    public $userId;
    public $roles = [];

    public function rules()
    {
        return [
            ['roles', 'default', 'value' => []],
            ['roles', 'each', 'rule' => ['string']],
            ['roles', 'validateRolesExist'],
        ];
    }

    public function beforeValidate()
    {
        if ($this->roles === '') {
            $this->roles = [];
        }
        return parent::beforeValidate();
    }

    public function validateRolesExist($attribute)
    {
        $auth = Yii::$app->authManager;

        foreach ($this->roles as $roleName) {
            if ($auth->getRole($roleName) === null) {
                $this->addError($attribute, Yii::t('app','Invalid role') . ': '. $roleName);
            }
        }
    }

    public function save()
    {
        if (!$this->validate()) {
            return false;
        }

        $auth = Yii::$app->authManager;
        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();

        try {
            if (in_array('admin', $this->roles, true)) {
                $this->roles = ['admin'];
            }

            $auth->revokeAll($this->userId);

            foreach ($this->roles as $roleName) {
                $role = $auth->getRole($roleName);
                $auth->assign($role, $this->userId);
            }

            $transaction->commit();
            return true;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            $this->addError('roles', Yii::t('app', 'Error saving new roles.'));
            Yii::error($e);
            return false;
        }
    }
}
