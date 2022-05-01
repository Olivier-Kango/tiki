{* $Id$ *}
{* changes 24.01.2021 by bob_romoxi *}
{* if for controls and pagination *}
{if $carousel and $carousel.id}{$containerId = $carousel.id}{else}{$containerId = 'wp_list_carousel'}{/if}
{if $carousel and $carousel.mode}{$mode = $carousel.mode}{else}{$mode = ''}{/if}
<div id="{$containerId}" class="carousel slide {$mode}" data-bs-ride="carousel"
        {if $carousel and $carousel.interval} data-bs-interval="{$carousel.interval}"{/if}
        {if $carousel and isset($carousel.pause)} data-pause="{$carousel.pause}"{/if}
        {if $carousel and isset($carousel.wrap)} data-wrap="{$carousel.wrap}"{/if}
>
    {if $carousel and (empty($carousel.indicators) or $carousel.indicators neq 'n')}
        {* Indicators *}
        <ol class="carousel-indicators">
            {foreach from=$results item=row}
                <li data-bs-target="#{$containerId}" data-bs-slide-to="{$row@index}"{if $row@index eq 0} class="active"{/if}></li>
            {/foreach}
        </ol>
    {/if}

    {* Wrapper for slides *}
    <div class="carousel-inner">
        {foreach from=$results item=row}
            <div class="carousel-item{if $row@index eq 0} active{/if}">
                {if $body and $body.field}
                    {if $body.mode eq 'raw'}
                        {$row[$body.field]}
                    {else}
                        {$row[$body.field]|escape}
                    {/if}
                {/if}

                <div class="carousel-caption d-none d-md-block">
                    {if $caption and $caption.field}
                        {if $caption.mode eq 'raw'}
                            {$row[$caption.field]}
                        {else}
                            {$row[$caption.field]|escape}
                        {/if}
                    {/if}
                </div>
            </div>
        {/foreach}
    </div>

    {* Controls *}
    {if $carousel and $carousel.controls neq 'n'}
        <a class="carousel-control-prev" href="#{$containerId}" role="button" data-bs-slide="prev">
            {icon name='chevron-left'}
            <span class="sr-only">Previous</span>
        </a>
        <a class="carousel-control-next" href="#{$containerId}" role="button" data-bs-slide="next">
            {icon name='chevron-right'}
            <span class="sr-only">Next</span>
        </a>
    {/if}

</div>
{if $carousel.pagination neq 'n'}
    {pagination_links resultset=$results}{/pagination_links}
{/if}
