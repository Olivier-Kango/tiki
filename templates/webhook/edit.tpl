{extends 'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
    <form method="post" action="{service controller=webhook action=update webhookId=$webhook.webhookId}">
        {include file='webhook/form.tpl'}
        <div class="submit">
            <input
                type="submit"
                class="btn btn-primary"
                value="{tr}Update{/tr}"
            >
        </div>
    </form>
{/block}
