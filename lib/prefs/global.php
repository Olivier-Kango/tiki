<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Package\VendorHelper;

function prefs_global_list($partial = false)
{
    return [
        'browsertitle' => [
            'name' => tra('Browser title'),
            'description' => tra('Visible label in the browser\'s title bar on all pages. Also appears in search engine results.'),
            'type' => 'text',
            'default' => '',
            'tags' => ['basic'],
            'public' => true,
            'translatable' => true,
        ],
        'fallbackBaseUrl' => [
            'name' => tra('Fallback for tiki base URL'),
            'description' => tra('The full URL to the Tiki base URL including protocol, domain and path (example: https://example.com/tiki/), used when the current URL can not be determined, example, when executing from the command line.'),
            'type' => 'text',
            'default' => '',
            'tags' => ['basic'],
            'public' => true,
        ],
        'validateUsers' => [
            'name' => tra('Validate new user registrations by email'),
            'description' => tra('Tiki will send an email message to the user. The message contains a link that must be clicked to validate the registration. After clicking the link, the user will be validated. You can use this option to limit false registrations or fake email addresses.'),
            'type' => 'flag',
            'dependencies' => [
                'sender_email',
            ],
            'default' => 'y',
            'tags' => ['basic'],
        ],
        'wikiHomePage' => [
            'name' => tra('Wiki homepage'),
            'description' => tra('The default home page of the wiki when no other page is specified. The page will be created if it does not already exist.'),
            'keywords' => 'homepage',
            'type' => 'text',
            'size' => 20,
            'default' => 'HomePage',
            'tags' => ['basic'],
            'profile_reference' => 'wiki_page',
        ],
        'useGroupHome' => [
            'name' => tra('Use group homepages'),
            'description' => tra('Users can be directed to different pages upon logging in, depending on their default group.'),
            'type' => 'flag',
            'help' => 'Groups',
            'keywords' => 'group home page pages',
            'default' => 'n',
        ],
        'limitedGoGroupHome' => [
            'name' => tra('Go to the group homepage only if logging in from the default homepage'),
            'type' => 'flag',
            'dependencies' => [
                'useGroupHome',
            ],
            'keywords' => 'group home page pages',
            'default' => 'n',
        ],
        'cachepages' => [
            'name' => tra('Cache external pages'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'cacheimages' => [
            'name' => tra('Cache external images'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'tmpDir' => [
            'name' => tra('Temporary directory'),
            'description' => tra('Directory on your server, relative to your Tiki installation, for storing temporary files. Tiki must have full read and write access to this directory.'),
            'keywords' => 'tmp temp path',
            'type' => 'text',
            'size' => 30,
            'default' => sys_get_temp_dir(),  // note: this gets overridden in lib/setup/prefs.php
            'perspective' => false,
        ],
        'helpurl' => [
            'name' => tra('Help URL'),
            'description' => tra('The default help system may not be complete. You can contribute to the Tiki documentation, which is a community-edited wiki.'),
            'help' => 'Welcome-Authors',
            'type' => 'text',
            'size' => '50',
            'dependencies' => [
                'feature_help',
            ],
            'default' => "http://doc.tiki.org/",
            'public' => true,
        ],
        'popupLinks' => [
            'name' => tra('Open external links in new window'),
            'type' => 'flag',
            'description' => tr('Open links to external sites in a new browser tab or window.'),
            'default' => 'y',
            'tags' => ['basic'],
        ],
        'allowImageLazyLoad' => [
            'name' => tra('Allow image lazy loading'),
            'type' => 'flag',
            'description' => tr('Allow that images are loaded in a lazy way'),
            'default' => 'n',
            'tags' => ['advanced'],
        ],
        'wikiLicensePage' => [
            'name' => tra('License page'),
            'description' => tra('The wiki page where the license information is written.'),
            'type' => 'text',
            'size' => '30',
            'default' => '',
        ],
        'wikiSubmitNotice' => [
            'name' => tra('Submit notice'),
            'description' => tra('Text to appear when content is being submitted'),
            'type' => 'text',
            'size' => '30',
            'default' => '',
        ],
        'gdaltindex' => [
            'name' => tra('Full path to gdaltindex'),
            'type' => 'text',
            'size' => '50',
            'help' => 'Maps',
            'perspective' => false,
            'default' => '',
        ],
        'ogr2ogr' => [
            'name' => tra('Full path to ogr2ogr'),
            'type' => 'text',
            'size' => '50',
            'help' => 'Maps',
            'perspective' => false,
            'default' => '',
        ],
        'mapzone' => [
            'name' => tra('Map Zone'),
            'type' => 'list',
            'help' => 'Maps',
            'options' => [
                '180' => '[-180 180]',
                '360' => '[0 360]',
            ],
            'default' => '180',
        ],
        'modallgroups' => [
            'name' => tra('Always display modules to all groups'),
            'type' => 'flag',
            'description' => tr('Any setting for the Groups parameter will be ignored and the module will be displayed to all users.'),
            'default' => 'n',
            'help' => 'Module-Setttings-Parameters',
        ],
        'modseparateanon' => [
            'name' => tra('Hide anonymous-only modules from registered users'),
            'type' => 'flag',
            'description' => tr('If an individual module is assigned to the Anonymous group, the module will be displayed only to anonymous visitors. Registered users will not see the module.'),
            'default' => 'n',
        ],
        'modhideanonadmin' => [
            'name' => tra('Hide anonymous-only modules from Admins'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'maxArticles' => [
            'name' => tra('Maximum number of articles on the articles homepage'),
            'type' => 'text',
            'description' => tr('The number of articles to show on each page of the Articles homepage.'),
            'size' => '5',
            'filter' => 'digits',
            'units' => tra('articles'),
            'default' => 10,
        ],
        'sitead' => [
            'name' => tra('Site Ads and Banners Content'),
            'hint' => tra('Example:') . ' ' . "{banner zone='" . tra('Test') . "'}",
            'type' => 'textarea',
            'size' => '5',
            'default' => '',
        ],
        'urlOnUsername' => [
            'name' => tra('URL to go to when clicking on a username'),
            'type' => 'text',
            'description' => tra('URL to go to when clicking on a username.') . ' ' . tra('Default') . ': tiki-user_information.php?userId=%userId% <em>(' . tra('Use %user% for login name and %userId% for userId)') . ')</em>',
            'default' => '',
        ],
        'forgotPass' => [
            'name' => tra('Forgot password'),
            'description' => tra('Users can request a password reset. They will receive a link by email.'),
            'type' => 'flag',
            'detail' => tra("Since passwords are stored securely, it's not possible to tell the user what the password is. It's only possible to change it."),
            'default' => 'y',
            'tags' => ['basic'],
        ],
        'twoFactorAuth' => [
            'name' => tra('Allow users to use 2FA'),
            'description' => tra('Allow users to enable Two-factor Authentication.'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'twoFactorAuthType' => [
            'name' => tra('2FA Type'),
            'description' => tra('Type of 2fa to be used.'),
            'type' => 'list',
            'options' => [
                'google2FA' => tra('Google 2FA'),
                'email2FA' => tra('Email 2FA'),
            ],
            'default' => 'google2FA',
        ],
        'twoFactorAuthEmailTokenLength' => [
            'name' => tra('Email 2FA Token Length'),
            'description' => tra('The token length generated by Tiki for email 2FA.'),
            'type' => 'text',
            'default' => '6',
        ],
        'twoFactorAuthEmailTokenTTL' => [
            'name' => tra('Email 2FA Token Time-to-live'),
            'description' => tra('The time-to-live for the token generated by Tiki for email 2FA.'),
            'type' => 'text',
            'default' => '30',
        ],
        'twoFactorAuthIntervalDays' => [
            'name' => tra('Number of days before requiring new MFA'),
            'description' => tra('A value of zero (default) means always, a value bigger than zero requires a user to go through the MFA challenge every X days.'),
            'type' => 'text',
            'default' => '0',
        ],
        'twoFactorAuthAllUsers' => [
            'name' => tra('Force all users to use 2FA'),
            'description' => tra('This will force all users to activate 2FA.'),
            'type' => 'flag',
            'dependencies' => [
                'twoFactorAuth',
            ],
            'default' => 'n',
        ],
        'twoFactorAuthIncludedGroup' => [
            'name' => tra('Force users in the indicated groups to enable 2FA'),
            'description' => tra('List of group names.'),
            'separator' => ';',
            'filter' => 'groupname',
            'profile_reference' => 'group',
            'dependencies' => [
                'twoFactorAuth',
            ],
            'default' => [],
        ],
        'twoFactorAuthIncludedUsers' => [
            'name' => tra('Force indicated users to enable 2FA'),
            'description' => tra('List of usernames.'),
            'separator' => ';',
            'filter' => 'username',
            'profile_reference' => 'user',
            'dependencies' => [
                'twoFactorAuth',
            ],
            'default' => [],
        ],
        'twoFactorAuthExcludedGroup' => [
            'name' => tra('Do not force users in the indicated groups to enable 2FA'),
            'description' => tra('List of group names.'),
            'separator' => ';',
            'filter' => 'groupname',
            'profile_reference' => 'group',
            'dependencies' => [
                'twoFactorAuth',
            ],
            'default' => [],
        ],
        'twoFactorAuthExcludedUsers' => [
            'name' => tra('Do not force indicated users to enable 2FA'),
            'description' => tra('List of usernames.'),
            'separator' => ';',
            'filter' => 'username',
            'profile_reference' => 'user',
            'dependencies' => [
                'twoFactorAuth',
            ],
            'default' => [],
        ],
        'useGroupTheme' => [
            'name' => tra('Group theme'),
            'description' => tra('Enable groups to each have their own visual theme.'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'sitetitle' => [
            'name' => tra('Site title'),
            'type' => 'text',
            'description' => tr('The displayed title of the website.'),
            'size' => '50',
            'default' => '',
            'tags' => ['basic'],
            'public' => true,
        ],
        'sitesubtitle' => [
            'name' => tra('Subtitle'),
            'type' => 'text',
            'description' => tr('A short phrase that, for example, describes the site.'),
            'size' => '50',
            'default' => '',
            'tags' => ['basic'],
            'public' => true,
        ],
        'maxRecords' => [
            'name' => tra('Maximum number of records in listings'),
            'type' => 'text',
            'size' => '3',
            'units' => tra('records'),
            'default' => 25,
            'tags' => ['basic'],
            'public' => true,
        ],
        'maxVersions' => [
            'name' => tra('Maximum number of versions:'),
            'type' => 'text',
            'units' => tra('versions'),
            'size' => '5',
            'hint' => tra('0 for unlimited'),
            'default' => 0,
            'keywords' => 'wiki history',
        ],
        'allowRegister' => [
            'name' => tra('Users can register'),
            'description' => tra('Allow site visitors to register, using the registration form. The log-in module will include a "Register" link. If this is not activated, new users will have to be added manually by the admin on the Admin-Users page.'),
            'type' => 'flag',
            'default' => 'n',
            'tags' => ['basic'],
        ],
        'validateEmail' => [
            'name' => tra("Validate user's email server"),
            'description' => tra('Tiki will attempt to validate the user’s email address by examining the syntax of the email address. It must be a string of letters, or digits or _ or . or - follows by a @ follows by a string of letters, or digits or _ or . or -. Tiki will perform a DNS lookup and attempt to open a SMTP session to validate the email server.'),
            'type' => 'list',
            'tip' => tra('Some web servers may disable this functionality, thereby disabling this feature. If you are not in in a high security site or if you are on an open users site, do not use this option.'),
            'options' => [
                'n' => tra('No'),
                'y'         => tra('Yes'),
                'd' => tra('Yes, with "deep MX" search'),   // filters out reserved IP addresses and uses checkdnsrr to check for a valid "A" record
            ],
            'default' => 'n',
        ],
        'validateRegistration' => [
            'name' => tra('Require validation by Admin'),
            'description' => tra('The administrator will receive an email for each new user registration, and must validate the user before the user can log in.'),
            'type' => 'flag',
            'dependencies' => [
                'sender_email',
            ],
            'default' => 'n',
        ],
        'useRegisterPasscode' => [
            'name' => tra('Require passcode to register'),
            'description' => tra('Users must enter an alphanumeric code to register.  The site administrator must inform users of this code. This is to restrict registration to invited users.'),
            'type' => 'flag',
            'default' => 'n',
            'tags' => ['basic'],
        ],
        'registerPasscode' => [
            'name' => tra('Passcode'),
            'type' => 'text',
            'size' => 15,
            'hint' => tra('Alphanumeric code required to complete the registration'),
            'default' => '',
            'tags' => ['basic'],
        ],
        'showRegisterPasscode' => [
            'name' => tra('Show passcode on registration form'),
            'description' => tra("Displays the required passcode on the registration form. This is helpful for legitimate users who want to register while making it difficult for automated robots because the passcode is unique for each site and because it is displayed in JavaScript."),
            'type' => 'flag',
            'default' => 'n',
            'tags' => ['basic'],
        ],
        'registerKey' => [
            'name' => tra('Registration page key'),
            'hint' => tra('Key required to be on included the URL to access the registration page (if not empty).'),
            'description' => tra('To register, users need to go to, for example: tiki-register.php?key=yourregistrationkeyvalue'),
            'type' => 'text',
            'size' => 15,
            'default' => '',
            'tags' => ['basic'],
        ],
        'userTracker' => [
            'name' => tra('Use a tracker to collect more user information'),
            'description' => tra('Display a tracker form for the user to complete as part of the registration process. This tracker will receive and store additional information about each user.'),
            'type' => 'flag',
            'help' => 'User-Tracker',
            'dependencies' => [
                'feature_trackers',
            ],
            'hint' => tra('Go to [tiki-admingroups.php|Admin Groups] to select which tracker and fields to display.'),
            'default' => 'n',
        ],
        'groupTracker' => [
            'name' => tra('Use tracker to collect more group information'),
            'type' => 'flag',
            'help' => 'Group-Tracker',
            'dependencies' => [
                'feature_trackers',
            ],
            'hint' => tra('Go to [tiki-admingroups.php|Admin Groups] to select which tracker and fields to display.'),
            'default' => 'n',
        ],
        'eponymousGroups' => [
            'name' => tra('Create a new group for each user'),
            'description' => tra('Automatically create a group for each user in order to, for example, assign permissions on the individual-user level.'),
            'type' => 'flag',
            'hint' => tra("The group name will be the same as the user's username"),
            'help' => 'Groups',
            'default' => 'n',
            'keywords' => 'eponymous groups',
        ],
        'syncGroupsWithDirectory' => [
            'name' => tra('Synchronize Tiki groups with a directory'),
            'type' => 'flag',
            'hint' => tra('Define the directory within the "LDAP" tab'),
            'default' => 'n',
        ],
        'syncUsersWithDirectory' => [
            'name' => tra('Synchronize Tiki users with a directory'),
            'type' => 'flag',
            'hint' => tra('Define the directory within the "LDAP" tab'),
            'default' => 'n',
        ],
        'rememberme' => [
            'name' => tra('Remember me'),
            'description' => tra("After logging in, users will automatically be logged in again when they leave and return to the site."),
            'type' => 'list',
            'help' => 'Login-General-Preferences#Remember_Me',
            'options' => [
                'disabled' => tra('Disabled'),
                'all'      => tra("User's choice"),
                'always'   => tra('Always'),
            ],
            'default' => 'all',
            'tags' => ['basic'],
        ],
        'remembertime' => [
            'name' => tra('Duration'),
            'description' => tra('The length of time before the user will need to log in again.'),
            'type' => 'list',
            'options' => [
                '300'       => '5 ' . tra('minutes'),
                '900'       => '15 ' . tra('minutes'),
                '1800'      => '30 ' . tra('minutes'),
                '3600'      => '1 ' . tra('hour'),
                '7200'      => '2 ' . tra('hours'),
                '14400'     => '4 ' . tra('hours'),
                '21600'     => '6 ' . tra('hours'),
                '28800'     => '8 ' . tra('hours'),
                '36000'     => '10 ' . tra('hours'),
                '72000'     => '20 ' . tra('hours'),
                '86400'     => '1 ' . tra('day'),
                '604800'    => '1 ' . tra('week'),
                '2629743'   => '1 ' . tra('month'),
                '31556926'  => '1 ' . tra('year'),
            ],
            'default' => 2629743,
            'tags' => ['basic'],
        ],
        'urlIndexBrowserTitle' => [
            'name' => tra('Homepage Browser title'),
            'description' => tra('Customize Browser title for the custom homepage'),
            'type' => 'text',
            'size' => 50,
            'default' => tra('Homepage'),
            'tags' => ['basic'],
            'dependencies' => [
                'useUrlIndex',
            ],
        ],
        'urlIndex' => [
            'name' => tra('Homepage URL'),
            'type' => 'text',
            'size' => 50,
            'default' => '',
            'tags' => ['basic'],
            'dependencies' => [
                'useUrlIndex',
            ],
        ],
        'useUrlIndex' => [
            'name' => tra('Use custom homepage'),
            'description' => tra('Use the top page of a Tiki feature or another homepage'),
            'warning' => tra('This option will override the Use Tiki feature as homepage setting.'),
            'type' => 'flag',
            'default' => 'n',
            'tags' => ['basic'],
        ],
        'tikiIndex' => [
            'name' => tra('Use the top page of a Tiki feature as the homepage'),
            'description' => tra('Select the Tiki feature to provide the site homepage. Only enabled features are listed.'),
            'type' => 'list',
            'options' => feature_home_pages($partial),
            'default' => 'tiki-index.php',
            'tags' => ['basic'],
        ],
        'maxRowsGalleries' => [
            'name' => tra('Maximum rows per page'),
            'type' => 'text',
            'units' => tra('rows'),
            'default' => '10',
        ],
        'rowImagesGalleries' => [
            'name' => tra('Images per row'),
            'type' => 'text',
            'units' => tra('images'),
            'default' => '6',
        ],
        'thumbSizeXGalleries' => [
            'name' => tra('Thumbnail width'),
            'type' => 'text',
            'units' => tra('pixels'),
            'default' => '80',
        ],
        'thumbSizeYGalleries' => [
            'name' => tra('Thumbnail height'),
            'type' => 'text',
            'units' => tra('pixels'),
            'default' => '80',
        ],
        'scaleSizeGalleries' => [
            'name' => tra('Default scale size'),
            'type' => 'text',
            'units' => tra('pixels'),
            'default' => '',
        ],
        'maintenanceMessageReindex' => [
            'name' => tra('Display maintenance message to users during search re-index'),
            'type' => 'flag',
            'default' => 'n',
            'description' => tra('If enabled, a message will be displayed to users during search re-index.'),
        ],
        'maintenanceReindexMessage' => [
            'name' => tra('Search re-index message'),
            'type' => 'text',
            'default' => tra('The search index is currently rebuilding. You can continue using the site normally, but please be aware that it could be slower than usual.'),
            'size' => 300,
            'description' => tra('The message displayed during search re-indexing. You can customize this message if needed.'),
        ],
        'maintenanceTimeBeforeDisplayMessage' => [
            'name' => tra('Time before maintenance to display the message (minutes)'),
            'description' => tra('The time will be used for display warning message before start maintenance.'),
            'type' => 'text',
            'size' => 3,
            'default' => '60',
            'tags' => ['advanced'],
        ],
        'maintenanceRecurrentEnable' => [
            'name' => tra('Recurrent Maintaine Enabled'),
            'description' => tra('Enable notification during maintenance.'),
            'type' => 'flag',
            'default' => 'n',
            'tags' => ['advanced'],
        ],
        'maintenanceRecurrentPreMessage' => [
            'name' => tra('Before Maintenance Message'),
            'description' => tra('Message to display before maintenance starts. Use TIME for minutes until maintenance and DOWN for duration.'),
            'type' => 'text',
            'size' => 300,
            'default' => tra('This website will be under maintenance in TIME minutes and will be unavailable for DOWN minutes.'),
            'tags' => ['advanced'],
        ],
        'maintenanceRecurrentDuringMessage' => [
            'name' => tra('During Maintenance Message'),
            'description' => tra('Message to display during maintenance. Use DOWN for remaining minutes of downtime.'),
            'type' => 'text',
            'size' => 300,
            'default' => tra('This website is under maintenance and will be back in DOWN minutes.'),
            'tags' => ['advanced'],
        ],
        'maintenanceRecurrentStartTime' => [
            'name' => tra('Start Time'),
            'description' => tra('It is for set when the maintenance will be started (24H format hh:mm)'),
            'help' => 'Date-and-Time#Date_and_Time_Formats',
            'type' => 'text',
            'size' => '30',
            'default' => '%H:%M',
            'tags' => ['advanced'],
        ],
        'maintenanceRecurrentDuration' => [
            'name' => tra('Duration (Minutes)'),
            'description' => tra('Period to display message while maintenance.'),
            'type' => 'text',
            'size' => 3,
            'default' => '60',
            'tags' => ['advanced'],
        ],
        'maintenanceEnableWeekdays' => [
            'name' => tra('Enable notifitication during maintenance on weekdays'),
            'type' => 'multilist',
            'options' => [
                0 => tra('Sunday'),
                1 => tra('Monday'),
                2 => tra('Tuesday'),
                3 => tra('Wednesday'),
                4 => tra('Thursday'),
                5 => tra('Friday'),
                6 => tra('Saturday'),
            ],
            'default' => [0,1,2,3,4,5,6],
            'tags' => ['advanced'],
        ],
        'maintenanceOnceOffEnable' => [
            'name' => tra('Once off notification maintenance enabled'),
            'description' => tra('Enable notifitication once off maintenance.'),
            'type' => 'flag',
            'default' => 'n',
            'tags' => ['advanced'],
        ],
        'maintenanceOnceOffPreMessage' => [
            'name' => tra('Before Once-Off Maintenance Message'),
            'description' => tra('Message to display before once-off maintenance starts. Use TIME for minutes until maintenance and DOWN for duration.'),
            'type' => 'text',
            'size' => 300,
            'default' => tra('This website will be under once-off maintenance in TIME minutes and will be unavailable for DOWN minutes.'),
            'tags' => ['advanced'],
        ],
        'maintenanceOnceOffDuringMessage' => [
            'name' => tra('During Once-Off Maintenance Message'),
            'description' => tra('Message to display during once-off maintenance. Use DOWN for remaining minutes of downtime.'),
            'type' => 'text',
            'size' => 300,
            'default' => tra('This website is under once-off maintenance and will be back in DOWN minutes.'),
            'tags' => ['advanced'],
        ],
        'maintenanceOnceOffStartDate' => [
            'name' => tra('Start Date'),
            'description' => tra('It is for set date the maintenance will be off'),
            'help' => 'Date-and-Time#Date_and_Time_Formats',
            'type' => 'text',
            'size' => '30',
            'default' => '%Y-%m-%d',
            'tags' => ['advanced'],
        ],
        'maintenanceOnceOffStartTime' => [
            'name' => tra('Start Time'),
            'description' => tra('It is for set when the maintenance will be off (24H format hh:mm)'),
            'help' => 'Date-and-Time#Date_and_Time_Formats',
            'type' => 'text',
            'size' => '30',
            'default' => '%H:%M',
            'tags' => ['advanced'],
        ],
        'maintenanceOnceOffDuration' => [
            'name' => tra('Duration (Minutes)'),
            'description' => tra('Period to display message while maintenance once off.'),
            'type' => 'text',
            'size' => 3,
            'default' => '60',
            'tags' => ['advanced'],
        ],
    ];
}

/**
 *  Computes the alternate homes for each feature
 *   (used in admin general template)
 *
 * @param $partial bool
 *
 * @return array of url's and labels of the alternate homepages
 * @throws Exception
 * @access public
 */
function feature_home_pages($partial = false)
{
    global $prefs;
    $tikilib = TikiLib::lib('tiki');
    $tikiIndex = [];

    //wiki
    $tikiIndex['tiki-index.php'] = tra('Wiki');

    // Articles
    if (! $partial && $prefs['feature_articles'] == 'y') {
        $tikiIndex['tiki-view_articles.php'] = tra('Articles');
    }
    // Blog
    if (! $partial && $prefs['feature_blogs'] == 'y') {
        if ($prefs['home_blog'] != '0') {
            $bloglib = TikiLib::lib('blog');
            $hbloginfo = $bloglib->get_blog($prefs['home_blog']);
            $home_blog_name = substr($hbloginfo['title'] ?? '', 0, 20);
        } else {
            $home_blog_name = tra('Set blogs homepage first');
        }
        $tikiIndex['tiki-view_blog.php?blogId=' . $prefs['home_blog']] = tra('Blog:') . $home_blog_name;
    }

    // File gallery
    if (! $partial && $prefs['feature_file_galleries'] == 'y') {
        $filegallib = TikiLib::lib('filegal');
        $hgalinfo = $filegallib->get_file_gallery($prefs['home_file_gallery']);
        if ($hgalinfo) {
            $home_gal_name = substr($hgalinfo["name"], 0, 20);
            $tikiIndex['tiki-list_file_gallery.php?galleryId=' . $prefs['home_file_gallery']] = tra('File Gallery:') . $home_gal_name;
        }
    }

    // Forum
    if (! $partial && $prefs['feature_forums'] == 'y') {
        if ($prefs['home_forum'] != '0') {
            $hforuminfo = TikiLib::lib('comments')->get_forum($prefs['home_forum']);
            $home_forum_name = substr($hforuminfo['name'], 0, 20);
        } else {
            $home_forum_name = tra('Set Forum homepage first');
        }
        $tikiIndex['tiki-view_forum.php?forumId=' . $prefs['home_forum']] = tra('Forum:') . $home_forum_name;
    }

    return $tikiIndex;
}
