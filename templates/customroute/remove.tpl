{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title|escape}{/title}
{/block}

{block name="content"}
{if $routeId}
    <form class="simple" method="post" action="{service controller=customroute action=remove}">
        <p>{tr _0=$from_path}Do you really want to remove the %0 route?{/tr}</p>
        <div class="submit">
            <input type="hidden" name="confirm" value="1">
            <input type="hidden" name="routeId" value="{$routeId|escape}">
            <input type="submit" class="btn btn-primary" value="{tr}Remove{/tr}">
        </div>
    </form>
{else}
    <a href="tiki-admin_routes.php">{tr}Back to tracker list{/tr}
{/if}
{/block}
