{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title|escape}{/title}
{/block}

{block name="content"}
    {if $success}
        {remarksbox type=feedback title="{tr}Operation Completed{/tr}"}
            <p>{tr _0=$info.account}%0 was removed{/tr}</p>
            <a class="btn btn-success" href="tiki-admin_mailin.php">{tr}Return to Query List{/tr}</a>
        {/remarksbox}
    {else}
        <form method="post" action="{service controller=mailin action=remove_account}">
            {ticket mode=confirm}
            <div class="mb-3 row">{tr _0=$info.account}Do you really want to remove the %0 account?{/tr}</div>
            <div class="mb-3 submit">
                <input type="hidden" name="accountId" value="{$info.accountId|escape}"/>
                <input class="btn btn-danger" type="submit" value="{tr}Delete Account{/tr}"/>
            </div>
        </form>
    {/if}
{/block}
