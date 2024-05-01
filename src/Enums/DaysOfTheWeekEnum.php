<?php

namespace App\Enums;

use DateTime;

enum DaysOfTheWeekEnum: int
{
    case MON = 1;
    case TUE = 2;
    case WED = 3;
    case THI = 4;
    case FRI = 5;
    case SAT = 6;
    case SUN = 7;

    /**
     * @param DateTime $dateTime
     * @return DateTime
     *
     * Here we are finding the first vacuuming day of the month
     * As we are passing the first day of the month.
     * The first vacuuming day of the week to which belongs the first day of the month
     * Will be the first vacuuming day of the month
     */
    public function firstVacuumingDay(DateTime $dateTime): DateTime
    {
        return match ($this) {
            self::TUE, self::THI => $dateTime,
            self::MON, self::WED => $dateTime->modify('+1 day'),
            self::FRI            => $dateTime->modify('+4 days'),
            self::SAT            => $dateTime->modify('+3 days'),
            self::SUN            => $dateTime->modify('+2 days')
        };
    }

    /**
     * @param DateTime $dateTime
     * @return DateTime
     *
     * Basically if the last day of the month is a weekend day we will make modifications in order to get the last working day
     * Otherwise return the day that was passed
     */
    public function lastWorkingDay(DateTime $dateTime): DateTime
    {
        return match ($this) {
            self::MON, self::TUE, self::WED, self::THI, self::FRI => $dateTime,
            self::SAT            => $dateTime->modify('-1 day'),
            self::SUN            => $dateTime->modify('-2 days')
        };
    }
}
