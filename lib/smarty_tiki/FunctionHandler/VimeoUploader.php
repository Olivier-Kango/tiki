<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

/** vimeo_uploader: Adds a widget to the page to upload vimeo
 *
 * @param array $params
 *     'url' => str upload_link_secure to upload to Vimeo with
 */
class VimeoUploader extends Base
{
    public function handle($params, Template $template)
    {
        $headerlib = \TikiLib::lib('header');
        $smarty = \TikiLib::lib('smarty');

        if (empty($params['url']) || empty($params['maxmegabytes'])) {
            // error
            return;
        }

        //  The Iframe Transport is required for browsers without support for XHR file uploads
        $headerlib->add_jsfile('vendor_bundled/vendor/npm-asset/blueimp-file-upload/js/jquery.iframe-transport.js');
        //  The basic File Upload plugin
        $headerlib->add_jsfile('vendor_bundled/vendor/npm-asset/blueimp-file-upload/js/jquery.fileupload.js');

        //  Tiki customised application script
        $headerlib->add_js("uploadlinksecure = '" . $params['url'] . "';");
        $headerlib->add_js("maxFileSize = '" . $params['maxmegabytes'] * 1000000 . "';");
        $headerlib->add_jsfile('lib/jquery_tiki/tiki-vimeo_upload.js');


        $return = $smarty->fetch('vimeo/jquery_upload.tpl');

        return $return;
    }
}
