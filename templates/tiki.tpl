{extends $global_extend_layout|default:'layout_view.tpl'}

{block name=title}
    {* Legacy template, no support for title block *}
{/block}

{block name=content}
    {$mid_data}
{/block}
