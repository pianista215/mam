<?php

namespace app\models\traits;

use Yii;

trait PasswordRulesTrait
{
    public function passwordRules()
    {
        return [
            [['password'], 'required'],
            [['password'], 'string', 'max' => 255],
            [['password'], 'string', 'min' => 8],
            [['password'], 'match', 'pattern'=>'/\d/', 'message' => Yii::t('app', 'Password must contain at least one numeric digit.')],
            [['password'], 'match', 'pattern'=>'/[a-zA-Z]/', 'message' => Yii::t('app', 'Password must contain at least one letter.')],
        ];
    }
}
