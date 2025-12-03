<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "issue_type_lang".
 *
 * @property int $id
 * @property int $issue_type_id
 * @property string $language
 * @property string $description
 *
 * @property IssueType $issueType
 */
class IssueTypeLang extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'issue_type_lang';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['issue_type_id', 'language', 'description'], 'required'],
            [['issue_type_id'], 'integer'],
            [['language'], 'string', 'max' => 2],
            [['description'], 'string', 'max' => 200],
            [['issue_type_id', 'language'], 'unique', 'targetAttribute' => ['issue_type_id', 'language']],
            [['issue_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => IssueType::class, 'targetAttribute' => ['issue_type_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'issue_type_id' => 'Issue Type ID',
            'language' => 'Language',
            'description' => 'Description',
        ];
    }

    /**
     * Gets query for [[IssueType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIssueType()
    {
        return $this->hasOne(IssueType::class, ['id' => 'issue_type_id']);
    }
}
