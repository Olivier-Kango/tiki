<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_bloglist_info()
{
    return [
        'name' => tra('Blog List'),
        'documentation' => 'PluginBlogList',
        'description' => tra('Display posts from a site blog'),
        'prefs' => [ 'feature_blogs', 'wikiplugin_bloglist' ],
        'iconname' => 'list',
        'introduced' => 1,
        'params' => [
            'Id' => [
                'required' => true,
                'name' => tra('Blog ID'),
                'description' => tra('Select one or more blogs to list posts from. Limitation: if more than one blog is selected, private posts (drafts) will not be shown.'),
                'filter' => 'striptags',
                'default' => '',
                'profile_reference' => 'blog',
                'separator' => ':',
                'since' => '1'
            ],
            'Items' => [
                'required' => false,
                'name' => tra('Maximum Items'),
                'description' => tra('Maximum number of entries to list (no maximum set by default)'),
                'filter' => 'digits',
                'default' => '',
                'since' => '3.0'
            ],
            'author' => [
                'required' => false,
                'name' => tra('Author'),
                'description' => tra('Only display posts created by this user (all posts listed by default)'),
                'default' => '',
                'profile_reference' => 'user',
                'since' => '3.5',
            ],
            'simpleList' => [
                'required' => false,
                'name' => tra('Simple List'),
                'description' => tra('Show simple list of date, title and author (default) or formatted list of blog
                    posts'),
                'default' => 'y',
                'since' => '3.5',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ],
            ],
            'charCount' => [
                'required' => false,
                'name' => tra('Character Count'),
                'description' => tra('Number of characters to display if not a simple list (defaults to all)'),
                'filter' => 'digits',
                'parentparam' => ['name' => 'simpleList', 'value' => 'n'],
                'default' => '',
                'since' => '12.0',
            ],
            'wordBoundary' => [
                'required' => false,
                'name' => tra('Word Boundary'),
                'description' => tra('If not a simple list and Character Count is non-zero, then marking this as yes will
                    break on word boundaries only.'),
                'default' => 'y',
                'since' => '12.0',
                'options' => [
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ],
                'parentparam' => ['name' => 'simpleList', 'value' => 'n'],
            ],
            'ellipsis' => [
                'required' => false,
                'name' => tra('Ellipsis'),
                'description' => tra('If not a simple list and Character Count is non-zero, then marking this as yes will
                    put ellipsis (...) at end of text (default).'),
                'default' => 'y',
                'since' => '12.0',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ],
                'parentparam' => ['name' => 'simpleList', 'value' => 'n'],
            ],
            'more' => [
                'required' => false,
                'name' => tra('More'),
                'description' => tra('If not a simple list and Character Count is non-zero, then marking this as yes
                    will put a "More" link to the full entry (default).'),
                'default' => 'y',
                'since' => '12.0',
                'options' => [
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ],
                'parentparam' => ['name' => 'simpleList', 'value' => 'n'],
            ],
            'showIcons' => [
                'required' => false,
                'name' => tra('Show Icons'),
                'description' => tra('If not a simple list, marking this as no will prevent the "edit" and "print" type
                    icons from displaying (default is to show the icons)'),
                'default' => 'y',
                'since' => '12.0',
                'options' => [
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ],
                'parentparam' => ['name' => 'simpleList', 'value' => 'n'],
            ],
            'useExcerpt' => [
                'required' => false,
                'name' => tra('Use Excerpt'),
                'description' => tra('If the blog has "Use post excerpt" enabled then use excerpts where available (default)'),
                'default' => 'y',
                'since' => '13.2',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ],
                'parentparam' => ['name' => 'simpleList', 'value' => 'n'],
            ],
            'dateStart' => [
                'required' => false,
                'name' => tra('Start Date'),
                'description' => tra('Earliest date to select posts from.') . ' (<code>YYYY-MM-DD</code>)',
                'filter' => 'date',
                'default' => '',
                'since' => '3.5',
            ],
            'dateEnd' => [
                'required' => false,
                'name' => tra('End Date'),
                'description' => tra('Latest date to select posts from.') . ' (<code>YYYY-MM-DD</code>)',
                'filter' => 'date',
                'default' => '',
                'since' => '3.5',
            ],
            'containerClass' => [
                'required' => false,
                'name' => tra('Container Class'),
                'description' => tr(
                    'CSS Class to add to the container %0DIV.article%1. (Default=%0wikiplugin_bloglist%1)',
                    '<code>',
                    '</code>'
                ),
                'filter' => 'text',
                'default' => 'wikiplugin_bloglist',
                'accepted' => tra('Valid CSS class'),
                'since' => '3.5',
            ],
        ],
    ];
}

function wikiplugin_bloglist($data, $params)
{
    global $user;
    $tikilib = TikiLib::lib('tiki');
    $smarty = TikiLib::lib('smarty');
    $params['Id'] = implode(':', $params['Id']);
    if (! isset($params['Id'])) {
        Feedback::error(tra('Missing blog ID for Bloglist plugin'));
        return '';
    }
    // Sanitize $params['Id'])
    $params['Id'] = preg_filter('/[^0-9:]*/', '', $params['Id']);

    if (! isset($params['Items'])) {
        $params['Items'] = -1;
    }
    if (! isset($params['offset'])) {
        $params['offset'] = 0;
    }
    if (! isset($params['sort_mode'])) {
        $params['sort_mode'] = 'created_desc';
    }
    if (! isset($params['find'])) {
        $params['find'] = '';
    }
    if (! isset($params['author'])) {
        $params['author'] = '';
    }
    if (! isset($params['simpleList'])) {
        $params['simpleList'] = 'y';
    }
    if (! isset($params['isHtml'])) {
        $params['isHtml'] = 'n';
    }
    if (! isset($params['useExcerpt'])) {
        $params['useExcerpt'] = 'y';
    }

    if (isset($params['dateStart'])) {
        $dateStartTS = strtotime($params['dateStart']);
    }
    if (isset($params['dateEnd'])) {
        $dateEndTS = strtotime($params['dateEnd']);
    }
    $dateStartTS = ! empty($dateStartTS) ? $dateStartTS : 0;
    $dateEndTS = ! empty($dateEndTS) ? $dateEndTS : $tikilib->now;

    if (! isset($params['containerClass'])) {
        $params['containerClass'] = 'wikiplugin_bloglist';
    }
    $smarty->assign('container_class', $params['containerClass']);

    if (! isset($params['wordBoundary'])) {
        $params['wordBoundary'] = '';
    }
    if (! isset($params['ellipsis'])) {
        $params['ellipsis'] = '';
    }
    if (! isset($params['more'])) {
        $params['more'] = '';
    }

    if ($params['simpleList'] == 'y') {
        $bloglib = TikiLib::lib('blog');
        $blogItems = $bloglib->list_posts($params['offset'], $params['Items'], $params['sort_mode'], $params['find'], $params['Id'], $params['author'], '', $dateStartTS, $dateEndTS);
        $smarty->assign_by_ref('blogItems', $blogItems['data']);
        $template = 'wiki-plugins/wikiplugin_bloglist.tpl';
    } else {
        $bloglib = TikiLib::lib('blog');

        $blogItems = $bloglib->list_blog_posts($params['Id'], false, $params['offset'], $params['Items'], $params['sort_mode'], $params['find'], $dateStartTS, $dateEndTS);

        if (isset($params['charCount']) && $params['charCount'] > 0) {
            $blogItems = $bloglib->mod_blog_posts($blogItems, $params['charCount'], $params['wordBoundary'], $params['ellipsis'], $params['more']);
        }

        $blog_data = TikiLib::lib('blog')->get_blog($params['Id']);
        $smarty->assign('blog_data', $blog_data);

        $smarty->assign('ownsblog', $user && ! empty($blog_data["user"]) && $user == $blog_data["user"] ? 'y' : 'n');

        if (isset($params['showIcons']) && $params['showIcons'] == 'n') {
            $smarty->assign('excerpt', 'y');
        }

        if ($params['useExcerpt'] === 'y' && ! empty($blog_data['use_excerpt']) && $blog_data['use_excerpt'] === 'y') {
            $smarty->assign('use_excerpt', 'y');
            $smarty->assign('excerpt', 'n');    // no real idea why this gets assigned depending on showIcons above but it prevents excerpts being shown
        }

        $smarty->assign('show_heading', 'n');
        $smarty->assign('use_author', 'y');
        $smarty->assign('add_date', 'y');
        $smarty->assign_by_ref('listpages', $blogItems['data']);
        $template = 'tiki-view_blog.tpl';
    }
    $ret = $smarty->fetch($template);
    return '~np~' . $ret . '~/np~';
}
