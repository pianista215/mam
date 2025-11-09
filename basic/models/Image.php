<?php

namespace app\models;

use yii\web\UploadedFile;
use Yii;

/**
 * This is the model class for table "image".
 *
 * @property int $id
 * @property string $type
 * @property int $related_id
 * @property int|null $element
 * @property string $filename
 * @property string $created_at
 * @property string $updated_at
 */
class Image extends \yii\db\ActiveRecord
{

    /** @var UploadedFile|null */
    public $uploadFile;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'image';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type', 'related_id', 'filename'], 'required'],
            [['related_id', 'element'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['type'], 'string', 'max' => 100],
            [['filename'], 'string', 'max' => 255],
            [['type', 'related_id', 'element'], 'unique', 'targetAttribute' => ['type', 'related_id', 'element']],

            ['type', 'in', 'range' => array_keys(self::getAllowedTypes())],
            ['related_id', 'validateRelatedExists'],

            [['uploadFile'], 'file', 'skipOnEmpty' => false, 'extensions' => 'png, jpg, jpeg', 'checkExtensionByMimeType' => true],
            ['uploadFile', 'validateImageDimensions'],
        ];
    }

    public static function getAllowedTypes(): array
    {
        return [
            'rank_icon'         => ['width' => 174, 'height' => 76, 'relatedModel' => Rank::class],
            'pilot_profile'     => ['width' => 200, 'height' => 250, 'relatedModel' => Pilot::class],
            'tour_image'        => ['width' => 1200, 'height' => 400, 'relatedModel' => Tour::class],
            'country_icon'      => ['width' => 44, 'height' => 22, 'relatedModel' => Country::class],
            'aircraftType_image'=> ['width' => 1200, 'height' => 600, 'relatedModel' => AircraftType::class],
            'page'              => ['width' => null, 'height' => null, 'relatedModel' => Page::class],
        ];
    }

    public static function getPlaceholder(string $type): string
    {
        $placeholders = [
            'rank_icon' => '@web/images/placeholders/rank_icon.png',
            'pilot_profile' => '@web/images/placeholders/pilot_profile.png',
            'tour_image' => '@web/images/placeholders/tour_image.png',
            'country_icon' => '@web/images/placeholders/country_icon.png',
            'aircraftType_image' => '@web/images/placeholders/aircraftType_image.png',
        ];

        return Yii::getAlias($placeholders[$type] ?? '@web/images/placeholders/default.png');
    }

    public function validateRelatedExists($attribute, $params)
    {
        $types = self::getAllowedTypes();

        if (!isset($types[$this->type])) {
            return;
        }

        $relatedClass = $types[$this->type]['relatedModel'];

        if (!class_exists($relatedClass)) {
            $this->addError('type', "Model for type '{$this->type}' doesn't exist.");
            return;
        }

        if (!$relatedClass::find()->where(['id' => $this->related_id])->exists()) {
            $this->addError($attribute, "The element (ID {$this->related_id}) doesn't exist in {$relatedClass}.");
        }
    }


    public function validateImageDimensions($attribute, $params)
    {
        if (!$this->$attribute instanceof UploadedFile) {
            return;
        }

        $types = self::getAllowedTypes();
        if (!isset($types[$this->type])) {
            return;
        }

        [$width, $height] = getimagesize($this->$attribute->tempName);
        $expectedW = $types[$this->type]['width'];
        $expectedH = $types[$this->type]['height'];

        if ($expectedW && $expectedH && ($width != $expectedW || $height != $expectedH)) {
            $this->addError($attribute, "Image must be {$expectedW}x{$expectedH}px (actual: {$width}x{$height}).");
        }
    }



    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => 'Type',
            'related_id' => 'Related ID',
            'element' => 'Element',
            'filename' => 'Filename',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
