{if not empty($item.children)}
    <li class="nav-item dropdown{if $item.selected|default:null} active{/if} {$item.class|escape}">
        <a href="{$item.sefurl|escape}" class="{if $sub|default:false}dropdown-item{else}nav-link{/if} dropdown-toggle" data-bs-toggle="dropdown">{tr}{$item.name}{/tr}</a>
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
        <a class="{if $sub|default:false}dropdown-item{else}nav-link{/if}" href="{$item.sefurl|escape}">{tr}{$item.name}{/tr}</a>
    </li>
{/if}