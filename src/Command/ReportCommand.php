<?php

declare(strict_types=1);

namespace App\Command;

use DateTime;
use DateInterval;
use App\Components\ReportFetcher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;

class ReportCommand extends Command
{
    private const REPORT_FORMAT = 'Y-m-d'; //date format
    private const DIFF_FORMAT = '%R%a'; // signing count of days
    private const INTERVAL = 'P1D'; // 1 day

    protected static $defaultName = 'app:report';

    protected function configure(): void
    {
        $this->setDescription('Report 14Day Retention')
            ->addArgument('fromDate', InputArgument::REQUIRED, 'Date from (format YYYY-MM-DD)')
            ->addArgument('toDate', InputArgument::REQUIRED, 'Date to (format YYYY-MM-DD)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = new Table($output->section());
        $table->setHeaders(['date', 'return_14d']);
        $table->render();

        $from = new DateTime($input->getArgument('fromDate'));
        $to = new DateTime($input->getArgument('toDate'));

        $start = microtime(true);

        try {
            $this->process($from, $to, $table);
        } catch (\Throwable $e) {
            $table->appendRow([new TableCell($e->getMessage(), ['colspan' => 2])]);
        }

        $output->writeln(sprintf("\nCompleted in %01.2f seconds", microtime(true) - $start));

        return 0;
    }

    private function process(DateTime $from, DateTime $to, Table &$table): void
    {
        $diff = (int) $to->diff($from)->format(self::DIFF_FORMAT);

        if ($diff > 0) {
            throw new \Exception('FromDate can not be bigger than ToDate');
        }

        $section = (new ConsoleOutput())->section();
        $progress = new ProgressBar($section, abs($diff)+1);

        ProgressBar::setFormatDefinition('process', "%message%\t%current%/%max%\t[%bar%]\t%memory:6s%");

        $progress->setFormat('process');
        $progress->setMessage('Processing...');
        $progress->start();

        $report = new ReportFetcher();

        for ($i=$diff; $i <= 0; $i++) {
            $count = $report->getCount($from);
            $table->appendRow([$from->format(self::REPORT_FORMAT), $count]);
            $from->add(new DateInterval(self::INTERVAL));
            $progress->advance();
        }

        $progress->finish();
        $section->clear();
    }
}
