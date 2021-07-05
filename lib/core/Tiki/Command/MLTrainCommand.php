<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MLTrainCommand extends Command
{
	protected function configure()
	{
		$this
			->setName('ml:train')
			->setDescription('Train a particular machine learning model')
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

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$mlmId = $input->getArgument('mlmId');
		$mllib = \TikiLib::lib('ml');

		$model = $mllib->get_model($mlmId);
		if (! $model) {
			$output->writeln("<error>Model $mlmId not found.</error>");
			return false;
		}

		$test = $input->getOption('test');

		try {
			$mllib->train($model, $test);
			$output->writeln("Successfully trained model {$model['name']}.");
		} catch (Exception $e) {
			$output->writeln("<error>Error while trying to train model " . $model['name'] . ": " . $e->getMessage() . "</error>");
		}
	}
}
