<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * \brief Show features state in a convenient way
 * \author zaufi <zaufi@sendmail.ru>
 */

require_once('lib/debug/debugger-ext.php');

/**
 * \brief Debugger command to show features on/off state
 */
class DbgFeatures extends DebuggerCommand
{
    /// \b Must have function to announce command name in debugger console
    public function name()
    {
        return 'features';
    }

    /// \b Must have function to provide help to debugger console
    public function description()
    {
        return 'Show features on/off state';
    }

    /// \b Must have function to provide help to debugger console
    public function syntax()
    {
        return 'features [partial-name]';
    }

    /// \b Must have function to show example of usage of given command
    public function example()
    {
        return 'features' . "\n" . 'features wiki';
    }

    /// Execute command with given set of arguments.
    public function execute($params)
    {
        $this->set_result_type(TPL_RESULT);

        $this->set_result_tpl('debug/tiki-debug_features.tpl');
        // Is regex to match against var name given?
        $p = explode(' ', trim($params));
        $mask = count($p) > 0 ? str_replace('$', '', trim($p[0])) : '';
        // Get list of all vars
        $smarty = TikiLib::lib('smarty');
        $tpl_vars = $smarty->getTemplateVars();
        // convert to vector of names, filter permissions only, and sort
        $perms = [];
        $len = strlen($mask);

        global $prefs;

        foreach ($prefs as $key => $val) {
            if ((! $len || $len && preg_match('/' . $mask . '/', $key)) && preg_match('/feature_/', $key)) {
                $perms[] = [
                    'name' => $key,
                    'value' => $val
                ];
            }
        }

        return $perms;
    }
}

/// Class factory to create instances of defined commands
function dbg_command_factory_features()
{
    return new DbgFeatures();
}
