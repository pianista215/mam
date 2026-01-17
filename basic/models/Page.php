<?php

namespace app\models;

use app\models\traits\ImageRelated;
use Yii;

/**
 * This is the model class for table "page".
 *
 * @property int $id
 * @property string $code
 * @property string $type
 * @property string $created_at
 * @property string $updated_at
 *
 * @property PageContent[] $pageContents
 */
class Page extends \yii\db\ActiveRecord
{
    use ImageRelated;

    public function getImageDescription(): string
    {
        return "page: {$this->code}";
    }

    public const TYPE_COMPONENT = 'component';
    public const TYPE_SITE = 'site';
    public const TYPE_TOUR = 'tour';

    public const HOME_PAGE = 'home';

    public const TYPES = [
        self::TYPE_COMPONENT,
        self::TYPE_SITE,
        self::TYPE_TOUR,
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'page';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['code', 'type'], 'required'],
            ['type', 'in', 'range' => self::TYPES],
            [['created_at', 'updated_at'], 'safe'],
            [['code'], 'string', 'max' => 50],
            [['type'], 'string', 'max' => 20],
            [['code'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'code' => 'Code',
            'type' => 'Type',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[PageContents]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPageContents()
    {
        return $this->hasMany(PageContent::class, ['page_id' => 'id']);
    }
}
