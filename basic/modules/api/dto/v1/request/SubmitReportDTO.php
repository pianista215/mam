<?php

namespace app\modules\api\dto\v1\request;

use yii\base\Model;
use app\models\SubmittedFlightPlan;

class SubmitReportDTO extends Model
{
    public $pilot_comments;
    public $last_position_lat;
    public $last_position_lon;
    public $network;
    public $report_tool;
    public $start_time;
    public $end_time;
    public $chunks = [];

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['pilot_comments', 'last_position_lat', 'last_position_lon', 'network', 'aircraft_name', 'start_time', 'end_time', 'chunks'], 'required'],
            [['pilot_comments', 'network', 'aircraft_name'], 'string'],
            [['last_position_lat', 'last_position_lon'], 'number'],
            [['last_position_lat'], 'compare', 'compareValue' => -90, 'operator' => '>=', 'message' => 'Latitude must be between -90 and 90.'],
            [['last_position_lat'], 'compare', 'compareValue' => 90, 'operator' => '<=', 'message' => 'Latitude must be between -90 and 90.'],
            [['last_position_lon'], 'compare', 'compareValue' => -180, 'operator' => '>=', 'message' => 'Longitude must be between -180 and 180.'],
            [['last_position_lon'], 'compare', 'compareValue' => 180, 'operator' => '<=', 'message' => 'Longitude must be between -180 and 180.'],
            [['pilot_comments', 'report_tool', 'network'], 'trim'],
            [['pilot_comments'], 'string', 'max' => 400],
            [['report_tool'], 'string', 'max' => 20],
            [['network'], 'string', 'max' => 50],
            [['start_time', 'end_time'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['chunks'], 'validateChunks'],
        ];
    }

    public function validateChunks($attribute, $params)
    {
        foreach ($this->$attribute as $chunkData) {
            $chunk = new AcarsChunkDTO($chunkData);
            if (!$chunk->validate()) {
                $this->addError($attribute, "Invalid chunk data: " . json_encode($chunk->getErrors()));
            }
        }
    }

    public function load($data, $formName = null)
    {
        $result = parent::load($data, $formName);

        if (isset($data['chunks']) && is_array($data['chunks'])) {
            foreach ($data['chunks'] as $chunkData) {
                $chunk = new AcarsChunkDTO();
                $chunk->load($chunkData, '');
                $this->chunks[] = $chunk;
            }
        }

        return $result;
    }

    public function toFlight($submittedFpl)
    {
        $flight = new Flight();
        $flight->pilot_id = $submittedFpl->pilot_id;

        return $flight;
    }

}