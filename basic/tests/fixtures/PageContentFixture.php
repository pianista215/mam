<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class PageContentFixture extends ActiveFixture
{
    public $modelClass = 'app\models\PageContent';
    public $depends = [
                        'tests\fixtures\PageFixture'
                        ];
}