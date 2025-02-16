<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

/** filegal_uploader: Adds a widget to the page to upload files
 *
 * @param array $params
 *     'galleryId' => int   file gallery to upload into by default
 *
 * @param Smarty $smarty
 * @return string html
 */
class FileGalUploader extends Base
{
    public function handle($params, Template $template)
    {
        $headerlib = \TikiLib::lib('header');
        $smarty = \TikiLib::lib('smarty');

        if (! empty($params['allowedMimeTypes'])) {
            $smarty->assign('allowedMimeTypes', $params['allowedMimeTypes']);
        }

        //  Image loader and canvas libs
        $headerlib->add_jsfile('vendor_bundled/vendor/npm-asset/blueimp-load-image/js/load-image.all.min.js');
        $headerlib->add_jsfile('vendor_bundled/vendor/npm-asset/blueimp-canvas-to-blob/js/canvas-to-blob.js');

        //  The Iframe Transport is required for browsers without support for XHR file uploads
        $headerlib->add_jsfile('vendor_bundled/vendor/npm-asset/blueimp-file-upload/js/jquery.iframe-transport.js');
        //  The basic File Upload plugin
        $headerlib->add_jsfile('vendor_bundled/vendor/npm-asset/blueimp-file-upload/js/jquery.fileupload.js');
        //  The File Upload processing plugin
        $headerlib->add_jsfile('vendor_bundled/vendor/npm-asset/blueimp-file-upload/js/jquery.fileupload-process.js');
        //  The File Upload image preview & resize plugin
        $headerlib->add_jsfile('vendor_bundled/vendor/npm-asset/blueimp-file-upload/js/jquery.fileupload-image.js');
        //  The File Upload audio preview plugin
        $headerlib->add_jsfile('vendor_bundled/vendor/npm-asset/blueimp-file-upload/js/jquery.fileupload-audio.js');
        //  The File Upload video preview plugin
        $headerlib->add_jsfile('vendor_bundled/vendor/npm-asset/blueimp-file-upload/js/jquery.fileupload-video.js');
        //  The File Upload validation plugin
        $headerlib->add_jsfile('vendor_bundled/vendor/npm-asset/blueimp-file-upload/js/jquery.fileupload-validate.js');
        //  The File Upload user interface plugin
        $headerlib->add_jsfile('vendor_bundled/vendor/npm-asset/blueimp-file-upload/js/jquery.fileupload-ui.js');
        // CSS
        $headerlib->add_cssfile('vendor_bundled/vendor/npm-asset/blueimp-file-upload/css/jquery.fileupload.css');
        $headerlib->add_cssfile('vendor_bundled/vendor/npm-asset/blueimp-file-upload/css/jquery.fileupload-ui.css');

        //  Tiki customised application script
        $headerlib->add_jsfile('lib/jquery_tiki/tiki-jquery_upload.js');


        $return = $smarty->fetch('file/jquery_upload.tpl');

        return $return;
    }
}
