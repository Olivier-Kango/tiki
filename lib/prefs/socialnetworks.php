<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_socialnetworks_list()
{
    return [
        'socialnetworks_twitter_consumer_key' => [
            'name' => tra('Consumer key'),
            'description' => tra('Consumer key generated by registering this Tiki site as an application at Twitter'),
            'type' => 'text',
            'keywords' => 'social networks',
            'size' => 40,
            'default' => '',
        ],
        'socialnetworks_twitter_consumer_secret' => [
            'name' => tra('Consumer secret'),
            'description' => tra('Consumer secret generated by registering this Tiki site as an application at Twitter.'),
            'keywords' => 'social networks',
            'type' => 'text',
            'size' => 60,
            'default' => '',
        ],
        'socialnetworks_twitter_site_name' => [
            'name' => tra('Twitter site name'),
            'description' => tra('The default website name that will be used by Twitter (twitter:site) for every web page. This parameter will be used instead of the browser title.'),
            'keywords' => 'social networks',
            'type' => 'text',
            'size' => 60,
            'default' => '',
        ],
        'socialnetworks_twitter_site_image' => [
            'name' => tra('Twitter site image'),
            'description' => tra('The default image (logo, picture, etc) that will be used by Twitter (twitter:image) for every web page. The image must be specified as a URL.'),
            'keywords' => 'social networks',
            'type' => 'text',
            'size' => 60,
            'default' => '',
        ],
        'socialnetworks_facebook_application_secr' => [
            'name' => tra('Application secret'),
            'description' => tra('Application secret generated by registering this Tiki site as an application at Facebook'),
            'keywords' => 'social networks',
            'type' => 'text',
            'size' => 60,
            'default' => '',
        ],
        'socialnetworks_facebook_application_id' => [
            'name' => tra('Application ID'),
            'description' => tra('Application ID generated by registering this Tiki site as an application at Facebook'),
            'keywords' => 'social networks',
            'type' => 'text',
            'size' => 60,
            'default' => '',
        ],
        'socialnetworks_facebook_site_name' => [
            'name' => tra('Facebook site name'),
            'description' => tra('The default website name that will be used by Facebook (og:site_name) for every webpage. This parameter will be used instead of the browser title.'),
            'keywords' => 'social networks',
            'type' => 'text',
            'size' => 60,
            'default' => '',
        ],
        'socialnetworks_facebook_site_image' => [
            'name' => tra('Facebook site image'),
            'description' => tra('The default image (logo, picture, etc.) that will be used by Facebook (og:image) for every webpage. It must be specified as a URL. The minimum valid image size is 200x200px. However, Facebook recommends 1200x630px or larger for the best display on high resolution devices.'),
            'keywords' => 'social networks',
            'type' => 'text',
            'size' => 60,
            'default' => '',
        ],
        'socialnetworks_facebook_login' => [
            'name' => tra('Login using Facebook'),
            'description' => tra('Allow users to log in using Facebook'),
            'keywords' => 'social networks',
            'type' => 'flag',
            'default' => 'n',
        ],
        'socialnetworks_facebook_autocreateuser' => [
            'name' => tra('Auto-create Tiki user'),
            'description' => tra('Automatically create a Tiki user by the username of fb_xxxxxxxx for users logging in using Facebook if they do not yet have a Tiki account. If not, they will be asked to link or register a Tiki account'),
            'keywords' => 'social networks',
            'type' => 'flag',
            'dependencies' => [
                'socialnetworks_facebook_login',
            ],
            'default' => 'n',
        ],
        'socialnetworks_facebook_firstloginpopup' => [
            'name' => tra('Require Facebook users to enter local account info on creation'),
            'description' => tra('Require Facebook users to enter local account info, specifically email and local log-in name'),
            'keywords' => 'social networks',
            'type' => 'flag',
            'dependencies' => [
                'socialnetworks_facebook_login',
                'socialnetworks_facebook_autocreateuser',
            ],
            'default' => 'n',
        ],
        'socialnetworks_facebook_publish_stream' => [
            'name' => tra('Tiki can post to the Facebook wall'),
            'description' => tra('Tiki may post status messages, notes, photos, and videos to the Facebook Wall.'),
            'keywords' => 'social networks',
            'type' => 'flag',
            'default' => 'n',
        ],
        'socialnetworks_facebook_manage_events' => [
            'name' => tra('Tiki can manage events'),
            'description' => tra('Tiki may create and RSVP to Facebook events.'),
            'keywords' => 'social networks',
            'type' => 'flag',
            'default' => 'n',
        ],
        'socialnetworks_facebook_manage_pages' => [
            'name' => tra('Tiki can manage pages'),
            'description' => tra('Tiki can manage user pages.'),
            'keywords' => 'social networks',
            'type' => 'flag',
            'default' => 'n',
        ],
        'socialnetworks_facebook_sms' => [
            'name' => tra('Tiki can SMS'),
            'description' => tra('Tiki can use SMS functions via Facebook.'),
            'keywords' => 'social networks',
            'type' => 'flag',
            'default' => 'n',
        ],
        'socialnetworks_facebook_email' => [
            'name' => tra('Set user email from Facebook on creation.'),
            'description' => tra("Tiki will set the user's email from Facebook on creation."),
            'keywords' => 'social networks',
            'dependencies' => [
                'socialnetworks_facebook_autocreateuser',
            ],
            'type' => 'flag',
            'default' => 'n',
        ],
        'socialnetworks_facebook_create_user_trackeritem' => [
            'name' => tra('Create a user tracker item on registration'),
            'description' => tra("Sets whether a tracker item should be created for the user upon registration"),
            'keywords' => 'social networks',
            'type' => 'flag',
            'default' => 'n',
            'dependencies' => [
                'userTracker',
                'socialnetworks_facebook_autocreateuser',
            ],
        ],
        'socialnetworks_facebook_names' => [
            'name' => tra('First and last name tracker field IDs to set on creation'),
            'description' => tra("Comma-separated, with first name field followed by last name field; for example, '2,3'"),
            'keywords' => 'social networks',
            'type' => 'text',
            'default' => 'n',
            'dependencies' => [
                'userTracker',
                'socialnetworks_facebook_create_user_trackeritem',
            ],
        ],
        'socialnetworks_bitly_login' => [
            'name' => tra('bit.ly login'),
            'description' => tra('Site-wide log-in name (username) for bit.ly'),
            'keywords' => 'social networks',
            'type' => 'text',
            'size' => 60,
            'default' => '',
        ],
        'socialnetworks_bitly_key' => [
            'name' => tra('bit.ly key'),
            'description' => tra('Site-wide API key for bit.ly'),
            'keywords' => 'social networks',
            'type' => 'text',
            'size' => 60,
            'default' => '',
        ],
        'socialnetworks_bitly_sitewide' => [
            'name' => tra('Use site-wide account'),
            'description' => tra('When set to "yes", only the site-wide account will be used for all users.'),
            'keywords' => 'social networks',
            'type' => 'flag',
            'default' => 'n',
        ],
        'socialnetworks_linkedin_client_id' => [
            'name' => tra('Client ID'),
            'description' => tra('Client ID generated by registering this site as an application at LinkedIn'),
            'keywords' => 'social networks',
            'type' => 'text',
            'size' => 60,
            'default' => '',
        ],
        'socialnetworks_linkedin_client_secr' => [
            'name' => tra('Client secret'),
            'description' => tra('Client Secret generated by registering this site as an application at LinkedIn'),
            'keywords' => 'social networks',
            'type' => 'text',
            'size' => 60,
            'default' => '',
        ],
        'socialnetworks_linkedin_login' => [
            'name' => tra('Log in using LinkedIn'),
            'description' => tra('Allow users to log in using LinkedIn.'),
            'keywords' => 'social networks',
            'type' => 'flag',
            'default' => 'n',
        ],
        'socialnetworks_linkedin_autocreateuser' => [
            'name' => tra('Auto-create Tiki user from LinkedIn'),
            'description' => tra('Automatically create a Tiki user by the username of li_xxxxxxxx for users logging in using LinkedIn if they do not yet have a Tiki account. If not, they will be asked to link or register a Tiki account'),
            'keywords' => 'social networks',
            'type' => 'flag',
            'dependencies' => [
                'socialnetworks_linkedin_login',
            ],
            'default' => 'n',
        ],
        'socialnetworks_linkedin_email' => [
            'name' => tra('Set user email from LinkedIn on creation.'),
            'description' => tra("Tiki will set the user's email from LinkedIn on creation."),
            'keywords' => 'social networks',
            'type' => 'flag',
            'dependencies' => [
                'socialnetworks_linkedin_autocreateuser',
            ],
            'default' => 'n',
        ],
        'socialnetworks_linkedin_create_user_trackeritem' => [
            'name' => tra('Create a user tracker item on registration'),
            'description' => tra("Sets whether a tracker item should be created for the user upon registration"),
            'keywords' => 'social networks',
            'type' => 'flag',
            'default' => 'n',
            'dependencies' => [
                'userTracker',
                'socialnetworks_linkedin_autocreateuser',
            ],
        ],
        'socialnetworks_linkedin_names' => [
            'name' => tra('First and last name tracker field IDs to set on creation'),
            'description' => tra("Comma-separated, with first name field followed by last name field; for example, '2,3'"),
            'keywords' => 'social networks',
            'type' => 'text',
            'default' => '',
            'dependencies' => [
                'userTracker',
                'socialnetworks_linkedin_create_user_trackeritem',
            ],
        ],
    ];
}
