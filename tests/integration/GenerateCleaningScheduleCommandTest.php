<?php

namespace App\Tests\integration;

use App\Enums\ActivityDurationEnum;
use App\Service\CSVData;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;

class GenerateCleaningScheduleCommandTest extends KernelTestCase
{
    public function testExecuteForStartDateSet(): void
    {
        self::bootKernel();

        if (self::$kernel instanceof KernelInterface) {
            $application = new Application(self::$kernel);

            $command = $application->find('generate:schedule');
            $commandTester = new CommandTester($command);
            $commandTester->execute(['startDate' => '2024-05-01']);
            $commandTester->assertCommandIsSuccessful();
            $output = $commandTester->getDisplay();

            $this->assertStringContainsString("Your schedule for the next three months has been created.\n", $output);
        }
    }

    public function testTotalTimeAmount(): void
    {

        $jsonString = (string) file_get_contents('tests/fixtures/2024-05-01___2024-08-01___schedule.json');

        if (empty($jsonString)) {
            $this->fail('Not able to get the fixture data');
        }

        $data = json_decode($jsonString, true);

        $path = "tests/files/2024-05-01___2024-08-01___schedule.csv";

        $totalTime = '00:00';

        if (file_exists($path)) {
            $rows = (array) file($path);
            $lastRow = array_pop($rows);
            if (is_string($lastRow) && substr_count($lastRow, ',') === 2) {
                $totalTime = str_getcsv($lastRow)[2];
            }
        } else {
            $this->fail("The file path $path does not exist");
        }

        if (!is_array($data) || empty($data)) {
            $this->fail('Not able to get the fixture data');
        }

        $last = array_key_last($data);

        $this->assertEquals($data[$last]['TotalTime'], $totalTime);
    }

    public function testExecuteNotValid(): void
    {
        self::bootKernel();

        if (self::$kernel instanceof KernelInterface) {

            $application = new Application(self::$kernel);

            $command = $application->find('generate:schedule');
            $commandTester = new CommandTester($command);
            $commandTester->execute(['holidays' => ['aaaa']]);
            $output = $commandTester->getDisplay();

            $this->assertStringContainsString("You haven't provide a valid startDate. We will take the current date as a start date.\n", $output);
            $this->assertStringContainsString("Please provide valid holidays dates.\n", $output);
        }
    }

    public function testExecuteForStartDateAndHolidaysSet(): void
    {
        self::bootKernel();

        if (self::$kernel instanceof KernelInterface) {
            $application = new Application(self::$kernel);

            $command = $application->find('generate:schedule');
            $commandTester = new CommandTester($command);
            $commandTester->execute(['startDate' => '2024-05-01', 'holidays' => ['2024-05-31']]);
            $commandTester->assertCommandIsSuccessful();
            $output = $commandTester->getDisplay();

            $this->assertStringContainsString("Your schedule for the next three months has been created.\n", $output);
        }
    }

    public function testTotalTimeAmountWithHolidays(): void
    {
        $path = "tests/files/2024-05-01___2024-08-01___schedule.csv";

        $jsonString = (string) file_get_contents('tests/fixtures/2024-05-01___2024-08-01___schedule.json');

        if (empty($jsonString)) {
            $this->fail('Not able to get the fixture data');
        }

        $data = json_decode($jsonString, true);

        $totalTime = '00:00';

        if (file_exists($path)) {
            $rows = (array) file($path);
            $lastRow = array_pop($rows);
            if (is_string($lastRow) && substr_count($lastRow, ',') === 2) {
                $totalTime = str_getcsv($lastRow)[2];
            }
        } else {
            $this->fail("The file path $path does not exist");
        }

        if (!is_array($data) || empty($data)) {
            $this->fail('Not able to get the fixture data');
        }

        $last = array_key_last($data);

        $timeArr = explode(':', $data[$last]['TotalTime']);

        $min = (((int) $timeArr[0]) * 60) + ((int) $timeArr[1]) - ActivityDurationEnum::WINDOW_CLEANING_DURATION->value;

        $refMethod = new ReflectionMethod(CSVData::class, 'calculateHours');
        $refMethod->setAccessible(true);

        $csvData = new CSVData();

        $res = $refMethod->invoke($csvData, $min);

        $this->assertEquals($res, $totalTime);
    }

    public function testFilesExist(): void
    {
        $filePath1 = $_ENV['FILE_PATH'] . '2024-05-01' . '___' . '2024-08-01' . '___schedule.csv';

        if (file_exists($filePath1)) {
            $this->assertFileExists($filePath1);
            //unlink($filePath1);
        } else {
            $this->fail("The file path $filePath1 does not exist");
        }
    }
}
