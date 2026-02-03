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
    name: 'app:delete-index',
    description: 'Delete a search index',
)]
class DeleteIndexCommand extends Command
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
            ->addArgument('index-name', InputArgument::REQUIRED, 'Name of the index to delete');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $indexName = $input->getArgument('index-name');

        $io->caution("You are about to delete index: {$indexName}");
        $io->note("This action cannot be undone!");

        // Confirm deletion
        if (!$io->confirm('Do you really want to delete this index?', false)) {
            $io->note('Operation cancelled.');
            return Command::SUCCESS;
        }

        $result = $this->manticoreService->deleteIndex($indexName);

        if ($result) {
            $io->success("Index '{$indexName}' deleted successfully!");
            return Command::SUCCESS;
        } else {
            $io->error("Failed to delete index '{$indexName}'");
            return Command::FAILURE;
        }
    }
}