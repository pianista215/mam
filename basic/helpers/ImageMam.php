<?php

namespace app\helpers;

use app\models\Image;
use yii\helpers\Html;
use Yii;

class ImageMam
{
    private static $cssRegistered = false;

    /**
     * Render image with hover if user can edit the image
     *
     * @param string $type Image type (rank_icon, pilot_profile, etc.)
     * @param int $related_id ID of the related table
     * @param int $element Secondary element
     * @param array $options Additional options <img>
     * @return string HTML
     */
    public static function render(string $type, int $related_id, int $element = 0, array $options = []): string
    {
        if (!self::$cssRegistered) {
            $css = <<<CSS
            .image-mam-container {
                position: relative;
                display: inline-block;
            }
            .image-mam-container img {
                max-width: 100%;
                height: auto;
                display: block;
            }
            .image-mam-edit {
                position: absolute;
                top: 4px;
                right: 4px;
                display: none;
                cursor: pointer;
                background: rgba(0,0,0,0.5);
                color: #fff;
                padding: 2px 6px;
                border-radius: 3px;
                font-size: 0.9rem;
                text-decoration: none;
            }
            .image-mam-container:hover .image-mam-edit {
                display: block;
            }
            CSS;
            Yii::$app->view->registerCss($css);
            self::$cssRegistered = true;
        }

        $image = Image::findOne([
            'type' => $type,
            'related_id' => $related_id,
            'element' => $element,
        ]) ?? new Image([
            'type' => $type,
            'related_id' => $related_id,
            'element' => $element,
        ]);

        $imgOptions = array_merge(['alt' => '', 'class' => 'image-mam-img'], $options);

        $html = Html::beginTag('div', ['class' => 'image-mam-container']);
        $html .= Html::img($image->getUrl(), $imgOptions);

        if (Yii::$app->user->can('uploadImage', ['image' => $image])) {
            $html .= Html::a(Yii::t('app', 'Edit'), $image->getUploadUrl(), ['class' => 'image-mam-edit']);
        }

        $html .= Html::endTag('div');

        return $html;
    }
}
