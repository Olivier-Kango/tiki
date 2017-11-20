<?php
// (c) Copyright 2002-2016 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_wiki_list($partial = false)
{

	global $prefs;
	$wiki_forums = [];

	if (! $partial && $prefs['feature_forums'] == 'y') {
		$all_forums = TikiDb::get()->fetchMap('SELECT `forumId`, `name` FROM `tiki_forums` ORDER BY `name` ASC');

		if (count($all_forums)) {
			$wiki_forums = $all_forums;
		} else {
			$wiki_forums[''] = tra('None');
		}
	}

	$prefslib = TikiLib::lib('prefs');
	$advanced_columns = $prefslib->getExtraSortColumns();

	$wiki_sort_columns = array_merge(
		[
			'lastModif' => tra('Last Modified'),
			'pageName' => tra('Name'),
			'created' => tra('Created'),
			'creator' => tra('Creator'),
			'hits' => tra('Hits'),
			'user' => tra('Last editor'),
			'page_size' => tra('Size'),
		],
		$advanced_columns
	);

	$comment_sort_orders = [
		'commentDate_desc' => tra('Newest first'),
		'commentDate_asc' => tra('Oldest first'),
		'points_desc' => tra('Points'),
	];
	$truncationHint = 'If the text is longer, it will be truncated after that length. Special value "0" disables truncation.';

	foreach ($advanced_columns as $key => $label) {
		$comment_sort_orders[ $key . '_asc' ] = $label . ' ' . tr('ascending');
		$comment_sort_orders[ $key . '_desc' ] = $label . ' ' . tr('descending');
	}

	return [
		'wiki_page_regex' => [
			'name' => tra('Wiki link format'),
			'description' => tra('Level of special characters acceptable in wiki links for page names. For example: ((Page &eacute;&agrave;&icirc;))'),
			'detail' => tra('"Strict" includes only basic characters such as a-z and 0-9. A site that uses accented or special characters in page names should not use "strict".'),
			'type' => 'list',
			'options' => [
				'complete' => tra('Complete'),
				'full' => tra('Relaxed'),
				'strict' => tra('Strict'),
			],
			'default' => 'complete',
		],
		'wiki_url_scheme' => [
			'name' => tr('Wiki URL scheme'),
			'description' => tr('Alter the SEFURL pattern for page names.'),
			'hint' => tr('Use the "View" action to regenerate your URLs after changing this setting.'),
			'type' => 'list',
			'default' => 'dash',
			'options' => TikiLib::lib('slugmanager')->getOptions(),
			'view' => $partial ? '' : TikiLib::lib('service')->getUrl([
				'controller' => 'wiki',
				'action' => 'regenerate_slugs',
			]),
			'keywords' => 'slug manager',
		],
		'wiki_show_version' => [
			'name' => tra('Display page version'),
			'description' => tra('Display the page version information when viewing the page.'),
			'type' => 'flag',
			'default' => 'n',
		],
		'wiki_page_name_above' => [
			'name' => tra('Display page name above page'),
			'description' => tra('Tiki will display the page name in small text at the top of each page (above the page’s content).'),
			'type' => 'flag',
			'default' => 'n',
		],
		'wiki_page_name_inside' => [
			'name' => tra('Display page name inside page'),
			'description' => tra('Display page name inside page content.'),
			'type' => 'flag',
			'default' => 'n',
		],
		'wiki_pagename_strip' => [
			'name' => tra('Page name display stripper'),
			'description' => tra('Character to use as a delimiter in the page name. The portion of the name after this character will not be displayed. If the page name display stripper conflicts with the namespace separator, the namespace is used and the page name display is not stripped'),
			'type' => 'text',
			'size' => 5,
			'default' => '',
		],
		'wiki_authors_style' => [
			'name' => tra('Wiki author list style'),
			'description' => tra('Changes the list format used to display the authors of the page.'),
			'type' => 'list',
			'options' => [
				'classic' => tra('Creator and Author'),
				'business' => tra('Business style'),
				'collaborative' => tra('Collaborative style'),
				'lastmodif' => tra('Page last modified on'),
				'none' => tra('none (disabled)'),
			],
			'default' => 'none',
			'tags' => ['basic'],
		],
		'wiki_authors_style_by_page' => [
			'name' => tra('Specify wiki author list style per page'),
			'description' => tra('Allows the style in which the author list is displayed to be modified on a per-page basis.'),
			'type' => 'flag',
			'default' => 'n',
		],
		'wiki_actions_bar' => [
			'name' => tra('Wiki action bar location'),
			'description' => tra('Specify the location of the edit buttons (such as Save, Preview, and Cancel) when editing wiki pages.'),
			'type' => 'list',
			'options' => [
				'top' => tra('Top'),
				'bottom' => tra('Bottom'),
				'both' => tra('Both'),
			],
			'default' => 'bottom',
		],
		'wiki_page_navigation_bar' => [
			'name' => tra('Wiki navigation bar location'),
			'description' => tra('When using the ...page... page break wiki syntax'),
			'type' => 'list',
			'options' => [
				'top' => tra('Top'),
				'bottom' => tra('Bottom'),
				'both' => tra('Both'),
			],
			'default' => 'bottom',
		],
		'wiki_topline_position' => [
			'name' => tra('Wiki top line location'),
			'description' => tra('Page description, page actions, icons, backlinks....'),
			'type' => 'list',
			'options' => [
				'top' => tra('Top'),
				'bottom' => tra('Bottom'),
				'both' => tra('Both'),
				'none' => tra('Neither'),
			],
			'default' => 'top',
		],
		'wiki_sharethis_encourage' => [
			'name' => tra('Display ShareThis icon prominently'),
			'description' => tra('Encourage sharing by showing the ShareThis icon (default is hiding icon in drop-down)'),
			'type' => 'flag',
			'default' => 'n',
		],
		'wiki_cache' => [
			'name' => tra('Cache wiki pages (global)'),
			'description' => tra('Enable page cache globally for wiki pages.'),
			'warning' => tra("Wiki cache reduces server load but can cause some empty pages and other issues when using wiki plugins. Use only if necessary; it may be better to use an individual wiki cache for only the pages that require it."),
			'type' => 'list',
			'options' => [
				0 => tra('no cache'),
				60 => '1 ' . tra('minute'),
				300 => '5 ' . tra('minutes'),
				600 => '10 ' . tra('minutes'),
				900 => '15 ' . tra('minutes'),
				1800 => '30 ' . tra('minutes'),
				3600 => '1 ' . tra('hour'),
				7200 => '2 ' . tra('hours'),
			],
			'default' => 0,
		],
		'wiki_comments_allow_per_page' => [
			'name' => tra('Allow comments per wiki page'),
			'description' => tra('Enable control for comments on wiki pages individually.'),
			'type' => 'list',
			'options' => [
				'n' => tra('Disable'),
				'y' => tra('Enable (default On)'),
				'o' => tra('Enable (default Off)'),
			],
			'default' => 'n',
		],
		'wiki_feature_copyrights' => [
			'name' => tra('Wiki copyright'),
			'type' => 'flag',
			'dependencies' => [
				'feature_wiki',
			],
			'default' => 'n',
		],
		'wiki_badchar_prevent' => [
			'name' => tra('Prevent special characters in page names'),
			'description' => tra('Some characters may prevent the pages from being easily accessible from the URL or through wiki links. This option prevents from creating pages with such characters.'),
			'type' => 'flag',
			'default' => 'y',
			'tags' => ['basic'],
		],
		'wiki_ranking_reload_probability' => [
			'name' => tra('Page ranking reload probability'),
			'description' => tra('Calculation of page rankings is a resource-intensive task that cannot be performed at every page load. It will be calculated at random page loads based on a dice roll. This option indicates the number of faces on the dice. Larger numbers lead to reduced resource use, but less-accurate ranking.'),
			'type' => 'text',
			'size' => 7,
			'filter' => 'digits',
			'default' => 1000,
		],
		'wiki_encourage_contribution' => [
			'name' => tra('Encourage contribution to wiki pages by anonymous'),
			'description' => tra('When a page is not editable and the user is anonymous, display the edit links anyway. The visitor will be prompted with a login screen and be encouraged to register.'),
			'type' => 'flag',
			'default' => 'n',
		],
		'wiki_timeout_warning' => [
			'name' => tra('Warn before page lock timeout'),
			'description' => tra('Provide a JavaScript alert before the user\'s lock on a page times out.'),
			'type' => 'flag',
			'default' => 'y',
			'tags' => ['basic'],
		],
		'wiki_plugindiv_approvable' => [
			'name' => tra('DIV plugin accepts unsafe parameters such as "style"'),
			'description' => tra('If set, more parameters are available but modifying them will require approval. If unset, DIV plugin is safe and never requires approval.'),
			'hint' => tra('If changed, you need to clear caches.'),
			'type' => 'flag',
			'default' => 'n',
			'tags' => ['advanced'],
		],
		'wiki_dynvar_style' => [
			'name' => tra('Dynamic variables'),
			'description' => tra('Global snippets of text that can be included in wiki pages and edited in place.'),
			'type' => 'list',
			'help' => 'Dynamic+Variable',
			'options' => [
				'disable' => tra('Disabled'),
				'single' => tra('Single (%varname%)'),
				'double' => tra('Double (%%varname%%)'),
			],
			'default' => 'single',
		],
		'wiki_dynvar_multilingual' => [
			'name' => tra('Multilingual dynamic variables'),
			'description' => tra('Make dynamic variable content language-specific.'),
			'type' => 'flag',
			'dependencies' => [
				'feature_multilingual',
			],
			'default' => 'n',
		],
		'wiki_edit_plugin' => [
			'name' => tra('Allow plugin-specific edits'),
			'description' =>
				tra('Adds an icon next to each plugin usage which enables to edit that plugin call via a popup form, without needing to edit its whole container.')
				. ' ' . tra('This feature is unavailable outside wiki pages, whether or not this preference is enabled.') // This may be by design, or just because it was faster to implement.
				,
			'type' => 'flag',
			'default' => 'y',
		],
		'wiki_edit_section' => [
			'name' => tra('Allow section-specific edits'),
			'description' => tra('Adds an edit icon above each section (up to a configurable maximum level) to accelerate modifications of a single section.'),
			'type' => 'flag',
			'default' => 'y',
			'tags' => ['basic'],
		],
		'wiki_edit_section_level' => [
			'name' => tra('Section-specific edits maximum level'),
			'description' => tra('Allows preventing a proliferation of edit icons for small sections. For example, when set to 2, no edit icons will show for third-level sections.'),
			'type' => 'list',
			'options' => [
				'0' => tra('All'),
				'1' => tra('1'),
				'2' => tra('2'),
				'3' => tra('3'),
				'4' => tra('4'),
				'5' => tra('5'),
				'6' => tra('6'),
			],
			'default' => '0',
		],
		'wiki_edit_icons_toggle' => [
			'name' => tra('Only display focused edit icons (for sections and/or plugins) on request'),
			'description' => tra('If enabled, focused edit icons will only display after a per-session "Edit icons" preference is enabled. This preference is defined directly from any wiki page.'),
			'type' => 'flag',
			'default' => 'y',
			'tags' => ['basic'],
		],
		'wiki_edit_minor' => [
			'name' => tra('Allow minor edits of wiki pages'),
			'type' => 'flag',
			'description' => tra('Minor edits do not flag new content for translation and do not send watch notifications (unless "Watch minor edits" is enabled).'),
			'permission' => [
				'textFilter' => 'tiki_p_minor',
			],
			'default' => 'n',
		],
		'wiki_comments_displayed_default' => [
			'name' => tra('Display comment list by default'),
			'type' => 'flag',
			'default' => 'n',
		],
		'wiki_comments_form_displayed_default' => [
			'name' => tra('Display Post new comment form by default'),
			'type' => 'flag',
			'default' => 'n',
		],
		'wiki_comments_per_page' => [
			'name' => tra('Default number per page'),
			'type' => 'text',
			'size' => '5',
			'units' => tra('comments'),
			'default' => 10,
		],
		'wiki_comments_default_ordering' => [
			'name' => tra('Default Ordering'),
			'type' => 'list',
			'options' => $comment_sort_orders,
			'default' => 'points_desc',
		],
		'wiki_comments_simple_ratings' => [
			'name' => tra('Simple wiki comment ratings'),
			'description' => tra('Enable users to rate comments based on a simple numeric scale.'),
			'type' => 'flag',
			'default' => 'n',
		],
		'wiki_comments_simple_ratings_options' => [
			'name' => tra('Wiki rating options'),
			'description' => tra('List of options available for the rating of wiki comments.'),
			'type' => 'text',
			'default' => "0,1,2,3,4,5",
		],
		'wiki_uses_slides' => [
			'name' => tra('Add a slideshow button on wiki pages'),
			'type' => 'flag',
			'help' => 'Slideshow',
			'default' => 'n',
			'tags' => ['basic'],
		],
		'wiki_creator_admin' => [
			'name' => tra('Page creators are administrators of their pages'),
			'type' => 'flag',
			'default' => 'n',
		],
		'wiki_watch_author' => [
			'name' => tra('Create watch for author on page creation'),
			'type' => 'flag',
			'default' => 'n',
			'dependencies' => [
				'feature_user_watches',
			],
		],
		'wiki_watch_comments' => [
			'name' => tra('Enable watches on comments'),
			'type' => 'flag',
			'default' => 'y',
			'dependencies' => [
				'feature_user_watches',
			],
		],
		'wiki_watch_editor' => [
			'name' => tra('Enable watch events when you are the editor'),
			'type' => 'flag',
			'default' => 'n',
			'dependencies' => [
				'feature_user_watches',
			],
		],
		'wiki_watch_minor' => [
			'name' => tra('Watch minor edits'),
			'type' => 'flag',
			'default' => 'n',
			'dependencies' => [
				'feature_user_watches',
			],
		],
		'wiki_list_id' => [
			'name' => tra('Page ID'),
			'type' => 'flag',
			'default' => 'n',
		],
		'wiki_list_name' => [
			'name' => tra('Name'),
			'type' => 'flag',
			'default' => 'y',
		],
		'wiki_list_name_len' => [
			'name' => tra('Page name maximum displayed length'),
			'hint' => tra($truncationHint),
			'type' => 'text',
			'size' => '3',
			'units' => tra('characters'),
			'filter' => 'digits',
			'default' => '40',
		],
		'wiki_list_hits' => [
			'name' => tra('Hits'),
			'type' => 'flag',
			'default' => 'y',
		],
		'wiki_list_lastmodif' => [
			'name' => tra('Last modification date'),
			'type' => 'flag',
			'default' => 'y',
		],
		'wiki_list_creator' => [
			'name' => tra('Creator'),
			'type' => 'flag',
			'default' => 'n',
		],
		'wiki_list_user' => [
			'name' => tra('Last modified by'),
			'type' => 'flag',
			'default' => 'y',
		],
		'wiki_list_lastver' => [
			'name' => tra('Version'),
			'type' => 'flag',
			'default' => 'n',
		],
		'wiki_list_comment' => [
			'name' => tra('Comment of the latest edit'),
			'type' => 'flag',
			'default' => 'y',
		],
		'wiki_list_comment_len' => [
			'name' => tra("Maximum displayed length of the latest edit's comment"),
			'hint' => tra($truncationHint),
			'type' => 'text',
			'size' => '3',
			'units' => tra('characters'),
			'default' => '200',
		],
		'wiki_list_description' => [
			'name' => tra('Description'),
			'type' => 'flag',
			'default' => 'y',
		],
		'wiki_list_description_len' => [
			'name' => tra('Description maximum displayed length'),
			'hint' => tra($truncationHint),
			'type' => 'text',
			'size' => '3',
			'units' => tra('characters'),
			'filter' => 'digits',
			'default' => '200',
		],
		'wiki_list_status' => [
			'name' => tra('Status'),
			'type' => 'flag',
			'default' => 'n',
		],
		'wiki_list_versions' => [
			'name' => tra('Versions'),
			'type' => 'flag',
			'default' => 'y',
		],
		'wiki_list_links' => [
			'name' => tra('Links'),
			'type' => 'flag',
			'default' => 'n',
		],
		'wiki_list_backlinks' => [
			'name' => tra('Backlinks'),
			'description' => tra('Allows users to view a list of pages, called backlinks, that link to the current page.'),
			'help' => 'Backlinks',
			'type' => 'flag',
			'default' => 'n',
		],
		'wiki_list_size' => [
			'name' => tra('Size'),
			'type' => 'flag',
			'default' => 'n',
		],
		'wiki_list_language' => [
			'name' => tra('Language'),
			'type' => 'flag',
			'dependencies' => [
				'feature_multilingual',
			],
			'default' => 'n',
		],
		'wiki_list_categories' => [
			'name' => tra('Categories'),
			'type' => 'flag',
			'dependencies' => [
				'feature_categories',
			],
			'default' => 'n',
		],
		'wiki_list_categories_path' => [
			'name' => tra('Categories path'),
			'type' => 'flag',
			'dependencies' => [
				'feature_categories',
			],
			'default' => 'n',
		],
		'wiki_list_sortorder' => [
			'name' => tra('Default sort order'),
			'type' => 'list',
			'options' => $wiki_sort_columns,
			'default' => 'lastModif',
		],
		'wiki_list_sortdirection' => [
			'name' => tra('Sort Direction'),
			'type' => 'radio',
			'options' => [
				'desc' => tra('Descending'),
				'asc' => tra('Ascending'),
			],
			'default' => 'desc',
		],
		'wiki_list_rating' => [
			'name' => tra('Rating'),
			'type' => 'flag',
			'dependencies' => [
				'feature_polls',
				'feature_wiki_ratings',
			],
			'default' => 'n',
			'tags' => ['experimental'] // Column content is most unclear. Assumes poll option labels are numbers. Chealer 2017-05-22
		],
		'wiki_discuss_visibility' => [
			'name' => tra('Visibility of discussion'),
			'description' => tra('Just a button among others (default), or special section'),
			'type' => 'list',
			'options' => [
				'button' => tra('In the button bar (default)'),
				'above' => tra('Special section above button bar'),
			],
			'default' => 'button',
		],
		'wiki_forum_id' => [
			'name' => tra('Forum for discussion'),
			'type' => 'list',
			'options' => $wiki_forums,
			'default' => '',
		],
		'wiki_keywords' => [
			'name' => tra('Keywords'),
			'description' => tra('Allow management of keywords on a per-page basis.'),
			'type' => 'flag',
			'default' => 'n',
		],
		'wiki_likepages_samelang_only' => [
			'name' => tra('Similar pages only listed in same language'),
			'description' => tra('When listing similar pages, such as in missing page 404, only display pages in the same language as the request.'),
			'type' => 'flag',
			'dependencies' => [ 'feature_multilingual' ],
			'default' => 'n',
		],
		'wiki_mandatory_edit_summary' => [
			'name' => tra('Mandatory wiki page edit summary'),
			'description' => tra('Reject save attempts that do not include an edit summary describing the changes made.'),
			'type' => 'flag',
			'default' => 'n',
		],
		'wiki_freetags_edit_position' => [
			'name' => tra('Position of tags selection'),
			'description' => tra('To place tags selection more prominently than in the properties tab.'),
			'type' => 'list',
			'options' => [
				'properties' => tra('Properties tab'),
				'edit' => tra('Edit tab'),
				'freetagstab' => tra('Tags tab'),
			],
			'default' => 'properties',
		],
		'wiki_structure_bar_position' => [
			'name' => tra('Structure navigation bar location'),
			'description' => tra('Displays Next/Back buttons, breadcrumbs, and form to add a new page.'),
			'type' => 'list',
			'options' => [
				'top' => tra('Top'),
				'bottom' => tra('Bottom'),
				'both' => tra('Both'),
				'none' => tra('Neither'),
			],
			'default' => 'top',
		],
		'wiki_backlinks_name_len' => [
			'name' => tra('Backlink page names maximum displayed length'),
			'hint' => tra($truncationHint),
			'type' => 'text',
			'units' => tra('characters'),
			'size' => '3',
			'filter' => 'digits',
			'dependencies' => [ 'feature_backlinks' ],
			'default' => 0,
			'units' => tra('characters'),
		],
		'wiki_simple_ratings' => [
			'name' => tra('Simple wiki ratings'),
			'description' => tra('Enable users to rate wiki pages based on a simple numeric scale.'),
			'type' => 'flag',
			'default' => 'n',
			'tags' => ['experimental'] // Confusion with "Rating" (non-simple), results display seems broken in 15.x and trunk as of 2017-05-22 (probably the bug reported in ticket #5971). Poor configuration, fails to validate options. No documentation. Chealer
		],
		'wiki_simple_ratings_options' => [
			'name' => tra('Wiki rating options'),
			'description' => tra('List of options available for the rating of wiki pages.'),
			'type' => 'text',
			'default' => "0,1,2,3,4,5",
		],
		'wiki_pagealias_tokens' => [
			'name' => tra('Semantic link types to use as page alias markers'),
			'description' => tra('Comma-separated list of semantic links to identify a page as an alias of another'),
			'type' => 'text',
			'dependencies' => ['feature_wiki_pagealias'],
			'default' => 'alias',
		],
		'wiki_prefixalias_tokens' => [
			'name' => tra('Redirect pages using these prefix-alias semantic links'),
			'description' => tra('Comma separated list of prefixes of which pages will be redirected to page with semantic link'),
			'type' => 'text',
			'help' => 'Semantic+Alias',
			'size' => '30',
			'dependencies' => [
				'feature_wiki_1like_redirection',
				'feature_semantic', // this is needed at point of creation of semantic link otherwise link will not register
			],
			'default' => '',
		],
		'wiki_pagination' => [
			'name' => tr('Wiki page pagination'),
			'description' => tr('Enables the sectioning of a wiki page\'s content into two or more paginated pages.'),
			'type' => 'flag',
			'default' => 'n',
		],
		'wiki_page_separator' => [
			'name' => tr('Wiki page separator'),
			'description' => tr('Separator to use in the content of a wiki page to divide the content into multiple pages.'),
			'type' => 'text',
			'default' => '...page...',
		],
		'wiki_auto_toc' => [
			'name' => tr('Wiki auto-toc'),
			'description' => tr('Automatic table of contents generation for wiki pages. The generated table of contents will display as a fixed-position aside next to the page contents. This setting can be toggled per page, in the page properties.'),
			'type' => 'flag',
			'help' => 'Auto TOC',
			'default' => 'n',
		],
		'wiki_inline_auto_toc' => [
			'name' => tr('Inline auto-toc'),
			'description' => tr('Change the display of the table of contents for wiki pages to inline.'),
			'type' => 'flag',
			'default' => 'n',
			'dependencies' => [
				'wiki_auto_toc',
			],
		],
		'wiki_toc_pos' => [
			'name' => tr('Table of contents position'),
			'description' => tr('Position for table of contents. One of top, left, right (right is the default)'),
			'type' => 'list',
			'options' => [
				'right' => tra('Right'),
				'left' => tra('Left'),
				'top' => tra('Top'),
			],
			'default' => 'right',
			'dependencies' => [
				'wiki_auto_toc',
			],
		],
		'wiki_toc_offset' => [
			'name' => tr('Table of contents offset'),
			'description' => tr('Offset for table of contents. Useful when there is a fixed navigation bar. If used with inline TOC, will behave as a top margin.'),
			'type' => 'text',
			'filter' => 'digits',
			'units' => tra('pixels'),
			'default' => '10',
			'dependencies' => [
				'wiki_auto_toc',
			],
		],
		'wiki_page_hide_title' => [
			'name' => tr('Hide title per wiki page'),
			'description' => tr('Allow the title to be hidden for individual wiki pages'),
			'type' => 'flag',
			'default' => 'y',
			'dependencies' => [],
		],
		'wiki_heading_links' => [
			'name' => tr('Anchor links on headings'),
			'description' => tr('Add links that appear on hover for each heading, useful for sharing the URL to an exact location on a page'),
			'keywords' => 'Display hidden anchor on mouseover of headings',
			'type' => 'flag',
			'default' => 'y',
			'dependencies' => [],
		],
		'wiki_make_ordered_list_items_display_unique_numbers' => [
			'name' => tr('Uniquely-numbered ordered list items'),
			'description' => tr('Make ordered list items display unique numbers for sub-items; for example 1.1 and 1.2 instead of just repeating 1 and 2 again'),
			'keywords' => 'unique ordered lists numbered items syntax',
			'help' => 'Wiki-Syntax-Lists#Creating_a_Numbered_List',
			'type' => 'flag',
			'default' => 'n',
		],
	];
}
