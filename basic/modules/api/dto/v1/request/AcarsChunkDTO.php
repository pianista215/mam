<?php

namespace app\modules\api\dto\v1\request;

use yii\base\Model;

class AcarsChunkDTO extends Model
{
    public $id;
    public $sha256sum;

    public function rules()
    {
        return [
            [['id', 'sha256sum'], 'required'],
            [['id'], 'filter', 'filter' => 'intval'],
            [['id'], 'integer'],
            [['sha256sum'], 'string', 'length' => 44],
        ];
    }
}