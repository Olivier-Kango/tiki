<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.<<<<<<< HEAD
function smarty_function_user_registration($params, \Smarty\Template $template)
{
    $smartyFunctionUserRegistrationHandler = new \SmartyTiki\FunctionHandler\UserRegistration();
    return $smartyFunctionUserRegistrationHandler->handle($params, $template);
}
