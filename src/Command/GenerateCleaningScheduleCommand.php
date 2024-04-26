<?php

namespace App\Command;

use App\Service\CSVData;
use DateInterval;
use DatePeriod;
use DateTime;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'generate:schedule',
    description: 'Generates a cleaning schedule for a fictional company.',
)]
class GenerateCleaningScheduleCommand extends Command
{
    public function __construct(private readonly CSVData $csvData)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Generates a cleaning schedule for a fictional company.')
            ->setHelp('This command allows you to generate a CSV file with a a cleaning schedule for the next three months.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $now = new DateTime();

        $inThreeMonths = new DateTime('+3 months');

        $path = $_ENV['FILE_PATH'] . $now->format('Y-m-d') . '___' . $inThreeMonths->format('Y-m-d') . '___schedule.csv';

        try {

            $fp = fopen($path, 'w');

            if (is_resource($fp)) {
                fputcsv($fp, ['Date', 'Activity', 'TotalTime']);

                fclose($fp);
            }

            $period = new DatePeriod(
                $now,
                (new DateInterval('P1D')),
                $inThreeMonths,
                DatePeriod::INCLUDE_END_DATE
            );

            $last = $period->getEndDate();

            $fp = fopen($path, 'a');

            $minutes = 0;

            if (is_resource($fp)) {
                foreach ($period as $date) {
                    fputcsv($fp, $this->csvData->generateCSVRowData($minutes, $last, $date));
                }

                fclose($fp);
            }

            $io->writeln('Your schedule for the next three months has been created.');

        } catch (\Exception $e) {

            if (isset($fp) && is_resource($fp)) {
                fclose($fp);
            }

            $io->writeln($e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
