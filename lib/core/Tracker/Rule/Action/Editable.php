<?php


namespace Tiki\Lib\core\Tracker\Rule\Action;


use Tiki\Lib\core\Tracker\Rule\Type\Field;
use Tiki\Lib\core\Tracker\Rule\Type\Nothing;

class Editable extends Action
{
	public function __construct()
	{
		parent::__construct(tr('Editable'), Nothing::class, '.find("input,textarea,select").prop("disabled", false)', [Field::class]);
	}
}
