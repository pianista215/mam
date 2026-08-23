<?php

namespace app\helpers;

use app\models\Flight;
use yii\helpers\Html;

class FlightStatusIconHelper
{
    /**
     * Render the status icon (with tooltip) for a flight.
     *
     * @param Flight $flight
     * @return string HTML
     */
    public static function renderIcon(Flight $flight): string
    {
        $icons = [
            Flight::STATUS_CREATED            => '<i class="fa-solid fa-arrow-up" style="color: #6c757d;"></i>',
            Flight::STATUS_SUBMITTED          => '<i class="fa-regular fa-clock" style="color: #0d6efd;"></i>',
            Flight::STATUS_PENDING_VALIDATION => '<i class="fa-regular fa-eye" style="color: orange;"></i>',
            Flight::STATUS_FINISHED           => '<i class="fa-regular fa-circle-check" style="color: green;"></i>',
            Flight::STATUS_REJECTED           => '<i class="fa-regular fa-circle-xmark" style="color: red;"></i>',
        ];

        $icon = $icons[$flight->status] ?? '<i class="fa-regular fa-question-circle"></i>';
        return '<span title="' . Html::encode($flight->fullStatus) . '">' . $icon . '</span>';
    }
}
