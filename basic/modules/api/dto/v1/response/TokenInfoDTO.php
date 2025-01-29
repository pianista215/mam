<?php

namespace app\modules\api\dto\v1\response;

use yii\base\Model;
use app\models\Pilot;

class TokenInfoDTO extends Model
{
    public $access_token;

    public static function fromModel(Pilot $model)
    {
        $dto = new self();
        $dto->access_token = $model->access_token;

        return $dto;
    }
}