<?php

namespace App\Service;

use App\Enums\ActivityDurationEnum;
use App\Enums\ActivityEnum;
use App\Enums\DaysOfTheWeekEnum;
use App\Enums\VacuumingDaysEnum;
use DateTime;
use DateTimeInterface;

class CSVData
{
    /**
     * @param int $minutes - cumulative amount of minutes
     * @param array{
     *     lastDate: string,
     *     currentDate: DateTimeInterface,
     *     firstVacuumingDate: string,
     *     lastWorkingDate: string,
     *     holidays: array<string>} $dates
     * @return array{date:string, activity:string, time: string}
     *
     * A method that contains the business logic for generating rows for a schedule into a CSV file
     * Its being called in a foreach loop in the src/Command/GenerateCleaningScheduleCommand.php command
     */
    public function generateCSVRowData(int &$minutes, array $dates): array
    {
        $row = [];
        $date = $dates['currentDate'];
        $dateString = $date->format('Y-m-d');

        $row['date'] = $dateString;
        $row['activity'] = '/';

        //If the current day is holiday, skip the generation of row values and go with the defaults
        if (!in_array($dateString, $dates['holidays'])) {

            //If the current date is Tuesday or Thursday it's vacuuming day
            if ($date->format('D') === VacuumingDaysEnum::VACUUMING_DAY_ONE->value || $date->format('D') === VacuumingDaysEnum::VACUUMING_DAY_TWO->value) {

                $this->fillARow(
                    $row,
                    $minutes,
                    $this->generateRowValues(
                        ActivityEnum::VACUUMING,
                        ActivityDurationEnum::VACUUMING_DURATION,
                    )
                );

                //We are doing REFRIGERATOR_CLEANING on the first vacuuming day of the month, so we are checking for that day
                if ($dates['firstVacuumingDate'] === $date->format('Y-m-d')) {
                    $this->fillARow(
                        $row,
                        $minutes,
                        $this->generateRowValues(
                            ActivityEnum::REFRIGERATOR_CLEANING,
                            ActivityDurationEnum::REFRIGERATOR_CLEANING_DURATION,
                            true
                        )
                    );
                }
            }

            //We are doing WINDOW_CLEANING on the last working day of the month, so we are checking for that day
            if ($dates['lastWorkingDate'] === $date->format('Y-m-d')) {
                $this->fillARow(
                    $row,
                    $minutes,
                    $this->generateRowValues(
                        ActivityEnum::WINDOW_CLEANING,
                        ActivityDurationEnum::WINDOW_CLEANING_DURATION,
                        $row['activity'] !== '/'
                    )
                );
            }
        }

        //For each current day or date we are checking whether it is the last day of the period
        //If it is we need to display the total time otherwise just add '/' for TotalTime
        $row['time'] = $this->calculateHoursForSpecificDay($date, $dates['lastDate'], $minutes);

        return $row;
    }

    /**
     * @param DateTime $dateTime
     * @return string
     *
     * We are returning the first vacuuming date of the month, and we are passing the first day of the month
     */
    public function determineTheFirstVacuumingDate(DateTime $dateTime): string
    {
        $dayOfTheWeek = (int) $dateTime->format('N');

        $dayOfTheWeek = DaysOfTheWeekEnum::tryFrom($dayOfTheWeek);

        return $dayOfTheWeek ? $dayOfTheWeek->firstVacuumingDay($dateTime)->format('Y-m-d') : '';
    }

    /**
     * @param DateTime $dateTime
     * @return string
     *
     * We are returning the last working date of the month, and we are passing the last day of the month
     */
    public function determineTheLastWorkingDate(DateTime $dateTime): string
    {
        $dayOfTheWeek = (int) $dateTime->format('N');

        $dayOfTheWeek = DaysOfTheWeekEnum::tryFrom($dayOfTheWeek);

        return $dayOfTheWeek ? $dayOfTheWeek->lastWorkingDay($dateTime)->format('Y-m-d') : '';
    }

    /**
     * @param array{date: string, activity: string, time: string} $row
     * @param int $minutes
     * @param array{minutes: int, activity: string} $values
     * @return void
     *
     * If the activity key in the row array is equal to '/'
     * Replace it with a real activity string value
     * Otherwise just append it to the string list of activities
     */
    public function fillARow(array &$row, int &$minutes, array $values): void
    {
        if ($row['activity'] === '/') {
            $row['activity'] =  $values['activity'];
        } else {
            $row['activity'] .=  $values['activity'];
        }

        $minutes += $values['minutes'];
    }

    /**
     * @param ActivityEnum $activityEnum
     * @param ActivityDurationEnum $activityDurationEnum
     * @param bool $appended
     * @return array{minutes: int, activity: string}
     *
     * If it should be appended, you need to add '&' in front of the activity string value
     */
    private function generateRowValues(
        ActivityEnum $activityEnum,
        ActivityDurationEnum $activityDurationEnum,
        bool $appended = false
    ): array {
        return [
            'minutes'  => $activityDurationEnum->value,
            'activity' => $appended ? ' & ' . $activityEnum->value : $activityEnum->value
        ];
    }

    /**
     * @param DateTimeInterface $current
     * @param string $last
     * @param int $minutes
     * @return string
     *
     * This method checks whether the current day of the period is also the last day of the period
     * In order to calculate the total time, since we display the total time at the last day of the period
     */
    private function calculateHoursForSpecificDay(DateTimeInterface $current, string $last, int $minutes): string
    {
        return $last === $current->format('Y-m-d') ? $this->calculateHours($minutes) : '/';
    }

    /**
     * @param int $minutes
     * @return string
     *
     * It calculates the total time
     */
    private function calculateHours(int $minutes): string
    {
        $hours = floor($minutes / 60);
        $min = $minutes - ($hours * 60);

        return sprintf('%02d:%02d', $hours, $min);
    }
}
