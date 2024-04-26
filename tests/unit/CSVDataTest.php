<?php

namespace App\Tests\unit;

use App\Enums\ActivityDurationEnum;
use App\Enums\ActivityEnum;
use App\Service\CSVData;
use DateTime;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionMethod;

class CSVDataTest extends TestCase
{
    /**
     * @return array<array{lastDay: DateTime, currentDay: DateTime, result: array{date:string, activity:string, time:string}}>
     */
    public function provideDataForGenerateCSVRow(): array
    {
        return [
            [
                'lastDay' => (new DateTime('2024-07-31')),
                'currentDay' => (new DateTime('2024-04-01')),
                'result' => [
                    "date" => "2024-04-01",
                    "activity" => "Window cleaning",
                    "time" => "/"
                ]
            ],
            [
                'lastDay' => (new DateTime('2024-07-31')),
                'currentDay' =>  (new DateTime('2024-04-25')),
                'result' => [
                    "date" => "2024-04-25",
                    "activity" => "Vacuuming",
                    "time" => "/"
                ]
            ],
            [
                'lastDay' => (new DateTime('2024-07-31')),
                'currentDay' =>  (new DateTime('2024-05-31')),
                'result' => [
                    "date" => "2024-05-31",
                    "activity" => "Refrigerator cleaning",
                    "time" => "/"
                ]
            ],
            [
                'lastDay' => (new DateTime('2024-07-31')),
                'currentDay' =>  (new DateTime('2024-04-30')),
                'result' => [
                    "date" => "2024-04-30",
                    "activity" => "Refrigerator cleaning&Vacuuming",
                    "time" => "/"
                ]
            ],
            [
                'lastDay' => (new DateTime('2024-09-28')),
                'currentDay' =>  (new DateTime('2024-08-01')),
                'result' => [
                    "date" => "2024-08-01",
                    "activity" => "Window cleaning&Vacuuming",
                    "time" => "/"
                ]
            ],
            [
                'lastDay' => (new DateTime('2024-07-31')),
                'currentDay' => (new DateTime('2024-04-24')),
                'result' => [
                    "date" => "2024-04-24",
                    "activity" => "/",
                    "time" => "/"
                ]
            ],
            [
                'lastDay' => (new DateTime('2024-07-31')),
                'currentDay' => (new DateTime('2024-07-31')),
                'result' => [
                    "date" => "2024-07-31",
                    "activity" => "Refrigerator cleaning",
                    "time" => "00:50"
                ]
            ],
            [
                'lastDay' => (new DateTime('2024-08-05')),
                'currentDay' => (new DateTime('2024-08-05')),
                'result' => [
                    "date" => "2024-08-05",
                    "activity" => "/",
                    "time" => "00:00"
                ]
            ],
        ];
    }

    /**
     * @param DateTime $lastDay
     * @param DateTime $currentDay
     * @param array<array{lastDay: DateTime, currentDay: DateTime, result: array{date:string, activity:string, time:string}}> $result
     * @dataProvider provideDataForGenerateCSVRow
     */
    public function testGenerateCSVRow(DateTime $lastDay, DateTime $currentDay, array $result): void
    {
        $csvData = new CSVData();

        $minutes = 0;

        $data = $csvData->generateCSVRowData($minutes, $lastDay, $currentDay);

        $this->assertArrayHasKey('date', $data);
        $this->assertArrayHasKey('activity', $data);
        $this->assertArrayHasKey('time', $data);

        $this->assertEquals($result, $data);
    }

    /**
     * @throws ReflectionException
     */
    public function testCalculateHoursForSpecificDateLastDay(): void
    {
        $refMethod = new ReflectionMethod(CSVData::class, 'calculateHoursForSpecificDay');
        $refMethod->setAccessible(true);

        $csvData = new CSVData();

        $res = $refMethod->invoke($csvData, (new DateTime('2024-07-31')), (new DateTime('2024-07-31')), 600);

        $this->assertEquals('10:00', $res);
    }

    /**
     * @throws ReflectionException
     */
    public function testCalculateHoursForSpecificDateFirstDay(): void
    {
        $refMethod = new ReflectionMethod(CSVData::class, 'calculateHoursForSpecificDay');
        $refMethod->setAccessible(true);

        $csvData = new CSVData();

        $res = $refMethod->invoke($csvData, (new DateTime('2024-07-31')), (new DateTime('2024-04-01')), 600);

        $this->assertEquals('/', $res);
    }

    /**
     * @throws ReflectionException
     */
    public function testCalculateHours(): void
    {
        $refMethod = new ReflectionMethod(CSVData::class, 'calculateHours');
        $refMethod->setAccessible(true);

        $csvData = new CSVData();

        $res = $refMethod->invoke($csvData, 300);

        $this->assertEquals('05:00', $res);
    }

    /**
     * @return array<array{activityEnum: ActivityEnum, activityDurationEnum: ActivityDurationEnum, appended: bool}>
     */
    public function provideDataForGenerateRowValues(): array
    {
        return [
          [
              'activityEnum' => ActivityEnum::WINDOW_CLEANING,
              'activityDurationEnum' => ActivityDurationEnum::WINDOW_CLEANING_DURATION,
              'appended' => false
          ],
          [
              'activityEnum' => ActivityEnum::VACUUMING,
              'activityDurationEnum' => ActivityDurationEnum::VACUUMING_DURATION,
              'appended' => true
          ]
        ];
    }

    /**
     * @throws ReflectionException
     * @dataProvider provideDataForGenerateRowValues
     */
    public function testGenerateRowValues(ActivityEnum $activityEnum, ActivityDurationEnum $activityDurationEnum, bool $appended): void
    {
        $refMethod = new ReflectionMethod(CSVData::class, 'generateRowValues');
        $refMethod->setAccessible(true);

        $csvData = new CSVData();

        $res = $refMethod->invoke($csvData, $activityEnum, $activityDurationEnum, $appended);

        $this->assertEquals([
            'minutes'  => $activityDurationEnum->value,
            'activity' => $appended ? '&' . $activityEnum->value : $activityEnum->value
        ], $res);
    }

    /**
     * @return array<array{row: array{}, minutes: int, values: array{}, result: array{}}>
     */
    public function provideForFillARow(): array
    {
        return [
            [
                'row' => [],
                'minutes' => 0,
                'values' => [
                    'activity' => ActivityEnum::WINDOW_CLEANING->value,
                    'minutes' => ActivityDurationEnum::WINDOW_CLEANING_DURATION->value,
                ],
                'result' => [
                    'activity' => ActivityEnum::WINDOW_CLEANING->value,
                    'minutes' => ActivityDurationEnum::WINDOW_CLEANING_DURATION->value,
                ]
            ],
            [
                'row' => ['activity' => ActivityEnum::REFRIGERATOR_CLEANING->value],
                'minutes' => 50,
                'values' => [
                    'activity' => '&' . ActivityEnum::VACUUMING->value,
                    'minutes' => ActivityDurationEnum::VACUUMING_DURATION->value,
                ],
                'result' => [
                    'activity' => ActivityEnum::REFRIGERATOR_CLEANING->value . '&' . ActivityEnum::VACUUMING->value,
                    'minutes' => ActivityDurationEnum::REFRIGERATOR_CLEANING_DURATION->value + ActivityDurationEnum::VACUUMING_DURATION->value,
                ]
            ]
        ];
    }

    /**
     * @param array{} $row
     * @param int $minutes
     * @param array{activity:string, minutes:int} $values
     * @param array{activity:string, minutes:int} $result
     * @dataProvider provideForFillARow
     */
    public function testFillARow(array $row, int $minutes, array $values, array $result): void
    {
        $csvData = new CSVData();

        $csvData->fillARow($row, $minutes, $values);

        $this->assertEquals($result['activity'], $row['activity']);
        $this->assertEquals($result['minutes'], $minutes);
    }
}
