<?php

namespace app\models\forms;

use app\models\traits\PasswordRulesTrait;
use yii\base\Model;

class ChangePasswordForm extends Model
{
    use PasswordRulesTrait;

    public $password;

    public function rules()
    {
        return $this->passwordRules();
    }
}
