<?php

namespace App\Service;

use App\Enums\ActivityDurationEnum;
use App\Enums\ActivityEnum;
use App\Enums\VacuumingDaysEnum;
use DateTimeInterface;

class CSVData
{
    /**
     * @param int $minutes - cumulative amount of minutes
     * @param DateTimeInterface $last - the last day of the period
     * @param DateTimeInterface $date - the current day of the period
     * @return array{date:string, activity:string, time: string}
     *
     * A method that contains the business logic for generating rows for a schedule into a CSV file
     * Its being called in a foreach loop in the tests/integration/GenerateCleaningScheduleCommandTest.php command
     */
    public function generateCSVRowData(int &$minutes, DateTimeInterface $last, DateTimeInterface $date): array
    {
        $row = [];
        $row['date'] = $date->format('Y-m-d');

        //if the current day is the first day of the month
        if ($date->format('d') === '01') {
            $this->fillARow(
                $row,
                $minutes,
                $this->generateRowValues(
                    ActivityEnum::WINDOW_CLEANING,
                    ActivityDurationEnum::WINDOW_CLEANING_DURATION
                )
            );

            //if the first day of the month is Tuesday or Thursday
            if ($date->format('D') === VacuumingDaysEnum::VACUUMING_DAY_ONE->value || $date->format('D') === VacuumingDaysEnum::VACUUMING_DAY_TWO->value) {
                $this->fillARow(
                    $row,
                    $minutes,
                    $this->generateRowValues(
                        ActivityEnum::VACUUMING,
                        ActivityDurationEnum::VACUUMING_DURATION,
                        true
                    )
                );
            }

            //if the current day is the last day of the month
        } elseif ($date->format('t') === $date->format('d')) {
            $this->fillARow(
                $row,
                $minutes,
                $this->generateRowValues(
                    ActivityEnum::REFRIGERATOR_CLEANING,
                    ActivityDurationEnum::REFRIGERATOR_CLEANING_DURATION
                )
            );

            //if the last day of the month is Tuesday or Thursday
            if ($date->format('D') === VacuumingDaysEnum::VACUUMING_DAY_ONE->value || $date->format('D') === VacuumingDaysEnum::VACUUMING_DAY_TWO->value) {
                $this->fillARow(
                    $row,
                    $minutes,
                    $this->generateRowValues(
                        ActivityEnum::VACUUMING,
                        ActivityDurationEnum::VACUUMING_DURATION,
                        true
                    )
                );
            }

        } elseif ($date->format('D') === VacuumingDaysEnum::VACUUMING_DAY_ONE->value || $date->format('D') === VacuumingDaysEnum::VACUUMING_DAY_TWO->value) {
            $this->fillARow(
                $row,
                $minutes,
                $this->generateRowValues(
                    ActivityEnum::VACUUMING,
                    ActivityDurationEnum::VACUUMING_DURATION,
                )
            );
        } else {
            $row['activity'] = '/';
        }

        $row['time'] = $this->calculateHoursForSpecificDay($date, $last, $minutes);

        return $row;
    }

    /**
     * @param array{date: string, activity: string, time: string} $row
     * @param int $minutes
     * @param array{minutes: int, activity: string} $values
     * @return void
     *
     * If the activity key in the row array is already existing
     * Append the string, don't assign it
     * And at the end add the minutes to the amount at that point or that day of the period
     */
    public function fillARow(array &$row, int &$minutes, array $values): void
    {
        if (array_key_exists('activity', $row)) {
            $row['activity'] .=  $values['activity'];
        } else {
            $row['activity'] =  $values['activity'];
        }

        $minutes += $values['minutes'];
    }

    /**
     * @param ActivityEnum $activityEnum
     * @param ActivityDurationEnum $activityDurationEnum
     * @param bool $appended
     * @return array{minutes: int, activity: string}
     *
     * For activity, it checks whether it should be appended
     * It should be appended only if it's Tuesday or Thursday, and it's the first or the last day of the month
     */
    private function generateRowValues(
        ActivityEnum $activityEnum,
        ActivityDurationEnum $activityDurationEnum,
        bool $appended = false
    ): array {
        return [
            'minutes'  => $activityDurationEnum->value,
            'activity' => $appended ? '&' . $activityEnum->value : $activityEnum->value
        ];
    }

    /**
     * @param DateTimeInterface $last
     * @param DateTimeInterface $current
     * @param int $minutes
     * @return string
     *
     * This method checks whether the current day of the period is also the last day of the period
     * In order to calculate the total time, since we display the total time at the last day of the period
     */
    private function calculateHoursForSpecificDay(DateTimeInterface $last, DateTimeInterface $current, int $minutes): string
    {
        return $last->format('Y-m-d') === $current->format('Y-m-d') ? $this->calculateHours($minutes) : '/';
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
