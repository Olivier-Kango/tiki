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
use Symfony\Component\Console\Style\SymfonyStyle;
use TikiLib;

class UsersPasswordCommand extends Command
{
    /**
     * ============================================
     * Constants for the output messages
     *  ============================================
     */
    const MSG_PASSWORD_CHANGED = 'Password changed successfully.';
    const MSG_TOO_MANY_ARGUMENTS = 'Wrong number of arguments.';
    const MSG_USER_REQUIRED = 'The username argument is required.';
    const MSG_PASSWORD_REQUIRED = 'Password cannot be empty.';
    const MSG_USER_DOES_NOT_EXIST = 'User %s does not exist.';
    const MSG_PROMPT_USER = 'Please enter the username %s:';
    const MSG_PROMPT_PASSWORD = 'Please enter the new password: ';
    const MSG_ENCRYPTION_FT_NOTICE = "User encryption feature is enabled.";
    /**
     * ============================================
     */

    private $userlib;

    protected static $defaultDescription = 'Set the password to a given user';

    public function __construct($userlib = null)
    {
        $this->userlib = $userlib ?: TikiLib::lib('user');
        parent::__construct();
    }

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
                'params',
                InputArgument::IS_ARRAY,
                'User login name and password'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        global $prefs;

        $logslib = TikiLib::lib('logs');
        $helper = $this->getHelper('question');

        $params = $input->getArgument('params');

        // If more than 2 arguments are provided, exit with an error
        if (count($params) > 2) {
            $io = new SymfonyStyle($input, $output);
            $io->error($this::MSG_TOO_MANY_ARGUMENTS);
            $io->writeln('Maybe your password contains special characters messing up the command line syntax?');
            $io->writeln('===================================================================================');
            $io->note([
                'Please try again with the following command:',
                'php console.php users:password [username]',
                'A prompt will ask for the password'
            ]);
            return Command::FAILURE;
        }

        /* ============================== */
        /* = username argument = */
        /* ============================== */
        $user = $params[0] ?? null;

        // If the username is not provided as an argument,
        if (empty($user)) {
            // try to get it from the environment variable
            $env_user = getenv('USERNAME');
            $show_env_user = $env_user ? "[{$env_user}]" : '';
            // If the environment variable is not set, prompt the user for the username interactively
            $question = new Question(sprintf($this::MSG_PROMPT_USER, $show_env_user));
            $user = $helper->ask($input, $output, $question) ?: $env_user;
        }

        // If the username is still empty, exit with an error
        if (! $user) {
            $output->writeln('<error>' . $this::MSG_USER_REQUIRED . '</error>');
            return Command::FAILURE;
        }

        // Check if the user exists
        if (! $this->userlib->user_exists($user)) {
            $output->writeln(sprintf('<error>' . $this::MSG_USER_DOES_NOT_EXIST . '</error>', $user));
            return Command::FAILURE;
        }

        /* ============================== */
        /* = password argument = */
        /* ============================== */
        $password = $params[1] ?? '';

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
            $output->writeln('<error>' . $this::MSG_PASSWORD_REQUIRED . '</error>');
            return Command::FAILURE;
        }

        // Check password constraints
        $polerr = $this->userlib->check_password_policy($password);
        if (! empty($polerr)) {
            $output->writeln("<error>{$polerr}</error>");
            return Command::FAILURE;
        }

        if ($prefs['auth_method'] != 'tiki') {
            $output->writeln("<info>\nWarning: Tiki authentication method set to: <options=bold>" . $prefs['auth_method'] . "</>\n"
            . "Depending on the settings for this authentication method, \n"
            . "this change of the local password might not be enough for the user to be able to login</info>"
            . "\n");
        }

        if ($prefs['feature_user_encryption'] === 'y' && ! $input->getOption('force')) {
            $output->writeln("<error>" . $this::MSG_ENCRYPTION_FT_NOTICE . "\n" .
                "Changing the user password might loose encrypted data.\n\n" .
                "Use -f to force changing password.</error>");
            return Command::FAILURE;
        }

        $this->userlib->change_user_password($user, $password);
        $output->writeln($this::MSG_PASSWORD_CHANGED);
        $logslib->add_action('adminusers', 'system', 'system', 'Password changed for ' . $user);
        return Command::SUCCESS;
    }
}
