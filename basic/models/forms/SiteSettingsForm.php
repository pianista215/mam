<?php

namespace app\models\forms;

use yii\base\Model;
use app\config\Config;
use app\models\Airport;

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

            ['registration_start_location', 'validateAirport'],

            [['chunks_storage_path','images_storage_path'], 'validatePath'],

            ['token_life_h', 'integer', 'min' => 1],

            ['charter_ratio', 'number'],

            [['no_reply_mail','support_mail'], 'email'],

            [['x_url','instagram_url','facebook_url'], 'url'],
        ];
    }

    public function loadFromConfig()
    {
        foreach ($this->attributes() as $attr) {
            $this->$attr = Config::get($attr);
        }
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

    public function validateAirport($attribute)
    {
        if (!$this->$attribute) {
            return;
        } // TODO: UNAI MEter relacion como en otros modelos????

        $exists = Airport::find()
            ->where(['icao' => strtoupper($this->$attribute)])
            ->exists();

        if (!$exists) {
            $this->addError($attribute, 'El aeropuerto no existe.');
        }
    }

    public function validatePath($attribute)
    {
        $value = $this->$attribute;

        if ($value[0] !== '/') {
            $this->addError($attribute, 'El path debe ser absoluto.');
            return;
        }

        if (!is_dir($value)) {
            $this->addError($attribute, 'El directorio no existe.');
        }
    }
}
