<?php

namespace app\models\traits;

use app\helpers\LoggerTrait;
use app\models\Image;

trait ImageRelated
{
    use LoggerTrait;

    public function getImageDescription(): string
    {
        return static::class . " #{$this->id}";
    }

    public function afterDelete()
    {
        parent::afterDelete();

        $class = static::class;

        foreach (Image::getAllowedTypes() as $type => $cfg) {
            if (($cfg['relatedModel'] ?? null) === $class) {

                $img = Image::findOne([
                    'type' => $type,
                    'related_id' => $this->id,
                ]);

                if ($img) {
                    $this->logInfo('Deleting image associated', ['model' => $this, 'image' => $img]);
                    $img->delete();
                }
            }
        }
    }
}
