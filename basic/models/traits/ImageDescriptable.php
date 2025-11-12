<?php

namespace app\models\traits;

trait ImageDescriptable
{
    public function getImageDescription(): string
    {
        return static::class . " #{$this->id}";
    }
}
