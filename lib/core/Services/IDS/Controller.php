<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Services_IDS_Controller
{
    /**
     * @var TikiAccessLib
     */
    private $access;

    public function setUp()
    {
        $this->access = TikiLib::lib('access');
    }


    /**
     * @param $input JitFilter
     * @return array
     * @throws Services_Exception_Denied
     * @throws Services_Exception_NotFound
     */
    public function action_remove($input)
    {
        Services_Exception_Denied::checkGlobal('admin_users');

        $ruleId = $input->ruleId->int();
        $confirm = $input->confirm->int();

        $rule = IDS_Rule::getRule($ruleId);

        if (! $rule) {
            throw new Services_Exception_NotFound();
        }

        $util = new Services_Utilities();
        if ($util->isConfirmPost()) {
            $rule->delete();

            return [
                'ruleId' => 0,
            ];
        }

        return [
            'ruleId' => $ruleId,
        ];
    }
}
