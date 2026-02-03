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
    name: 'app:add-document',
    description: 'Add a document to a search index',
)]
class AddDocumentCommand extends Command
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
            ->addArgument('index-name', InputArgument::OPTIONAL, 'Name of the index to add document to', 'documents')
            ->addOption('id', 'i', InputOption::VALUE_REQUIRED, 'Document ID')
            ->addOption('title', 't', InputOption::VALUE_REQUIRED, 'Document title')
            ->addOption('content', 'c', InputOption::VALUE_REQUIRED, 'Document content')
            ->addOption('data', 'd', InputOption::VALUE_REQUIRED, 'JSON string with document data');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $indexName = $input->getArgument('index-name');
        $id = $input->getOption('id');
        $title = $input->getOption('title');
        $content = $input->getOption('content');
        $data = $input->getOption('data');

        // Build document from options
        $document = [];
        
        if ($data) {
            $jsonData = json_decode($data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $io->error("Invalid JSON data provided");
                return Command::FAILURE;
            }
            $document = $jsonData;
        }
        
        // Override with specific options if provided
        if ($id !== null) {
            $document['id'] = $id;
        }
        if ($title !== null) {
            $document['title'] = $title;
        }
        if ($content !== null) {
            $document['content'] = $content;
        }

        // Validate required fields
        if (!isset($document['id'])) {
            $io->error("Document ID is required (use --id option or include in JSON data)");
            return Command::FAILURE;
        }

        $io->note("Adding document to index: {$indexName}");
        $io->comment("Document ID: {$document['id']}");

        $result = $this->manticoreService->addDocument($indexName, $document);

        if ($result) {
            $io->success("Document added successfully to index '{$indexName}'");
            return Command::SUCCESS;
        } else {
            $io->error("Failed to add document to index '{$indexName}'");
            return Command::FAILURE;
        }
    }
}