{extends "layout_view.tpl"}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="navigation"}
    {include file='manager/nav.tpl'}
{/block}

{block name="content"}
    <p class="p-3" style="background: #ccc;">{$info|nl2br}</p>
{/block}
