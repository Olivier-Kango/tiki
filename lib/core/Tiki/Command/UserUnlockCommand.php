<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UserUnlockCommand extends Command
{
	protected function configure()
	{
		$this
			->setName('users:unlock')
			->setDescription('Unlock a user')
			->addArgument(
				'identifiers',
				InputArgument::IS_ARRAY | InputArgument::REQUIRED,
				'Logins or emails'
			)
			->addOption(
				'format',
				null,
				InputOption::VALUE_REQUIRED,
				'Output format',
				'table'
			);
	}

	private function _unlock_users ($identifiers)
	{
		$userlib = \TikiLib::lib('user');

		$return = [];
		foreach ($identifiers as $identifier) {
			$login = filter_var($identifier, FILTER_VALIDATE_EMAIL)
				? $userlib->get_user_by_email($identifier)
				: $identifier;
			$user = $userlib->get_user_info($login, false, 'login');

			$row = [ 'user' => $identifier ];
			if (empty($user)) {
				$row['result'] = 'error';
				$row['message'] = 'user not found';
			}
			elseif (empty($user['valid']) && empty($user['waiting'])) {
				$row['result'] = 'success';
				$row['message'] = 'user already unlocked';
			}
			else {
				$userlib->confirm_user($user['login']);
				$row['result'] = 'success';
				$row['message'] = 'user unlocked';
			}
			$return[] = $row;
		}
		return $return;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		global $prefs;

		$identifiers = $input->getArgument('identifiers');
		$format = $input->getOption('format') ?? 'table';

		$result = $this->_unlock_users($identifiers);

		if ($format === 'json') {
			$output->write(json_encode($result, JSON_PRETTY_PRINT));
		}
		else {
			$header = array_keys($result[0]);
			$result = array_map('array_values', $result);

			$table = new Table($output);
			$table->setHeaders($header);
			$table->setRows($result);
			$table->render();
		}
	}
}
