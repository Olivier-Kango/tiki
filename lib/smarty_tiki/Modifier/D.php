<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use Kint\Kint;
use Kint\Parser\Parser;
use Kint\Parser\PluginBeginInterface;
use Kint\Value\AbstractValue;
use Kint\Value\Context\BaseContext;
use Kint\Value\Context\ContextInterface;

/**
 * If installed, this modifier will use Kint (from https://github.com/kint-php/kint/)
 *
 *   You need to enable dev mode for composer by running `php console.php help dev:configure`
 *   and then setup.sh
 *
 * Example usage:
 *
 *     {$smarty.request|d}
 */
class D
{
    public function handle($var, $modifier = '')
    {
        if (is_callable('Kint::dump')) {
            // add this function as an alias of Kint::dump
            Kint::$aliases[] = 'smarty_modifier_d';
            // So far SmartyKin just replaces the ugly
            Kint::$plugins[] = new SmartyKint();

            switch ($modifier) {
                case '!':                   // Expand all data in this dump automatically
                    ! Kint::dump($var);
                    break;
                case '+':                   // Disable the depth limit in this dump
                    +Kint::dump($var);
                    break;
                case '-':                   // Clear buffered output and flush after dump
                    -Kint::dump($var);
                    break;
                case '@':                   // Return the output of this dump instead of echoing it
                    @Kint::dump($var);
                    break;
                case '~':                   // Use the text renderer for this dump
                    ~Kint::dump($var);
                    break;
                default:
                    Kint::$return = true;
                    Kint::$depth_limit = 8;
                    $return = Kint::dump($var);
                    echo str_replace(['~np~', '~/np~'], '', $return);
            }
        } else {
            var_dump($var);
        }
    }
}

if (class_exists('Kint')) {
//    https://kint-php.github.io/kint/writing-plugins/
    class SmartyKint implements PluginBeginInterface
    {
        protected $parser;

        public function setParser(Parser $p): void
        {
            $this->parser = $p;
        }

        public function getTypes(): array
        {
            return ['integer', 'string', 'array', 'object'];
        }

        public function getTriggers(): int
        {
            return Parser::TRIGGER_BEGIN;
        }
        public function parseBegin(&$var, ContextInterface $c): ?AbstractValue
        {
            // TODO: Implement parseBegin() method.
            $accessPath = $c->getAccessPath();
            if ($accessPath !== null) {
                $replace = preg_replace('/.*\[\'([^\']+)\']->value/', '$$1', $accessPath);
                if ($accessPath !== $replace) {
                    $base = new BaseContext($replace);
                    $base->depth = $c->getDepth();
                    $base->access_path = $c->getAccessPath();
                    $base->reference = $c->isRef();
                    return $this->parser?->parse($var, $base);
                }
            }

            return null;
        }
    }
}
