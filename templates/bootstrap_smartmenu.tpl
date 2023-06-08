{if not empty($item.children)}
    <li class="nav-item dropdown{if $item.selected|default:null} active{/if} {$item.class|escape} {if $module_params.megamenu eq 'y' and $module_params.megamenu_static eq 'y' }static{/if}">
        <a href="{$item.sefurl|escape}" class="{if $sub|default:false}dropdown-item{else}nav-link{/if} dropdown-toggle" data-bs-toggle="dropdown">
            {if $prefs.menus_items_icons eq "y"}
                <span 
                    data-preset="icon-picker" 
                    tabindex="0" 
                    role="button" 
                    data-bs-toggle="popover" 
                    data-bs-trigger="focus" 
                    title="Pick an icon" 
                    data-icon-for="{$item.optionId}" 
                    data-icon-editable="{if $prefs.theme_iconeditable eq "y" AND $tiki_p_admin eq 'y'}yes{/if}"
                >
                {* here we display the icon html passed constructed from function.menu.php *}
                {* TODO Add a way to modify the look: eg. the size,color,position etc*}
                {* Refer to the style section in this file to know how this is done manually*}
                {$menu_icons_html[$item.optionId]}
                </span>
            {/if}    
            {tr}{$item.name}{/tr}
        </a>
        {if $item.sectionLevel eq 0 and $module_params.megamenu eq 'y'}
            <ul class="dropdown-menu mega-menu">
                <li class="mega-menu--inner-container row mx-0">
                    <ul class="mega-menu--item-container {if $module_params.megamenu_images eq 'y' and $item.image} col-sm-9{else} col-sm-12{/if} pd-0">
                        {foreach from=$item.children item=sub}
                            {include file='bootstrap_smartmenu_megamenu_children.tpl' item=$sub sub=true}
                        {/foreach}
                    </ul>
                    {if $module_params.megamenu_images eq 'y' and $item.image}*}
                        <div class="mega-menu-image col-sm-3 pe-0">
                            {* Test image link - https://picsum.photos/300/300 *}
                            <img src="{$item.image}" alt="Megamenu image" />
                        </div>
                    {/if}
                </li>
            </ul>
        {else}
            <ul class="dropdown-menu">
                {foreach from=$item.children item=sub}
                    {include file='bootstrap_smartmenu_children.tpl' item=$sub sub=true}
                {/foreach}
            </ul>
        {/if}
    </li>
{else}
    <li class="nav-item {$item.class|escape}{if $item.selected|default:null} active{/if}">
        <a class="{if $sub|default:false}dropdown-item{else}nav-link{/if}" href="{$item.sefurl|escape}">
            {if $prefs.menus_items_icons eq "y"}
                <span
                    data-preset="icon-picker" 
                    tabindex="0" 
                    role="button" 
                    data-bs-toggle="popover" 
                    data-bs-trigger="focus" 
                    title="Pick an icon" 
                    data-icon-for="{$item.optionId}" 
                    data-icon-editable="{if $prefs.theme_iconeditable eq "y" AND $tiki_p_admin eq 'y'}yes{/if}"
                >
                {* here we display the icon html passed constructed from function.menu.php *}
                {* TODO Add a way to modify the look: eg. the size,color,position etc*}
                {* Refer to the style section the bootstrap_menu.tpl file to know how this is done manually*}
                {$menu_icons_html[$item.optionId]}
                </span>
            {/if}
            {tr}{$item.name}{/tr}
        </a>
    </li>
{/if}
