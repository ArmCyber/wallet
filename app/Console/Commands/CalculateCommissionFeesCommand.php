<?php

namespace App\Console\Commands;

use App\Exceptions\CsvLoadException;
use App\Exceptions\InvalidOperationException;
use App\Services\CommissionService;
use App\Services\CsvReaderService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as BaseCommand;

class CalculateCommissionFeesCommand extends Command
{
    /**
     * CSV Reader service
     *
     * @var CsvReaderService
     */
    private CsvReaderService $csvReaderService;

    /**
     * Commission service
     *
     * @var CommissionService
     */
    private CommissionService $commissionService;

    /**
     * Line of CSV
     *
     * @var int
     */
    private int $line = 0;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commission-fees:calculate {filename}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate commission fees from CSV.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(CsvReaderService $csvReaderService, CommissionService $commissionService)
    {
        $this->csvReaderService = $csvReaderService;
        $this->commissionService = $commissionService;

        // Load CSV File
        try {
            $filename = $this->argument('filename');
            $csvReaderService->loadFile($filename);
        } catch (CsvLoadException $exception) {
            $this->handleException($exception);
            return BaseCommand::FAILURE;
        }

        $this->calculateOperationFee();

        return BaseCommand::SUCCESS;
    }

    /**
     * Recursive function which calculates fee line by line.
     *
     * @return void
     */
    private function calculateOperationFee(): void
    {
        $operation = $this->csvReaderService->getLine();
        if ($operation === false) {
            // Means all lines completed
            return;
        }

        ++$this->line;

        try {
            $fee = $this->commissionService->calculateOperationFee($operation);
            $this->line($fee);
        } catch (InvalidOperationException $exception) {
            $this->warn('Line ' . $this->line . ' failed: ' . $exception->getMessage());
        }

        // Calculate the next line
        $this->calculateOperationFee();
    }

    /**
     * Print exception message
     *
     * @param $exception
     * @return void
     */
    private function handleException($exception)
    {
        $this->error('Command failed. Exception message: ' . $exception->getMessage());
    }
}
