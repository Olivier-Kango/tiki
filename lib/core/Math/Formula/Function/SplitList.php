<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Math_Formula_Function_SplitList extends Math_Formula_Function
{
    public function evaluate($element)
    {
        $allowed = ['content', 'separator', 'keys', 'key'];

        if ($extra = $element->getExtraValues($allowed)) {
            $this->error(tr('Unexpected values: %0', implode(', ', $extra)));
        }

        $content = $element->content;

        if (! $content || count($content) < 1) {
            $this->error(tra('Content required.'));
        }

        $separator = $element->separator;
        if (! $separator || count($separator) != 1) {
            $this->error(tra('Field must be provided and contain one argument: separator'));
        }
        $separator = $separator[0];

        if (! $element->keys && ! $element->key) {
            $this->error(tra('Field must be provided and contain one or more values: key or keys'));
        }

        $out = [];

        if ($element->key) {
            $key = $element->key[0];
            foreach ($content as $child) {
                $string = $this->evaluateChild($child);
                foreach (explode($separator, $string) as $value) {
                    $out[] = [$key => $value];
                }
            }
        } else {
            $keys = [];
            foreach ($element->keys as $key) {
                $keys[] = $key;
            }
            $keyCount = count($keys);

            foreach ($content as $child) {
                $string = $this->evaluateChild($child);
                foreach (explode("\n", $string) as $line) {
                    $parts = explode($separator, $line, $keyCount);

                    // Skip entries with missing values
                    if (count($parts) === $keyCount) {
                        $entry = array_combine($keys, $parts);
                        $out[] = $entry;
                    }
                }
            }
        }

        return $out;
    }
}
