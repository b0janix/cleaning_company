<?php

namespace App\Tests\integration;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;

//tools/php-cs-fixer/vendor/bin/php-cs-fixer fix src
//php bin/phpunit
//vendor/bin/phpstan analyse

class GenerateCleaningScheduleCommandTest extends KernelTestCase
{
    public function testExecute(): void
    {
        self::bootKernel();

        if (self::$kernel instanceof KernelInterface) {
            $application = new Application(self::$kernel);

            $command = $application->find('generate:schedule');
            $commandTester = new CommandTester($command);
            $commandTester->execute([]);

            $commandTester->assertCommandIsSuccessful();

            // the output of the command in the console
            $output = $commandTester->getDisplay();

            $this->assertStringContainsString("Your schedule for the next three months has been created.\n", $output);
        }

    }

    public function testExecuteFileCreated(): void
    {
        $now = new \DateTime();
        $inThreeMonths = new \DateTime('+ 3 months');
        $filePath = $_ENV['FILE_PATH'] . $now->format('Y-m-d') . '___' . $inThreeMonths->format('Y-m-d') . '___schedule.csv';

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        self::bootKernel();

        if (self::$kernel instanceof KernelInterface) {
            $application = new Application(self::$kernel);

            $command = $application->find('generate:schedule');
            $commandTester = new CommandTester($command);
            $commandTester->execute([]);

            $this->assertFileExists($filePath);
        }
    }
}
