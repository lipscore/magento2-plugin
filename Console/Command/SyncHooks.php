<?php

declare(strict_types=1);

namespace Lipscore\RatingsReviews\Console\Command;

use Lipscore\RatingsReviews\Model\ApiKeyRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncHooks extends Command
{
    protected $repository;

    public function __construct(
        ApiKeyRepository $repository,
        ?string $name = null
    ) {
        $this->repository = $repository;

        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName('lipscore:hooks:sync')
            ->setDescription('Asks Lipscore API for product refresh');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->repository->syncKeys();
        $output->writeln("<info>Lipscore Sync Finished</info>");

        return 0;
    }
}
