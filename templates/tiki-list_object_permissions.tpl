{title}{tr}Object Permissions List{/tr}{/title}

<div class="t_navbar">
    {button href="tiki-objectpermissions.php" class="btn btn-link" _type="link" _icon_name="permission" _text="{tr}Manage permissions{/tr}"}
</div>

<br>
{if $all_groups|@count >= 5}
    {$size = 6}
{else}
    {$size = $all_groups|@count +1}
{/if}
<form method="post">
    <div class="clearfix">
        <legend>{tr}Group Filter{/tr}</legend>
        <fieldset>
            <div class="mb-3 row">
                <div class="col-lg-6">
                    <select class='form-control' multiple="multiple" id="filterGroup" name="filterGroup[]" size="{$size}">
                        <option value=""{if empty($filterGroup)}selected="selected"{/if}></option>
                        {foreach from=$all_groups item=gr}
                            <option value="{$gr|escape}" {if in_array($gr, $filterGroup)}selected="selected"{/if}>{$gr|escape}</option>
                        {/foreach}
                    </select>
                </div>
                <div class="col-lg-6">
                    <input type="submit" class="btn btn-primary" name="filter" value="{tr}Filter{/tr}">
                </div>
            </div>
        </fieldset>
    </div>
</form>
<br>
<legend>{tr}Object Permissions{/tr}</legend>
<ul class="nav nav-tabs" id="allperms">
    {foreach $res as $type => $content}
        <li class="nav-item">
            <a href="#{$type|strip:'_'}" data-bs-toggle="tab" class="nav-link">{$type|ucwords}</a>
        </li>
    {/foreach}
</ul>
<div class="tab-content">
    <br>
    {foreach $res as $type => $content}
        <div id="{$type|strip:'_'}" class="tab-pane">
            <ul class="nav nav-tabs" id="allperms">
                <li class="nav-item"><a href="#{$type|strip:'_'}-global" data-bs-toggle="tab" class="nav-link active">{tr}Global permissions{/tr} ({$content.default|@count})</a></li>
                <li class="nav-item"><a href="#{$type|strip:'_'}-object" data-bs-toggle="tab" class="nav-link">{tr}Object permissions{/tr} ({$content.objects|@count})</a></li>
                <li class="nav-item"><a href="#{$type|strip:'_'}-category" data-bs-toggle="tab" class="nav-link">{tr}Category permissions{/tr} ({$content.category|@count})</a></li>
            </ul>
            {* global permissions *}
            <div class="tab-content">
                    <div id="{$type|strip:'_'}-global" class="tab-pane active">
                        {if count($content.default)}
                            <form id="{$type|strip:'_'}-global" method="post">
                                {ticket}
                                {foreach from=$filterGroup item=f}
                                    <input type="hidden" name="filterGroup[]" value="{$f|escape}">
                                {/foreach}
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <tr>
                                            <th class="checkbox-cell">{select_all checkbox_names='groupPerm[]'}</th>
                                            <th>{tr}Group{/tr}</th>
                                            <th>{tr}Permission{/tr}</th>
                                        </tr>

                                        {foreach from=$content.default item=default}
                                            <tr>
                                                <td class="checkbox-cell"><input type="checkbox" name="groupPerm[]" value='{$default|json_encode|escape}' class="form-check-input" aria-label="{tr}Select{/tr}"></td>
                                                <td class="text">{$default.group|escape}</td>
                                                <td class="text">{$default.perm|escape}</td>
                                            </tr>
                                        {/foreach}
                                    </table>
                                </div>
                                <legend>{tr}Perform action with selected permissions:{/tr}</legend>
                                <div class="mb-3 row">
                                    <label for="delete" class="col-lg-4 col-form-label">
                                        {tr}Delete{/tr}
                                    </label>
                                    <div class="col-lg-2">
                                        <button class="btn btn-danger" name="delete" onclick="confirmPopup()" value="delete">
                                            {tr}OK{/tr}
                                        </button>
                                    </div>
                                    <div class="col-lg-6"></div><br>
                                </div>
                                <div class="mb-3 row">
                                    <label for="duplicate" class="col-lg-4 col-form-label">
                                        {tr}Assign to this group{/tr}
                                    </label>
                                    <div class="col-lg-4">
                                        <div class="input-group">
                                            <input type="text" name="toGroup" class="form-control">
                                            <button class="btn btn-primary" name="duplicate" value="duplicate">
                                                {tr}OK{/tr}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        {else}<br>
                            {remarksbox title="{tr}Only default global permissions are being used.{/tr}"}{/remarksbox}
                        {/if}
                    </div><br>
                {* object permissions *}
                <div id="{$type|strip:'_'}-object" class="tab-pane">
                    {if count($content.objects)}
                        <form method="post">
                            {ticket}
                            {foreach from=$filterGroup item=f}<input type="hidden" name="filterGroup[]" value="{$f|escape}">{/foreach}
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <tr>
                                        <th class="checkbox-cell">{select_all checkbox_names='objectPerm[]'}</th>
                                        <th>{tr}Object{/tr}</th>
                                        <th>{tr}Group{/tr}</th>
                                        <th>{tr}Permission{/tr}</th>
                                        <th>{tr}Reason{/tr}</th>
                                    </tr>
                                    {foreach from=$content.objects item=object}
                                        {if !empty($object.special)}
                                            {foreach from=$object.special item=special}
                                                <tr>
                                                    <td class="checkbox-cell"><input type="checkbox" name="objectPerm[]" value='{$special|json_encode|escape}' class="form-check-input" aria-label="{tr}Select{/tr}"></td>
                                                    <td class="text">{object_link type=$special.objectType id=$special.objectId title=$special.objectName}</td>
                                                    <td class="text">{$special.group|escape}</td>
                                                    <td class="text">{$special.perm|escape}</td>
                                                    <td class="text">
                                                        {if !empty($special.objectId)}
                                                            {* I doubt this link worked in the past, permType was not specified *}
                                                            {permission_link mode=link type=$special.objectType id=$special.objectId title=$special.objectName label=$special.reason}
                                                        {else}
                                                            {$special.reason|escape}
                                                        {/if}
                                                        {if !empty($special.detail)}({$special.detail|escape}){/if}
                                                    </td>
                                                </tr>
                                            {/foreach}
                                        {/if}
                                    {/foreach}
                                </table>
                            </div>
                            <legend>{tr}Perform action with selected permissions:{/tr}</legend>
                            <div class="mb-3 row">
                                <label for="delete" class="col-lg-4 col-form-label">
                                    {tr}Delete{/tr}
                                </label>
                                <div class="col-lg-2">
                                    <button class="btn btn-danger" name="delete" value="delete">
                                        {tr}OK{/tr}
                                    </button>
                                </div>
                                <div class="col-lg-6"></div><br>
                            </div>
                            <div class="mb-3 row">
                                <label for="duplicate" class="col-lg-4 col-form-label">
                                    {tr}Assign to this group{/tr}
                                </label>
                                <div class="col-lg-4">
                                    <div class="input-group">
                                        <input type="text" name="toGroup" class="form-control">
                                        <span class="input-group-text">
                                            <button class="btn btn-primary" name="duplicate" value="duplicate">
                                                {tr}OK{/tr}
                                            </button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </form>
                    {else}<br>
                        {remarksbox title="{tr}No object permissions apply.{/tr}"}{/remarksbox}
                    {/if}
                </div>
                {* category permissions *}
                <div id="{$type|strip:'_'}-category" class="tab-pane">
                    {if count($content.category)}
                        <form method="post">
                            {ticket}
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <tr>
                                        <th>{tr}Object{/tr}</th>
                                        <th>{tr}Group{/tr}</th>
                                        <th>{tr}Permission{/tr}</th>
                                        <th>{tr}Reason{/tr}</th>
                                    </tr>
                                    {foreach from=$content.category item=object}
                                        {if !empty($object.category)}
                                            {foreach from=$object.category item=special}
                                                <tr>
                                                    <td class="text">{object_link type=$object.objectType objectId=$object.objectId}</td>
                                                    <td class="text">{$special.group|escape}</td>
                                                    <td class="text">{$special.perm|escape}</td>
                                                    <td class="text">
                                                        {if !empty($special.objectId)}
                                                            {* I doubt this link worked in the past, permType was not specified *}
                                                            {permission_link mode=icon type=$special.objectType id=$special.objectId title=$special.objectName}
                                                            {tr}{$special.reason|escape}:{/tr} {$special.objectName|escape}
                                                        {else}
                                                            {$special.reason|escape}: {$special.objectName}
                                                        {/if}
                                                        {if !empty($special.detail)}({$special.detail|escape}){/if}
                                                    </td>
                                                </tr>
                                            {/foreach}
                                        {/if}
                                    {/foreach}
                                </table>
                            </div>
                        </form>
                    {else}<br>
                        {remarksbox title="{tr}No category permissions apply.{/tr}"}{/remarksbox}
                    {/if}
                </div>
            </div>
        </div>
    {/foreach}
</div>
