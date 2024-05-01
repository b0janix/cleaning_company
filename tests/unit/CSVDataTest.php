<?php

namespace App\Tests\unit;

use App\Enums\ActivityDurationEnum;
use App\Enums\ActivityEnum;
use App\Service\CSVData;
use DateTime;
use Exception;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionMethod;

class CSVDataTest extends TestCase
{
    /**
     * @return array{array{
     *     dates: array{
     *          lastDate: string,
     *          currentDate: DateTime,
     *          firstVacuumingDate: string,
     *          lastWorkingDate: string,
     *          holidays: array{}
     *     },
     *     result: array{
     *          date: string,
     *          activity: string,
     *          time: string
     *     }
     * }}
     */
    public function provideDataForGenerateCSVRow(): array
    {
        return [
            [
                'dates' => [
                    'lastDate'           => '2024-08-01',
                    'currentDate'        => (new DateTime('2024-05-01')),
                    'firstVacuumingDate' => '2024-05-02',
                    'lastWorkingDate'    => '2024-05-31',
                    'holidays'           => []
                ],
                'result' => [
                    "date" => "2024-05-01",
                    "activity" => "/",
                    "time" => "/"
                ],
            ],
            [
                'dates' => [
                    'lastDate'           => '2024-07-31',
                    'currentDate'        => (new DateTime('2024-04-30')),
                    'firstVacuumingDate' => '2024-04-02',
                    'lastWorkingDate'    => '2024-04-30',
                    'holidays'           => []
                ],
                'result' => [
                    "date" => "2024-04-30",
                    "activity" => "Vacuuming & Window cleaning",
                    "time" => "/"
                ],
            ],
            [
                'dates' => [
                    'lastDate'           => '2024-08-01',
                    'currentDate'        => (new DateTime('2024-05-02')),
                    'firstVacuumingDate' => '2024-05-02',
                    'lastWorkingDate'    => '2024-05-31',
                    'holidays'           => []
                ],
                'result' => [
                    "date" => "2024-05-02",
                    "activity" => "Vacuuming & Refrigerator cleaning",
                    "time" => "/"
                ],
            ],
            [
                'dates' => [
                    'lastDate'           => '2024-08-01',
                    'currentDate'        => (new DateTime('2024-06-04')),
                    'firstVacuumingDate' => '2024-06-04',
                    'lastWorkingDate'    => '2024-06-28',
                    'holidays'           => []
                ],
                'result' => [
                    "date" => "2024-06-04",
                    "activity" => "Vacuuming & Refrigerator cleaning",
                    "time" => "/"
                ],
            ],
            [
                'dates' => [
                    'lastDate'           => '2024-08-01',
                    'currentDate'        => (new DateTime('2024-05-02')),
                    'firstVacuumingDate' => '2024-05-02',
                    'lastWorkingDate'    => '2024-05-31',
                    'holidays'           => []
                ],
                'result' => [
                    "date" => "2024-05-02",
                    "activity" => "Vacuuming & Refrigerator cleaning",
                    "time" => "/"
                ],
            ],
            [
                'dates' => [
                    'lastDate'           => '2024-08-07',
                    'currentDate'        => (new DateTime('2024-05-07')),
                    'firstVacuumingDate' => '2024-05-02',
                    'lastWorkingDate'    => '2024-05-31',
                    'holidays'           => []
                ],
                'result' => [
                    "date" => "2024-05-07",
                    "activity" => "Vacuuming",
                    "time" => "/"
                ],
            ],
            [
                'dates' => [
                    'lastDate'           => '2024-08-31',
                    'currentDate'        => (new DateTime('2024-05-31')),
                    'firstVacuumingDate' => '2024-05-02',
                    'lastWorkingDate'    => '2024-05-31',
                    'holidays'           => []
                ],
                'result' => [
                    "date" => "2024-05-31",
                    "activity" => "Window cleaning",
                    "time" => "/"
                ],
            ],
            [
                'dates' => [
                    'lastDate'           => '2024-08-31',
                    'currentDate'        => (new DateTime('2024-05-31')),
                    'firstVacuumingDate' => '2024-05-02',
                    'lastWorkingDate'    => '2024-05-31',
                    'holidays'           => ['2024-05-31']
                ],
                'result' => [
                    "date" => "2024-05-31",
                    "activity" => "/",
                    "time" => "/"
                ],
            ],
            [
                'dates' => [
                    'lastDate'           => '2024-08-31',
                    'currentDate'        => (new DateTime('2024-08-31')),
                    'firstVacuumingDate' => '2024-05-02',
                    'lastWorkingDate'    => '2024-05-31',
                    'holidays'           => []
                ],
                'result' => [
                    "date" => "2024-08-31",
                    "activity" => "/",
                    "time" => "00:00"
                ],
            ],
        ];
    }


    /**
     * @param array{
     *           lastDate: string,
     *           currentDate: DateTime,
     *           firstVacuumingDate: string,
     *           lastWorkingDate: string,
     *           holidays: array{}
     *      }  $dates
     * @param array{
     *           date: string,
     *           activity: string,
     *           time: string
     *      } $result
     * @return void
     *
     * @dataProvider provideDataForGenerateCSVRow
     */
    public function testGenerateCSVRow(array $dates, array $result): void
    {
        $csvData = new CSVData();

        $minutes = 0;

        $data = $csvData->generateCSVRowData($minutes, $dates);

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

        $res = $refMethod->invoke($csvData, (new DateTime('2024-07-31')), '2024-07-31', 600);

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

        $res = $refMethod->invoke($csvData, (new DateTime('2024-04-01')), '2024-07-31', 600);

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
                'activityEnum' => ActivityEnum::VACUUMING,
                'activityDurationEnum' => ActivityDurationEnum::VACUUMING_DURATION,
                'appended' => false
            ],
            [
                'activityEnum' => ActivityEnum::REFRIGERATOR_CLEANING,
                'activityDurationEnum' => ActivityDurationEnum::REFRIGERATOR_CLEANING_DURATION,
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
            'activity' => $appended ? ' & ' . $activityEnum->value : $activityEnum->value
        ], $res);
    }

    /**
     * @return array<array{row: array{}, minutes: int, values: array{}, result: array{}}>
     */
    public function provideForFillARow(): array
    {
        return [
            [
                'row' => ['activity' => '/'],
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
                    'activity' => ' & ' . ActivityEnum::VACUUMING->value,
                    'minutes' => ActivityDurationEnum::VACUUMING_DURATION->value,
                ],
                'result' => [
                    'activity' => ActivityEnum::REFRIGERATOR_CLEANING->value . ' & ' . ActivityEnum::VACUUMING->value,
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

    /**
     * @return array{array{inputDate: string, resultDate: string}}
     */
    public function provideForFirstVacuumingDate(): array
    {
        return [
            [
                'inputDate' => '2024-05-01',
                'resultDate' => '2024-05-02'
            ],
            [
                'inputDate' => '2024-05-02',
                'resultDate' => '2024-05-02'
            ],
            [
                'inputDate' => '2024-05-03',
                'resultDate' => '2024-05-07'
            ],
            [
                'inputDate' => '2024-05-04',
                'resultDate' => '2024-05-07'
            ],
            [
                'inputDate' => '2024-05-05',
                'resultDate' => '2024-05-07'
            ],
            [
                'inputDate' => '2024-05-06',
                'resultDate' => '2024-05-07'
            ],
            [
                'inputDate' => '2024-05-07',
                'resultDate' => '2024-05-07'
            ],
        ];
    }


    /**
     * @param string $inputDate
     * @param string $resultDate
     * @return void
     *
     * @dataProvider provideForFirstVacuumingDate
     * @throws Exception
     */
    public function testFirstVacuumingDate(string $inputDate, string $resultDate): void
    {
        $csvData = new CSVData();

        $res = $csvData->determineTheFirstVacuumingDate((new DateTime($inputDate)));

        $this->assertEquals($resultDate, $res);
    }

    /**
     * @return array{array{inputDate: string, resultDate: string}}
     */
    public function provideForLastWorkingDate(): array
    {
        return [
            [
                'inputDate' => '2024-06-30',
                'resultDate' => '2024-06-28'
            ],
            [
                'inputDate' => '2024-06-29',
                'resultDate' => '2024-06-28'
            ],
            [
                'inputDate' => '2024-06-28',
                'resultDate' => '2024-06-28'
            ],
            [
                'inputDate' => '2024-06-27',
                'resultDate' => '2024-06-27'
            ],
        ];
    }

    /**
     * @param string $inputDate
     * @param string $resultDate
     * @return void
     *
     * @dataProvider provideForLastWorkingDate
     * @throws Exception
     */
    public function testLastWorkingDate(string $inputDate, string $resultDate): void
    {
        $csvData = new CSVData();

        $res = $csvData->determineTheLastWorkingDate((new DateTime($inputDate)));

        $this->assertEquals($resultDate, $res);
    }
}
