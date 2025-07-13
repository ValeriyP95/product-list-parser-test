<?php

namespace App\Command;

use App\Model\Product\Processor\ProductListParsingProcessor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:product:parse-list',
    description: 'Parse product list',
)]
class ProductParseListCommand extends Command
{
    public function __construct(
        private ProductListParsingProcessor $productListParsingProcessor,
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

        $io->info('Starting processing product list parsing with dry run mode: ' . ($isDryRun ? 'ON' : 'OFF') . '.');

        $this->productListParsingProcessor->process($isDryRun);

        $io->success('Product list parsing processed successfully.');

        return Command::SUCCESS;
    }
}
