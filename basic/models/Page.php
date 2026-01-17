<?php

namespace app\models;

use app\models\traits\ImageRelated;
use Yii;
use yii\helpers\Url;

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

    /**
     * Gets query for [[Images]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getImages()
    {
        return $this->hasMany(Image::class, ['related_id' => 'id'])
            ->andWhere(['type' => Image::TYPE_PAGE_IMAGE])
            ->orderBy(['element' => SORT_ASC]);
    }

    public function getNextImageElement(): int
    {
        $lastImage = $this->getImages()->orderBy(['element' => SORT_DESC])->one();
        return $lastImage ? $lastImage->element + 1 : 0;
    }

    public function getViewUrl(): string
    {
        if ($this->code === self::HOME_PAGE) {
            return Url::to(['/']);
        } elseif ($this->type === self::TYPE_TOUR) {
            $tourId = Tour::extractIdFromPageCode($this->code);
            return Url::to(['/tour/view', 'id' => $tourId]);
        } else {
            return Url::to(['/page/view', 'code' => $this->code]);
        }
    }
}
