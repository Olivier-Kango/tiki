<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use Search_Expr_Token as Token;
use Search_Expr_And as AndX;
use Search_Expr_Or as OrX;
use Search_Expr_Not as NotX;
use Search_Expr_Range as Range;
use Search_Expr_Initial as Initial;
use Search_Expr_MoreLikeThis as MoreLikeThis;
use Search_Expr_ImplicitPhrase as ImplicitPhrase;
use Search_Expr_Distance as Distance;
use Manticoresearch\Query;

class Search_Manticore_QueryDecorator extends Search_Manticore_Decorator
{
    protected $factory;
    protected $matches;
    protected $must_nots;
    protected $documentReader;

    public function __construct(\Manticoresearch\Search $search, Search_Manticore_Index $index)
    {
        global $prefs;

        parent::__construct($search, $index);

        $this->factory = new Search_Manticore_TypeFactory();
        $this->documentReader = function ($type, $object) {
            return null;
        };
        // TODO: field weights supported by SQL search only
    }

    public function setDocumentReader($callback)
    {
        $this->documentReader = $callback;
    }

    public function decorate(Search_Expr_Interface $expr)
    {
        $this->matches = [];
        $this->must_nots = [];
        $q = $expr->traverse($this);
        foreach ($this->matches as $method => $subqs) {
            foreach ($subqs as $subq) {
                $q->$method($subq);
            }
        }
        foreach ($this->must_nots as $subq) {
            $q->mustNot($subq);
        }
        $this->search->search($q);
    }

    public function __invoke($callback, $node, $childNodes)
    {
        global $prefs;

        if ($node instanceof ImplicitPhrase) {
            $node = $node->getBasicOperator();
        }

        if ($node instanceof Token) {
            return $this->handleToken($node);
        } elseif ($node instanceof AndX || $node instanceof OrX || $node instanceof NotX) {
            if (count($childNodes) == 0) {
                return null;
            }
            if (count($childNodes) == 1 && ! $node instanceof NotX) {
                return reset($childNodes)->traverse($callback);
            }
            $childFields = array_map(function ($child) {
                if (method_exists($child, 'getType') && $child->getType() == 'multivalue') {
                    return $this->getNodeField($child);
                } else {
                    return '';
                }
            }, $childNodes);
            if (count(array_unique($childFields)) == 1 && array_filter($childFields) && $node instanceof OrX) {
                $terms = array_map(function ($child) {
                    return $this->getTerm($child);
                }, $childNodes);
                return new Query\In('ANY(' . reset($childFields) . ')', array_merge(...$terms));
            }
            if ($node instanceof OrX) {
                $method = 'should';
            } elseif ($node instanceof NotX) {
                $method = 'mustNot';
            } else {
                $method = 'must';
            }
            $q = new Query\BoolQuery();
            $isEmpty = true;
            foreach ($childNodes as $child) {
                $subq = $child->traverse($callback);
                if ($subq) {
                    if ($subq instanceof Query\MatchQuery || $subq instanceof Query\MatchPhrase) {
                        $this->matches[$method][] = $subq;
                    } elseif ($node instanceof NotX) {
                        $this->must_nots[] = $subq;
                    } else {
                        $q->$method($subq);
                        $isEmpty = false;
                    }
                }
            }
            if ($isEmpty) {
                return null;
            } else {
                return $q;
            }
        } elseif ($node instanceof Initial) {
            return new Query\Equals('REGEX(' . $this->getNodeField($node) . ', "^' . $this->getTerm($node) . '")', 1);
        } elseif ($node instanceof Range) {
            $from = $this->getTerm($node->getToken('from'));
            $to = $this->getTerm($node->getToken('to'));
            if (empty($from)) {
                $from = 0;
            }
            if (empty($to)) {
                $to = 0;
            }
            return new Query\Range($this->getNodeField($node), [
                'gte' => $from,
                'lte' => $to
            ]);
        } elseif ($node instanceof MoreLikeThis) {
            $type = $node->getObjectType();
            $object = $node->getObjectId();

            $content = $node->getContent() ?: $this->getDocumentContent($type, $object);
            // TODO: https://play.manticoresearch.com/mlt/ possible implementation
        } elseif ($node instanceof Distance) {
            return new Query\Distance([
                'location_anchor' => ["lat" => $node->getLat(), "lon" => $node->getLon()],
                'location_source' => $this->getNodeField($node),
                'location_distance' => $node->getDistance(),
            ]);
        } else {
            throw new Exception(tr('Feature not supported.'));
        }
    }

    private function getTerm($node, $forceType = null)
    {
        $value = $node->getValue($this->factory);
        if ($forceType && $node->getType() != $forceType) {
            $value = $this->factory->$forceType($value->getValue());
        }
        return $value->getValue();
    }

    private function getDocumentContent($type, $object)
    {
        $cb = $this->documentReader;
        $document = $cb($type, $object);

        if (isset($document['contents'])) {
            return $document['contents'];
        }

        return '';
    }

    private function handleToken($node)
    {
        global $prefs;

        $mapping = $this->index ? $this->index->getFieldMapping($node->getField()) : new stdClass();
        if ($mapping && in_array('indexed', $mapping['options'])) {
            $phrase = $this->getTerm($node);
            if ($prefs['unified_search_default_operator'] != 1) {
                $phrase = preg_replace('/\s+/', ' | ', $phrase);
            }
            if ($node->getType() == 'identifier') {
                return new Query\MatchPhrase($phrase, $this->getNodeField($node));
            } else {
                return new Query\MatchQuery($phrase, $this->getNodeField($node));
            }
        } elseif (isset($mapping['types']) && in_array('json', $mapping['types']) && $node->getType() == 'multivalue') {
            return new Query\In($this->getNodeField($node), json_decode($this->getTerm($node)));
        } elseif (isset($mapping['types']) && (in_array('multi', $mapping['types']) || in_array('mva', $mapping['types']))) {
            $terms = $this->getTerm($node, 'multivalue');
            return new Query\In('ANY(' . $this->getNodeField($node) . ')', $terms);
        } elseif ($node->getType() == 'identifier') {
            return new Query\Equals($this->getNodeField($node), $this->getTerm($node));
        } elseif ($mapping && in_array('timestamp', $mapping['types'])) {
            return new Query\Equals($this->getNodeField($node), intval($this->getTerm($node)));
        } else {
            return new Query\Equals('REGEX(' . $this->getNodeField($node) . ', "' . addslashes($this->getTerm($node)) . '")', 1);
        }
    }
}
