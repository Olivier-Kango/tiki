{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="content"}
    <table class="table">
        <tr>
            <th>{tr}Label{/tr}</th>
        </tr>
        {foreach $rewards as $key => $reward}
            <tr>
                <td>
                    <a class="edit" href="#" data-element="{$key|escape}">{$reward.label|escape}</a>
                    {if !empty($reward.hidden)}
                        <span class="label label-info">{tr}Hidden{/tr}</span>
                    {/if}
                    <a class="delete float-sm-end text-danger" href="#" data-element="{$key|escape}">{icon name="delete"} {tr}Delete{/tr}</a>
                </td>
            </tr>
        {foreachelse}
            <tr>
                <td colspan="1">{tr}No rewards yet!{/tr}</td>
            </tr>
        {/foreach}
    </table>
    <button class="btn btn-primary add float-sm-end">{icon name="add"} {tr}Add Reward{/tr}</button>
    <input type="hidden" name="rewards" value="{$rewards|json_encode|escape}">
{/block}
