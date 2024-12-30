<?php

namespace app\modules\api\dto\v1;

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

        $route = $model->route0;
        $dep = $route->departure0;

        $dto->departure_icao = $dep->icao_code;
        $dto->departure_latitude = $dep->latitude;
        $dto->departure_longitude = $dep->longitude;

        $dto->arrival_icao = $route->arrival0->icao_code;
        $dto->alt1_icao = $model->alternative1_icao;
        $dto->alt2_icao = $model->alternative2_icao;

        $aircraft = $model->aircraft;
        $dto->aircraft_type_icao = $aircraft->aircraftConfiguration->aircraftType->icao_type_code;
        $dto->aircraft_reg = $aircraft->registration;

        return $dto;
    }
}