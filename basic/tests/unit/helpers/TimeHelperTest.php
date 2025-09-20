<?php

namespace tests\unit\helpers;

use app\helpers\TimeHelper;
use tests\unit\BaseUnitTest;

class TimeHelperTest extends BaseUnitTest
{
    public function testFormatHoursMinutes()
    {
        // Basic decimal hours
        $this->assertEquals('1:30', TimeHelper::formatHoursMinutes(1.5));
        $this->assertEquals('0:00', TimeHelper::formatHoursMinutes(0));
        $this->assertEquals('255:42', TimeHelper::formatHoursMinutes(255.7));

        // Rounding edge case
        $this->assertEquals('2:00', TimeHelper::formatHoursMinutes(1.999));

        // Null or empty
        $this->assertEquals('', TimeHelper::formatHoursMinutes(null));
        $this->assertEquals('', TimeHelper::formatHoursMinutes(''));
    }
}