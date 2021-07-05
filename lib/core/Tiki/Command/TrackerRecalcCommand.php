<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Perms;
use Symfony\Component\Console\Helper\ProgressBar;
use TikiLib;
use Tracker_Definition;

class TrackerRecalcCommand extends Command
{
	protected function configure()
	{
		$this
			->setName('tracker:recalc')
			->setDescription('Recalculate all math fields items from a tracker')
			->addOption(
				'trackerId',
				null,
				InputOption::VALUE_REQUIRED,
				'List of tracker IDs to be recalculated, separated by comma (,)'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$io = new SymfonyStyle($input, $output);
		$trackerLib = TikiLib::lib('trk');
		$trackerIds = $input->getOption('trackerId');
		$trackerIds = ! empty($trackerIds) ? explode(',', $trackerIds) : [];

		if (empty($trackerIds)) {
			$io->error('No trackerId was specified. Please use --trackerId=<TRACKER_ID');
			return 1;
		}

		foreach ($trackerIds as $trackerId) {
			$io->section('TrackerId ' . $trackerId);
			$trackerDef = Tracker_Definition::get($trackerId);
			if (! $trackerDef) {
				$io->writeln('<error>> Tracker not found</error>');
				continue;
			}

			$perms = Perms::get('tracker', $trackerId);
			if (! $perms->admin_trackers) {
				$io->writeln('<error>> Admin permission required</error>');
				continue;
			}

			$trackerFields = $trackerDef->getFields();
			$mathFields = [];

			foreach ($trackerFields as $field) {
				if ($field['type'] == 'math') {
					$mathFields[$field['fieldId']] = $field;
				}
			}

			if (empty($mathFields)) {
				$io->writeln('<comment>> Math fields not found</comment>');
				continue;
			}

			$trackerItems = $trackerLib->get_all_tracker_items($trackerId);

			if (empty($trackerItems)) {
				$io->writeln('<comment>> Do not have any items. Skipping.</comment>');
				continue;
			}

			ProgressBar::setFormatDefinition('minimal', '<comment>> Recalculating %percent%%</comment>');
			$progressBar = new ProgressBar($output, count($trackerItems));
			$progressBar->setFormat('minimal');
			$progressBar->start();

			\array_map(function ($itemId) use ($mathFields, $trackerLib, $progressBar) {
				$item = $trackerLib->get_tracker_item($itemId);

				foreach ($mathFields as $field) {
					$handler = $trackerLib->get_field_handler($field, $item);
					$handler->recalculate();
				}

				$progressBar->advance();
			}, $trackerItems);

			$progressBar->finish();
			$io->newLine();

			$io->writeln('<comment>> Finished</comment>');
			$io->newLine();
		}

		return 0;
	}
}
