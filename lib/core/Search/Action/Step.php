<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/** Prepares the item to execute an action */
interface Search_Action_Step
{
    public function getFields();

    public function validate(array $entry);

    public function execute(array $entry);

    public function inputType();

    public function requiresInput();

    public function getAction();

    public function getDefinition();
}
