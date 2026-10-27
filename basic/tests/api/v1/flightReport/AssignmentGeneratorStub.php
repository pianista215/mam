<?php

namespace tests\api\v1\flightReport;

use app\helpers\AssignmentGenerator;
use app\models\Aircraft;

/**
 * Replaces AssignmentGenerator through the DI container: returns fixed proposals or throws.
 */
class AssignmentGeneratorStub extends AssignmentGenerator
{
    public static array $proposals = [];
    public static ?\Throwable $exception = null;
    /** @var string[] Location of the aircraft received on each call */
    public static array $origins = [];

    public static function reset(): void
    {
        self::$proposals = [];
        self::$exception = null;
        self::$origins = [];
    }

    public static function generate(Aircraft $aircraft, ?callable $rand = null): array
    {
        self::$origins[] = $aircraft->location;
        if (self::$exception !== null) {
            throw self::$exception;
        }
        return self::$proposals;
    }
}
