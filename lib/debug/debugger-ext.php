<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * \brief Base class for external debugger command
 * \author zaufi <zaufi@sendmail.ru>
 */

require_once('lib/debug/debugger-common.php');

/**
 * \brief Base class for external debugger command
 */
class DebuggerCommand extends ResultType
{
    /**
     * \brief Must have function to announce command name in debugger console
     *
     * Assume interface extension if no name provided
     */
    public function name()
    {
        return '';
    }
    /**
     * \brief Must have function to provide help to debugger console
     *
     * Used as title foe interface extentions
     */
    public function description()
    {
        return 'No help available for ' . $this->name();
    }

    /// \b Must have function to provide help to debugger console
    public function syntax()
    {
        return $this->name();
    }

    /// \b Must have functio to show exampla of usage of given command
    public function example()
    {
        return 'No example available for ' . $this->name();
    }

    /// Execute command with given set of arguments. Must return string of result.
    public function execute($params)
    {
        return 'No result';
    }

    /// Say to debugger is this command need to draw some interface on console...
    public function have_interface()
    {
        return false;
    }

    /// Return HTML code of our interface to debugger
    public function draw_interface()
    {
        return '';
    }

    /// Function to return caption string to draw plugable tab in interface
    public function caption()
    {
        return 'caption';
    }
}

// Also developer must provide factory function
// so debugger can create an instance of command handler
// It must be called 'dbg_command_factory_[your-cmd-name]'
// which is returns handler instance...
