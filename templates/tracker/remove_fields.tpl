{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title|escape}{/title}
{/block}

{block name="content"}
{if $status neq 'DONE'}
    <form method="post" action="{service controller=tracker action=remove_fields}">
        <input type="hidden" name="confirm" value="1">
        <input type="hidden" name="trackerId" value="{$trackerId|escape}">
        {foreach from=$fields item=fieldId}
            <input type="hidden" name="fields[]" value="{$fieldId|escape}">
        {/foreach}
        <p>
            {tr}Are you sure you want to remove the fields? Data will be lost.{/tr}
        </p>
        <div class="submit">
            <input type="submit" class="btn btn-danger" value="{tr}Remove Fields{/tr}">
        </div>
    </form>
{/if}
<a href="{$trackerId|sefurl:'trackerfields'}">{tr}Return to field administration{/tr}</a>
{/block}
