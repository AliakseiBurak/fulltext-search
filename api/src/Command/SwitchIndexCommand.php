<?php

namespace App\Command;

use App\Service\ManticoreService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:switch-index',
    description: 'Switch active search index (for versioning)',
)]
class SwitchIndexCommand extends Command
{
    private ManticoreService $manticoreService;

    public function __construct(ManticoreService $manticoreService)
    {
        $this->manticoreService = $manticoreService;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('index-name', InputArgument::REQUIRED, 'Name of the index to switch to');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $indexName = $input->getArgument('index-name');

        $io->note("Switching to index: {$indexName}");

        $result = $this->manticoreService->switchActiveIndex($indexName);

        if ($result) {
            $io->success("Successfully switched to index: {$indexName}");
            return Command::SUCCESS;
        } else {
            $io->error("Failed to switch to index: {$indexName}");
            return Command::FAILURE;
        }
    }
}