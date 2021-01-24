<?php


namespace Tiki\Lib\core\Tracker\Rule\Operator;


use Tiki\Lib\core\Tracker\Rule\Type\Nothing;

class TextIsUsername extends Operator
{
	function __construct()
	{
		parent::__construct(tr('is username'), Nothing::class, '.val()===jqueryTiki.username');
	}
}