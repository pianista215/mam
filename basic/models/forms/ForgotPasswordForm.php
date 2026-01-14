<?php

namespace app\models\forms;

use Yii;
use yii\base\Model;
use app\models\Pilot;

class ForgotPasswordForm extends Model
{
   public $email;

       public function rules()
       {
           return [
               [['email'], 'required'],
               [['email'], 'email'],
           ];
       }
}
