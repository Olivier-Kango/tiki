<?php


namespace Tiki\Lib\core\Tracker\Rule\Operator;


use Tiki\Lib\core\Tracker\Rule\Type\DateTime;
use Tiki\Lib\core\Tracker\Rule\Type\Nothing;
use Tiki\Lib\core\Tracker\Rule\Type\Text;

class TextIsEmpty extends Operator
{
	function __construct()
	{
		parent::__construct(tr('is empty'), Nothing::class, '.val()===""', [Text::class, DateTime::class]);
	}
}