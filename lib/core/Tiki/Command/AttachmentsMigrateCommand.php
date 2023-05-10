<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Exception;
use TikiLib;
use Tiki\FileGallery\File as TikiFile;

class AttachmentsMigrateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('attachments:migrate')
            ->setDescription(tra('Convert legacy wiki attachment storage to file galleries or vice versa depending on settings.'))
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        global $prefs;

        if ($prefs['feature_wiki_attachments'] != 'y') {
            throw new Exception(tra('Feature wiki attachments not set up'));
        }

        $wikilib = TikiLib::lib('wiki');
        $filegallib = TikiLib::lib('filegal');

        if ($prefs['feature_use_fgal_for_wiki_attachments'] === 'y') {
            // check for legacy attachments
            $count = $wikilib->attachmentsCount();
        } else {
            // check for fgal attachments
            $count = $filegallib->fileGalleryAttachmentsCount();
        }

        if (! ($count > 0)) {
            $output->writeln('<comment>' . tr('No attachments found to migrate.') . '</comment>');
            return Command::SUCCESS;
        }

        if ($prefs['feature_use_fgal_for_wiki_attachments'] === 'y') {
            $count = 0;
            $result = $wikilib->list_all_attachments();
            foreach ($result['data'] as $att) {
                $output->writeln(tr('Processing page %0, attachment %1 %2...', $att['page'], $att['attId'], $att['filename']));
                // find or create attachments gallery for the corresponding wiki apge
                $galleryId = $filegallib->get_attachment_gallery($att['page'], 'wiki page', true);
                if (! $galleryId) {
                    $output->writeln('<error>' . tr('File gallery for page %0 could not be found or created. Does the page exist?', $att['page']) . '</error>');
                    continue;
                }
                // create file and replace its contents
                $file = new TikiFile([
                    'galleryId' => $galleryId,
                    'description' => $att['comment'],
                    'user' => $att['user'],
                    'comment' => $att['comment'],
                    'hits' => $att['hits'],
                ]);
                $data = $wikilib->get_item_attachement_data($att);
                $fileId = $file->replace($data, $att['filetype'], $att['filename'], $att['filename']);
                // remove wiki attachment row
                $wikilib->remove_wiki_attachment($att['attId']);
                // TODO: replace attachment usage in wiki page
                $count++;
            }
            $output->writeln('<comment>' . tr('Finished migrating legacy attachments to file galleries. Total files migrated: %0', $count) . '</comment>');
        } else {
            $mapping = [];
            $result = $filegallib->list_file_galleries(0, -1, 'galleryId', '', '', $prefs['fgal_root_wiki_attachments_id']);
            foreach ($result['data'] as $gal_info) {
                $output->writeln(tr('Processing file gallery %0 %1...', $gal_info['id'], $gal_info['name']));
                $files = $filegallib->get_files(0, -1, 'fileId', '', $gal_info['id']);
                foreach ($files['data'] as $file_info) {
                    $output->writeln(tr('Processing file %0 %1...', $file_info['id'], $file_info['name']));
                    // create wiki attachment and store data or path
                    $file = TikiFile::id($file_info['id']);
                    $data = $file->getContents();
                    if ($prefs['w_use_db'] === 'y') {
                        $fhash = '';
                    } else {
                        $fhash = TikiLib::lib('tiki')->get_attach_hash_file_name($file->filename);
                        $fp = fopen($prefs['w_use_dir'] . $fhash, "wb");
                        fwrite($fp, $data);
                        fclose($fp);
                        $data = '';
                    }
                    $attId = $wikilib->wiki_attach_file($gal_info['name'], $file->filename, $file->filetype, $file->filesize, $data, $file->description, $file->user, $fhash);
                    // remove from file galleries
                    $file->delete();
                    $mapping[] = [$file->fileId, $attId, $file->filename];
                }
                $filegallib->remove_file_gallery($gal_info['id']);
            }
            $output->writeln('<comment>' . tr('Replacing file references with attachment references in wiki pages must be done manually. Here\'s a table with ID mapping:') . '</comment>');
            $table = new Table($output);
            $table->setHeaders(['File ID', 'Attachment ID', 'File Name']);
            $table->setRows($mapping);
            $table->render();
        }

        return Command::SUCCESS;
    }
}
