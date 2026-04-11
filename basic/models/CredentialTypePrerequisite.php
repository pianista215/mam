<?php

namespace app\models;

/**
 * This is the model class for table "credential_type_prerequisite".
 *
 * Each row represents a directed edge parent -> child in the career DAG.
 * Semantics: to obtain child_id, the pilot needs at least ONE of the parent_id
 * entries listed for that child (OR logic across multiple parents).
 *
 * @property int $parent_id
 * @property int $child_id
 *
 * @property CredentialType $parent
 * @property CredentialType $child
 */
class CredentialTypePrerequisite extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'credential_type_prerequisite';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['parent_id', 'child_id'], 'required'],
            [['parent_id', 'child_id'], 'integer'],
            [['parent_id'], 'exist', 'targetClass' => CredentialType::class, 'targetAttribute' => 'id'],
            [['child_id'], 'exist', 'targetClass' => CredentialType::class, 'targetAttribute' => 'id'],
            [['parent_id', 'child_id'], 'unique', 'targetAttribute' => ['parent_id', 'child_id']],
        ];
    }

    public function getParent()
    {
        return $this->hasOne(CredentialType::class, ['id' => 'parent_id']);
    }

    public function getChild()
    {
        return $this->hasOne(CredentialType::class, ['id' => 'child_id']);
    }
}
