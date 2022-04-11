{if not empty($item.children)}
    <li class="mega-menu--item mega-menu--item-level-{$item.sectionLevel}">
        <a href="{$item.sefurl|escape}" class="" data-bs-toggle="dropdown">{tr}{$item.name}{/tr}</a>
        <ul class="">
            {foreach from=$item.children item=sub}
                {include file='bootstrap_smartmenu_megamenu_children.tpl' item=$sub sub=true}
            {/foreach}
        </ul>
    </li>
{else}
    <li class="mega-menu--item mega-menu--item-level-{$item.sectionLevel}">
        {if $item.block}
            <div class="block--container">{tr}{$item.name}{/tr}</div>
        {else}
            <a class="" href="{$item.sefurl|escape}">{tr}{$item.name}{/tr}</a>
        {/if}
    </li>
{/if}
