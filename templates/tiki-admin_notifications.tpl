{title help="Mail Notifications"}{tr}Mail notifications{/tr}{/title}

{if empty($prefs.sender_email)}
    {remarksbox type="warning" title="{tr}Warning{/tr}"}
        {tr}You need to set <a class="alert-link" href="tiki-admin.php?page=general">Sender Email</a> before creating email notifications{/tr}.
    {/remarksbox}
{/if}

<h2>{tr}Add notification{/tr}</h2>
<form action="tiki-admin_notifications.php" method="post">
    {ticket}
    <input type="hidden" name="find" value="{$find|escape}">
    <input type="hidden" name="sort_mode" value="{$sort_mode|escape}">
    {if $offset}<input type="hidden" name="offset" value="{$offset|escape}">{/if}
    {if $numrows ne $prefs.maxRecords and $numrows}<input type="hidden" name="numrows" value="{$numrows|escape}">{/if}
    <div class="mb-3 row">
        <label for="event" class="col-form-label col-sm-3">
            {tr}Event{/tr}
        </label>
        <div class="col-sm-9">
            <select id="event" name="event" class="form-control">
                {foreach from=$watches key=key item=watch}
                    <option value="{$key}">{$watch.label|escape}</option>
                {/foreach}
            </select>
        </div>
    </div>
    <div class="mb-3 row">
        <label for="destination" class="col-form-label col-sm-3">
            {tr}Destination{/tr}
        </label>
        <div class="col-sm-9">
            <select id="destination" name="destination" class="form-control">
                <option value="login" selected="selected">{tr}User{/tr}</option>
                <option value="email">{tr}Email{/tr}</option>
            </select>
            {jq}
            $("select[name='destination']").on("change", function () {
                $("#loginrow").hide();
                $("#emailrow").hide();
                $("input[name='login']").attr("disabled","disabled");
                $("input[name='email']").attr("disabled","disabled");
                $("#" + $("select[name='destination']").val() + "row").show();
                $("input[name='" + $("select[name='destination']").val() + "']").trigger("focus");
                $("input[name='" + $("select[name='destination']").val() + "']").removeAttr("disabled");
            }
            );
            {/jq}
        </div>
    </div>
    <div id="loginrow" class="mb-3 row">
        <label for="flogin" class="col-form-label col-sm-3">
            {tr}User{/tr}
        </label>
        <div class="col-sm-6">
            <input type="text" id="flogin" name="login" class="form-control" placeholder="{tr}Username{/tr}...">
            {autocomplete element='#flogin' type='username'}
        </div>
        <div class="col-sm-3">
            <a href="#" onclick="document.getElementById('flogin').value='{$user}'; return false;" class="btn btn-link">{tr}Myself{/tr}</a>
        </div>
    </div>
    <div class="mb-3 row" id="emailrow" style="display:none">
        <label for="femail" class="col-form-label col-sm-3">
            {tr}Email{/tr}
        </label>
        <div class="col-sm-9">
            <input type="text" id="femail" name="email" class="form-control" placeholder="{tr}Email{/tr}...">
            <div class="form-text">
                {tr}Note that a user is not notified for his or her own action{/tr}
            </div>
        </div>
    </div>
    <div class="submit text-center">
        <input type="submit" class="btn btn-primary" name="add" value="{tr}Add{/tr}"></td>
    </div>
</form>
<br>
<h2>{tr}Mail notifications{/tr}</h2>
{if $channels or ($find ne '')}
    {include file='find.tpl' find_show_num_rows='y'}
{/if}
<form method="get" action="tiki-admin_notifications.php">
    {ticket}
    <div class="table-responsive notifications-table">
        <table class="table table-striped table-hover">
            <tr>
                <th>
                    {if $channels}
                        {select_all checkbox_names='checked[]'}
                    {/if}
                </th>
                <th>{self_link _sort_arg="sort_mode" _sort_field="event"}{tr}Event{/tr}{/self_link}</th>
                <th>{self_link _sort_arg="sort_mode" _sort_field="object"}{tr}Object Id{/tr}{/self_link}</th>
                <th>{self_link _sort_arg="sort_mode" _sort_field="email"}{tr}Email{/tr}{/self_link}</th>
                <th>{self_link _sort_arg="sort_mode" _sort_field="user"}{tr}User / Group{/tr}{/self_link}</th>
                <th></th>
            </tr>

            {section name=user loop=$channels}
                <tr>
                    <td class="checkbox-cell">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" aria-label="{tr}Select{/tr}" name="checked[]" value="{$channels[user].watchtype}{$channels[user].watchId|escape}" {if $smarty.request.checked and in_array($channels[user].watchId,$smarty.request.checked)}checked="checked"{/if}>
                        </div>
                    </td>
                    <td class="text">{$channels[user].event}</td>
                    <td class="text">
                        {if $channels[user].url}
                            <a href="{$channels[user].url}" class="tips" title=":{$channels[user].title|escape}">{$channels[user].object|escape}</a>
                        {else}
                            {$channels[user].object|escape}
                        {/if}
                        </td>
                    <td class="email">
                        {if $channels[user].watchtype eq 'user'}
                            {$channels[user].email}
                        {else}
                            <em>{tr}Multiple{/tr}</em>
                        {/if}
                    </td>
                    <td class="text">
                        {if $channels[user].watchtype eq 'group'}
                            {icon name="group"}
                        {else}
                            {icon name="user"}
                        {/if}
                        {$channels[user].user|escape}
                    </td>
                    <td class="action">
                        {icon name="delete" class="tips" href="{$smarty.server.SCRIPT_NAME}?{query removeevent=$channels[user].watchId removetype=$channels[user].watchtype}" title=":{tr}Delete{/tr}" onclick="confirmPopup('{tr}Delete mail notification event?{/tr}', '{ticket mode=get}')"}
                    </td>
                </tr>
            {sectionelse}
                {norecords _colspan=6}
            {/section}
        </table>
    </div>
    {if $channels}
        <br>
        <div class="input-group col-sm-8">
            <select class="form-select" name="action">
                <option value="" selected="selected">
                    {tr}Select action to perform with checked{/tr}...
                </option>
                <option
                    value="delete"
                    class="confirm-popup"
                    data-confirm-text="{tr}Delete selected notification events?{/tr}"
                >
                    {tr}Remove{/tr}
                </option>
            </select>
            <button type="submit" class="btn btn-primary" onclick="confirmPopup()">
                    {tr}OK{/tr}
                </button>
        </div>
    {/if}
</form>

{pagination_links cant=$cant step=$maxRecords offset=$offset}{/pagination_links}

{if !empty($trackers)}
    <h2>{tr}Trackers Outbound Emails{/tr}</h2>
    <div class="table-responsive">
        <table class="table">
            {section name=ix loop=$trackers}
                <tr>
                    <td><a href="tiki-list_trackers.php?trackerId={$trackers[ix].trackerId}">{$trackers[ix].value|escape}</a></td>
                </tr>
            {/section}
        </table>
    </div>
{/if}

{if !empty($forums)}
    <h2>{tr}Forums Outbound Emails{/tr}</h2>
    <div class="table-responsive">
        <table class="table">
            {section name=ix loop=$forums}
                <tr>
                    <td><a href="tiki-admin_forums.php?forumId={$forums[ix].forumId}&amp;cookietab=2">{$forums[ix].outbound_address|escape}</a><br/></td>
                </tr>
            {/section}
        </table>
    </div>
{/if}
