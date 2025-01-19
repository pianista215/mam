<?php

namespace app\modules\api\dto\v1\request;

use yii\base\Model;

class AcarsChunkDTO extends Model
{
    public $id;
    public $sha256;

    public function rules()
    {
        return [
            [['id', 'sha256'], 'required'],
            [['id'], 'integer'],
            [['sha256'], 'string', 'length' => 64],
        ];
    }
}