<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Services_Draw_Controller
{
    public function setUp()
    {
        global $prefs;

        if ($prefs['feature_file_galleries'] != 'y') {
            throw new Services_Exception_Disabled('feature_file_galleries');
        }

        if ($prefs['feature_draw'] != 'y') {
            throw new Services_Exception_Disabled('feature_draw');
        }
    }

    /**
     * Returns the section for use with certain features like banning
     * @return string
     */
    public function getSection()
    {
        return 'file_galleries';
    }

    public function action_edit($input)
    {
        require_once 'lib/mime/mimetypes.php';

        $headerlib = TikiLib::lib('header');
        $tikilib = TikiLib::lib('tiki');
        $filegallib = TikiLib::lib('filegal');

        $fileId = $input->fileId->int();
        $galleryId = $input->galleryId->int();
        $imgParams = $input->imgParams->array();
        $data = $input->data->none();
        $archive = $input->archive->string();
        $index = $input->index->string();
        $page = $input->page->string();
        $label = $input->label->string();
        $width = $input->width->int();
        $height = $input->height->int();

        if (! $fileId && ! $galleryId) {
            throw new ServicesExceptionBadRequest('No file or gallery id');
        }

        if ($fileId) {
            $fileInfo = $filegallib->get_file_info($fileId);

            if (! $fileInfo) {
                throw new Services_Exception_NotFound('No file found');
            }

            $name = $fileInfo['name'];

            if (! empty($fileInfo['archiveId']) && $fileInfo['archiveId'] > 0) {
                $fileInfo = $filegallib->get_file_info($fileInfo['archiveId']);
            }

            if (! $galleryId) {
                $galleryId = $fileInfo['galleryId'];
            }

            $fileGalperms = Perms::get(['type' => 'file gallery', 'object' => $galleryId]);

            if (! $fileGalperms->upload_files) {
                throw new Services_Exception_Denied(tr('Permission denied'));
            }

            if (
                ! (
                    ($fileInfo['filetype'] == $mimetypes["svg"]) ||
                    ($fileInfo['filetype'] == $mimetypes["gif"]) ||
                    ($fileInfo['filetype'] == $mimetypes["jpg"]) ||
                    ($fileInfo['filetype'] == $mimetypes["png"]) ||
                    ($fileInfo['filetype'] == $mimetypes["tiff"])
                )
            ) {
                throw new Services_Exception_Denied(tr("Wrong file type, expected ") . json_encode($mimetypes['svg']));
            }

            if ($fileInfo['filetype'] == $mimetypes["svg"]) {
                $data = $fileInfo["data"];
            } else { //we already confirmed that this is an image, here we make it compatible with svg
                $src = $tikilib->tikiUrl() . 'tiki-download_file.php?fileId=' . $fileInfo['fileId'];

                $file = new \Tiki\FileGallery\File($fileInfo);

                $image = imagecreatefromstring($file->getContents());
                $w = ! is_bool($image) ? imagesx($image) : 0;
                $h = ! is_bool($image) ? imagesy($image) : 0;

                if ($w == 0 && $h == 0) {
                    $w = 640;
                    $h = 480;
                }
                $data = '<svg width="' . $w . '" height="' . $h
                    . '" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                <g>
                    <title>Layer 1</title>
                    <image x="1" y="1" width="100%" height="100%" id="svg_1" xlink:href="' . $src . '#image"/>
                </g>
            </svg>';
            }
        }

        $fileId = htmlspecialchars($fileId);
        $galleryId = htmlspecialchars($galleryId);
        $name = htmlspecialchars($name);
        $archive = htmlspecialchars($archive ?? '', ENT_QUOTES);
        $index = htmlspecialchars($index ?? '', ENT_QUOTES);
        $page = htmlspecialchars($page ?? '', ENT_QUOTES);
        $label = htmlspecialchars($label ?? '', ENT_QUOTES);
        $width = htmlspecialchars($width ?? '640', ENT_QUOTES);
        $height = htmlspecialchars($height ?? '480', ENT_QUOTES);

        $jsTracking = "$.wikiTrackingDraw = {
            index: '$index',
            page: '$page',
            label: '$label',
            type: 'draw',
            content: '',
            params: {
                width: '$width',
                height: '$height',
                id: '$fileId'
            }
        };";



        if ($index && $page && $label) {
            $headerlib->add_jq_onready($jsTracking);
        }

        return [
            'title' => tra('Draw'),
            'fileId' => $fileId,
            'galleryId' => $galleryId,
            'name' => $name,
            'archive' => $archive,
            'width' => $width,
            'height' => $height,
            'data' => $data,
            'page' => $page,
            'isFromPage' => isset($page),
            'imgParams' => $imgParams
        ];
    }

    public function action_replace($input)
    {
        require_once 'lib/mime/mimetypes.php';
        global $prefs;

        $tikilib = TikiLib::lib('tiki');
        $filegallib = TikiLib::lib('filegal');

        $fileId = $input->fileId->int();
        $galleryId = $input->galleryId->int();
        $imgParams = $input->imgParams->array();
        $data = $input->data->none();
        $name = $input->name->string();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (! $data) {
                throw new ServicesExceptionBadRequest('No data');
            }

            $dom = new DOMDocument();
            if (! $dom->loadXML($data, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET)) {
                throw new Services_Exception_FieldError('data', 'Invalid XML ' . json_encode($data));
            }

            $data = $filegallib->clean_xml($data, $galleryId);

            if ($imgParams) {
                $fromFieldId = $imgParams['fromFieldId'];
                $fromItemId = $imgParams['fromItemId'];
            }

            $type = $mimetypes["svg"];
            $description = htmlspecialchars($input->description->string() ?? '');
            $isConversion = $fileInfo['filetype'] != $type;

            if ($fileId &&  ($prefs['feature_draw_separate_base_image'] !== 'y' || ! $isConversion)) {
                $fileInfo = $filegallib->get_file_info($fileId);

                if (! $name) {
                    $name = $fileInfo['name'];
                }

                //existing file
                $file = Tiki\FileGallery\File::id($fileId);
                $fileId = $file->replace($data, $type, $name, $name . '.svg');

                // this is a conversion from an image other than svg
                if ($isConversion && $prefs['fgal_keep_fileId'] == 'y') {
                    $archiveFileId = $tikilib->getOne(
                        'SELECT fileId
                        FROM tiki_files
                        WHERE archiveId = ?
                        ORDER BY lastModif DESC',
                        [$fileId]
                    );

                    $data = str_replace(
                        '?fileId=' . $fileInfo['fileId'] . '#',
                        '?fileId=' . $archiveFileId . '#',
                        $file->data()
                    );

                    $fileId = $file->replace($data);
                }
            } else {
                if ($isConversion) {
                    $name .= tra(' drawing');
                }

                if ($prefs['feature_draw_in_userfiles'] === 'y') {
                    $galleryId = $filegallib->get_user_file_gallery();
                }

                $file = new Tiki\FileGallery\File([
                    'galleryId' => $galleryId,
                    'description' => $description,
                    'user' => $user
                ]);
                $fileId = $file->replace($data, $type, $name, $name . '.svg');
            }

            if (! empty($fromItemId)) {
                $item = Tracker_Item::fromId($fromItemId);
                if ($item->canModifyField($fromFieldId)) {
                    $definition = $item->getDefinition();
                    $field = $definition->getField($fromFieldId);
                    $trackerInput = $item->prepareFieldInput($field, [$fromFieldId->$fileId]);
                    $fileIds = explode(',', $trackerInput['value']);
                    if (! in_array($fileId, $fileIds)) {
                        if (! empty($fileId) && $fileId != $input->fileId->int()) {
                            $old_index = array_search(
                                $fileId,
                                $fileIds
                            );            // replacement (id changed when drawn on)
                        } else {
                            $old_index = false;
                        }
                        if ($old_index !== false) {
                            $fileIds[$old_index] = $fileId;
                        } else {
                            $fileIds[] = $fileId;
                        }
                    }
                    $trackerInput['value'] = implode(',', $fileIds);

                    TikiLib::lib('trk')->replace_item(
                        $field['trackerId'],
                        $fromItemId,
                        ['data' => [$trackerInput]]
                    );
                }
            }

            return [
                'title' => tra('Draw Post'),
                'fileId' => $fileId,
                'galleryId' => $galleryId
            ];
        } else {
            throw new Services_Exception_NotAvailable('Method not available');
        }
    }

    public function action_removeButtons()
    {
        global $prefs;
        return ['removeButtons' => $prefs['feature_draw_hide_buttons']];
    }
}
