<?php

namespace app\models;

use app\config\Languages;

use Yii;

/**
 * This is the model class for table "page_content".
 *
 * @property int $id
 * @property int $page_id
 * @property string $language
 * @property string title
 * @property string $content_md
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Page $page
 */
class PageContent extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'page_content';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['page_id', 'language', 'content_md'], 'required'],
            ['title', 'required', 'when' => function ($model) {
                return $model->page && $model->page->type === Page::TYPE_SITE;
            }],
            ['language', 'in', 'range' => Languages::ALL],
            [['page_id'], 'integer'],
            [['content_md'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['language'], 'string', 'max' => 2],
            [['title'], 'string', 'max' => 100],
            [['page_id', 'language'], 'unique', 'targetAttribute' => ['page_id', 'language']],
            [['page_id'], 'exist', 'skipOnError' => true, 'targetClass' => Page::class, 'targetAttribute' => ['page_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'page_id' => 'Page ID',
            'language' => 'Language',
            'title' => 'Title',
            'content_md' => 'Content Md',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[Page]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPage()
    {
        return $this->hasOne(Page::class, ['id' => 'page_id']);
    }
}
