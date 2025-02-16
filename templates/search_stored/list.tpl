{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title url=$url}{$title|escape}{/title}
{/block}

{block name="content"}
{if $queryId}
    {if $description}
        <div class="card bg-body-tertiary">
            <div class="card-body">
                {$description}
            </div>
        </div>
    {/if}
    {$results}

    <h2>{tr}Other Queries{/tr}</h2>
{/if}
<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>{tr}Label{/tr}</th>
                <th>{tr}Last Modification{/tr}</th>
                <th>{tr}Actions{/tr}</th>
            </tr>
        </thead>
        <tbody>
            {foreach $queries as $q}
                <tr{if $q.queryId eq $queryId} class="active"{/if}>
                    <td>
                        <a href="{service controller=search_stored action=list queryId=$q.queryId}">{$q.label|escape}</a>
                        <span class="label {$priorities[$q.priority].class|escape}">{$priorities[$q.priority].label|escape}</span>
                    </td>
                    <td>
                        {if !empty($q.lastModif)}
                            {$q.lastModif|tiki_short_datetime}
                        {else}
                            {tr}Never{/tr}
                        {/if}
                    </td>
                    <td>
                        <a class="btn btn-primary btn-sm" href="{bootstrap_modal controller=search_stored action=edit queryId=$q.queryId}">{icon name="edit"} {tr}Edit{/tr}</a>
                        <a class="btn btn-danger btn-sm" href="{bootstrap_modal controller=search_stored action=delete queryId=$q.queryId}">{icon name="delete"} {tr}Delete{/tr}</a>
                    </td>
                </tr>
            {foreachelse}
                <tr>
                    <td>
                        {tr}No stored queries!{/tr}
                    </td>
                    <td>{tr}Never{/tr}</td>
                    <td>&nbsp;</td>
                </tr>
            {/foreach}
        </tbody>
    </table>
</div>
{/block}
