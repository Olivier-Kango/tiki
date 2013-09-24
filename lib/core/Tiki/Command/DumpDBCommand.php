<?php
// (c) Copyright 2002-2013 by authors of the Tiki Wiki CMS Groupware Project
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

class DumpDBCommand extends Command
{
	protected function configure()
	{
		$this
			->setName('database:dump')
			->setDescription('Create a database dump')
			->addArgument(
				'path',
				InputArgument::REQUIRED,	
				'Path to save dump (relative to console.php, or absolute)' 
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$path = $input->getArgument('path');
		if (substr($path, -1) == '/') {
			$path = substr($path, 0, strlen($path) - 1);
		}

		if (!is_dir($path)) {
			$output->writeln('<error>Error: Provided path not found</error>');
			return;
		}

		$site = $input->getOption('site');
		if (!$site) { 
	                require('db/local.php');
		} else {
			if (file_exists('db/'.$site.'/local.php')) {
				require('db/'.$site.'/local.php');
			} else {
				$output->writeln('<error>Error: db/'.$site.'/local.php not found.</error>');
				return;
			}
		}

		$args = array();
		if( $user_tiki ) {
			$args[] = "-u" . escapeshellarg( $user_tiki );
		}
		if( $pass_tiki ) {
			$args[] = "-p" . escapeshellarg( $pass_tiki );
		}
		if( $host_tiki ) {
			$args[] = "-h" . escapeshellarg( $host_tiki );
		}
		$args[] = $dbs_tiki;
	
		$args = implode( ' ', $args );
		$outputFile = $path . '/' . $dbs_tiki . '_' . date( 'Y-m-d_H:i:s' ) . '.sql.gz';
		$command = "mysqldump --quick $args | gzip -5 > " . escapeshellarg( $outputFile );
		exec( $command );
		$output->writeln('<comment>Database dumped: '.$outputFile.'</comment>');
	}
}
