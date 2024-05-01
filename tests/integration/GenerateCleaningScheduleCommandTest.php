<?php

namespace App\Tests\integration;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;

class GenerateCleaningScheduleCommandTest extends KernelTestCase
{
    public function testExecuteNotValid(): void
    {
        self::bootKernel();

        if (self::$kernel instanceof KernelInterface) {

            $application = new Application(self::$kernel);

            $command = $application->find('generate:schedule');
            $commandTester = new CommandTester($command);
            $commandTester->execute(['holidays' => ['aaaa']]);
            $output = $commandTester->getDisplay();

            $this->assertStringContainsString("You haven't provided a valid startDate. We will take the current date as a start date.\n", $output);
            $this->assertStringContainsString("Please provide valid holidays dates.\n", $output);
        }
    }

    /**
     * @return array{array{startDate: string, holidays: array{}, time: string}}
     */
    public function provideForTextExecuteForArgumentsSet(): array
    {
        return [
            [
                'startDate' => '2024-05-01',
                'holidays' => [],
                'time' => '14:32'
            ],
            [
                'startDate' => '2024-05-01',
                'holidays' => ['2024-05-31'],
                'time' => '13:57'
            ],
        ];
    }

    /**
     * @param string $startDate
     * @param array{} $holidays
     * @param string $time
     * @return void
     *
     * @dataProvider provideForTextExecuteForArgumentsSet
     */
    public function testExecuteForStartDateSet(string $startDate, array $holidays, string $time): void
    {
        $path = "tests/files/2024-05-01___2024-08-01___schedule.csv";

        if (file_exists($path)) {
            unlink($path);
        }

        self::bootKernel();

        if (self::$kernel instanceof KernelInterface) {
            $application = new Application(self::$kernel);

            $command = $application->find('generate:schedule');
            $commandTester = new CommandTester($command);
            $commandTester->execute(['startDate' => $startDate, 'holidays' => $holidays]);
            $commandTester->assertCommandIsSuccessful();
            $output = $commandTester->getDisplay();

            $this->assertFileExists($path);
            $this->assertStringContainsString("Your schedule for the next three months has been created.\n", $output);
        }

        $rows = file($path);

        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);

        $lastRow = array_pop($rows);

        $this->assertIsString($lastRow);
        $this->assertNotEmpty($lastRow);
        $this->assertTrue(substr_count($lastRow, ',') === 2);

        $totalTime = str_getcsv($lastRow)[2];

        $this->assertEquals($time, $totalTime);
    }
}
