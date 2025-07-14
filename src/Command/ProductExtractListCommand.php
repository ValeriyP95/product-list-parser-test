<?php

namespace App\Command;

use App\Model\Product\Processor\ProductListExtractionProcessor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:product:extract-list',
    description: 'Extract product list',
)]
class ProductExtractListCommand extends Command
{
    public function __construct(
        private ProductListExtractionProcessor $productListExtractionProcessor,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_OPTIONAL, 'Is dry run', false)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $isDryRun = $input->getOption('dry-run');

        $io->info('Starting processing product list extraction with dry run mode: ' . ($isDryRun ? 'ON' : 'OFF') . '.');

        $this->productListExtractionProcessor->process($isDryRun);

        $io->success('Product list extraction processed successfully.');

        return Command::SUCCESS;
    }
}
