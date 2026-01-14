<?php

namespace app\models\forms;

use app\config\Config;
use app\models\Airport;
use yii\base\Model;
use Yii;

class SiteSettingsForm extends Model
{
    public $registration_start;
    public $registration_end;
    public $registration_start_location;

    public $chunks_storage_path;
    public $images_storage_path;

    public $token_life_h;
    public $charter_ratio;

    public $airline_name;
    public $no_reply_mail;
    public $support_mail;

    public $x_url;
    public $instagram_url;
    public $facebook_url;

    public function rules()
    {
        return [
            [['registration_start','registration_end','registration_start_location',
              'chunks_storage_path','images_storage_path','token_life_h','charter_ratio',
              'airline_name','no_reply_mail','support_mail','x_url','instagram_url','facebook_url'], 'trim'],

            [['registration_start','registration_end'], 'date', 'format' => 'php:Y-m-d'],
            ['registration_start_location', 'filter', 'filter' => 'strtoupper'],
            ['registration_start_location', 'string', 'max' => 4],

            [
                'registration_start_location',
                'exist',
                'skipOnError' => true,
                'targetClass' => Airport::class,
                'targetAttribute' => ['registration_start_location' => 'icao_code'],
            ],

            [['chunks_storage_path','images_storage_path'], 'validatePath'],

            ['token_life_h', 'integer', 'min' => 1],

            ['charter_ratio', 'filter', 'filter' => [$this, 'normalizeDecimal']],
            ['charter_ratio', 'number'],
            ['charter_ratio', 'number', 'min' => 0, 'max' => 1],

            [['no_reply_mail','support_mail'], 'email'],

            [['x_url','instagram_url','facebook_url'], 'url'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'registration_start' => Yii::t('app', 'Registration start date'),
            'registration_end' => Yii::t('app', 'Registration end date'),
            'registration_start_location' => Yii::t('app', 'Registration start airport'),

            'chunks_storage_path' => Yii::t('app', 'Chunks storage path'),
            'images_storage_path' => Yii::t('app', 'Images storage path'),

            'token_life_h' => Yii::t('app', 'Token lifetime (hours)'),
            'charter_ratio' => Yii::t('app', 'Charter ratio'),

            'airline_name' => Yii::t('app', 'Airline name'),
            'no_reply_mail' => Yii::t('app', 'No-reply email'),
            'support_mail' => Yii::t('app', 'Support email'),

            'x_url' => Yii::t('app', 'X / Twitter URL'),
            'instagram_url' => Yii::t('app', 'Instagram URL'),
            'facebook_url' => Yii::t('app', 'Facebook URL'),
        ];
    }


    public function loadFromConfig()
    {
        foreach ($this->attributes() as $attr) {
            $this->$attr = Config::get($attr);
        }
    }

    public function normalizeDecimal($value)
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $value = str_replace(',', '.', $value);
        return $value;
    }


    public function save()
    {
        if (!$this->validate()) {
            return false;
        }

        foreach ($this->attributes() as $attr) {
            $new = (string)$this->$attr;
            $old = (string)Config::get($attr);

            if ($new !== $old) {
                Config::set($attr, $new);
            }
        }

        return true;
    }

    public function validatePath($attribute)
    {
        $value = $this->$attribute;

        if ($value[0] !== '/') {
            $this->addError($attribute, Yii::t('app', 'Path must be absolute.'));
            return;
        }

        if (!is_dir($value)) {
            $this->addError($attribute, Yii::t('app', 'Path doesn\'t exist or is not a folder.'));
        }
    }
}
