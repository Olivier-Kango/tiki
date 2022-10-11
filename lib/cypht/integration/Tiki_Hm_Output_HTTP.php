<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Tiki_Hm_Output_HTTP
{
    public function send_response($response, $input = [])
    {
        if (array_key_exists('http_headers', $input)) {
            return $this->output_content($response, $input['http_headers']);
        } else {
            return $this->output_content($response, []);
        }
    }

    protected function output_headers($headers)
    {
        foreach ($headers as $name => $value) {
            Hm_Functions::header($name . ': ' . $value);
        }
    }

    protected function output_content($content, $headers = [])
    {
        $this->output_headers($headers);
        return $content;
    }
}
