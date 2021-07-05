<?php

function smarty_modifier_packageitemid($token)
{

    $api = new \Tiki\Package\Extension\Api();
        return $api->getItemIdFromToken($token);
}
