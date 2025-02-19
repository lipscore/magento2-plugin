<?php

namespace Lipscore\RatingsReviews\Console;

use Lipscore\RatingsReviews\Model\Action\ImportRatings as ImportRatingsAction;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportRatings extends Command
{
    protected $importRatingsAction;

    public function __construct(
        ImportRatingsAction $importRatingsAction
    ) {
        $this->importRatingsAction = $importRatingsAction;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('lipscore:import:ratings');
        $this->setDescription('Lipscore - Import ratings');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->importRatingsAction->process();
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $output->writeln($e->getTraceAsString());
            }
            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }
}
