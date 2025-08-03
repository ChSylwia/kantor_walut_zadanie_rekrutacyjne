<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\LastRatesUpdater\LastRatesUpdaterService;
use DateTimeImmutable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class UpdateRatesCommand extends Command
{
    protected static $defaultName = 'app:update-rates';

    public function __construct(
        private readonly LastRatesUpdaterService $ratesUpdaterService
    ) {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->setDescription('Update currency rates from NBP API to Airtable');
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $updatedRecords = $this->ratesUpdaterService->updateRates();
            $io->success(sprintf('Successfully updated %d currency rates', count($updatedRecords)));
            return 0;
        } catch (\Exception $e) {
            $io->error('Failed to update rates: ' . $e->getMessage());
            return 1;
        }
    }
}
