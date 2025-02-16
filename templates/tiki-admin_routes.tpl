{title help="Custom Route" admpage="sefurl" url="tiki-admin_routes.php"}{tr}Custom Route{/tr}{/title}
<div class="t_navbar mb-4">
    {if isset($route.id)}
        {button href="?add=1" class="btn btn-primary" _text="{tr}Add a new Custom Route{/tr}"}
    {/if}

</div>
{tabset name='tabs_admin_custom_routes'}

    {* ---------------------- tab with list -------------------- *}
{if $routes|count > 0}
    {tab name="{tr}Custom Routes{/tr}"}
        <div id="admin_custom_routes-div">
            <div class="{if $js}table-responsive {/if}ts-wrapperdiv">
                {* Use css menus as fallback for item dropdown action menu if javascript is not being used *}
                <table id="admin_custom_routes" class="table normal table-striped table-hover" data-count="{$routes|count}">
                    <thead>
                    <tr>
                        <th>
                            {tr}From{/tr}
                        </th>
                        <th>
                            {tr}Description{/tr}
                        </th>
                        <th>
                            {tr}Type{/tr}
                        </th>
                        <th>
                            {tr}Short URL{/tr}
                        </th>
                        <th>
                            {tr}Active{/tr}
                        </th>
                        <th id="actions"></th>
                    </tr>
                    </thead>

                    <tbody>
                    {section name=route loop=$routes}
                        <tr>
                            <td class="route_from">
                                <a class="link tips"
                                   href="tiki-admin_routes.php?route={$routes[route].id}{if $prefs.feature_tabs ne 'y'}#2{/if}"
                                   {tr}Edit route settings{/tr}"
                                >
                                {$routes[route].from|escape}
                                </a>
                            </td>
                            <td class="route_description">
                                {$routes[route].description|escape}
                            </td>
                            <td class="route_type">
                                {$routes[route].type|escape}
                            </td>
                            <td class="route_short_url">
                                {icon name="{if $routes[route].short_url}check{else}close{/if}" alt="{$routes[route].short_url}"}
                            </td>
                            <td class="route_active">
                                {icon name="{if $routes[route].active}check{else}close{/if}" alt="{$routes[route].active}"}
                            </td>
                            <td class="action">
                                {actions}
                                    {strip}
                                        <action>
                                            <a href="{query _type='relative' route=$routes[route].id}">
                                                {icon name="edit" _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
                                            </a>
                                        </action>
                                        <action>
                                            <a href="{bootstrap_modal controller=customroute action=remove routeId=$routes[route].id}">
                                                {icon name="remove" _menu_text='y' _menu_icon='y' alt="{tr}Remove{/tr}"}
                                            </a>
                                        </action>
                                    {/strip}
                                {/actions}
                            </td>
                        </tr>
                    {/section}
                    </tbody>
                </table>
            </div>
        </div>
    {/tab}
{/if}
    {* ---------------------- tab with form -------------------- *}
    <a id="tab2"></a>
{if isset($route.id) && $route.id}
    {$add_edit_route_tablabel = "{tr}Edit route{/tr}"}
    {$schedulename = "<i>{$route.name|escape}</i>"}
{else}
    {$add_edit_route_tablabel = "{tr}Add a new route{/tr}"}
    {$schedulename = ""}
{/if}

{tab name="{$add_edit_route_tablabel} {$schedulename}"}
    <br><br>
    <form action="tiki-admin_routes.php" method="post"
          enctype="multipart/form-data" name="RegForm" autocomplete="off">
        {ticket}

        {remarksbox type="note" title="{tr}Information{/tr}"}
            {tr}If you select router type "To tracker item by field value", you must use a regular expression in the From field value.{/tr}
            {tr}The users will get redirected to the tracker item that has a field that matches the value found in the URL.{/tr}
            <br>
            {tr}Example:{/tr}
            <br/>
            {tr}|^page(\d+)$| will redirect /page10 to the tracker item where the selected value is 10.{/tr}
        {/remarksbox}

        <div class="tiki-form-group row">
            <label class="col-sm-3 col-md-2 col-form-label" for="router_type">{tr}Router Type{/tr} *</label>
            <div class="col-sm-9 col-md-10">
                <select id='router_type' class="form-select" name='router_type'>
                    <option value=''></option>
                    {html_options options=$routerTypes selected=$route.type}
                </select>
            </div>
        </div>

        <div class="tiki-form-group row">
            <label class="col-sm-3 col-md-2 col-form-label" for="router_from">{tr}From{/tr} *</label>
            <div class="col-sm-9 col-md-10">
                <input id='router_from' class="form-control" name='router_from' value="{$route.from}">
            </div>
        </div>

        {foreach from=$routerTypes key=className item=itemName}
            {router_params name=$className params=$route.params}
        {/foreach}

        <div class="tiki-form-group row">
            <label class="col-sm-3 col-md-2 col-form-label" for="router_description">{tr}Description{/tr}</label>
            <div class="col-sm-9 col-md-10">

                <input id='router_description' class="form-control" name='router_description' value="{$route.description}">
            </div>
        </div>


        <div class="tiki-form-group row">
            <label class="col-sm-3 col-md-2 col-form-label" for="router_active">{tr}Active{/tr}</label>
            <div class="col-sm-9 col-md-10">

                <input type="checkbox" class="form-check-input" id='router_active' name='router_active' {if !empty($route.active)}checked{/if}>
            </div>
        </div>

        <div class="tiki-form-group row">
            <label class="col-sm-3 col-md-2 col-form-label" for="router_short_url">{tr}Short URL{/tr}</label>
            <div class="col-sm-9 col-md-10">
                <input type="checkbox" class="form-check-input" id='router_short_url' name='router_short_url' {if !empty($route.short_url)}checked{/if}>
                <span id="helpBlock" class="form-text">{tr}Check this option to set route as a Short URL.{/tr}</span>
            </div>
        </div>

        <div class="tiki-form-group row">
            <div class="col-sm-7 col-md-6 offset-sm-3 offset-md-2">
                <input type="hidden" name="load_options" value="0">
                {if isset($route.id) && $route.id}
                    <input type="hidden" name="route" value="{$route.id|escape}">
                    <input type="hidden" name="editroute" value="1">
                    <input type="submit" class="btn btn-primary" name="save" value="{tr}Save{/tr}">
                {else}
                    <input type="submit" class="btn btn-secondary" name="new_route" value="{tr}Add{/tr}">
                {/if}
            </div>
        </div>

    </form>
{/tab}
{/tabset}

{jq}

    var selectedRouterType = $('select[name="router_type"]').val();
    $('div [data-task-name="'+selectedRouterType+'"]').show();

    $('select[name="router_type"]').on('change', function() {
        var taskName = this.value;
        $('div [data-task-name]:not([data-task-name="'+taskName+'"])').hide();
        $('div [data-task-name="'+taskName+'"]').show();
    });

    $('select[name="tikiobject_type"]').on('change', function() {
        $('input[name="load_options"]').val(1);
        $(this).parents('form').trigger("submit");
    });

    $('select[name="trackerfield_tracker"]').on('change', function() {
        $('input[name="load_options"]').val(1);
        $(this).parents('form').trigger("submit");
    });

{/jq}
