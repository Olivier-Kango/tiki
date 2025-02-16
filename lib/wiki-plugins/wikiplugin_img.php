<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Lib\Image\Image;
use Tiki\Package\VendorHelper;

function wikiplugin_img_info()
{
    global $prefs;
    $info = [
        'name' => tra('Image'),
        'documentation' => 'PluginImg',
        'description' => tra('Display one or more custom-formatted images'),
        'prefs' => [ 'wikiplugin_img'],
        'iconname' => 'camera',
        'tags' => [ 'basic' ],
        'introduced' => 3,
        'params' => [
            'type' => [
                'required' => true,
                'name' => tra('Image Source'),
                'description' => tra('Choose where to get the image from'),
                'since' => '11.0',
                'doctype' => 'id',
                'default' => '',
                'filter' => 'word',
                'options' => [
                    ['text' => tra('Select an option'), 'value' => ''],
                    ['text' => tra('An image in the file galleries'), 'value' => 'fileId'],
                    ['text' => tra('An image attached to a wiki page'), 'value' => 'attId'],
                    ['text' => tra('An image anywhere on the Internet'), 'value' => 'src'],
                    ['text' => tra('All the images in a file gallery'), 'value' => 'fgalId'],
                    ['text' => tra('One random image from a file gallery'), 'value' => 'randomGalleryId'],
                ],
            ],
            'fileId' => [
                'required' => true,
                'name' => tra('File ID'),
                'type' => 'image',
                'area' => 'fgal_picker_id',
                'description' => tr(
                    'Numeric ID of an image in a file gallery (or a comma- or %0-separated list of IDs).',
                    '<code>|</code>'
                ),
                'since' => '4.0',
                'doctype' => 'id',
                'filter' => 'text',
                'default' => '',
                'accepted' => tra('Valid file IDs separated by commas or |'),
                'parentparam' => ['name' => 'type', 'value' => 'fileId'],
                'profile_reference' => 'file',
            ],
            'id' => [
                'required' => false,
                'name' => tra('Image ID'),
                'description' => tr('Only available when file_galleries_redirect_from_image_gallery is active'),
                'since' => '4.0',
                'doctype' => 'id',
                'filter' => 'text',
                'advanced' => $prefs['feature_file_galleries'] == 'y',
                'accepted' => tra('Valid image IDs separated by commas or |'),
                'default' => '',
                'parentparam' => ['name' => 'type', 'value' => 'id'],
            ],
            'src' => [
                'required' => true,
                'name' => tra('Image Source'),
                'description' => tra('Full URL to the image to display.'),
                'since' => '3.0',
                'doctype' => 'id',
                'filter' => 'url',
                'default' => '',
                'parentparam' => ['name' => 'type', 'value' => 'src'],
            ],
            'randomGalleryId' => [
                'required' => true,
                'name' => tra('Gallery ID'),
                'description' => tra('Numeric ID of a file gallery. Displays a random image from that gallery.'),
                'since' => '5.0',
                'doctype' => 'id',
                'filter' => 'digits',
                'advanced' => true,
                'default' => '',
                'parentparam' => ['name' => 'type', 'value' => 'randomGalleryId'],
                'profile_reference' => 'file_gallery',
            ],
            'fgalId' => [
                'required' => true,
                'name' => tra('File Gallery ID'),
                'description' => tra('Numeric ID of a file gallery. Displays all images from that gallery.'),
                'since' => '8.0',
                'doctype' => 'id',
                'filter' => 'digits',
                'advanced' => true,
                'default' => '',
                'parentparam' => ['name' => 'type', 'value' => 'fgalId'],
                'profile_reference' => 'file_gallery',
            ],
            'attId' => [
                'required' => true,
                'name' => tra('Attachment ID'),
                'description' => tr(
                    'Numeric ID of an image attached to a wiki page (or a comma- or %0-separated list).',
                    '<code>|</code>'
                ),
                'since' => '4.0',
                'doctype' => 'id',
                'filter' => 'text',
                'accepted' => tra('Valid attachment IDs separated by commas or |'),
                'default' => '',
                'parentparam' => ['name' => 'type', 'value' => 'attId'],
            ],
            'thumb' => [
                'required' => false,
                'name' => tra('Thumbnail'),
                'description' => tr('Makes the image a thumbnail with various options.'),
                'since' => '4.0',
                'doctype' => 'link',
                'filter' => 'alpha',
                'default' => '',
                'options' => [
                    ['text' => tra('None'), 'value' => ''],
                    ['text' => tra('Simple - links to full size image on a new page'), 'value' => 'y'],
                    ['text' => tra('"Lightbox" - enlarges in an overlay box when clicked'), 'value' => 'box'],
                    ['text' => tra('Mouseover - enlarges in a popup when moused over'), 'value' => 'mouseover'],
                    ['text' => tra('Mouseover (sticky)'), 'value' => 'mousesticky'],
                    ['text' => tra('Popup - enlarges in a separate window'), 'value' => 'popup'],
                    ['text' => tra('Download'), 'value' => 'download'],
                ],
            ],
            'link' => [
                'required' => false,
                'name' => tra('Link'),
                'description' => tr('Causes the image to be a link to this address. Overrides %0thumb%1 unless %0thumb%1 is
                    set to %0mouseover%1 or %0mousesticky%1', '<code>', '</code>'),
                'since' => '3.0',
                'doctype' => 'link',
                'filter' => 'url',
                'default' => '',
            ],
            'height' => [
                'required' => false,
                'name' => tra('Image Height'),
                'description' => tr('Height in pixels or percent. Syntax: %0100%1 or %0100px%1 means 100 pixels;
                    %050%%1 means 50 percent. Percent applies when Image Source is set to file galleries images only.', '<code>', '</code>'),
                'since' => '3.0',
                'doctype' => 'size',
                'filter' => 'text',
                'default' => '',
            ],
            'width' => [
                'required' => false,
                'name' => tra('Image Width'),
                'description' => tr('Width in pixels or percent. Syntax: %0100%1 or %0100px%1 means 100 pixels;
                    %050%%1 means 50 percent. Percent applies when Image Source is set to file galleries images only.', '<code>', '</code>'),
                'since' => '3.0',
                'doctype' => 'size',
                'filter' => 'text',
                'default' => '',
            ],
            'retina' => [
                'required' => false,
                'name' => tra('Serve retina images'),
                'description' => tr('Serves up retina images to high density screen displays. Width must be set to use this.'),
                'since' => '18.0',
                'doctype' => 'size',
                'filter' => 'alpha',
                'default' => 'n',
                'options' => [
                    ['text' => tra('Default'), 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'widths' => [
                'required' => false,
                'name' => tra('Responsive Image Widths'),
                'description' => tr('Comma-separated widths at which we may want the browser to request the image. Requires "sizes".'),
                'since' => '18.0',
                'doctype' => 'size',
                'filter' => 'text',
                'default' => '',
            ],
            'sizes' => [
                'required' => false,
                'name' => tra('Sizes'),
                'description' => tr('Comma-separated sizes (in vw, em, px) for the image in xs, sm, md, and lg layouts. Must be 4 parameters.'),
                'since' => '18.0',
                'doctype' => 'size',
                'filter' => 'text',
                'default' => '',
            ],
            'max' => [
                'required' => false,
                'name' => tra('Maximum Size'),
                'description' => tra('Maximum height or width in pixels (largest dimension is scaled). Overrides height
                    and width settings.'),
                'since' => '4.0',
                'doctype' => 'size',
                'filter' => 'digits',
                'default' => '',
                'parentparam' => ['name' => 'type', 'value' => 'fileId'],
            ],
            'desc' => [
                'required' => false,
                'name' => tra('Caption'),
                'since' => '3.0',
                'doctype' => 'text',
                'filter' => 'text',
                'description' => tr('Image caption. Use %0name%1 or %0desc%1 or %0namedesc%1 for Tiki name and
                    description properties, %0idesc%1 or %0ititle%1 for metadata from the image itself, otherwise
                    enter your own description.', '<code>', '</code>'),
                'default' => '',
            ],
            'alt' => [
                'required' => false,
                'name' => tra('Alternate Text'),
                'filter' => 'text',
                'description' => tra('Alternate text that displays when image does not load. Set to "Image" by default.'),
                'since' => '3.0',
                'doctype' => 'text',
                'default' => 'Image',
            ],
            'responsive' => [
                'required' => false,
                'name' => tra('Responsive Image'),
                'filter' => 'alpha',
                'description' => tr('Default set by the admin using a preference and determines whether the image has the %0img-fluid%1 class.', '<code>', '</code>'),
                'since' => '14.0',
                'doctype' => 'style',
                'advanced' => false,
                'default' => '',
                'options' => [
                    ['text' => tra('Default'), 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'featured' => [
                'required' => false,
                'name' => tra('Featured Image'),
                'filter' => 'alpha',
                'description' => tr('Set the image to be used for a thumbnail on referencing social network sites or for other special purpose'),
                'since' => '18.0',
                'doctype' => 'show',
                'advanced' => false,
                'default' => 'n',
                'options' => [
                    ['text' => tra('Default'), 'value' => ''],
                    ['text' => tra('No'), 'value' => 'n'],
                    ['text' => tra('Yes'), 'value' => 'y'],
                ],
            ],

            ///// advanced parameters ///////

            'sort_mode' => [
                'required' => false,
                'name' => tra('Sort Mode'),
                'description' => tr('Sort by database table field name, ascending or descending. Examples:
                    %0 or %1.', '<code>fileId_asc</code>', '<code>name_desc</code>'),
                'filter' => 'word',
                'accepted' => tr('%0 or %1 with actual database field name in place of
                    %2.', '<code>fieldname_asc</code>', '<code>fieldname_desc</code>', '<code>fieldname</code>'),
                'default' => 'created_desc',
                'since' => '8.0',
                'doctype' => 'id',
                'advanced' => true,
                'options' => [
                    ['text' => tra(''), 'value' => ''],
                    ['text' => tra('Random'), 'value' => 'random'],
                    ['text' => tra('Created Ascending'), 'value' => 'created_asc'],
                    ['text' => tra('Created Descending'), 'value' => 'created_desc'],
                    ['text' => tra('Name Ascending'), 'value' => 'name_asc'],
                    ['text' => tra('Name Descending'), 'value' => 'name_desc'],
                    ['text' => tra('File Name Ascending'), 'value' => 'filename_asc'],
                    ['text' => tra('File Name Descending'), 'value' => 'filename_desc'],
                    ['text' => tra('Description Ascending'), 'value' => 'description_asc'],
                    ['text' => tra('Description Descending'), 'value' => 'description_desc'],
                    ['text' => tra('Comment Ascending'), 'value' => 'comment_asc'],
                    ['text' => tra('Comment Descending'), 'value' => 'comment_desc'],
                    ['text' => tra('Hits Ascending'), 'value' => 'hits_asc'],
                    ['text' => tra('Hits Descending'), 'value' => 'hits_desc'],
                    ['text' => tra('Max Hits Ascending'), 'value' => 'maxhits_asc'],
                    ['text' => tra('Max Hits Descending'), 'value' => 'maxhits_desc'],
                    ['text' => tra('File Size Ascending'), 'value' => 'filesize_asc'],
                    ['text' => tra('File Size Descending'), 'value' => 'filesize_desc'],
                    ['text' => tra('File Type Ascending'), 'value' => 'filetype_asc'],
                    ['text' => tra('File Type Descending'), 'value' => 'filetype_desc'],
                    ['text' => tra('User Ascending'), 'value' => 'user_asc'],
                    ['text' => tra('User Descending'), 'value' => 'user_desc'],
                    ['text' => tra('Author Ascending'), 'value' => 'author_asc'],
                    ['text' => tra('Author Descending'), 'value' => 'author_desc'],
                    ['text' => tra('Locked By Ascending'), 'value' => 'lockedby_asc'],
                    ['text' => tra('Locked By Descending'), 'value' => 'lockedby_desc'],
                    ['text' => tra('Last modified User Ascending'), 'value' => 'lastModifUser_asc'],
                    ['text' => tra('Last modified User Descending'), 'value' => 'lastModifUser_desc'],
                    ['text' => tra('Last modified Date Ascending'), 'value' => 'lastModif_asc'],
                    ['text' => tra('Last modified Date Descending'), 'value' => 'lastModif_desc'],
                    ['text' => tra('Last Download Ascending'), 'value' => 'lastDownload_asc'],
                    ['text' => tra('Last Download Descending'), 'value' => 'lastDownload_desc'],
                    ['text' => tra('Delete After Ascending'), 'value' => 'deleteAfter_asc'],
                    ['text' => tra('Delete After Descending'), 'value' => 'deleteAfter_desc'],
                    ['text' => tra('Votes Ascending'), 'value' => 'votes_asc'],
                    ['text' => tra('Votes Descending'), 'value' => 'votes_desc'],
                    ['text' => tra('Points Ascending'), 'value' => 'points_asc'],
                    ['text' => tra('Points Descending'), 'value' => 'points_desc'],
                    ['text' => tra('Archive ID Ascending'), 'value' => 'archiveId_asc'],
                    ['text' => tra('Archive ID Descending'), 'value' => 'archiveId_desc'],
                ],
            ],
            'button' => [
                'required' => false,
                'name' => tra('Enlarge Button'),
                'description' => tr('Adds an enlarge button (magnifying glass icon) below the image for use together
                    with %0thumb%1. Follows %0thumb%1 settings unless %0thumb%1 is set to %0mouseover%1 or %0mousesticky%1
                    (or overridden by %0link%1), otherwise button settings are followed, operating as described above
                    for %0thumb%1', '<code>', '</code>'),
                'since' => '4.0',
                'doctype' => 'link',
                'filter' => 'alpha',
                'default' => '',
                'advanced' => true,
                'options' => [
                    ['text' => tra('None'), 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('Popup'), 'value' => 'popup'],
                    ['text' => tra('Browse'), 'value' => 'browse'],
                    ['text' => tra('Browse Popup'), 'value' => 'browsepopup'],
                    ['text' => tra('Download'), 'value' => 'download'],
                ],
            ],
            'rel' => [
                'required' => false,
                'name' => tra('Link Relation'),
                'since' => '3.0',
                'doctype' => 'link',
                'filter' => 'text',
                'description' => tr('Specifies the relationship between the link image and the target. Enter %0 to
                    cause the image to enlarge in a popup when clicked.', '<code>box</code>'),
                'advanced' => true,
                'default' => '',
            ],
            'usemap' => [
                'required' => false,
                'name' => tra('Image Map'),
                'filter' => 'text',
                'description' => tra('Name of the image map to use for the image.'),
                'since' => '3.0',
                'doctype' => 'link',
                'advanced' => true,
                'default' => '',
            ],
            'hspace' => [
                'required' => false,
                'name' => tra('Horizontal spacing'),
                'description' => tra('Horizontal spacing, in pixels, applied to both sides of the image. It may be necessary to use this legacy type of styling if the legacyalign parameter needs to be used for cases where float does not work eg newsletters viewed as an email.'),
                'since' => '15.0',
                'doctype' => 'size',
                'filter' => 'digits',
                'advanced' => true,
                'default' => '',
            ],
            'vspace' => [
                'required' => false,
                'name' => tra('Vertical spacing'),
                'description' => tra('Vertical spacing, in pixels, applied to top and bottom of the image. It may be necessary to use this legacy type of styling if the legacyalign parameter needs to be used for cases where float does not work eg newsletters viewed as an email.'),
                'since' => '15.0',
                'doctype' => 'size',
                'filter' => 'digits',
                'advanced' => true,
                'default' => '',
            ],
            'legacyalign' => [
                'required' => false,
                'name' => tra('Align image using legacy align tag'),
                'description' => tra('Aligns the image itself using the legacy align tag for cases where float does not work eg newsletters viewed as an email. Can be used in addition to the imalign parameter for cases where web pages are viewed by modern browsers and are used by the Newsletter function to send a web page as an email'),
                'since' => '15.0',
                'filter' => 'alpha',
                'advanced' => true,
                'default' => '',
                'options' => [
                    ['text' => tra('None'), 'value' => ''],
                    ['text' => tra('Right'), 'value' => 'right'],
                    ['text' => tra('Left'), 'value' => 'left'],
                    ['text' => tra('Center'), 'value' => 'center'],
                ],
            ],
            'imalign' => [
                'required' => false,
                'name' => tra('Align Image'),
                'description' => tr('Aligns the image itself. Overridden by any alignment settings in %0styleimage%1.
                    If %0stylebox%1 or %0desc%1 are also set, then image only aligns inside the box - use %0stylebox%1
                    in this case to align the box on the page.', '<code>', '</code>'),
                'since' => '3.0',
                'doctype' => 'style',
                'filter' => 'alpha',
                'advanced' => true,
                'default' => '',
                'options' => [
                    ['text' => tra('None'), 'value' => ''],
                    ['text' => tra('Right'), 'value' => 'right'],
                    ['text' => tra('Left'), 'value' => 'left'],
                    ['text' => tra('Center'), 'value' => 'center'],
                ],
            ],
            'styleimage' => [
                'required' => false,
                'name' => tra('Image Style'),
                'description' => tr('Enter %0border%1 to place a dark gray border around the image. Otherwise enter
                    CSS styling syntax for other style effects.', '<code>', '</code>'),
                'since' => '4.0',
                'doctype' => 'style',
                'filter' => 'text',
                'advanced' => true,
                'default' => '',
            ],
            'align' => [
                'required' => false,
                'name' => tra('Align Image Block'),
                'description' => tr('Aligns a block around the image (including the image). Image is no longer inline
                    when this setting is used. Can be overridden by any alignment settings in %0stylebox%1.', '<code>', '</code>'),
                'since' => '3.0',
                'doctype' => 'style',
                'filter' => 'alpha',
                'advanced' => true,
                'default' => '',
                'options' => [
                    ['text' => tra('None'), 'value' => ''],
                    ['text' => tra('Right'), 'value' => 'right'],
                    ['text' => tra('Left'), 'value' => 'left'],
                    ['text' => tra('Center'), 'value' => 'center'],
                ],
            ],
            'stylebox' => [
                'required' => false,
                'name' => tra('Image Block Style'),
                'filter' => 'text',
                'description' => tr('Enter %0border%1 to place a dark gray border around the image. Otherwise enter
                    CSS styling syntax for other style effects.', '<code>', '</code>'),
                'since' => '4.0',
                'doctype' => 'style',
                'advanced' => true,
                'default' => '',
            ],
            'styledesc' => [
                'required' => false,
                'name' => tra('Description Style'),
                'since' => '4.0',
                'doctype' => 'text',
                'filter' => 'text',
                'description' => tr('Enter %0right%1 or %0left%1 to align text accordingly. Otherwise enter CSS styling
                    syntax for other style effects.', '<code>', '</code>'),
                'advanced' => true,
                'default' => '',
            ],
            'block' => [
                'required' => false,
                'name' => tra('Wrapping'),
                'description' => tra('Control how other items wrap around the image.'),
                'since' => '4.0',
                'doctype' => 'style',
                'filter' => 'alpha',
                'advanced' => true,
                'default' => '',
                'options' => [
                    ['text' => tra('None'), 'value' => ''],
                    ['text' => tra('Top'), 'value' => 'top'],
                    ['text' => tra('Bottom'), 'value' => 'bottom'],
                    ['text' => tra('Both'), 'value' => 'both'],
                ],
            ],
            'class' => [
                'required' => false,
                'name' => tra('CSS Class'),
                'filter' => 'text',
                'description' => tr('CSS class to apply to the image. %0class="fixedSize"%1 prevents the image from being
                    automatically resized and relocated in Tiki SlideShows', '<code>', '</code>'),
                'since' => '3.0',
                'doctype' => 'style',
                'advanced' => true,
                'default' => '',
            ],
            'title' => [
                'required' => false,
                'name' => tra('Link Title'),
                'filter' => 'text',
                'description' => tr('This text will appear in a tool tip when the image is moused over. If this is
                    not set, the %0desc%1 setting will be used. Use %0name%1 or %0desc%1 or %0namedesc%1 for Tiki name
                    and description properties', '<code>', '</code>'),
                'since' => '3.0',
                'doctype' => 'text',
                'advanced' => true,
                'default' => '',
            ],
            'metadata' => [
                'required' => false,
                'name' => tra('Metadata'),
                'filter' => 'text',
                'description' => tra('Display the image metadata (IPTC, EXIF and XMP information).'),
                'since' => '8.0',
                'doctype' => 'text',
                'default' => '',
                'advanced' => true,
                'options' => [
                    ['text' => tra('None'), 'value' => ''],
                    ['text' => tra('View'), 'value' => 'view'],
                ],
            ],
            'quality' => [
                'required' => false,
                'name' => tra('Compression Quality'),
                'description' => tra('0 to 100 (default is 75)'),
                'default' => 75,
                'filter' => 'digits',
                'doctype' => 'text',
                'since' => '20.1',
                'advanced' => true,
            ],
            'lazyLoad' => [
                'required' => false,
                'name' => tra('Lazy Loading'),
                'filter' => 'alpha',
                'description' => tr('Set to "n" to prevent lazy loading if enabled. Useful in carousels and so on sometimes.'),
                'since' => '21.3',
                'doctype' => 'show',
                'default' => '',
                'options' => [
                    ['text' => tra('Default'), 'value' => ''],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
                'advanced' => true,
            ],
            'absoluteLinks' => [
                'required' => false,
                'name' => tra('Absolute Links'),
                'filter' => 'alpha',
                'description' => tr('Use the full URL for src and link URLS.'),
                'since' => '24.1',
                'doctype' => 'link',
                'default' => '',
                'options' => [
                    ['text' => tra('Default'), 'value' => ''],
                    ['text' => tra('No'), 'value' => 'n'],
                    ['text' => tra('Yes'), 'value' => 'y'],
                ],
                'advanced' => true,
            ],
            'default' => [
                'required' => false,
                'name' => tra('Default Settings'),
                'description' => tra('Default configuration settings (usually set by admin in the source code or
                    through Plugin Alias).'),
                'since' => '4.1',
                'doctype' => 'deprecated',
                'advanced' => true,
                'default' => '',
            ],
            'mandatory' => [
                'required' => false,
                'name' => tra('Mandatory Setting'),
                'description' => tra('Mandatory configuration settings (usually set by admin in the source code or
                    through Plugin Alias).'),
                'since' => '4.1',
                'doctype' => 'deprecated',
                'advanced' => true,
                'default' => '',
            ],
        ],
    ];
    if ($prefs['feature_draw'] === 'y') {
        $info['params']['noDrawIcon'] = [
            'required' => false,
            'name' => tra('Hide Draw Icon'),
            'description' => tra('Do not show draw/edit icon button under image.'),
            'since' => '11.0',
            'doctype' => 'style',
            'advanced' => true,
            'filter' => 'alpha',
            'options' => [
                ['text' => tra('None'), 'value' => ''],
                ['text' => tra('No'), 'value' => 'n'],
                ['text' => tra('Yes'), 'value' => 'y'],
            ],
            'default' => '',
        ];
    }

    if ($prefs['feature_jquery_zoom'] === 'y') {
        $info['params']['thumb']['options'][] = ['text' => tra('Overlay with zoom'), 'value' => 'zoombox', 'description' => tra('Full size image appears with zoom option in a "Colorbox" overlay when thumbnail is clicked.')];
        $info['params']['thumb']['options'][] = ['text' => tra('Zoom'), 'value' => 'zoom', 'description' => tra('Adds a magnifying glass icon and zooms the image when hovered over.')];
    }

    return $info;
}

function wikiplugin_img($data, $params)
{
    global $tikidomain, $prefs, $user, $tikilib;
    $userlib = TikiLib::lib('user');
    $smarty = TikiLib::lib('smarty');

    $imgdata = [];

    $imgdata['src'] = '';
    $imgdata['fileId'] = '';
    $imgdata['randomGalleryId'] = '';
    $imgdata['galleryId'] = '';
    $imgdata['fgalId'] = '';
    $imgdata['sort_mode'] = 'created_desc';
    $imgdata['attId'] = '';
    $imgdata['thumb'] = '';
    $imgdata['button'] = '';
    $imgdata['link'] = '';
    $imgdata['rel'] = '';
    $imgdata['usemap'] = '';
    $imgdata['height'] = '';
    $imgdata['width'] = '';
    $imgdata['max'] = '';
    $imgdata['legacyalign'] = '';
    $imgdata['hspace'] = '';
    $imgdata['vspace'] = '';
    $imgdata['imalign'] = '';
    $imgdata['styleimage'] = '';
    $imgdata['align'] = '';
    $imgdata['stylebox'] = '';
    $imgdata['styledesc'] = '';
    $imgdata['block'] = '';
    $imgdata['class'] = '';
    $imgdata['desc'] = '';
    $imgdata['title'] = '';
    $imgdata['metadata'] = '';
    $imgdata['alt'] = '';
    $imgdata['responsive'] = $prefs['image_responsive_class'];
    $imgdata['default'] = '';
    $imgdata['mandatory'] = '';
    $imgdata['fromFieldId'] = 0;        // "private" params set by Tracker_Field_Files
    $imgdata['fromItemId']  = 0;        // ditto
    $imgdata['checkItemPerms']  = 'y';  // ditto
    $imgdata['noDrawIcon']  = 'y';
    $imgdata['retina']  = 'n';
    $imgdata['widths']  = '';
    $imgdata['sizes']  = '';
    $imgdata['featured']  = 'n';

    $params = array_map(function ($param) {
        return str_replace('"', '&quot;', $param);
    }, $params);

    $imgdata = array_merge($imgdata, $params);

    $srcset = '';
    $sizes = '';

    //function calls
    if (! empty($imgdata['default']) || ! empty($imgdata['mandatory'])) {
        require_once('lib/Images/img_plugin_default_and_mandatory.php');
        if (! empty($imgdata['default'])) {
            $imgdata = apply_default_and_mandatory($imgdata, 'default');    //first process defaults
            $imgdata = array_merge($imgdata, $params);                  //then apply user settings, overriding defaults
        }
        //apply mandatory settings, overriding user settings
        if (! empty($imgdata['mandatory'])) {
            $imgdata = apply_default_and_mandatory($imgdata, 'mandatory');
        }
    }

    // Before it was possible to specify many image types at once and Tiki will guess which one to use.
    // Now there is "type" field that clearly identifies image type.
    // Code below leaves image param that is related to "type", removing all others,  this way code is not confused if
    // several parameters are passed
    if (! empty($imgdata['type'])) {
        $info = wikiplugin_img_info();
        foreach ($info['params']['type']['options'] as $type) {
            if (! empty($type['value']) && $type['value'] != $imgdata['type'] && ! empty($imgdata[$type['value']])) {
                $imgdata[$type['value']] = null;
            }
        }
    }

    if (isset($imgdata['id'])) {
        if ($prefs['file_galleries_redirect_from_image_gallery'] !== 'y') {
            return WikiParser_PluginOutput::error(tr('Plugin Image'), tr('The "id" parameter is not allowed unless "file_galleries_redirect_from_image_gallery" preference is enabled.'));
        }

        $notFoundTikiImages = [];
        $foundTikiImages = [];

        $idsList = preg_split('/[|,]/', $imgdata['id']);

        foreach ($idsList as $idList) {
            $fileInfo = $tikilib->table('tiki_object_attributes')->fetchRow(
                ['itemId'],
                ['value' => $idList, 'attribute' => 'tiki.file.imageid']
            );
            if ($fileInfo) {
                $foundTikiImages[] = $fileInfo['itemId'];
            } else {
                $notFoundTikiImages[] = $idList;
            }
        }

        if (count($notFoundTikiImages)) {
            $msg = sprintf(tr('Image(s) having Ids %s not found.'), implode("-", $notFoundTikiImages));
            if (! count($foundTikiImages)) {
                Feedback::error(['mes' => $msg]);
                return;
            } else {
                Feedback::warning(['mes' => $msg]);
            }
        }

        $imgdata['fileId'] = implode(",", $foundTikiImages);
        unset($imgdata['id']);
        unset($params['id']);
    }

    //////////////////////////////////////////////////// Error messages and clean javascript //////////////////////////////
    // Must set at least one image identifier
    $set = ! empty($imgdata['fileId']) + ! empty($imgdata['src']) + ! empty($imgdata['attId'])
        + ! empty($imgdata['randomGalleryId']) + ! empty($imgdata['fgalId']);
    if ($set == 0) {
        return WikiParser_PluginOutput::error(tr('Plugin Image'), tr('No image specified. One of the following parameters must be set: fileId, randomGalleryId, fgalId, attId or src.'));
    } elseif ($set > 1) {
        return WikiParser_PluginOutput::error(tr('Plugin Image'), tr('Use one and only one of the following parameters: fileId, randomGalleryId, fgalId, attId or src.'));
    }
    // Clean up src URLs to exclude javascript
    $imgdata['src'] = $imgdata['src'] ?? '';
    $imgdata['src'] = str_replace(' ', '', $imgdata['src']);
    if (stristr($imgdata['src'], 'javascript:')) {
        $imgdata['src'] = '';
    }
    if (strstr($imgdata['src'], 'javascript:')) {
        $imgdata['src']  = '';
    }

    if (! isset($data) or ! $data) {
        $data = '&nbsp;';
    }

    include_once('tiki-sefurl.php');
    //////////////////////Process multiple images //////////////////////////////////////
    //Process "|" or "," separated images
    $notice = '<!--' . tra('PluginImg: User lacks permission to view image') . '-->';
    $srcmash = $imgdata['fileId'] . $imgdata['attId'] . $imgdata['src'];
    if (( strpos($srcmash, '|') !== false ) || (strpos($srcmash, ',') !== false ) || ! empty($imgdata['fgalId'])) {
        $separator = '';
        if (! empty($imgdata['fileId'])) {
            $id = 'fileId';
        } elseif (! empty($imgdata['attId'])) {
            $id = 'attId';
        } else {
            $id = 'src';
        }
        if (strpos($imgdata[$id], '|') !== false) {
            $separator = '|';
        } elseif (strpos($imgdata[$id], ',') !== false) {
            $separator = ',';
        }
        $repl = '';
        $id_list = [];
        if (! empty($separator)) {
            $id_list = explode($separator, $imgdata[$id]);
        } else { //fgalId parameter - show all images in a file gallery
            $filegallib = TikiLib::lib('filegal');
            $galdata = $filegallib->get_files(0, -1, $imgdata['sort_mode'], '', $imgdata['fgalId'], false, false, false, true, false, false, false, false, '', true, false, false);
            foreach ($galdata['data'] as $filedata) {
                $id_list[] = $filedata['id'];
            }
            $id = 'fileId';
        }
        $params[$id] = '';
        foreach ($id_list as $i => $value) {
            $params[$id] = trim($value);
            $params['fgalId'] = '';
            $params['type'] = $id;
            $pluginResult = wikiplugin_img($data, $params);
            $repl .= WikiPlugin_Helper::resultString($pluginResult);
        }
        if (strpos($repl, $notice) !== false) {
            return $repl;
        } else {
            $repl = "\n\r" . '<br style="clear:both" />' . "\r" . $repl . "\n\r" . '<br style="clear:both" />' . "\r";
            return $repl; // return the multiple images
        }
    }

    $repl = '';

    //////////////////////Set src for html///////////////////////////////
    //Set variables for the base path for images in file galleries, image galleries and attachments
    global $base_url;

    if (empty($params['absoluteLinks'])) {
        // \WikiParser_Parsable::pluginExecute sends $this as the 4th param to all plugins
        if (func_num_args() >= 4) {
            $parsable = func_get_arg(3);
            $absolute_links = ! empty($parsable->option['absolute_links']);
        } else {
            $absolute_links = false;
        }
    } else {
        $absolute_links = $params['absoluteLinks'] === 'y';
    }
    $imagegalpath = ($absolute_links ? $base_url : '') . 'show_image.php?id=';
    $filegalpath = ($absolute_links ? $base_url : '') . 'tiki-download_file.php?fileId=';
    $attachpath = ($absolute_links ? $base_url : '') . 'tiki-download_wiki_attachment.php?attId=';
    $dbinfo = [];

    //get random image and treat as file gallery image afterwards
    if (! empty($imgdata['randomGalleryId'])) {
        $filegallib = TikiLib::lib('filegal');
        $dbinfo = $filegallib->get_file(0, $imgdata['randomGalleryId']);
        $imgdata['fileId'] = $dbinfo['fileId'];
        $imgdata['file'] = \Tiki\FileGallery\File::id($imgdata['fileId']);
    }

    if (empty($imgdata['src'])) {
        if (! empty($imgdata['fileId'])) {
            $src = smarty_modifier_sefurl($imgdata['fileId'], 'file');

            if ($absolute_links) {
                $src = TikiLib::tikiUrl($src);
            }
        } elseif ($prefs['feature_use_fgal_for_wiki_attachments'] === 'y' && ! empty($imgdata['attId'])) {
            $src = $filegalpath . $imgdata['attId'];
        } else {                    //only attachments left
            $src = $attachpath . $imgdata['attId'];
        }
    } elseif ((! empty($imgdata['src'])) && $absolute_links && ! preg_match('|^[a-zA-Z]+:\/\/|', $imgdata['src'])) {
        global $base_host, $url_path;
        $src = $base_host . ( $imgdata['src'][0] == '/' ? '' : $url_path ) . $imgdata['src'];
    } elseif (! empty($imgdata['src']) && $tikidomain && ! preg_match('|^https?:|', $imgdata['src'])) {
        $src = preg_replace("~" . DEPRECATED_IMG_WIKI_UP_PATH . " /~", DEPRECATED_IMG_WIKI_UP_PATH . "/$tikidomain/", $imgdata['src']);
    } elseif (! empty($imgdata['src'])) {
        $src = $imgdata['src'];
    }

    $browse_full_image = $src;
    $srcIsEditable = false;
    ///////////////////////////Get DB info for image size and metadata/////////////////////////////
    if (
        ! empty($imgdata['height']) || ! empty($imgdata['width']) || ! empty($imgdata['max'])
        || ! empty($imgdata['desc']) || strpos($imgdata['rel'], 'box') !== false
        || ! empty($imgdata['stylebox']) || ! empty($imgdata['styledesc']) || ! empty($imgdata['button'])
        || ! empty($imgdata['thumb'])  || ! empty($imgdata['align']) || ! empty($imgdata['metadata'])  || ! empty($imgdata['fileId'])
    ) {
        //Get ID numbers for images in galleries and attachments included in src as url parameter
        //So we can get db info for these too
        $parsed = parse_url($imgdata['src']);
        if (empty($parsed['host']) || (! empty($parsed['host']) && strstr($base_url, $parsed['host']))) {
            if (strlen(strstr($imgdata['src'], $imagegalpath)) > 0) {
                $imgdata['id'] = substr(strstr($imgdata['src'], $imagegalpath), strlen($imagegalpath));
            } elseif (strlen(strstr($imgdata['src'], $filegalpath)) > 0) {
                $imgdata['fileId'] = substr(strstr($imgdata['src'], $filegalpath), strlen($filegalpath));
            } elseif (strlen(strstr($imgdata['src'], $attachpath)) > 0) {
                $imgdata['attId'] = substr(strstr($imgdata['src'], $attachpath), strlen($attachpath));
            }
        }
        $imageObj = '';
        //Deal with images with info in tiki databases (file and image galleries and attachments)
        if (
            empty($imgdata['randomGalleryId']) && (! empty($imgdata['fileId'])
            || ! empty($imgdata['attId']))
        ) {
            //Try to get image from database
            if (empty($dbinfo) && ! empty($imgdata['fileId'])) {
                $imgdata['file'] = \Tiki\FileGallery\File::id($imgdata['fileId']);
                if ($imgdata['file']->fileId) {
                    $dbinfo = $imgdata['file']->getParams();
                }
            } elseif ($prefs['feature_use_fgal_for_wiki_attachments'] === 'y' && ! isset($dbinfo) && ! empty($imgdata['attId'])) {
                $imgdata['file'] = \Tiki\FileGallery\File::id($imgdata['attId']);
                $dbinfo = $imgdata['file']->getParams();
            } else {                    //only attachments left
                global $atts;
                $wikilib = TikiLib::lib('wiki');
                $dbinfo = $wikilib->get_item_attachment($imgdata['attId']);
                $basepath = $prefs['w_use_dir'];
            }
            //Give error messages if a file doesn't exist, isn't an image. Display nothing if user lacks permission
            if (! empty($imgdata['fileId']) || ! empty($imgdata['attId'])) {
                if (! $dbinfo) {
                    return WikiParser_PluginOutput::error(tr('Plugin Image'), tr('File not found.'));
                } elseif (substr($dbinfo['filetype'], 0, 5) != 'image' and ! preg_match('/thumbnail/i', $imgdata['fileId'])) {
                    return WikiParser_PluginOutput::error(tr('Plugin Image'), tr('File is not an image.'));
                } elseif (! Image::isAvailable()) {
                    return WikiParser_PluginOutput::error(tr('Plugin Image'), tr('Server does not support image manipulation.'));
                } elseif (! empty($imgdata['fileId'])) {
                    $gal_info = TikiLib::lib('filegal')->get_file_gallery_info($dbinfo['galleryId']);
                    if ($gal_info && $gal_info['type'] == 'attachments') {
                        $perms = Perms::get(['object' => $gal_info['name'], 'type' => 'wiki page']);
                        if ((! $perms->view || ! $perms->wiki_view_attachments) && ! $perms->wiki_admin_attachments) {
                            return $notice;
                        }
                    } else {
                        if (! $userlib->user_has_perm_on_object($user, $imgdata['fileId'], 'file', 'tiki_p_download_files')) {
                            return $notice;
                        }
                    }
                } elseif (! empty($imgdata['attId'])) {
                    if (! $userlib->user_has_perm_on_object($user, $dbinfo['page'], 'wiki page', 'tiki_p_wiki_view_attachments')) {
                        return $notice;
                    }
                }
            }
        } //finished getting info from db for images in image or file galleries or attachments

        //get image to get height and width and iptc data
        if (! empty($dbinfo['data'])) {
            $imageObj = Image::create($dbinfo['data'], false);
            $filename = $dbinfo['filename'];
        } elseif (! empty($dbinfo['path']) && isset($basepath)) {
            $imageObj = Image::create($basepath . $dbinfo['path'], true);
            $filename = $dbinfo['filename'];
        } elseif (isset($imgdata['file'])) {
            $imageObj = Image::create($imgdata['file']->getContents(), false);
            $filename = $imgdata['file']->filename;
        } elseif (strpos($src, '//') === false) {
            $imageObj = Image::create($src, true);
            $filename = $src;
        }
        // NOTE image sizing should only happen with local images, otherwise will break if remote server can't be reached

        //if we need metadata
        $xmpview = ! empty($imgdata['metadata']) ? true : false;
        if (is_object($imageObj) && ($imgdata['desc'] == 'idesc' || $imgdata['desc'] == 'ititle' || $xmpview)) {
            $dbinfoparam = isset($dbinfo) ? $dbinfo : false;
            $metadata = getMetadataArray($imageObj, $dbinfoparam);
            if ($imgdata['desc'] == 'idesc') {
                $idesc = getMetaField($metadata, ['User Data' => 'Description']);
            }
            if ($imgdata['desc'] == 'ititle') {
                $ititle = getMetaField($metadata, ['User Data' => 'Title']);
            }
        }

        if (! is_object($imageObj) || isset(TikiLib::lib('parser')->option['indexing']) && TikiLib::lib('parser')->option['indexing']) {
            $fwidth = 1;
            $fheight = 1;
        } else {
            $fwidth = $imageObj->getWidth();
            $fheight = $imageObj->getHeight();
        }

        $fheightt = 1;
        $fwidtht = 1;
        //get image gal thumbnail image for height and width
        if (! empty($dbinfot['data']) || ! empty($dbinfot['path'])) {
            if (! empty($dbinfot['data'])) {
                $imageObjt = Image::create($dbinfot['data'], false);
            } elseif (! empty($dbinfot['path'])) {
                $imageObjt = Image::create($basepath . $dbinfot['path'] . '.thumb', true);
            }
            $fwidtht = $imageObjt->getWidth();
            $fheightt = $imageObjt->getHeight();
        }
    /////////////////////////////////////Add image dimensions to src string////////////////////////////////////////////
        //Use url resizing parameters for file gallery images to set $height and $width
        //since they can affect other elements; overrides plugin parameters
        if (! empty($imgdata['fileId']) && strpos($src, '&') !== false) {
            $urlthumb = strpos($src, '&thumbnail');
            $urlprev = strpos($src, '&preview');
            $urldisp = strpos($src, '&display');
            preg_match('/(?<=\&max=)[0-9]+(?=.*)/', $src, $urlmax);
            preg_match('/(?<=\&x=)[0-9]+(?=.*)/', $src, $urlx);
            preg_match('/(?<=\&y=)[0-9]+(?=.*)/', $src, $urly);
            preg_match('/(?<=\&scale=)[0]*\.[0-9]+(?=.*)/', $src, $urlscale);
            if (! empty($urlmax[0]) && $urlmax[0] > 0) {
                $imgdata['max'] = $urlmax[0];
            }
            if (! empty($urlx[0]) && $urlx[0] > 0) {
                $imgdata['width'] = $urlx[0];
            }
            if (! empty($urly[0]) && $urly[0] > 0) {
                $imgdata['height'] = $urly[0];
            }
            if (! empty($urlscale[0]) && $urlscale[0] > 0) {
                $height = floor($urlscale[0] * $fheight);
                $width = floor($urlscale[0] * $fwidth);
                $imgdata['width'] = '';
                $imgdata['height'] = '';
            }
            if ($urlthumb != false && empty($imgdata['height']) && empty($imgdata['width']) && empty($imgdata['max'])) {
                $imgdata['max'] = 120;
            }
            if ($urlprev != false && empty($urlscale[0]) && empty($imgdata['height']) && empty($imgdata['width']) && empty($imgdata['max'])) {
                $imgdata['max'] = 800;
            }
        }
        //Note if image gal url thumb parameter is used
        $imgalthumb = false;
        if (! empty($imgdata['id'])) {
            preg_match('/(?<=\&thumb=1)[0-9]+(?=.*)/', $src, $urlimthumb);
            if (! empty($urlimthumb[0]) && $urlimthumb[0] > 0) {
                $imgalthumb = true;
            }
        }

        include_once('lib/mime/mimetypes.php');
        global $mimetypes;

        //Now set dimensions based on plugin parameter settings
        if (
            ! empty($imgdata['max']) || ! empty($imgdata['height']) || ! empty($imgdata['width'])
            || ! empty($imgdata['thumb'])
        ) {
            // find svg image size
            if (! empty($dbinfo['filetype'])  && ! empty($mimetypes['svg']) && $dbinfo['filetype'] == $mimetypes['svg']) {
                if (preg_match('/width="(\d+)" height="(\d+)"/', $dbinfo['data'], $svgdim)) {
                    $fwidth = $svgdim[1];
                    $fheight = $svgdim[2];
                }
            }

            // Adjust for max setting, keeping aspect ratio
            if (! empty($imgdata['max'])) {
                if (($fwidth > $imgdata['max']) || ($fheight > $imgdata['max'])) {
                    //use image gal thumbs when possible
                    if (
                        (! empty($imgdata['id']) && $imgalthumb == false)
                        && ($imgdata['max'] < $fwidtht || $imgdata['max'] < $fheightt)
                    ) {
                        $src .= '&thumb=1';
                        $imgalthumb == true;
                    }
                    if ($fwidth > $fheight) {
                        $width = $imgdata['max'];
                        $height = floor($width * $fheight / $fwidth);
                    } else {
                        $height = $imgdata['max'];
                        $width = floor($height * $fwidth / $fheight);
                    }
                //cases where max is set but image is smaller than max
                } else {
                    $height = $fheight;
                    $width = $fwidth;
                }
            // Adjust for user settings for height and width if max isn't set.
            } elseif (! empty($imgdata['height'])) {
                //use image gal thumbs when possible
                if (
                    (! empty($imgdata['id']) && $imgalthumb == false)
                    && ($imgdata['height'] < $fheightt)
                ) {
                    $src .= '&thumb=1';
                    $imgalthumb == true;
                }
                $height = $imgdata['height'];
                if (empty($imgdata['width']) && $fheight > 1 && is_numeric($height)) {
                    $width = floor($height * $fwidth / $fheight);
                } else {
                    $width = $imgdata['width'];
                }
            } elseif (! empty($imgdata['width'])) {
                //use image gal thumbs when possible
                if (
                    (! empty($imgdata['id']) && $imgalthumb == false)
                    && ($imgdata['width'] < $fwidth)
                ) {
                    $src .= '&thumb=1';
                    $imgalthumb == true;
                }
                $width = $imgdata['width'];
                if (empty($imgdata['height']) && $fwidth > 1 && is_numeric($width)) {
                    $height = floor($width * $fheight / $fwidth);
                } else {
                    $height = $imgdata['height'];
                }
            // If not otherwise set, use default setting for thumbnail height if thumb is set
            } elseif ((! empty($imgdata['thumb']) || ! empty($urlthumb))) {
                if (! empty($imgdata['fileId'])) {
                    $thumbdef = $prefs['fgal_thumb_max_size'];
                } else {
                    $thumbdef = 84;
                }
                //handle image gal thumbs
                if (! empty($imgdata['id']) && ! empty($fwidtht)  && ! empty($fheightt)) {
                    $width = $fwidtht;
                    $height = $fheightt;
                    if ($imgalthumb == false) {
                        $src .= '&thumb=1';
                        $imgalthumb == true;
                    }
                } else {
                    if (($fwidth > $thumbdef) || ($fheight > $thumbdef)) {
                        if ($fwidth > $fheight) {
                            $width = $thumbdef;
                            $height = floor($width * $fheight / $fwidth);
                        } else {
                            $height = $thumbdef;
                            $width = floor($height * $fwidth / $fheight);
                        }
                    }
                }
            }
        }

        //Set final height and width dimension string
        //handle file gallery images separately to use server-side resizing capabilities
        $imgdata_dim = '';
        if (! empty($imgdata['fileId'])) {
            if (empty($urldisp) && empty($urlthumb)) {
                $srcIsEditable = true;
                $src .= '&display';
            }
            if (
                (! empty($imgdata['max']) && $imgdata['thumb'] != 'download')
                    && (empty($urlthumb) && empty($urlmax[0]) && empty($urlprev))
            ) {
                $src .= '&max=' . $imgdata['max'];
                $imgdata_dim .= ' width="' . $width . '"';
                $imgdata_dim .= ' height="' . $height . '"';
            } elseif (! empty($width) || ! empty($height)) {
                if ((! empty($width) && ! empty($height)) && (empty($urlx[0]) && empty($urly[0]) && empty($urlscale[0]))) {
                    $imgdata_dim .= ' width="' . $width . '"';
                    $imgdata_dim .= ' height="' . $height . '"';
                } elseif (! empty($width) && (empty($urlx[0]) && empty($urlthumb) && empty($urlscale[0]))) {
                    $height = $fheight;
                    $imgdata_dim .= ' width="' . $width . '"';
                    $imgdata_dim .= ' height="' . $height . '"';
                } elseif (! empty($height) && (empty($urly[0]) && empty($urlthumb) && empty($urlscale[0]))) {
                    $width = $fwidth;
                    $imgdata_dim .= ' width="' . $width . '"';
                    $imgdata_dim .= ' height="' . $height . '"';
                }
            } else {
                $imgdata_dim = '';
                $height = $fheight;
                $width = $fwidth;
                if (! empty($width) && ! empty($height)) {
                    $imgdata_dim .= ' width="' . $width . '"';
                    $imgdata_dim .= ' height="' . $height . '"';
                }
            }
            if (isset($imgdata['quality'])) {
                $src .= '&format=' . str_replace('image/', '', $imgdata['file']->filetype) . '&quality=' . $imgdata['quality'];
            }
        } else {
            if (! empty($height)) {
                $imgdata_dim = ' height="' . $height . '"';
            } else {
                $imgdata_dim = '';
                $height = $fheight;
            }
            if (! empty($width)) {
                $imgdata_dim .= ' width="' . $width . '"';
            } elseif (empty($height)) {
                $imgdata_dim = '';
                $width = $fwidth;
            }
        }
    }
    if ($imgdata['retina'] == 'y' && $imgdata['width']) {
        $srcset_arr = [];
        $srcset_format = "tiki-download_file.php?display&fileId=%s&x=%d&y=%d %s";
        $srcset_arr[] = sprintf($srcset_format, $params['fileId'], $width * 2, $height * 2, "2x");
        $srcset_arr[] = sprintf($srcset_format, $params['fileId'], $width, $height, "1x");
        $srcset = implode(",", $srcset_arr);
    }
    if ($imgdata['widths'] <> '' && $params['sizes']) {
        $srcset_arr = [];
        $widths_arr = array_map('trim', explode(',', $params['widths']));
        foreach ($widths_arr as $entry) {
            $srcset_format = "tiki-download_file.php?display&fileId=%s&x=%d %dw";
            $srcset_arr[] = sprintf($srcset_format, $params['fileId'], $entry, $entry);
        }
        $srcset = implode(",", $srcset_arr);

        $size_max_breaks = ['767', '991', '1199' ]; //max sizes for xs, sm, and md
        $sizes_arr = array_map('trim', explode(",", $params['sizes']));

        if (count($sizes_arr) === 4) {
            $sizes = "(max-width: " . $size_max_breaks[0] . "px) " . $sizes_arr[0] . ",";
            $sizes .= "(max-width: " . $size_max_breaks[1] . "px) " . $sizes_arr[1] . ",";
            $sizes .= "(max-width: " . $size_max_breaks[2] . "px) " . $sizes_arr[2] . ",";
            $sizes .= $sizes_arr[3];
        }
        if (count($sizes_arr) === 1) {
            $sizes = $sizes_arr[0];
        }
    }

    ////////////////////////////////////////// Create the HTML img tag //////////////////////////////////////////////
    //Start tag with src and dimensions
    $src = filter_out_sefurl($src);

    if ($imgdata['featured'] === 'y') {
        $full_url = TikiLib::tikiUrl($src);
        $header_featured_images = $smarty->getTemplateVars('header_featured_images');
        if (! is_array($header_featured_images)) {
            $header_featured_images = [];
        }
        $header_featured_images[] = $full_url;
        $smarty->assign('header_featured_images', $header_featured_images);
    }

    $printing = preg_match("/tiki-print.php/", $_SERVER['REQUEST_URI']);
    $lozardImg = false;
    if (
        $prefs['allowImageLazyLoad'] === 'y' &&
        ! $printing &&
        (empty($params['lazyLoad']) || $params['lazyLoad'] !== 'n')
    ) {
        $lozardImg = true;
    }

    $tagName = '';
    if (! empty($dbinfo['filetype'])  && ! empty($mimetypes['svg']) && $dbinfo['filetype'] == $mimetypes['svg']) {
        $tagName = 'div';
        $repldata = $dbinfo['data'] ? $dbinfo['data'] : $imgdata['file']->getContents();
        if (! empty($fwidth) && ! empty($fheight) && ! empty($imgdata_dim)) {       // change svg attributes to show at the correct size
            $svgAttributes = $imgdata_dim . ' viewBox="0 0 ' . $fwidth . ' ' . $fheight . '" preserveAspectRatio="xMinYMin meet"';
            $repldata = preg_replace('/width="' . $fwidth . '" height="' . $fheight . '"/', $svgAttributes, $repldata);
            if ($repldata === null) {
                // if preg_replace fails restore original SVG data
                Feedback::error(tr('SVG Image replace error "%0"', preg_last_error()));
                $repldata = $dbinfo['data'];
            }
        }
        $replimg = '<div type="image/svg+xml" ';
        $imgdata['class'] .= ' svgImage pluginImg' . $imgdata['fileId'];
        if ($imgdata['responsive'] == 'y') {
            $imgdata['class'] .= ' table-responsive';
        }
        $imgdata['class'] = trim($imgdata['class']);
    } else {
        $tagName = 'img';
        $replimg = '<img src="' . $src . '" ';

        if ($srcset) {
            $replimg .= 'srcset="' . $srcset . '" ';
        }
        if ($sizes) {
            $replimg .= 'sizes="' . $sizes . '" ';
        }
        $imgdata['class'] .= ' regImage pluginImg' . $imgdata['fileId'];
        if ($imgdata['responsive'] == 'y') {
            $imgdata['class'] .= ' img-fluid';
        }
        if ($imgdata['featured'] == 'y') {
            $imgdata['class'] .= ' featured';
        }
        $imgdata['class'] = trim($imgdata['class']);
    }

    if ($lozardImg) {
        $replimg .= 'loading="lazy" ';
        $imgdata['data-src'] = true;
    }

    if (! empty($imgdata_dim)) {
        $replimg .= $imgdata_dim;
    }

    //Configure alignment if legacy align has been set
    //legacyalign
    if (! empty($imgdata['legacyalign'])) {
        $replimg .= ' align="' . $imgdata['legacyalign'] . '"';
    }

    //Configure horizontal spacing if legacy hspace has been set
    //hspace
    if (! empty($imgdata['hspace'])) {
        $replimg .= ' hspace="' . $imgdata['hspace'] . '"';
    }

    //Configure vertical spacing if legacy vspace has been set
    //vspace
    if (! empty($imgdata['vspace'])) {
        $replimg .= ' vspace="' . $imgdata['vspace'] . '"';
    }

    //Create style attribute allowing for shortcut inputs
    //First set alignment string
    $center = 'display:block; margin-left:auto; margin-right:auto;';    //used to center image and box
    $imalign = '';
    if (! empty($imgdata['imalign'])) {
        if ($imgdata['imalign'] == 'center') {
            $imalign = $center;
        } else {
            $imalign = 'float:' . $imgdata['imalign'] . ';';
        }
    } elseif ($imgdata['stylebox'] == 'border') {
        $imalign = $center;
    }
    //set entire style string
    $style = '';
    if (! empty($imgdata['styleimage']) || ! empty($imalign)) {
        $border = '';
        $borderdef = 'border:1px solid darkgray;';   //default border when styleimage set to border
        if (! empty($imgdata['styleimage'])) {
            if (! empty($imalign)) {
                if (
                    (strpos(trim($imgdata['styleimage'], ' '), 'float:') !== false)
                    || (strpos(trim($imgdata['styleimage'], ' '), 'display:') !== false)
                ) {
                    $imalign = '';          //override imalign setting if style image contains alignment syntax
                }
            }
            if ($imgdata['styleimage'] == 'border') {
                $border = $borderdef;
            } elseif (
                strpos($imgdata['styleimage'], 'hidden') === false
                && strpos($imgdata['styleimage'], 'position') === false
            ) { // quick filter for dangerous styles
                $style = $imgdata['styleimage'];
            }
        }
        $replimg .= ' style="' . $imalign . $border . $style . '"';
    }
    //alt
    if (! empty($imgdata['alt'])) {
        $replimg .= ' alt="' . $imgdata['alt'] . '"';
    } elseif (! empty($imgdata['desc'])) {
        $replimg .= ' alt="' . $imgdata['desc'] . '"';
    } elseif (! empty($dbinfo['description'])) {
        $replimg .= ' alt="' . str_replace('"', '&quot;', $dbinfo['description']) . '"';
    } elseif (! empty($dbinfo['name'])) {
        $replimg .= ' alt="' . str_replace('"', '&quot;', $dbinfo['name']) . '"';
    } else {
        $replimg .= ' alt="Image"';
    }
    //usemap
    if (! empty($imgdata['usemap'])) {
        $replimg .= ' usemap="#' . $imgdata['usemap'] . '"';
    }
    //class
    if (! empty($imgdata['class'])) {
        $replimg .= ' class="' . $imgdata['class'] . '"';
    }
    //data-src
    if (! empty($imgdata['data-src'])) {
        $replimg .= ' data-src="' . $src . '"';
    }

    //src
    if ((! empty($imgdata['src']) || ! empty($src)) && strpos($replimg, 'src="' . $src . '"') == false) {
        $replimg .= ' src="' . $src . '"';
    }

    //title (also used for description and link title below)
    //first set description, which is used for title if no title is set
    if (! empty($imgdata['desc']) || ! empty($imgdata['title'])) {
        $desconly = '';
        //attachment database uses comment instead of description or name
        if (! empty($dbinfo['comment'])) {
            $desc = $dbinfo['comment'];
            $imgname = $dbinfo['comment'];
        } else {
            $desc = ! empty($dbinfo['description']) ? str_replace('"', '&quot;', $dbinfo['description']) : '';
            $imgname = ! empty($dbinfo['name']) ? str_replace('"', '&quot;', $dbinfo['name']) : '';
        }
        if (! empty($imgdata['desc'])) {
            switch ($imgdata['desc']) {
                case 'desc':
                    $desconly = $desc;
                    break;
                case 'idesc':
                    $desconly = $idesc;
                    break;
                case 'name':
                    $desconly = $imgname;
                    break;
                case 'ititle':
                    $desconly = $ititle;
                    break;
                case 'namedesc':
                    $desconly = $imgname . ((! empty($imgname) && ! empty($desc)) ? ' - ' : '') . $desc;
                    break;
                default:
                    $desconly = $imgdata['desc'];
            }
        }
        //now set title
        $imgtitle = '';
        $titleonly = '';
        if (! empty($imgdata['title']) || ! empty($desconly)) {
            $imgtitle = ' title="';
            if (! empty($imgdata['title'])) {
                switch ($imgdata['title']) {
                    case 'desc':
                        $titleonly = $desc;
                        break;
                    case 'name':
                        $titleonly = $imgname;
                        break;
                    case 'namedesc':
                        $titleonly = $imgname . ((! empty($imgname) && ! empty($desc)) ? ' - ' : '') . $desc;
                        break;
                    default:
                        $titleonly = $imgdata['title'];
                }
            //use desc setting for title if title is empty
            } else {
                $titleonly = $desconly;
            }
            $imgtitle .= $titleonly . '"';
            $replimg .= $imgtitle;
        }
    }

    if (empty($repldata)) {
        $replimg .= ' />' . "\r";
    } else {
        $replimg .= '>' . $repldata . '</' . $tagName . '>';
    }

    ////////////////////////////////////////// Create the HTML link ///////////////////////////////////////////
    //Variable for identifying if javascript mouseover is set
    if (($imgdata['thumb'] == 'mouseover') || ($imgdata['thumb'] == 'mousesticky')) {
        $javaset = 'true';
    } else {
        $javaset = '';
    }
    // Set link to user setting or to image itself if thumb is set
    $imgtarget = "";
    if (! $printing && (! empty($imgdata['link']) || (! empty($imgdata['thumb']) && ! (isset($params['link']) && empty($params['link']))))) {
        $mouseover = '';
        if (! empty($imgdata['link'])) {
            $link = $imgdata['link'];
            if (! empty($imgdata['thumb']) && $imgdata['thumb'] === 'y') {
                $imgtarget = " target='_blank' ";
            }
        } elseif ($javaset == 'true') {
            $link = 'javascript:void(0)';
            $fwidth = empty($fwidth) ? '' : $fwidth;
            $fheight = empty($fheight) ? '' : $fheight;
            $popup_params = [ 'text' => $data, 'width' => $fwidth, 'height' => $fheight, 'background' => $browse_full_image];
            if ($imgdata['thumb'] == 'mousesticky') {
                $popup_params['sticky'] = true;
            }

            if ($imgdata['thumb'] == 'mouseover') {
                $popup_params['trigger'] = 'hover';
            }
            // avoid big images will not be closeable on hover if repsonsive is not set. Fallback to require a click to open and a second click somewhere to close.
            if ((isset($imgdata['responsive']) && $imgdata['responsive'] != 'y') && ($fwidth > 400 || $fheight > 400)) {
                $popup_params['trigger'] = 'focus';
            }

            $mouseover = ' ' . smarty_function_popup($popup_params, $smarty->getEmptyInternalTemplate());
        } else {
            if (! empty($imgdata['fileId']) && $imgdata['thumb'] != 'download' && empty($urldisp)) {
                $link = $browse_full_image . '&display';
            } else {
                $link = $browse_full_image;
            }
        }
        if (($imgdata['thumb'] == 'box' || $imgdata['thumb'] == 'zoombox') && empty($imgdata['rel'])) {
            $imgdata['rel'] = 'box';
        } elseif ($imgdata['thumb'] == 'zoom') {
            $imgdata['rel'] = 'zoom';
        }

        if ($imgdata['thumb'] == 'zoombox') {
            $zoomscript = "$(document).on('cbox_complete', function(){
                                $('.cboxPhoto').wrap('<span class=\"zoom_container\" style=\"display:inline-block\"></span>')
                                .css('display', 'block')
                                .parent()
                                .zoom({
                                    on: 'click'
                                });
                                $('.zoom_container').append('<div class=\"zoomIcon\"></div>');
                                $('.zoomIcon').css('position','relative').css('height','20px').css('width','90px').css('top','-20px')
                                    .css('background','white').css('padding','3px').css('font-size','14px')
                                    .html('Click to zoom');
                                $('#cboxLoadedContent').css('height', 'auto');
                            });";
            TikiLib::lib('header')->add_jq_onready($zoomscript);
        }
        // Set other link-related attributes
        // target
        if (
            ($prefs['popupLinks'] == 'y' && (preg_match('#^([a-z0-9]+?)://#i', $link)
            || preg_match('#^www\.([a-z0-9\-]+)\.#i', $link))) || ($imgdata['thumb'] == 'popup')
            || ($imgdata['thumb'] == 'browsepopup')
        ) {
            if (! empty($javaset) || ($imgdata['rel'] == 'box')) {
                $imgtarget = '';
            } else {
                $imgtarget = ' target="_blank"';
            }
        }
        // rel or data-box
        if (! empty($imgdata['rel'])) {
            $box = ['box', 'type=', 'slideshow', 'zoom'];
            foreach ($box as $btype) {
                if (strpos($imgdata['rel'], $btype) !== false) {
                    $attr = 'data-box';
                    break;
                }
            }
            if (! isset($attr)) {
                $attr = 'rel';
            }
            $linkrel = ' ' . $attr . '="' . $imgdata['rel'] . '"';
        } else {
            $linkrel = '';
        }
        // title
        ! empty($imgtitle) ? $linktitle = $imgtitle : $linktitle = '';

        $link = filter_out_sefurl($link);

        // For ImgPlugin alignment
        $style = '';
        if ($imgdata['imalign'] == "right") {
            $style = 'style="float: right;"';
        }

        //Final link string
        $replimg = "\r\t" . '<a href="' . $link . '"' . $style . ' class="internal" ' . $linkrel . $imgtarget . $linktitle
                    . $mouseover . '>' . "\r\t\t" . $replimg . "\r\t" . '</a>';
        if ($imgdata['thumb'] == 'mouseover') {
            $mouseevent = "$('.internal').popover({ 
                          html : true,
                          placement :wheretoplace
                          });
                            function wheretoplace(pop, dom_el) {
                              var width = window.innerWidth;
                              if (width<500) return 'bottom';
                              var left_pos = $(dom_el).offset().left;
                              if (width - left_pos > 400) return 'right';
                              return 'left';
                            }
                            ";
            TikiLib::lib('header')->add_jq_onready($mouseevent);
        }
    }

    //Add link string to rest of string
    $repl .= $replimg;

//////////////////////////Generate metadata dialog box and jquery (dialog icon added in next section)////////////////////////////////////
    if ($imgdata['metadata'] == 'view') {
        //create unique id's in case of multiple pictures
        static $lastval = 0;
        $id_meta = 'imgdialog-' . ++$lastval;
        $id_link = $id_meta . '-link';
        //use metadata stored in file gallery db if available
        include_once 'lib/metadata/metadatalib.php';
        $meta = new FileMetadata();
        $dialog = $meta->dialogTabs($metadata, $id_meta, $id_link, $filename);
        $repl .= $dialog;
    }
    //////////////////////  Create enlarge button, metadata icon, description and their divs////////////////////
    //Start div that goes around button and description if these are set
    if (! empty($imgdata['button']) || ! empty($imgdata['desc']) || ! empty($imgdata['styledesc']) || ! empty($imgdata['metadata'])) {
        //To set room for enlarge button under image if there is no description
        $descheightdef = 'height:17px;clear:left;';
        if (! empty($imgdata["width"])) {
            $descwidth = 'max-width: 100%; width:' . $width . 'px;';
        } else {
            $descwidth = '';
        }
        $repl .= "\r\t" . '<div class="mini" style="' . $descwidth;
        if (! empty($imgdata['styledesc'])) {
            if (($imgdata['styledesc'] == 'left') || ($imgdata['styledesc'] == 'right')) {
                $repl .= 'text-align:' . $imgdata['styledesc'] . '">';
            } else {
                $repl .= $imgdata['styledesc'] . '">';
            }
        } elseif ((! empty($imgdata['button'])) && (empty($desconly))) {
            $repl .= $descheightdef . '">';
        } else {
            $repl .= '">';
        }

        //Start description div that also includes enlarge button div
        $repl .= "\r\t\t" . '<div class="thumbcaption">';

        //Enlarge button div and link string (innermost div)
        if (! empty($imgdata['button'])) {
            if (empty($link) || (! empty($link) && ! empty($javaset))) {
                if (! empty($imgdata['fileId']) && $imgdata['button'] != 'download') {
                    $link_button = $browse_full_image . '&display';
                } elseif (! empty($imgdata['attId']) && $imgdata['thumb'] == 'download') {
                    $link_button = $browse_full_image . '&download=y';
                } else {
                    $link_button = $browse_full_image;
                }
                $link_button = filter_out_sefurl($link_button);
            } else {
                $link_button = $link;
            }
            //Set button rel
            if (! empty($imgdata['rel'])) {
                $box = ['box', 'type=', 'slideshow', 'zoom'];
                foreach ($box as $btype) {
                    if (strpos($imgdata['rel'], $btype) !== false) {
                        $attr = 'data-box';
                        break;
                    }
                }
                if (! isset($attr)) {
                    $attr = 'rel';
                }
                $linkrel_button = ' ' . $attr . '="' . $imgdata['rel'] . '"';
            } else {
                $linkrel_button = '';
            }
            //Set button target
            if (empty($imgtarget) && (empty($imgdata['thumb']) || ! empty($javaset))) {
                if (($imgdata['button'] == 'popup') || ($imgdata['button'] == 'browsepopup')) {
                    $imgtarget_button = ' target="_blank"';
                } else {
                    $imgtarget_button = '';
                }
            } else {
                $imgtarget_button = $imgtarget;
            }
            $repl .= "\r\t\t\t" . '<div class="magnify" style="float:right">';
            $repl .= "\r\t\t\t\t" . '<a href="' . $link_button . '"' . $linkrel_button . $imgtarget_button ;
            $repl .= ' class="internal"';
            if (! empty($titleonly)) {
                $repl .= ' title="' . $titleonly . '"';
            }
            $repl .= ">\r\t\t\t\t" . smarty_function_icon(['name' => 'view', 'iclass' => 'tips',
                    'ititle' => ':' . tra('Enlarge')], $smarty->getEmptyInternalTemplate()) . '</a>' . "\r\t\t\t</div>";
        }
        //Add metadata icon
        if ($imgdata['metadata'] == 'view') {
            $repl .= '<div style="float:right; margin-right:2px"><a href="#" id="' . $id_link
                . '" class="tips" title=":' . tra('Metadata') . '">' . smarty_function_icon(['name' => 'tag'], $smarty->getEmptyInternalTemplate())
                . '</a></div>';
        }
        //Add description based on user setting (use $desconly from above) and close divs
        isset($desconly) ? $repl .= $desconly : '';
        $repl .= "\r\t\t</div>";
        $repl .= "\r\t</div>";
    }
    ///////////////////////////////Wrap in overall div that includes image if needed////////////////
    //Need a box if any of these are set
    if (
        ! empty($imgdata['button']) || ! empty($imgdata['desc']) || ! empty($imgdata['metadata'])
        || ! empty($imgdata['stylebox']) || ! empty($imgdata['align'])
    ) {
        //Make the div surrounding the image 2 pixels bigger than the image
        if (empty($height) || ! is_numeric($height)) {
            $height = 0;
        }
        if (empty($width) || ! is_numeric($width)) {
            $width = 0;
        }
        $boxwidth = $width + 2;
        $boxheight = $height + 2;
        $alignbox = '';
        $class = '';
        if (! empty($imgdata['align'])) {
            if ($imgdata['align'] == 'center') {
                $alignbox = $center;
            } else {
                $alignbox = 'float:' . $imgdata['align'] . '; margin-' . ($imgdata['align'] == 'left' ? 'right' : 'left') . ':5px;';
            }
        }
        //first set stylebox string if style box is set
        if (! empty($imgdata['stylebox']) || ! empty($imgdata['align'])) {      //create strings from shortcuts first
            if (! empty($imgdata['stylebox'])) {
                if ($imgdata['stylebox'] == 'border') {
                    $class = 'class="imgbox" ';
                    if (! empty($alignbox)) {
                        if (
                            (strpos(trim($imgdata['stylebox'], ' '), 'float:') !== false)
                            || (strpos(trim($imgdata['stylebox'], ' '), 'display:') !== false)
                        ) {
                            $alignbox = '';         //override align setting if stylebox contains alignment syntax
                        }
                    }
                } else {
                    $styleboxinit = $imgdata['stylebox'] . ';';
                }
            }
            if (empty($imgdata['button']) && empty($imgdata['desc']) && empty($styleboxinit) && $boxwidth !== 2) {
                $styleboxplus = $alignbox . ' max-width: 100%; width:' . $boxwidth . 'px; height:' . $boxheight . 'px';
            } elseif (! empty($styleboxinit)) {
                if (
                    (strpos(trim($imgdata['stylebox'], ' '), 'height:') === false)
                    && (strpos(trim($imgdata['stylebox'], ' '), 'width:') === false)
                ) {
                    $styleboxplus = $styleboxinit . ' max-width: 100%; width:' . $boxwidth . 'px;';
                } else {
                    $styleboxplus = $styleboxinit;
                }
            } elseif ($boxwidth === 2) {
                $styleboxplus = $alignbox . ' width: auto;';
            } else {
                $styleboxplus = $alignbox . ' max-width: 100%; width:' . $boxwidth . 'px;';
            }
        } elseif (! empty($imgdata['button']) || ! empty($imgdata['desc']) || ! empty($imgdata['metadata'])) {
            if ($boxwidth === 2) {
                $styleboxplus = ' max-width: 100%; width: auto;';
            } else {
                $styleboxplus = ' max-width: 100%; width: ' . $boxwidth . 'px;';
            }
        } elseif ($boxwidth === 2) {
            $styleboxplus = ' width: auto;';
        }
    }
    if (! empty($styleboxplus)) {
        $repl = "\r" . '<div ' . $class . 'style="display: inline-block; ' . $styleboxplus . '">' . $repl . "\r" . '</div>';
    }
//////////////////////////////////////Place 'clear' block///////////////////////////////////////////////////////////
    if (! empty($imgdata['block'])) {
        switch ($imgdata['block']) {
            case 'top':
                $repl = "\n\r<br style=\"clear:both\" />\r" . $repl;
                break;
            case 'bottom':
                $repl = $repl . "\n\r<br style=\"clear:both\" />\r";
                break;
            case 'both':
                $repl = "\n\r<br style=\"clear:both\" />\r" . $repl . "\n\r<br style=\"clear:both\" />\r";
                break;
        }
    }
    // Mobile
    if (isset($_REQUEST['mode']) && $_REQUEST['mode'] == 'mobile') {
        $repl = '{img src=' . $src . "\"}\n<p>" . $imgdata['desc'] . '</p>';
    }

    if (
        ! TikiLib::lib('parser')->option['suppress_icons'] &&
            $prefs['feature_draw'] == 'y' && ! empty($dbinfo['galleryId']) && $imgdata['noDrawIcon'] !== 'y'
    ) {
        global $tiki_p_edit;
        $perms = TikiLib::lib('tiki')->get_perm_object($imgdata['fileId'], 'file', $dbinfo);
        if ($imgdata['fromItemId']) {
            if ($imgdata['checkItemPerms'] !== 'n') {
                $perms_Accessor = Perms::get(['type' => 'trackeritem', 'object' => $imgdata['fromItemId']]);
                $trackerItemPerms = $perms_Accessor->modify_tracker_items;
            } else {
                $trackerItemPerms = true;
            }
        } else {
            $trackerItemPerms = false;
        }

        if (
            $perms['tiki_p_upload_files'] === 'y' &&
            (empty($src) == true || $srcIsEditable == true) &&
            ($tiki_p_edit == 'y' || $trackerItemPerms)
        ) {
            if ($prefs['wiki_edit_icons_toggle'] == 'y' && ! isset($_COOKIE['wiki_plugin_edit_view']) && ! $imgdata['fromItemId']) {
                $iconDisplayStyle = " style=\"display:none;\"";
            } else {
                $iconDisplayStyle = '';
            }
            $jsonParams = json_encode(array_filter($imgdata));
            // While smarty_function_bootstrap_modal is available for such use cases, it doesn't fit in cases where the final html is generated as strings because there happens to be quotes not properly escaped.
            $repl .= "<a href='tiki-ajax_services.php?controller=draw&action=edit&fileId{$imgdata['fileId']}=&modal=1' data-tiki-bs-toggle='modal' data-bs-backdrop='static' data-bs-target='.footer-modal.fade:not(.show):first' data-size='modal-fullscreen' title=\""
                . tr("Draw on the Image") . "\"" .
                " class=\"editplugin pluginImgEdit{$imgdata['fileId']}\" data-fileid=\"{$imgdata['fileId']}\" " .
                "data-galleryid=\"{$dbinfo['galleryId']}\"{$iconDisplayStyle} data-imgparams='$jsonParams'>" .
                smarty_function_icon(['name' => 'edit', 'iclass' => 'tips', 'ititle' => ':' . tra('Edit')], $smarty->getEmptyInternalTemplate())
                . '</a>';
        }
    }
    return '~np~' . $repl . "\r" . '~/np~';
}

function getMetadataArray($imageObj, $dbinfo = false)
{
    if ($dbinfo !== false) {
        if (! empty($dbinfo['metadata'])) {
            $metarray = json_decode($dbinfo['metadata'], true);
        } elseif (isset($dbinfo['fileId'])) {
            $filegallib = TikiLib::lib('filegal');
            $metarray = $filegallib->metadataAction($dbinfo['fileId']);
        } else {
            $metarray = $imageObj->getMetadata()->typemeta['best'];
        }
    } else {
        $metarray = $imageObj->getMetadata()->typemeta['best'];
    }
    return $metarray;
}

function getMetaField($metarray, $labelarray)
{
    include_once 'lib/metadata/reconcile.php';
    $rec = new ReconcileExifIptcXmp();
    $labelmap = $rec->basicSummary[key($labelarray)][$labelarray[key($labelarray)]];
    foreach ($labelmap as $type => $fieldname) {
        foreach ($metarray as $subtype => $group) {
            if ($type == $subtype) {
                foreach ($group as $groupname => $fields) {
                    if (array_key_exists($fieldname, $fields)) {
                        $ret = $fields[$fieldname]['newval'];
                        return $ret;
                    }
                }
                break;
            }
        }
    }
}
