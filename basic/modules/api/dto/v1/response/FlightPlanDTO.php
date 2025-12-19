<?php

namespace app\modules\api\dto\v1\response;

use yii\base\Model;
use app\models\SubmittedFlightPlan;

class FlightPlanDTO extends Model
{
    public $id;
    public $departure_icao;
    public $departure_latitude;
    public $departure_longitude;
    public $arrival_icao;
    public $alt1_icao;
    public $alt2_icao;
    public $aircraft_type_icao;
    public $aircraft_reg;

    public static function fromModel(SubmittedFlightPlan $model)
    {
        $dto = new self();
        $dto->id = $model->id;
        $departure = null;
        $arrival = null;

        if(!empty($model->tour_stage_id)){
            $stage = $model->tourStage;
            $departure = $stage->departure0;
            $arrival = $stage->arrival0;
        } else if(!empty($model->charter_route_id)){
            $charterRoute = $model->charterRoute;
            $departure = $charterRoute->departure0;
            $arrival = $charterRoute->arrival0;
        } else {
            $route = $model->route0;
            $departure = $route->departure0;
            $arrival = $route->arrival0;
        }

        $dto->departure_icao = $departure->icao_code;
        $dto->departure_latitude = $departure->latitude;
        $dto->departure_longitude = $departure->longitude;

        $dto->arrival_icao = $arrival->icao_code;


        $dto->alt1_icao = $model->alternative1_icao;
        $dto->alt2_icao = $model->alternative2_icao;

        $aircraft = $model->aircraft;
        $dto->aircraft_type_icao = $aircraft->aircraftConfiguration->aircraftType->icao_type_code;
        $dto->aircraft_reg = $aircraft->registration;

        return $dto;
    }
}