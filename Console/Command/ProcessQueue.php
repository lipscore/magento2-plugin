<?php

declare(strict_types=1);

namespace Lipscore\RatingsReviews\Console\Command;

use Lipscore\RatingsReviews\Model\Action\ProcessLipscoreQueue as Action;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessQueue extends Command
{
    protected $action;

    public function __construct(
        Action $action,
        ?string $name = null
    ) {
        $this->action = $action;

        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName('lipscore:queue')
            ->setDescription('Asks for reviews data if product is present in queue table');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->action->execute();
            $output->writeln('<info>Lipscore Queue Finished</info>');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln('<error>Lipscore Queue Failed</error>');

            return Command::FAILURE;
        }
    }
}
