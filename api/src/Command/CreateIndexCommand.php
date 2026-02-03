<?php

namespace App\Command;

use App\Service\ManticoreService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-index',
    description: 'Create a new search index',
)]
class CreateIndexCommand extends Command
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
            ->addArgument('index-name', InputArgument::REQUIRED, 'Name of the index to create')
            ->addOption('field', 'f', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Fields to add to the index in format name:type (e.g., title:text, category:string)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $indexName = $input->getArgument('index-name');
        
        // Parse fields from command line options
        $fieldOptions = $input->getOption('field');
        $fields = [];
        
        foreach ($fieldOptions as $fieldOption) {
            if (strpos($fieldOption, ':') !== false) {
                [$fieldName, $fieldType] = explode(':', $fieldOption, 2);
                $fields[$fieldName] = $fieldType;
            } else {
                $io->error("Invalid field format: {$fieldOption}. Use name:type format.");
                return Command::FAILURE;
            }
        }
        
        // Use default fields if none provided
        if (empty($fields)) {
            $fields = [
                'title' => 'text',
                'content' => 'text',
                'category' => 'string',
                'date_added' => 'timestamp'
            ];
        }

        $io->note("Creating index: {$indexName}");
        $io->comment("Fields: " . json_encode($fields));

        $result = $this->manticoreService->createIndex($indexName, $fields);

        if ($result) {
            $io->success("Index '{$indexName}' created successfully!");
            return Command::SUCCESS;
        } else {
            $io->error("Failed to create index '{$indexName}'");
            return Command::FAILURE;
        }
    }
}