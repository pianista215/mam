<?php

namespace app\models;

use app\config\Config;
use yii\helpers\Url;
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
            ['element', 'validateElement'],
            ['related_id', 'validateRelatedExists'],
            ['filename', 'validateImage']
        ];
    }

    public function getPath()
    {
        return Config::get('images_storage_path').'/'.$this->type.'/'.$this->filename;
    }

    public function afterDelete()
    {
        parent::afterDelete();

        $filePath = $this->getPath();

        if ($filePath && file_exists($filePath)) {
            unlink($filePath);
        }
    }

    public function getUrl(): string
    {
        return Url::to([
            'image/view',
            'type' => $this->type,
            'related_id' => $this->related_id,
            'element' => $this->element,
        ]);
    }

    public function getUploadUrl(): string
    {
        return Url::to([
            'image/upload',
            'type' => $this->type,
            'related_id' => $this->related_id,
            'element' => $this->element,
        ]);
    }

    public static function getAllowedTypes(): array
    {
        return [
            'rank_icon'         => ['width' => 174, 'height' => 76, 'relatedModel' => Rank::class],
            'pilot_profile'     => ['width' => 200, 'height' => 250, 'relatedModel' => Pilot::class],
            'tour_image'        => ['width' => 1200, 'height' => 400, 'relatedModel' => Tour::class],
            'country_icon'      => ['width' => 44, 'height' => 33, 'relatedModel' => Country::class],
            'aircraftType_image'=> ['width' => 1200, 'height' => 400, 'relatedModel' => AircraftType::class],
            'page_image'        => ['width' => null, 'height' => null, 'relatedModel' => Page::class],
        ];
    }

    public static function getPlaceholder(string $type): ?string
    {
        $placeholders = [
            'rank_icon' => '@app/web/images/placeholders/rank_icon.png',
            'pilot_profile' => '@app/web/images/placeholders/pilot_profile.png',
            'tour_image' => '@app/web/images/placeholders/tour_image.png',
            'country_icon' => '@app/web/images/placeholders/country_icon.png',
            'aircraftType_image' => '@app/web/images/placeholders/aircraftType_image.png',
        ];

        return $placeholders[$type] ?? null;
    }

    public function isValidElement() {
        return $this->type === 'page_image' || $this->element === 0;
    }

    public function validateElement($attribute, $params)
    {
        if ($this->type !== 'page_image' && $this->$attribute != 0) {
            $this->addError($attribute, "Attribute 'element' must be 0 for type '{$this->type}'.");
        }
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

    public function getRelatedModel()
    {
        $types = self::getAllowedTypes();

        if (!isset($types[$this->type])) {
            return null;
        }

        $relatedClass = $types[$this->type]['relatedModel'] ?? null;

        if (!$relatedClass || !class_exists($relatedClass)) {
            return null;
        }

        return $relatedClass::findOne($this->related_id);
    }

    public function validateImage($attribute, $params)
    {
        $filePath = $this->getPath();

        if (!$filePath || !is_file($filePath)) {
            $this->addError($attribute, 'File does not exist.');
            return;
        }

        // Extension
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!in_array($ext, ['png', 'jpg', 'jpeg'])) {
            $this->addError($attribute, 'Invalid file extension. Allowed: png, jpg, jpeg.');
            return;
        }

        // MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        if (!in_array($mime, ['image/png', 'image/jpeg'])) {
            $this->addError($attribute, 'Invalid MIME type.');
            return;
        }

        [$width, $height] = getimagesize($filePath);

        $types = self::getAllowedTypes();
        if (!isset($types[$this->type])) {
            return; // Some images haven't restrictions
        }

        $expectedW = $types[$this->type]['width'];
        $expectedH = $types[$this->type]['height'];

        if ($expectedW && $expectedH && ($width != $expectedW || $height != $expectedH)) {
            $this->addError($attribute, "Image must be {$expectedW}x{$expectedH}px (actual: {$width}x{$height}).");
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

    public function getCallbackUrl(): string
    {
        $types = self::getAllowedTypes();

        if (!isset($types[$this->type])) {
            return Url::to(['/site/index']);
        }

        $relatedClass = $types[$this->type]['relatedModel'] ?? null;
        if (!$relatedClass) {
            return Url::to(['/site/index']);
        }

        $shortName = (new \ReflectionClass($relatedClass))->getShortName();

        // Camel case -> Kebab-case
        $controllerId = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $shortName));

        return Url::to(["/{$controllerId}/view", 'id' => $this->related_id]);
    }


    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'type' => Yii::t('app', 'Type'),
            'related_id' => Yii::t('app', 'Related ID'),
            'element' => Yii::t('app', 'Element'),
            'filename' => Yii::t('app', 'Filename'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }
}
