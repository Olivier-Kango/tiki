<?php

namespace Tiki\Lib\core\Toolbar;

use TikiLib;

class ToolbarFileGalleryFile extends ToolbarFileGallery
{

    public function getOnClick(): string
    {
        $smarty = TikiLib::lib('smarty');
        $smarty->loadPlugin('smarty_function_filegal_manager_url');
        return 'openFgalsWindow(\'' . htmlentities(
                smarty_function_filegal_manager_url(['area_id' => $this->domElementId], $smarty->getEmptyInternalTemplate())
            )
            . '&insertion_syntax=file\', true);';
    }
}
