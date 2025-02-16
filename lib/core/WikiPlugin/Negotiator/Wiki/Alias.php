<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class WikiPlugin_Negotiator_Wiki_Alias
{
    public static function info($name)
    {
        global $prefs;

        if (empty($name)) {
            return false;
        }

        $name = mb_strtolower($name);

        $prefName = "pluginalias_" . $name;

        if (! isset($prefs[$prefName])) {
            return false;
        }

        return unserialize($prefs[$prefName]);
    }

    public static function getList()
    {
        global $prefs;
        if (isset($prefs['pluginaliaslist'])) {
            $alias = @unserialize($prefs['pluginaliaslist']);
            $alias = array_filter($alias);
            return $alias;
        }
        return [];
    }

    public static function store($name, $data)
    {
        /*
            Input data structure:

            implementation: other plugin_name
            description:
                ** Equivalent of plugin info function here **
            body:
                input: use|ignore
                default: body content to use
                params:
                    token_name:
                        input: token_name, default uses same name above
                        default: value to use if missing
                        encoding: none|html|url - default to none
            params:
                ; Use input parameter directly
                token_name: default value

                ; Custom input parameter replacement
                token_name:
                    pattern: body content to use
                    params:
                        token_name:
                            input: token_name, default uses same name above
                            default: value to use if missing
                            encoding: none|html|url - default to none
        */
        if (empty($name)) {
            return;
        }

        $name = mb_strtolower($name);
        $data['plugin_name'] = $name;

        $prefName = "pluginalias_$name";
        $tikilib = TikiLib::lib('tiki');
        $tikilib->set_preference($prefName, serialize($data));

        global $prefs;
        $list = [];
        if (isset($prefs['pluginaliaslist'])) {
            $list = unserialize($prefs['pluginaliaslist']);
        }

        if (! in_array($name, $list)) {
            $list[] = $name;
            $tikilib->set_preference('pluginaliaslist', serialize($list));
        }

        foreach (glob(WIKIPLUGIN_CACHE_FILES_GLOB) as $file) {
            unlink($file);
        }

        $cachelib = TikiLib::lib('cache');
        $cachelib->invalidate('plugindesc');
    }


    public static function delete($name)
    {
        $tikilib = TikiLib::lib('tiki');
        $prefName = "pluginalias_" . $name;

        // Remove from list
        $list = $tikilib->get_preference('pluginaliaslist', [], true);
        $list = array_diff($list, [ $name ]);
        $tikilib->set_preference('pluginaliaslist', serialize($list));

        // Remove the definition
        $tikilib->delete_preference($prefName);

        // Clear cache
        $cachelib = TikiLib::lib('cache');
        $cachelib->invalidate('plugindesc');
        foreach (glob(WIKIPLUGIN_CACHE_FILES_GLOB) as $file) {
            unlink($file);
        }
    }

    public static function getDetails($details = [])
    {
        if (self::findImplementation($details['name'], $details['body'], $details['args'])) {
            return $details;
        } else {
            return false;
        }
    }

    public static function findImplementation(&$implementation, &$data, &$args)
    {
        if ($info = self::info($implementation)) {
            $implementation = $info['implementation'];

            // Do the body conversion
            if (isset($info['body'])) {
                if (! empty($info['body']['input'])) {
                    if (($info['body']['input'] == 'ignore' ) || empty($data)) {
                        $data = isset($info['body']['default']) ? $info['body']['default'] : '';
                    } elseif (strpos($info['body']['default'], '%body%') !== false) {
                        // replace the string %body% with the provided body text if not ignoring user input
                        $rules = [
                            'body' => [
                                'input'    => '',
                                'encoding' => null,
                                'default'  => '',
                            ],
                        ];

                        $data = self::replaceArgs($info['body']['default'], $rules, ['body' => $data]);
                    }
                }

                if (isset($info['body']['params'])) {
                    $data = self::replaceArgs($data, $info['body']['params'], $args);
                }
            } else {
                $data = '';
            }

            // Do parameter conversion
            $params = [];
            if (isset($info['params'])) {
                foreach ($info['params'] as $key => $value) {
                    if (is_array($value) && isset($value['pattern']) && isset($value['params'])) {
                        $params[$key] = self::replaceArgs($value['pattern'], $value['params'], $args);
                    } else {
                        // Handle simple values
                        if (isset($args[$key])) {
                            $params[$key] = $args[$key];
                        } else {
                            $params[$key] = $value;
                        }
                    }
                }
            }

            $args = $params;

            // Attempt to find recursively
            self::findImplementation($implementation, $data, $args);

            return true;
        }

        return false;
    }

    public static function replaceArgs($content, $rules, $args)
    {
        $patterns = [];
        $replacements = [];

        foreach ($rules as $token => $info) {
            $patterns[] = "%$token%";
            if (isset($info['input']) && ! empty($info['input'])) {
                $token = $info['input'];
            }

            if (isset($args[$token])) {
                $value = $args[$token];
            } else {
                $value = isset($info['default']) ? $info['default'] : '';
            }

            switch (isset($info['encoding']) ? $info['encoding'] : 'none') {
                case 'html':
                    $replacements[] = htmlentities($value, ENT_QUOTES, 'UTF-8');
                    break;
                case 'url':
                    $replacements[] = rawurlencode($value);
                    break;
                default:
                    $replacements[] = $value;
            }
        }

        return str_replace($patterns, $replacements, $content);
    }
}
