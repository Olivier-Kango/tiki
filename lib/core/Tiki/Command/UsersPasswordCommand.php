<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use TikiLib;

class UsersPasswordCommand extends Command
{
    protected static $defaultDescription = 'Set the password to a given user';
    protected function configure()
    {
        $this
            ->setName('users:password')
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force set password'
            )
            ->addArgument(
                'username',
                InputArgument::OPTIONAL,
                'User login name'
            )
            ->addArgument(
                'password',
                InputArgument::OPTIONAL,
                'User new password'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        global $prefs;

        $userlib = TikiLib::lib('user');
        $logslib = TikiLib::lib('logs');
        $helper = $this->getHelper('question');

        /* ============================== */
        /* = username argument = */
        /* ============================== */
        $user = $input->getArgument('username');

        // If the username is not provided as an argument,
        if (empty($user)) {
            // try to get it from the environment variable
            $env_user = getenv('USERNAME');
            $show_env_user = $env_user ? " [{$env_user}]" : '';
            // If the environment variable is not set, prompt the user for the username interactively
            $question = new Question("Please enter the username {$show_env_user}: ");
            $user = $helper->ask($input, $output, $question) ?: $env_user;
        }

        // If the username is still empty, exit with an error
        if (! $user) {
            $output->writeln('<error>The username argument is required.</error>');
            return Command::FAILURE;
        }

        // Check if the user exists
        if (! $userlib->user_exists($user)) {
            $output->writeln("<error>User {$user} does not exist.</error>");
            exit(1);
        }

        /* ============================== */
        /* = password argument = */
        /* ============================== */
        $password = $input->getArgument('password');

        // If the password is not provided as an argument,
        if (empty($password)) {
            // try to get it from the environment variable
            $password = getenv('PASSWORD');
            if (empty($password)) {
                // If the environment variable is not set, prompt the user for the password interactively
                $question = new Question('Please enter the new password: ');
                $question->setHidden(true);
                $question->setHiddenFallback(false);
                $password = $helper->ask($input, $output, $question);
            }
        }

        // If the password is still empty, exit with an error
        if (empty($password)) {
            $output->writeln("<error>Password cannot be empty.</error>");
            exit(1);
        }

        // Check password constraints
        $polerr = $userlib->check_password_policy($password);
        if (! empty($polerr)) {
            $output->writeln("<error>{$polerr}</error>");
            exit(1);
        }

        if ($prefs['auth_method'] != 'tiki') {
            $output->writeln("<info>\nWarning: Tiki authentication method set to: <options=bold>" . $prefs['auth_method'] . "</>\n"
            . "Depending on the settings for this authentication method, \n"
            . "this change of the local password might not be enough for the user to be able to login</info>"
            . "\n");
        }

        if ($prefs['feature_user_encryption'] === 'y' && ! $input->getOption('force')) {
            $output->writeln("<error>User encryption feature is enabled.\n" .
                "Changing the user password might loose encrypted data.\n\n" .
                "Use -f to force changing password.</error>");
            return Command::FAILURE;
        }

        $userlib->change_user_password($user, $password);
        $output->writeln('Password changed successfully.');
        $logslib->add_action('adminusers', 'system', 'system', 'Password changed for ' . $user);
        return Command::SUCCESS;
    }
}
