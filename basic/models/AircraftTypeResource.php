<?php

namespace app\models;

use app\config\ConfigHelper as CK;
use Yii;

/**
 * @property int $id
 * @property int $aircraft_type_id
 * @property string $filename
 * @property string $original_name
 * @property int $size_bytes
 * @property string $created_at
 *
 * @property AircraftType $aircraftType
 */
class AircraftTypeResource extends \yii\db\ActiveRecord
{
    public const ALLOWED_EXTENSIONS = ['png', 'jpg', 'jpeg', 'pdf', 'rar', 'zip', '7z'];
    public const STORAGE_SUBDIRECTORY = 'aircraft_type';

    public static function tableName(): string
    {
        return 'aircraft_type_resource';
    }

    public function rules(): array
    {
        return [
            [['aircraft_type_id', 'filename', 'original_name', 'size_bytes'], 'required'],
            [['aircraft_type_id', 'size_bytes'], 'integer'],
            [['aircraft_type_id'], 'exist', 'targetClass' => AircraftType::class, 'targetAttribute' => 'id'],
            [['filename', 'original_name'], 'string', 'max' => 255],
            [['created_at'], 'safe'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'               => Yii::t('app', 'ID'),
            'aircraft_type_id' => Yii::t('app', 'Aircraft Type'),
            'filename'         => Yii::t('app', 'Filename'),
            'original_name'    => Yii::t('app', 'Original Name'),
            'size_bytes'       => Yii::t('app', 'Size'),
            'created_at'       => Yii::t('app', 'Uploaded At'),
        ];
    }

    public function getPath(): string
    {
        return CK::getAircraftTypeResourcesStoragePath() . '/' . self::STORAGE_SUBDIRECTORY . '/' . $this->aircraft_type_id . '/' . $this->filename;
    }

    public function afterDelete(): void
    {
        parent::afterDelete();
        $path = $this->getPath();
        if (file_exists($path)) {
            unlink($path);
        }
    }

    public static function getTotalSizeMb(): float
    {
        return (float) self::find()->sum('size_bytes') / 1024 / 1024;
    }

    public function getAircraftType()
    {
        return $this->hasOne(AircraftType::class, ['id' => 'aircraft_type_id']);
    }
}
