{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
    <p style="background: #ccc">{$info|nl2br}</p>
{/block}
