<?php

namespace App\Command;

use App\Service\CSVData;
use DateInterval;
use DatePeriod;
use DateTime;
use DateTimeImmutable;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

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
            ->setHelp('This command allows you to generate a CSV file with a a cleaning schedule for the next three months.')
            ->addArgument(
                'startDate',
                InputArgument::OPTIONAL,
                'The start date of the cleaning schedule. (use the format "Y-m-d")',
            )
            ->addArgument(
                'holidays',
                InputArgument::IS_ARRAY,
                'Which dates are holidays in the next three months? (use the format "Y-m-d")'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {

            $holidays = is_array($input->getArgument('holidays')) ? $input->getArgument('holidays') : [];

            $dates = [
              'lastDate'           => '',
              'currentDate'        => '',
              'firstVacuumingDate' => '',
              'lastWorkingDate'    => '',
              'holidays'           => $holidays
            ];

            $startDate = is_string($input->getArgument('startDate')) ? $input->getArgument('startDate') : '';

            if (!$startDate || !$now = DateTime::createFromFormat('Y-m-d', $startDate)) {
                $io->writeln('You haven\'t provide a valid startDate. We will take the current date as a start date.');
                $now = new DateTimeImmutable();
            } else {
                $now = new DateTimeImmutable($now->format('Y-m-d'));
            }

            $currentMonth = '';
            $inThreeMonths = $now->add(new DateInterval('P3M'));

            $path = $_ENV['FILE_PATH'] . $now->format('Y-m-d') . '___' . $inThreeMonths->format('Y-m-d') . '___schedule.csv';

            $fp = fopen($path, 'w');

            if (!is_resource($fp)) {

                throw new Exception('There are problems with creating the csv file.');

            }

            fputcsv($fp, ['Date', 'Activity', 'TotalTime']);

            $period = new DatePeriod(
                $now,
                (new DateInterval('P1D')),
                $inThreeMonths,
                DatePeriod::INCLUDE_END_DATE
            );

            foreach ($holidays as $holiday) {
                $dateTimeObj = DateTime::createFromFormat('Y-m-d', $holiday);

                if (
                    !($dateTimeObj instanceof DateTime) ||
                    ($dateTimeObj > $period->getEndDate()) ||
                    ($dateTimeObj < $period->getStartDate())
                ) {
                    $io->writeln('Please provide valid holidays dates.');

                    return Command::FAILURE;
                }
            }

            $dates['lastDate'] = $period->getEndDate()->format('Y-m-d');

            $minutes = 0;

            foreach ($period as $dateObj) {
                //we are detecting the change of the month in order to calculate the first vacuuming day and the last working day for each month
                //and then pass them as arguments
                if ($currentMonth !== $dateObj->format('m')) {
                    $currentMonth = $dateObj->format('m');

                    $firstDateObj = new DateTime($dateObj->format('Y-m-01'));

                    $obj = DateTime::createFromFormat(
                        'Y-m-d',
                        $this->csvData->determineTheFirstVacuumingDate($firstDateObj)
                    );

                    $dates['firstVacuumingDate'] = is_bool($obj) ? throw new Exception('Invalid first dDate format') : $obj->format('Y-m-d');

                    $lastDateObj = new DateTime($dateObj->format('Y-m-t'));

                    $obj = DateTime::createFromFormat(
                        'Y-m-d',
                        $this->csvData->determineTheLastWorkingDate($lastDateObj)
                    );

                    $dates['lastWorkingDate'] = is_bool($obj) ? throw new Exception('Invalid last date format') : $obj->format('Y-m-d');
                }

                $dates['currentDate'] = $dateObj;

                fputcsv($fp, $this->csvData->generateCSVRowData($minutes, $dates));
            }

            fclose($fp);

            $io->writeln('Your schedule for the next three months has been created.');

        } catch (Throwable $e) {

            if (isset($fp) && is_resource($fp)) {
                fclose($fp);
            }

            $io->writeln($e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
