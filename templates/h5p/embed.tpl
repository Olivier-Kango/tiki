{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$h5p_title|escape}{/title}
{/block}

{block name="content"}
    {$html}
{/block}
