<?php

namespace app\assets;

use yii\web\AssetBundle;

class NavaidMapAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl  = '@web';
    public $js       = ['js/navaid-map.js'];
}
