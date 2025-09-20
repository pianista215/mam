<?php
namespace app\helpers;

class TimeHelper
{
    /**
     * Format decimal hours into HH:MM without 24h limit.
     *
     * Example: 255.7 -> "255:42"
     *
     * @param float|int|null $value Decimal hours (e.g. 1.5 = 1h 30m)
     * @return string
     */
    public static function formatHoursMinutes($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $hours = (int) floor($value);
        $minutes = (int) round(($value - $hours) * 60);

        if ($minutes === 60) { // rounding edge case
            $hours++;
            $minutes = 0;
        }

        return sprintf('%d:%02d', $hours, $minutes);
    }
}
