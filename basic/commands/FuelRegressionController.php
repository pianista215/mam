<?php

namespace app\commands;

use app\helpers\FuelEstimator;
use app\models\AircraftConfiguration;
use app\models\Flight;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Query;
use Yii;

/**
 * Nightly job that calculates linear fuel regression parameters per aircraft configuration.
 */
class FuelRegressionController extends Controller
{
    /**
     * Calculates fuel regression (fuel = a + b*distance) for all aircraft configurations
     * using completed historical flights.
     */
    public function actionCalculate(): int
    {
        $configs = AircraftConfiguration::find()->all();

        foreach ($configs as $config) {
            $flights = (new Query())
                ->select([
                    'fr.distance_nm',
                    'fr.total_fuel_burn_kg',
                    'fr.flight_time_minutes',
                ])
                ->from('flight_report fr')
                ->innerJoin('flight f', 'fr.flight_id = f.id')
                ->innerJoin('aircraft a', 'f.aircraft_id = a.id')
                ->where([
                    'a.aircraft_configuration_id' => $config->id,
                    'f.status' => Flight::STATUS_FINISHED,
                ])
                ->andWhere(['not', ['fr.distance_nm' => null]])
                ->andWhere(['not', ['fr.total_fuel_burn_kg' => null]])
                ->andWhere(['not', ['fr.flight_time_minutes' => null]])
                ->all();

            $result = FuelEstimator::calculateRegression($flights);

            $config->fuel_regression_a = $result['a'] ?? null;
            $config->fuel_regression_b = $result['b'] ?? null;
            $config->fuel_regression_n = $result['n'] ?? null;
            $config->fuel_avg_kg_per_min = $result['avg_kg_per_min'] ?? null;
            $config->fuel_regression_updated_at = (new \DateTime())->format('Y-m-d H:i:s');

            $config->save(false);

            if ($result !== null) {
                echo sprintf(
                    "[config #%d] n=%d, a=%.2f, b=%.4f, avg_kg_per_min=%.4f\n",
                    $config->id,
                    $result['n'],
                    $result['a'],
                    $result['b'],
                    $result['avg_kg_per_min']
                );
            } else {
                echo sprintf("[config #%d] insufficient data, regression set to null\n", $config->id);
            }
        }

        return ExitCode::OK;
    }
}
