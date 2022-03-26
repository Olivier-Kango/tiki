<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Services_Manager_Utilities
{
    use Services_Manager_Trait;

    public function loadEnv() {
        $this->loadManagerEnv();
        $this->setManagerOutput();
    }

    public function getManagerOutput() {
        return $this->manager_output;
    }
}
