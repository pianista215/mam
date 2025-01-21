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
    public $sim_aircraft_name;
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
            [['pilot_comments', 'last_position_lat', 'last_position_lon', 'network', 'sim_aircraft_name', 'start_time', 'end_time', 'chunks'], 'required'],
            [['pilot_comments', 'network', 'sim_aircraft_name'], 'string'],
            [['last_position_lat', 'last_position_lon'], 'number'],
            [['last_position_lat'], 'compare', 'compareValue' => -90, 'operator' => '>=', 'message' => 'Latitude must be between -90 and 90.'],
            [['last_position_lat'], 'compare', 'compareValue' => 90, 'operator' => '<=', 'message' => 'Latitude must be between -90 and 90.'],
            [['last_position_lon'], 'compare', 'compareValue' => -180, 'operator' => '>=', 'message' => 'Longitude must be between -180 and 180.'],
            [['last_position_lon'], 'compare', 'compareValue' => 180, 'operator' => '<=', 'message' => 'Longitude must be between -180 and 180.'],
            [['pilot_comments', 'report_tool', 'network', 'sim_aircraft_name'], 'trim'],
            [['pilot_comments'], 'string', 'max' => 400],
            [['report_tool'], 'string', 'max' => 20],
            [['network'], 'string', 'max' => 50],
            [['sim_aircraft_name'], 'string', 'max' => 50],
            [['start_time', 'end_time'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['chunks'], 'validateChunks'],
        ];
    }

    public function validateChunks($attribute, $params)
    {
        if (empty($this->chunks)) {
            $this->addError($attribute, 'At least one chunk is required.');
            return;
        }

        $chunkIds = [];

        foreach ($this->chunks as $chunk) {
            if (!$chunk->validate()) {
                $this->addError($attribute, "Invalid chunk data: " . json_encode($chunk->getErrors()));
                return;
            }
            $chunkIds[] = $chunk->id;
        }

        // Check correlative ids and starting in 1
        sort($chunkIds);
        $expectedIds = range(1, count($chunkIds));

        if ($chunkIds !== $expectedIds) {
            $this->addError(
                $attribute,
                'Chunk IDs must be sequential and start from 1. Missing or duplicated IDs detected.'
            );
        }
    }

    public function load($data, $formName = null)
    {
        $result = parent::load($data, $formName);

        $this->chunks = [];

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