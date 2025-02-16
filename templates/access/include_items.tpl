<div class="mb-3 row mx-0">
    <h5 class="w-100">{$customMsg|escape}</h5>
    {if isset($extra["warning"])}
        <div class="alert alert-warning" role="alert">
            {$extra["warning"]|escape}
        </div>
    {/if}
    {if isset($items) && $items|count > 0}
        {if $items|count < 16}
            <ul id="list-items">
                {foreach $items as $name}
                    <li class="mx-4">
                        {$name|escape}
                    </li>
                {/foreach}
            </ul>
        {else}
            {foreach $items as $name}
                {$name|escape}{if !$name@last}, {/if}
            {/foreach}
        {/if}
    {/if}
</div>
