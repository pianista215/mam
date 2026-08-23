<?php

namespace tests\unit\helpers;

use app\helpers\FlightStatusIconHelper;
use app\models\Flight;
use tests\unit\BaseUnitTest;

class FlightStatusIconHelperTest extends BaseUnitTest
{
    public function testRenderIconReturnsExpectedIconClassPerStatus()
    {
        $expectedClasses = [
            Flight::STATUS_CREATED            => 'fa-arrow-up',
            Flight::STATUS_SUBMITTED          => 'fa-clock',
            Flight::STATUS_PENDING_VALIDATION => 'fa-eye',
            Flight::STATUS_FINISHED           => 'fa-circle-check',
            Flight::STATUS_REJECTED           => 'fa-circle-xmark',
        ];

        foreach ($expectedClasses as $status => $iconClass) {
            $flight = new Flight(['status' => $status]);
            $html = FlightStatusIconHelper::renderIcon($flight);

            $this->assertStringContainsString($iconClass, $html, "Status $status should render icon class $iconClass");
            $this->assertStringContainsString('title="' . $flight->fullStatus . '"', $html, "Status $status should include fullStatus in the tooltip");
        }
    }
}
