<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_Expr_Tokenizer
{
    private const QUOTE = '"';
    private const OPEN = '(';
    private const CLOSE = ')';

    public function tokenize($string)
    {
        $tokens = [];
        $open = false;
        $current = '';

        $length = strlen($string);
        for ($i = 0; $length > $i; ++$i) {
            $char = $string[$i];

            if ($open) {
                if ($char === self::QUOTE) {
                    $this->addToken($tokens, $current);
                    $open = false;
                } else {
                    $current .= $char;
                }
            } else {
                if ($char === self::QUOTE) {
                    $open = true;
                } elseif ($char === self::OPEN || $char === self::CLOSE) {
                    $this->addToken($tokens, $current);
                    $this->addToken($tokens, $char);
                } elseif (ctype_space($char)) {
                    $this->addToken($tokens, $current);
                } else {
                    $current .= $char;
                }
            }
        }

        $this->addToken($tokens, $current);

        return $tokens;
    }

    private function addToken(&$tokens, &$current)
    {
        if (strlen($current)) {
            $tokens[] = $current;
            $current = '';
        }
    }
}
