<?php

namespace app\models;

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
        ];
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
