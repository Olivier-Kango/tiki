{* param: $relationValue from a relations field *}
{if not empty($relationValue)}
	{$ids = []}
    {* *}
	{if preg_match_all('/(.+?)\:([^\v]+)$/mis', $relationValue, $matches, 2)}{* PREG_SET_ORDER = 2 *}
		{if isset($smarty.request.debug)}<pre style="display: none;" class="matches">{$matches|var_dump}</pre>{/if}
		{foreach $matches as $match}
			{if not in_array(trim($match[2]), $ids)}
				{$ids[] = trim($match[2])}
			{/if}
		{/foreach}

        {wikiplugin _name='list'}{literal}
            {filter type="trackeritem"}
            {filter field="object_id" content="{/literal}{' OR '|implode:$ids}{literal}"}
        {/literal}{/wikiplugin}

	{/if}
{/if}
