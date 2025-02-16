<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Tiki_Transition
{
    private $from;
    private $to;
    private $states = [];
    private $guards = [];

    private $blockers;

    public function __construct($from, $to)
    {
        $this->from = $from;
        $this->to = $to;
    }

    public function setStates(array $states)
    {
        $this->states = $states;
        $this->blockers = null;
    }

    public function addGuard($type, $boundary, $set)
    {
        if (method_exists($this, $this->guardTypeToMethod($type))) {
            $this->guards[] = [$type, $boundary, $set];
        } else {
            $this->guards[] = ['unknown', 1, [$type]];
        }
    }

    public function isReady()
    {
        return count($this->explain()) == 0;
    }

    public function explain()
    {
        $this->blockers = [];
        $this->guardExactly(1, [$this->from]);
        $this->guardExactly(0, [$this->to]);

        $this->applyGuards();

        return $this->blockers;
    }

    /**
     * Converts a guard type to a class method to be called
     */
    private function guardTypeToMethod($type)
    {
        return 'guard' . ucfirst($type);
    }

    private function applyGuards()
    {
        foreach ($this->guards as $guard) {
            $method = $this->guardTypeToMethod(array_shift($guard));

            call_user_func_array([$this, $method], $guard);
        }
    }

    private function addBlocker($type, $amount, $set)
    {
        $this->blockers[] = ['class' => $type, 'count' => $amount, 'set' => array_values($set)];
    }

    private function guardExactly($amount, $list)
    {
        if (count($list) < $amount) {
            $this->addBlocker('invalid', $amount, $list);
            return;
        }

        $intersect = array_intersect($this->states, $list);
        $count = count($intersect);

        if ($count > $amount) {
            $this->addBlocker('extra', $count - $amount, $intersect);
        } elseif ($count < $amount) {
            $set = array_diff($list, $intersect);
            $this->addBlocker('missing', $amount - $count, $set);
        }
    }

    private function guardAtMost($amount, $list)
    {
        $intersect = array_intersect($this->states, $list);
        $count = count($intersect);

        if ($count > $amount) {
            $this->addBlocker('extra', $count - $amount, $intersect);
        }
    }

    private function guardAtLeast($amount, $list)
    {
        if (count($list) < $amount) {
            $this->addBlocker('invalid', $amount, $list);
            return;
        }

        $intersect = array_intersect($this->states, $list);
        $count = count($intersect);

        if ($count < $amount) {
            $set = array_diff($list, $intersect);
            $this->addBlocker('missing', $amount - $count, $set);
        }
    }

    private function guardUnknown($amount, $list)
    {
        $this->addBlocker('unknown', 1, $list);
    }
}
