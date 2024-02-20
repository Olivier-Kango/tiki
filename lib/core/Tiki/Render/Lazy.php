<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Tiki_Render_Lazy
{
    private $callback;
    private $data;

    public function __construct($callback)
    {
        $this->callback = $callback;
    }

    public function __toString()
    {
        if ($this->callback) {
            try {
                $this->data = call_user_func($this->callback);
            } catch (Throwable $e) {
                TikiLib::lib('errortracking')->captureException($e);
                //We want a helpfull message for developers in PHP error messages:
                $msg = "{$e->getMessage()} (Initially thrown in {$e->getFile()}:{$e->getLine()})";
                trigger_error($msg);
                //We want just the original error message for the end user
                $this->data = $e->getMessage();
            }
            $this->callback = null;
        }

        return (string) $this->data;
    }
}
