<?php

/**
 * Gets Category Id from the Category name
 */

function smarty_modifier_categid($category)
{
	return TikiLib::lib('categ')->get_category_id($category);
}
