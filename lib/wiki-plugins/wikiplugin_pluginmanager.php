<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
require_once 'lib/wiki/pluginslib.php';
require_once 'lib/wiki-plugins/wikiplugin_tikidocfromcode.php';
class WikiPluginPluginManager extends PluginsLib
{
    public function getDefaultArguments()
    {
        return [
                    'info' => 'description|parameters|paraminfo',
                    'type' => '',
                    'plugin' => '',
                    'module' => '',
                    'preference' => '',
                    'trackerfield' => '',
                    'prefsexport' => '',
                    'singletitle' => 'none',
                    'titletag' => 'h3',
                    'start' => '',
                    'limit' => '',
                    'paramtype' => '',
                    'showparamtype' => 'n',
                    'showtopinfo' => 'y'
                ];
    }
    public function getName()
    {
        return 'PluginManager';
    }
    public function getVersion()
    {
        return preg_replace("/[Revision: $]/", '', "\$Revision: 1.11 $");
    }
    public function getDescription()
    {
        return wikiplugin_pluginmanager_help();
    }
    public function run($data, $params)
    {
        global $helpurl;
        $wikilib = TikiLib::lib('wiki');
        $tikilib = TikiLib::lib('tiki');
        if (empty($helpurl)) {
            $helpurl = 'http://doc.tiki.org/';
        }

        $params = $this->getParams($params);
        extract($params, EXTR_SKIP);

        $singletitle = $params['singletitle'] ?? null;
        $info = $params['info'] ?? null;
        $titletag = $params['titletag'] ?? null;

        if (! empty($module) && ! empty($plugin)) {
            return $this->error(tra('Either the module or plugin parameter must be set, but not both.'));
        } elseif (! empty($module)) {
            $aPrincipalField = ['field' => 'plugin', 'name' => 'Module'];
            $helppath = $helpurl . $aPrincipalField['name'] . ' ';
            $filepath = 'mod-func-';

            $modlib = TikiLib::lib('mod');
            $aPlugins = $modlib->list_module_files();
            $mod = true;
            $type = ' module';
            $plugin = $module;
            $sourceurl = 'https://gitlab.com/tikiwiki/tiki/-/blob/master/modules/';
        } else {
            $aPrincipalField = ['field' => 'plugin', 'name' => 'Plugin'];
            $helppath = $helpurl . $aPrincipalField['name'];
            $filepath = WIKIPLUGINS_SRC_PATH . '/wikiplugin_';
            $aPlugins = $wikilib->list_plugins();
            $mod = false;
            $type = ' plugin';
            $sourceurl = 'https://gitlab.com/tikiwiki/tiki/-/blob/master/';
        }
        $all = $aPlugins;
        //if the user set $module, that setting has now been moved to $plugin so that one code set is used
        //$aPlugins and $all now has the complete list of plugin or module file names - the code below modifies $aPlugins
        //if necessary based on user settings
        if (! empty($plugin)) {
            if (strpos($plugin, '|') !== false) {
                $aPlugins = [];
                $userlist = explode('|', $plugin);
                foreach ($userlist as $useritem) {
                    $file = $filepath . $useritem . '.php';
                    $confirm = in_array($file, $all);
                    if ($confirm === false) {
                        return '{BOX(class="text-bg-light")}' . tr('Plugin Manager error: %0%1 not found', $useritem, $type) . '{BOX}';
                    } else {
                        $aPlugins[] = $file;
                    }
                }
            } elseif (strpos($plugin, '-') !== false) {
                $userrange = explode('-', $plugin);
                $begin = array_search($filepath . $userrange[0] . '.php', $aPlugins);
                $end = array_search($filepath . $userrange[1] . '.php', $aPlugins);
                $beginerror = '';
                $enderror = '';
                $type2 = $type;
                if ($begin === false || $end === false) {
                    if ($begin === false) {
                        $beginerror = $userrange[0];
                    }
                    if ($end === false) {
                        $enderror = $userrange[1];
                        if (! empty($beginerror)) {
                            $and = ' and ';
                        } else {
                            $and = '';
                            $type = '';
                        }
                    }
                    return '{BOX(class="text-bg-light")}' . tr('Plugin Manager error: %0%1%2%3%4 not found', $beginerror, $type, $and, $enderror, $type2) . '{BOX}';
                } elseif ($end > $begin) {
                    $aPlugins = array_slice($aPlugins, $begin, $end - $begin + 1);
                } else {
                    $aPlugins = array_slice($aPlugins, $end, $begin - $end + 1);
                }
            } elseif (! empty($limit)) {
                $begin = array_search($filepath . $plugin . '.php', $aPlugins);
                if ($begin === false) {
                    return '{BOX(class="text-bg-light")}' . tr('Plugin Manager error: %0%1 not found', $begin, $type) . '{BOX)}';
                } else {
                    $aPlugins = array_slice($aPlugins, $begin, $limit);
                }
            } elseif ($plugin != 'all') {
                $file = $filepath . $plugin . '.php';
                $confirm = in_array($file, $aPlugins);
                if ($confirm === false) {
                    return '{BOX(class="text-bg-light")}' . tr('Plugin Manager error:  %0%1 not found', $plugin, $type) . '{BOX}';
                } else {
                    $aPlugins = [];
                    $aPlugins[] = $file;
                }
            }
        } else {
            if (! empty($start) || ! empty($limit)) {
                if (! empty($start) && ! empty($limit)) {
                    $aPlugins = array_slice($aPlugins, $start - 1, $limit);
                } elseif (! empty($start)) {
                    $aPlugins = array_slice($aPlugins, $start - 1);
                } else {
                    $aPlugins = array_slice($aPlugins, 0, $limit);
                }
            }
        }
        //Set all data variables needed for separate code used to generate the display table
        $aData = [];
        if ($singletitle == 'table' || count($aPlugins) > 1) {
            foreach ($aPlugins as $sPluginFile) {
                global $sPlugin, $numparams;
                if ($mod) {
                    $infoPlugin = get_module_params($sPluginFile);
                    $namepath = $sPlugin;
                } else {
                    $infoPlugin = get_plugin_info($sPluginFile);
                    $namepath = ucfirst($sPlugin);
                }
                if (in_array('description', $info)) {
                    if (isset($infoPlugin['description'])) {
                        if ($numparams > 1) {
                            $aData[$sPlugin]['description']['onekey'] = $infoPlugin['description'];
                        } else {
                            $aData[$sPlugin]['description'] = $infoPlugin['description'];
                        }
                    } else {
                        $aData[$sPlugin]['description'] = ' --- ';
                    }
                }
                if (in_array('sourcecode', $info)) {
                    if ($numparams > 1) {
                        $aData[$sPlugin]['sourcecode']['onekey'] = '[' . $sourceurl . $sPluginFile . '|' . tra('Go to the source code') . ']';
                    } else {
                        $aData[$sPlugin]['sourcecode'] = '[' . $sourceurl . $sPluginFile . '|' . tra('Go to the source code') . ']';
                    }
                }
                if (in_array('parameters', $info)) {
                    if ($numparams > 0) {
                        if ($aPrincipalField['field'] == 'plugin' && ! in_array('options', $info) && $numparams > 1) {
                            $aData[$sPlugin][$aPrincipalField['field']]['rowspan'] = $numparams;
                            if (in_array('description', $info)) {
                                $aData[$sPlugin]['description']['rowspan'] = $numparams;
                            }
                        }
                        foreach ($infoPlugin['params'] as $paramname => $param) {
                            if (isset($infoPlugin['params'][$paramname]['description'])) {
                                $paramblock = '~np~' . $infoPlugin['params'][$paramname]['description'] . '~/np~';
                            }
                            if (isset($param['options']) && is_array($param['options'])) {
                                $paramblock .= '<br /><em>' . tra('Options:') . '</em> ';
                                $i = 0;
                                foreach ($param['options'] as $oplist => $opitem) {
                                    if (isset($opitem['value'])) {
                                        $paramblock .= $opitem['value'];
                                    } else {
                                        $paramblock .= $opitem['text'];
                                    }
                                    $paramblock .= ' | ';
                                    $i++;
                                }
                                $paramblock = substr($paramblock, 0, -3);
                            }
                            if (isset($infoPlugin['params'][$paramname]['required']) && $infoPlugin['params'][$paramname]['required'] == true) {
                                $aData[$sPlugin]['parameters']['<b><code>' . $paramname . '</code></b>'] = $paramblock;
                            } else {
                                $aData[$sPlugin]['parameters']['<code>' . $paramname . '</code>'] = $paramblock;
                            }
                        }
                    } else {
                        $aData[$sPlugin]['parameters']['<em>no parameters</em>'] = '<em>' . tra('n/a') . '</em>';
                    }
                }
                $aData[$sPlugin]['plugin']['plugin'] = '[' . $helppath . $namepath . '|' . ucfirst($sPlugin) . ']';
            } // Plugins Loop
            return PluginsLibUtil::createTable($aData, $info, $aPrincipalField);
        } else {
            //Replicates a documentation table for parameters for a single plugin or module
            //Not using plugin lib table to avoid making custom modifications
            global $sPlugin, $numparams;
            $sourcecode = $sourceurl . $aPlugins[0];
            if ($mod) {
                $infoPlugin = get_module_params($aPlugins[0]);
                $namepath = $sPlugin;
            } else {
                $infoPlugin = get_plugin_info($aPlugins[0]);
                $namepath = ucfirst($sPlugin);
            }
            if ($singletitle == 'top') {
                $title = '<' . $titletag . '>[' . $helppath . $namepath
                    . '|' . ucfirst($sPlugin) . ']</' . $titletag . '>';
                $title .= $infoPlugin['description'] . '<br />';
            } else {
                $title = '';
            }
            $headbegin = "\n\t\t" . '<th class="heading">';
            $cellbegin = "\n\t\t" . '<td>';
            $header = "\n\t" . '<tr class="heading sticky-top bg-light">' . $headbegin . 'Parameters</th>';
            $rows = '';
            if (isset($numparams) && $numparams > 0) {
                $header .= $headbegin . tra('Accepted Values') . '</th>';
                    $header .= $headbegin . tra('Description') . '</th>';
                $rowCounter = 1;
                //sort required params first
                $reqarray = array_column($infoPlugin['params'], 'required');
                $keysarray = array_keys($infoPlugin['params']);
                $reqarray = array_combine($keysarray, $reqarray);
                if (count($reqarray) == count($infoPlugin['params'])) {
                    array_multisort($reqarray, SORT_DESC, $infoPlugin['params']);
                }
                //add body instructions to the parameter array
                if (! empty($infoPlugin['body'])) {
                    $body = ['(body of plugin)' => ['description' => $infoPlugin['body']]];
                    $infoPlugin['params'] = array_merge($body, $infoPlugin['params']);
                }
                $count = 1;
                foreach ($infoPlugin['params'] as $paramname => $paraminfo) {
                    unset($sep, $septext);
                    //check is paramtype filter is set
                    if (
                        empty($params['paramtype'])
                        || ((empty($paraminfo['doctype']) && ! empty($params['paramtype']) && $params['paramtype'] === 'none')
                        || (! empty($paraminfo['doctype']) && $params['paramtype'] == $paraminfo['doctype']))
                    ) {
                        $filteredparams[] = $paraminfo;
                        $rows .= "\n\t" . '<tr style="word-break: break-word;">' . $cellbegin;
                        //Parameters column
                        if (isset($paraminfo['required']) && $paraminfo['required'] == true) {
                            $rows .= '<strong><code>' . $paramname . '</code></strong>';
                        } elseif ($paramname == '(body of plugin)') {
                            $rows .= tra('(body of plugin)');
                        } else {
                            $rows .= '<code>' . $paramname . '</code>' ;
                        }
                        if (
                            isset($params['showparamtype']) && $params['showparamtype'] === 'y'
                            && ! empty($paraminfo['doctype'])
                        ) {
                            $rows .= '<br /><small>(' . $paraminfo['doctype'] . ')</small>';
                        }
                        $rows .= '</td>';
                        //Accepted Values column
                        $rows .= $cellbegin;
                        if (isset($paraminfo['separator'])) {
                            $sep = $paraminfo['separator'];
                            $septext = tr('%0separator:%1 ', '<em>', '</em>');
                            if (is_string($paraminfo['separator'])) {
                                $septext .= '<code>' . $paraminfo['separator'] . '</code>';
                            } elseif (is_array($paraminfo['separator'])) {
                                $septext .= implode(' ', array_map(function ($separator) {
                                    return '<code>' . $separator . '</code>';
                                }, $paraminfo['separator']));
                            }
                        } else {
                            $sep = '| ';
                        }
                        if (isset($paraminfo['accepted'])) {
                            if (is_array($paraminfo['accepted'])) {
                                $rows .= implode(', ', $paraminfo['accepted']);
                            } else {
                                $rows .= $paraminfo['accepted'];
                            }
                            if (isset($septext)) {
                                $rows .= '<br />' . $septext;
                            }
                            $rows .= '</td>';
                        } elseif (isset($paraminfo['options'])) {
                            $optcounter = 1;
                            $numoptions = count($paraminfo['options']);
                            foreach ($paraminfo['options'] as $oplist => $opitem) {
                                if (isset($opitem['value'])) {
                                    $rows .= strlen($opitem['value']) == 0 ? tra('(blank)') : $opitem['value'];
                                } else {
                                    $rows .= tra('(blank)'); // Set a default value if 'value' key is not defined
                                }
                                if ($optcounter < $numoptions) {
                                    if ($numoptions > 10) {
                                        $rows .= $sep;
                                    } else {
                                        $rows .= '<br />';
                                    }
                                }
                                $optcounter++;
                            }
                            if (isset($septext)) {
                                $rows .= '<br />' . $septext;
                            }
                            $rows .= '</td>';
                        } elseif (isset($paraminfo['filter'])) {
                            if ($paraminfo['filter'] == 'striptags') {
                                $rows .= tra('any string except for HTML and PHP tags');
                            } else {
                                $rows .= $paraminfo['filter'];
                            }
                            if (isset($septext)) {
                                $rows .= '<br />' . $septext;
                            }
                            $rows .= '</td>';
                        } else {
                            if (isset($septext)) {
                                $rows .= '<br />' . $septext;
                            }
                            $rows .= '</td>';
                        }
                        //Description column
                        if (isset($paraminfo['description'])) {
                            $rows .= $cellbegin . $paraminfo['description'] . '</td>';
                        }
                        //Default column
                        if ($rowCounter == 1) {
                            $header .= $headbegin . tra('Default') . '</th>';
                        }
                        if (! isset($paraminfo['default'])) {
                            $paraminfo['default'] = '';
                        }
                        $default = is_array($paraminfo['default']) ? implode(',', $paraminfo['default']) : $paraminfo['default'];
                        $rows .= $cellbegin . $default . '</td>';
                        //Since column
                        if ($rowCounter == 1) {
                            $header .= $headbegin . tra('Since') . '</th>';
                        }
                        $since = ! empty($paraminfo['since']) ? $paraminfo['since'] : '';
                        $rows .= $cellbegin . $since . '</td>';
                        $rows .= "\n\t" . '</tr>';
                        $rowCounter++;
                    }
                }
                if (! empty($infoPlugin['additional']) && (empty($params['paramtype']) || $params['paramtype'] === 'none')) {
                    $rows .= '<tr><td colspan="5">' . $infoPlugin['additional'] . '</td></tr>';
                }
            } else {
                if (! empty($infoPlugin['body'])) {
                    $rows .= "\n\t" . '<tr>' . $cellbegin . '<em>' . tra('(body of plugin)') . ' - </em>'
                        . $infoPlugin['body'] . '</td>';
                }
                $rows .= "\n\t" . '<tr>' . $cellbegin . '<em>' . tra('no parameters') . '</em></td>';
            }
            $header .= "\n\t" . '</tr>';
            $pluginprefs = ! empty($infoPlugin['prefs']) && $params['showtopinfo'] !== 'n' ? '<em>'
                . tra('Preferences required:') . '</em> ' . implode(', ', $infoPlugin['prefs']) . '<br/>' : '';
            $title .= isset($infoPlugin['introduced']) && $params['showtopinfo'] !== 'n' ? '<em>' .
                tr('Introduced in %0', 'Tiki ' . $infoPlugin['introduced']) . '.</em>' : '';
            $link = '[' . $sourcecode . '|' . tra('Go to the source code') . ']';
            $required = ! empty($filteredparams) ? array_column($filteredparams, 'required') : [];
            $bold = in_array(true, $required) > 0 ? '<em> ' . tr(
                'Required parameters are in%0 %1bold%2',
                '</em>',
                '<strong><code>',
                '</code></strong>.'
            ) : '';
            $description = $infoPlugin['description'];
            $sOutput = $description . '<br/>' . $title . $bold . '<br>' . $link . '<br>' . $pluginprefs . '<br> <div class="table-responsive overflow-visible">' .
                '<table class="table table-striped table-hover">' . $header . $rows . '</table></div>' . "\n";
            return $sOutput;
        }
    }
    public function processDescription($sDescription)
    {
        $sDescription = str_replace(',', ', ', $sDescription);
        $sDescription = str_replace('|', '| ', $sDescription);
        $sDescription = strip_tags(wordwrap($sDescription, 35));
        return $sDescription;
    }
}

function wikiplugin_pluginmanager_info()
{
    return [
        'name' => tra('Plugin Manager'),
        'documentation' => 'PluginPluginManager',
        'description' => tra('List wiki plugin or module information for the site'),
        'prefs' => [ 'wikiplugin_pluginmanager' ],
        'introduced' => 1,
        'iconname' => 'plugin',
        'params' => [
            'info' => [
                'required' => false,
                'name' => tra('Information'),
                'description' => tr('Determines what information is shown. Values separated with %0|%1.
                    Ignored when %0singletitle%1 is set to %0top%1 or %0none%1.', '<code>', '</code>'),
                   'filter' => 'text',
                'accepted' => tra('One or more of: description | parameters | paraminfo'),
                'default' => 'description | parameters | paraminfo ',
                'since' => '1',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Description'), 'value' => 'description'],
                    ['text' => tra('Description and Source Code'), 'value' => 'description|sourcecode'],
                    ['text' => tra('Description and Parameters'), 'value' => 'description|parameters'],
                    ['text' => tra('Description & Parameter Info'), 'value' => 'description|paraminfo'],
                    ['text' => tra('Parameters & Parameter Info'), 'value' => 'parameters|paraminfo'],
                    ['text' => tra('All'), 'value' => 'description|parameters|paraminfo']
                ]
            ],
            'plugin' => [
                'required' => false,
                'name' => tra('Plugin'),
                'description' => tr('Name of a plugin (e.g., backlinks), or list separated by %0|%1, or range separated
                     by %0-%1. Single plugin can be used with %0limit%1 parameter.', '<code>', '</code>'),
                'filter' => 'text',
                'default' => '',
                'since' => '5.0',
            ],
            'module' => [
                'required' => false,
                'name' => tra('Module'),
                'description' => tr('Name of a module (e.g., calendar_new), or list separated by %0|%1, or range separated
                    by %0-%1. Single module can be used with %0limit%1 parameter.', '<code>', '</code>'),
                'filter' => 'text',
                'default' => '',
                'since' => '6.1',
            ],
            'preference' => [
                'required' => false,
                'name' => tra('Preference'),
                'description' => tr('Name of a preference (e.g., bigbluebutton_dynamic_configuration), or list separated by %0|%1, or range separated
                    by %0-%1. Single preference can be used with %0limit%1 parameter.', '<code>', '</code>'),
                'filter' => 'text',
                'default' => '',
                'since' => '27',
            ],
            'trackerfield' => [
                'required' => false,
                'name' => tra('Tracker field'),
                'description' => tr('Name of a tracker field, or list separated by %0|%1, or range separated
                    by %0-%1. Single preference can be used with %0limit%1 parameter.', '<code>', '</code>'),
                'filter' => 'text',
                'default' => '',
                'since' => '27',
            ],
            'prefsexport' => [
                'required' => false,
                'name' => tra('Preferences export'),
                'description' => tr('Default preferences export with columns name, description, location or list more separated by %0|%1.', '<code>', '</code>'),
                'filter' => 'text',
                'default' => 'name | description | locations',
                'since' => '27',
            ],
            'type' => [
                'required' => false,
                'name' => tra('Type'),
                'description' => tr('Type of the object to get tiki doc from (e.g: plugin, module,  preference, ...)'),
                'filter' => 'text',
                'default' => '',
                'since' => '27',
            ],
            'singletitle' => [
                'required' => false,
                'name' => tra('Single Title'),
                'description' => tr('Set placement of plugin name and description when displaying information for only one plugin'),
                'filter' => 'alpha',
                'default' => 'none',
                'since' => '5.0',
                'options' => [
                    ['text' => tra(''), 'value' => ''],
                    ['text' => tra('Top'), 'value' => 'top'],
                    ['text' => tra('Table'), 'value' => 'table'],
                ],
            ],
            'titletag' => [
                'required' => false,
                'name' => tra('Title Heading'),
                'description' => tr('Sets the heading size for the title, e.g., %0h2%1.', '<code>', '</code>'),
                'filter' => 'alnum',
                'default' => 'h3',
                'since' => '5.0',
                'advanced' => true,
            ],
            'start' => [
                'required' => false,
                'name' => tra('Start'),
                'description' => tra('Start with this plugin record number (must be an integer 1 or greater).'),
                'filter' => 'digits',
                'default' => '',
                'since' => '5.0',
            ],
            'limit' => [
                'required' => false,
                'name' => tra('Limit'),
                'description' => tra('Number of plugins to show. Can be used either with start or plugin as the starting
                    point. Must be an integer 1 or greater.'),
                'filter' => 'digits',
                'default' => '',
                'since' => '5.0',
            ],
            'paramtype' => [
                'required' => false,
                'name' => tra('Parameter Type'),
                'description' => tr('Only list parameters with this %0doctype%1 setting. Set to %0none%1 to show only
                    parameters without a type setting and the body instructions.', '<code>', '</code>'),
                'since' => '15.0',
                'filter' => 'alpha',
                'default' => '',
                'advanced' => true,
            ],
            'showparamtype' => [
                'required' => false,
                'name' => tra('Show Parameter Type'),
                'description' => tr('Show the parameter %0doctype%1 value.', '<code>', '</code>'),
                'since' => '15.0',
                'filter' => 'alpha',
                'default' => '',
                'advanced' => true,
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'showtopinfo' => [
                'required' => false,
                'name' => tra('Show Top Info'),
                'description' => tr('Show information above the table regarding preferences required and the first
                    version when the plugin became available. Shown by default.'),
                'since' => '15.0',
                'filter' => 'alpha',
                'default' => '',
                'advanced' => true,
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
        ],
    ];
}

function wikiplugin_pluginmanager($data, $params)
{
    $plugin = new WikiPluginTikiDocFromCode();
    return $plugin->run($data, $params);
}
