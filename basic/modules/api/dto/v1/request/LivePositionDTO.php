<?php

namespace app\modules\api\dto\v1\request;

use yii\base\Model;

class LivePositionDTO extends Model
{
    public $latitude;
    public $longitude;
    public $altitude;
    public $heading;
    public $ground_speed;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['latitude', 'longitude', 'altitude', 'heading', 'ground_speed'], 'required'],
            [['latitude', 'longitude'], 'number'],
            [['altitude', 'heading', 'ground_speed'], 'integer'],
            [['latitude'], 'compare', 'compareValue' => -90, 'operator' => '>=', 'message' => 'Latitude must be between -90 and 90.'],
            [['latitude'], 'compare', 'compareValue' => 90, 'operator' => '<=', 'message' => 'Latitude must be between -90 and 90.'],
            [['longitude'], 'compare', 'compareValue' => -180, 'operator' => '>=', 'message' => 'Longitude must be between -180 and 180.'],
            [['longitude'], 'compare', 'compareValue' => 180, 'operator' => '<=', 'message' => 'Longitude must be between -180 and 180.'],
            [['heading'], 'compare', 'compareValue' => 0, 'operator' => '>=', 'message' => 'Heading must be between 0 and 360.'],
            [['heading'], 'compare', 'compareValue' => 360, 'operator' => '<=', 'message' => 'Heading must be between 0 and 360.'],
            [['altitude', 'ground_speed'], 'compare', 'compareValue' => 0, 'operator' => '>=', 'message' => '{attribute} must be a positive value.'],
        ];
    }
}
