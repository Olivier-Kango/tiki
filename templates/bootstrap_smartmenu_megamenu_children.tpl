{if not empty($item.children)}
    <li class="mega-menu--item mega-menu--item-level-{$item.sectionLevel}">
        <a href="{$item.sefurl|escape}" class="dropdown-item" data-bs-toggle="dropdown">{tr}{$item.name}{/tr}</a>
        <ul class="">
            {foreach from=$item.children item=sub}
                {include file='bootstrap_smartmenu_megamenu_children.tpl' item=$sub sub=true}
            {/foreach}
        </ul>
    </li>
{else}
    <li class="mega-menu--item mega-menu--item-level-{$item.sectionLevel}">
        {if !empty($item.block)}
            <div class="block--container">
            {if $prefs.menus_items_icons eq "y"}
                <span
                    data-preset="icon-picker"
                    tabindex="0"
                    role="button"
                    data-bs-toggle="popover"
                    data-bs-trigger="focus"
                    title="Pick an icon"
                    data-icon-for="{$item.sefurl}"
                    data-icon-editable="{if $prefs.theme_iconeditable eq "y" AND $tiki_p_admin eq 'y'}yes{/if}"
                >
                {* here we display the icon html passed constructed from function.menu.php *}
                {* TODO Add a way to modify the look: eg. the size,color,position etc*}
                {* Refer to the style section the bootstrap_menu.tpl file to know how this is done manually*}
                {$menu_icons_html[$item.sefurl]}
                </span>
            {/if}
            {tr}{$item.name}{/tr}</div></a> {* </a> added to close the anchor in the menu list item -- g_c-l *}
        {else}
            <a class="dropdown-item" href="{$item.sefurl|escape}">{tr}{$item.name}{/tr}</a>
        {/if}
    </li>
{/if}
