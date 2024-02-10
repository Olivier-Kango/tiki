<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'ml:train',
    description: 'Train a particular machine learning model'
)]
class MLTrainCommand extends Command
{
    protected function configure()
    {
        $this
            ->addArgument(
                'mlmId',
                InputArgument::REQUIRED,
                'Machine learning model ID'
            )
            ->addOption(
                'test',
                null,
                InputOption::VALUE_NONE,
                'Test model training on a sample of the data.'
            )
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $mlmId = $input->getArgument('mlmId');
        $mllib = \TikiLib::lib('ml');

        $model = $mllib->get_model($mlmId);
        if (! $model) {
            $output->writeln("<error>Model $mlmId not found.</error>");
            return (int) false;
        }

        $test = $input->getOption('test');

        try {
            $mllib->train($model, $test);
            $output->writeln("Successfully trained model {$model['name']}.");
        } catch (Exception $e) {
            $output->writeln("<error>Error while trying to train model " . $model['name'] . ": " . $e->getMessage() . "</error>");
        }
        return Command::SUCCESS;
    }
}
