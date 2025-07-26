{capture name=add_info}{strip}
    <table class="table-condensed">
        {if !empty($item->description)}
            <tr>
                <th class="text-end">{tr}Description{/tr}</th>
                <td>{$item->description|escape}</td>
            </tr>
        {/if}
        <tr>
            <th class="text-end">{tr}Status{/tr}</th>
            <td>{$item->stateString|escape}</td>
        </tr>
        <tr>
            <th class="text-end">{tr}Video ID{/tr}</th>
            <td><pre style="margin:0">{$item->uuid|escape}</pre></td>
        </tr>
        <tr>
            <th class="text-end">{tr}Duration{/tr}</th>
            <td>{$item->duration|escape}s</td>
        </tr>
        <tr>
            <th class="text-end">{tr}Views{/tr}</th>
            <td>{$item->views|escape}</td>
        </tr>
        <tr>
            <th class="text-end">{tr}Wiki plugin code{/tr}</th>
            <td> </td>
        </tr>
        <tr>
            <td colspan="2"><pre style="margin:0;font-size:1.1em;">{ldelim}peertube id="{$item->uuid}"{rdelim}</pre></td>
        </tr>
    </table>
{/strip}{/capture}
