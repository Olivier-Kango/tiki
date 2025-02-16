<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

/**
 * smarty function attachments handler
 * -----------------------------------
 * Purpose: Display the list of files attached to a wiki page (when stored in a file gallery)
 *
 * params will be used as smarty params for fgal_attachments.tpl, except special params starting with '_' :
 *   _id : id of the object (for a wiki page, use it's name)
 *   _type : type of the object ( e.g. "wiki page" - see objectTypes in lib/setup/sections.php )
 */
class Attachments extends Base
{
    public function handle($params, Template $template)
    {
        if (! is_array($params) || ! isset($params['_id']) || ! isset($params['_type'])) {
            return tra('Missing _id or _type params');
        }

        global $prefs, $page;
        $filegallib = \TikiLib::lib('filegal');
        $smarty = \TikiLib::lib('smarty');
        /*** For the moment, only wiki attachments are handled through file galleries ***/
        if ($prefs['feature_wiki_attachments'] != 'y') {
            return;
        }

        $galleryId = $filegallib->get_attachment_gallery($params['_id'], $params['_type']);

        /*** If anything in this function is changed, please change lib/wiki-plugins/wikiplugin_attach.php as well. ***/
        /* but wikiplugin_attach doesn't seem to work at all with file gals attachemnts??? jonnyb tiki12 */

        if (empty($galleryId)) {            // no gallery for this page yet, is no problem (12.0+)
            $gal_info = $filegallib->default_file_gallery();
            $gal_info['name'] = $page . ' *';   // temp name with * - not displayed in most configs
        } elseif (! $gal_info = $filegallib->get_file_gallery($galleryId)) {
            $repeat = false;
            return smarty_block_remarksbox(
                ['type' => 'errors', 'title' => tra('Wrong attachments gallery')],
                tra('You are attempting to display a gallery that is not a valid attachment gallery') . ' (ID=' . $galleryId . ')',
                $template,
                $repeat
            ) . "\n";
        }

    ////    if ( $this->showAttachments !== false )
    ////        $this->smartyassign('atts_show', $this->showAttachments);

        foreach ($params as $k => $v) {
            if ($k[0] == '_') {
                unset($params[ $k ]);
            }
        }

        // Get URL params specific to this smarty function that should be assigned in smarty
        $url_override_prefix = 's_f_attachments';
        $url_overrided_arguments = [ 'sort_mode', 'remove', 'galleryId', 'comment', 'upload', 'page' ];
        $smarty->set_request_overriders($url_override_prefix, $url_overrided_arguments);

        $params['sort_mode'] = isset($_REQUEST[ $url_override_prefix . '-sort_mode' ]) ? $_REQUEST[ $url_override_prefix . '-sort_mode' ] : '';

        // Get listing display config
        include_once('fgal_listing_conf.php');

        // Force some gallery display parameters
        $gal_info['show_checked'] = 'n';

        // Get list of files in the gallery
        if (! empty($galleryId)) {
            $files = $filegallib->get_files(0, -1, $params['sort_mode'], '', $galleryId);
        } else {
            $files = ['data' => [], 'cant' => 0];
        }

        // Readjust perms using special wiki attachments perms
        global $tiki_p_wiki_admin_attachments, $tiki_p_wiki_view_attachments;

        foreach ($files[ 'data' ] as &$file) {
            // First disable file galleries "assign perms" & "admin" perms that allows too much actions on the list of files or that are related to subgalleries
            //   (attachments display should be simple)
            $file['perms'][ 'tiki_p_admin_file_galleries' ] = 'n';
            $file['perms'][ 'tiki_p_assign_perm_file_gallery' ] = 'n';

            // Disabling permissions below should not be necessary because subgalleries in attachments galleries should not happen...
            // $p[ 'tiki_p_upload_files' ] = 'n';
            // $p[ 'tiki_p_create_file_galleries' ] = 'n';

            $file['perms'][ 'tiki_p_download_files' ] = ( $tiki_p_wiki_admin_attachments == 'y' || $tiki_p_wiki_view_attachments == 'y' ) ? 'y' : 'n';
            $file['perms'][ 'tiki_p_edit_gallery_file' ] = $tiki_p_wiki_admin_attachments;
        }

        $params['gal_info'] = $gal_info;
        $params['files'] = $files['data'];
        $params['cant'] = $files['cant'];
        $params['from_wiki_page'] = true;

        $return = "\n" . $smarty->plugin_fetch('fgal_attachments.tpl', $params) . "\n";

        $smarty->remove_request_overriders($url_override_prefix, $url_overrided_arguments);
        return $return;
    }
}

// Handle special actions of the smarty_function_attachments smarty plugin
function s_f_attachments_actionshandler($params)
{
    global $prefs, $user, $tikilib;
    if ($prefs['feature_wiki_attachments'] != 'y') {
        return false;
    }

    /*** Works only for wiki attachments yet ***/
    if (! empty($params['upload']) && empty($params['fileId']) && empty($params['page'])) {
        return false; ///FIXME
    }

    if (! empty($params['page'])) {
        require_once("lib/wiki/renderlib.php");
        $info =& $tikilib->get_page_info($params['page']);
        $pageRenderer = new \WikiRenderer($info, $user, $info['data']);
        $objectperms = $pageRenderer->applyPermissions();
    }

    $filegallib = \TikiLib::lib('filegal');
    $access = \TikiLib::lib('access');

    foreach ($params as $k => $v) {
        switch ($k) {
            case 'remove':
                if ($access->checkCsrf(true)) {
                    $result = $filegallib->actionHandler('removeFile', [ 'fileId' => $v ]);
                    if ($result && $result->numrows()) {
                        \Feedback::success(tr('File (ID %0) removed', $v));
                    } else {
                        \Feedback::error(tr('File (ID %0) not removed', $v));
                    }
                }
                break;

            case 'upload':
                if (isset($objectperms) && ( $objectperms->wiki_admin_attachments || $objectperms->wiki_attach_files )) {
                    $galleryId = $filegallib->get_attachment_gallery($params['page'], 'wiki page', true);
                    $smarty = \TikiLib::lib('smarty');
                    if ($access->checkCsrf()) {
                        $result = $filegallib->actionHandler(
                            'uploadFile',
                            [
                                'galleryId' => [$galleryId],
                                'comment' => [$params['comment']],
                                'returnUrl' => smarty_function_query(
                                    [
                                        '_type' => 'absolute_path',
                                        's_f_attachments-upload' => 'NULL',
                                        's_f_attachments-page' => 'NULL',
                                        's_f_attachments-comment' => 'NULL',
                                        'ticket' => 'NULL',
                                    ],
                                    $smarty->getEmptyInternalTemplate()
                                ),
                            ]
                        );
                        if ($result) {
                            \Feedback::success(tr('File uploaded'));
                        } else {
                            \Feedback::error(tr('File not uploaded'));
                        }
                    }
                }

                break;
        }
    }

    return true;
}
