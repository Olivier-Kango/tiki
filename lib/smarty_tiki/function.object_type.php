<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function smarty_function_object_type($params, \Smarty\Template $template)
{
    $smartyFunctionOjectTypeHandler = new \SmartyTiki\FunctionHandler\ObjectType();
    return $smartyFunctionOjectTypeHandler->handle($params, $template);
}
