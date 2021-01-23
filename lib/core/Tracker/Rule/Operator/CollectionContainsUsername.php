<?php


namespace Tiki\Lib\core\Tracker\Rule\Operator;


use Tiki\Lib\core\Tracker\Rule\Type\Nothing;

class CollectionContainsUsername extends Operator
{
	function __construct()
	{
		parent::__construct(tr('contains username'), Nothing::class, '.val().indexOf(jqueryTiki.username)>-1');
	}
}
