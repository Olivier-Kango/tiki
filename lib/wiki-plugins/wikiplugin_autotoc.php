<?php

function wikiplugin_autotoc_info()
{
	return [
		'name' => tra('Automatic Table of Contents'),
		'documentation' => 'PluginAutoToc',
		'description' => tra('Display a Table Of Contents in wiki pages'),
		'prefs' => [ 'wikiplugin_autotoc'],
		'iconname' => 'list-numbered',
		'introduced' => 22.0,
		'lateParse' => true,
		'params' => [
			'activity' => [
				'required' => true,
				'name' => tr('Activity'),
				'description' => tr('By default, the table of contents for the current page will be displayed.
					Alternate activity may be provided.'),
				'since' => '22.0',
				'filter' => 'alpha',
				'default' => 'yes',
				'options' => [
					['text' => tra('Yes'), 'value' => 'yes'],
					['text' => tra('No'), 'value' => 'no']
				]
			],
			'align' => [
				'required' => false,
				'name' => tra('Align'),
				'description' => tr('Position of the table of contents, either right, left, page ("right" is the default and "page" will display the table of contents where it is placed in the wiki page.).'),
				'since' => '22.0',
				'filter' => 'alpha',
				'default' => 'right',
				'options' => [
					['text' => tra('Right'), 'value' => 'right'],
					['text' => tra('Left'), 'value' => 'left'],
					['text' => tra('Page'), 'value' => 'page']
				],
			],
			'levels' => [
				'required' => false,
				'name' => tra('Levels'),
				'description' => tr('Specify which levels you want to see in the toc. Levels are integers (1 to 6) separated with colon. Example: <code>levels="1:2:3"</code>.'),
				'since' => '22.0',
				'filter' => 'text',
				'default' => '',
				'separator' => ':',
			],
			'offset' => [
				'required' => false,
				'name' => tra('Offset'),
				'description' => tra('Offset of Table Of Contents. Offset default value is 15'),
				'since' => '22.0',
				'filter' => 'int',
				'default' => 15,
			],
			'mode' => [
				'required' => false,
				'name' => tra('Mode'),
				'description' => tr('Change the display of the table of contents for wiki pages to inline.'),
				'since' => '22.0',
				'filter' => 'alpha',
				'default' => 'off',
				'options' => [
					['text' => tra('Off'), 'value' => 'off'],
					['text' => tra('Inline'), 'value' => 'inline'],
				],
			],
			'title' => [
				'required' => false,
				'name' => tra('Title'),
				'description' => tra('Title for the Table Of Contents'),
				'since' => '22.0',
				'default' => tra(''),
			],
		]
	];
}

function wikiplugin_autotoc($data, $params)
{
	$defaults = [
		'align' => 'right',
		'levels' => '',
		'offset' => 10,
		'mode' => 'off',
		'title' => ''
	];

	$params = array_merge($defaults, $params);
	extract($params, EXTR_SKIP);
	$tikilib = TikiLib::lib('tiki');
	$headerlib = TikiLib::lib('header');

	$currPage = isset($_REQUEST['page']) ? $_REQUEST['page'] : '';
	if (
        ! empty($currPage) &&
		(strstr($_SERVER["SCRIPT_NAME"], "tiki-editpage.php") === false) &&
		(strstr($_SERVER["SCRIPT_NAME"], 'tiki-pagehistory.php') === false)
    ) {
		if (! isset($params['activity'])) {
			return ("<b>Missing activity parameter for plugin</b><br/>");
		}
		if ($params['activity'] == 'yes') {
			$jqueryAutoToc['plugin_autoToc_activity'] = true;
			$jqueryAutoToc['plugin_autoToc_mode'] = isset($params['mode']) ? $params['mode'] === 'inline' : 'off';
			$autotocPos = ! empty($params['align']) ? $params['align'] : 'right';
			$jqueryAutoToc['plugin_autoToc_pos'] = $autotocPos;
			$jqueryAutoToc['plugin_autoToc_offset'] = ! empty($params['offset']) && $params['offset'] > 0 ? $params['offset'] : 15;
			$jqueryAutoToc['plugin_autoToc_title'] = ! empty($params['title']) ? $params['title'] : '';

			if (! empty($params['levels'])) {
				$autoTocLevels = $params['levels'];
			} else {
				$autoTocLevels = null;
			}
			$jqueryAutoToc['plugin_autoToc_levels'] = $autoTocLevels;

			$js = '
					var jqueryAutoToc = ' . json_encode($jqueryAutoToc, JSON_UNESCAPED_SLASHES) . "\n";

			$headerlib->add_js($js);
			if (
                $prefs['wiki_auto_toc'] !== 'y'
                || $prefs['wiki_toc_default'] !== 'on'
            ) {
				$headerlib->add_jsfile('lib/jquery_tiki/autoToc.js');
			}
			if ($autotocPos == 'page') {
				$headerlib->add_css('
					#autotoc ul li {
						list-style: none;
						position: relative;
					}

					#autotoc ul > li > a {
						display: block;
						font-size: 15px;
					}

					#autotoc ul > li > a:hover,
					#autotoc ul > li > a:focus {
						font-weight: bold;
						padding-left: 6px;
						text-decoration: none;
						background-color: transparent;
						border-left: 1px solid #0075ff;
					}');

				return "<div id='autotoc'></div>";
			} else {
				$headerlib->add_css('
					body {
						position: relative;
					}

					#autotoc .nav > li > a {
						display: block;
						padding: 4px 20px;
						font-size: 13px;
						font-weight: 500;
					}

					#autotoc .nav > li > a:hover,
					#autotoc .nav > li > a:focus {
						padding-left: 19px;
						text-decoration: none;
						background-color: transparent;
						border-left: 1px solid #0075ff;
					}

					#autotoc .nav-link.active,
					#autotoc .nav-link.active:hover,
					#autotoc .nav-link.active:focus {
						padding-left: 30px;
						font-weight: bold;
						background-color: transparent;
						border-left: 2px solid #0075ff;
					}

					#autotoc .nav-link + ul {
						display: none;
						padding-bottom: 10px;
					}

					#autotoc .nav .nav > li > a {
						padding-top: 1px;
						padding-bottom: 1px;
						padding-left: 30px;
						font-size: 12px;
						font-weight: normal;
					}

					#autotoc .nav .nav > li > a:hover,
					#autotoc .nav .nav > li > a:focus {
						padding-left: 29px;
					}

					#autotoc .nav .nav > li > .active,
					#autotoc .nav .nav > li > .active:hover,
					#autotoc .nav .nav > li > .active:focus {
						padding-left: 28px;
						font-weight: 500;
					}

					#autotoc .nav-link.active + ul {
						display: block;
					}

					@media screen and (max-width: 991px) {
						.hidden {
							display: none!important;
						}
					}');
			}
		}
	}
}
