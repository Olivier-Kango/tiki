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

class Search_Manticore_QueryDecorator extends Search_Manticore_Decorator
{
    protected $factory;
    protected $currentOp;
    protected $documentReader;

    public function __construct(\Manticoresearch\Search $search, Search_Manticore_Index $index)
    {
        global $prefs;

        parent::__construct($search, $index);

        $this->factory = new Search_Manticore_TypeFactory();
        $this->currentOp = $prefs['unified_search_default_operator'] == 1 ? \Manticoresearch\Search::FILTER_AND : \Manticoresearch\Search::FILTER_OR;
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
        $expr->traverse($this);
    }

    public function __invoke($callback, $node, $childNodes)
    {
        if ($node instanceof ImplicitPhrase) {
            $node = $node->getBasicOperator();
        }

        if ($node instanceof Token) {
            $mapping = $this->index ? $this->index->getFieldMapping($node->getField()) : new stdClass();
            if ($mapping && in_array('indexed', $mapping['options'])) {
                if ($this->currentOp == \Manticoresearch\Search::FILTER_AND) {
                    $this->search->phrase($this->getTerm($node), $this->getNodeField($node));
                } else {
                    $this->search->match($this->getTerm($node), $this->getNodeField($node));
                }
            } else {
                $this->search->filter($this->getNodeField($node), '=', $this->getTerm($node), $this->currentOp);
            }
        } elseif (count($childNodes) === 1 && ($node instanceof AndX || $node instanceof OrX)) {
            return reset($childNodes)->traverse($callback);
        } elseif ($node instanceof OrX) {
            $this->currentOp = \Manticoresearch\Search::FILTER_OR;
            array_map(
                function ($expr) use ($callback) {
                    $expr->traverse($callback);
                },
                $childNodes
            );

        } elseif ($node instanceof AndX) {
            $this->currentOp = \Manticoresearch\Search::FILTER_AND;
            array_map(
                function ($expr) use ($callback) {
                    $expr->traverse($callback);
                },
                $childNodes
            );
        } elseif ($node instanceof NotX) {
            $this->currentOp = \Manticoresearch\Search::FILTER_NOT;
            array_map(
                function ($expr) use ($callback) {
                    $expr->traverse($callback);
                },
                $childNodes
            );
        } elseif ($node instanceof Initial) {
            $this->search->filter('REGEX('.$this->getNodeField($node).', "^'.$this->getTerm($node).'")', '=', 1, $this->currentOp);
        } elseif ($node instanceof Range) {
            $this->search->filter(
                $this->getNodeField($node),
                'range',
                [
                    $this->getTerm($node->getToken('from')),
                    $this->getTerm($node->getToken('to'))
                ],
                $this->currentOp
            );
        } elseif ($node instanceof MoreLikeThis) {
            $type = $node->getObjectType();
            $object = $node->getObjectId();

            $content = $node->getContent() ?: $this->getDocumentContent($type, $object);
            // TODO: https://play.manticoresearch.com/mlt/ possible implementation
        } elseif ($node instanceof Distance) {
            $this->search->distance([
                'location_anchor' => ["lat" => $node->getLat(), "lon" => $node->getLon()],
                'location_source' => $this->getNodeField($node),
                'location_distance' => $node->getDistance(),
            ]);
        } else {
            throw new Exception(tr('Feature not supported.'));
        }
    }

    private function getTerm($node)
    {
        return $node->getValue($this->factory)->getValue();
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
}
