{title help="Admin DSN"}{tr}Admin Content Sources{/tr}{/title}

{remarksbox type="tip" title="{tr}Tip{/tr}"}
    {tr}Use Admin DSN to define the database to be used by the SQL plugin.{/tr}
{/remarksbox}

<h2>{tr}Create/edit DSN{/tr}</h2>
<form action="tiki-admin_dsn.php" method="post">
    {ticket}
    <input type="hidden" name="dsnId" value="{$dsnId|escape}">
    <div class="mb-3 row">
        <label class="col-sm-3 col-form-label" for="name">{tr}Name{/tr}</label>
        <div class="col-sm-9">
            <input type="text" maxlength="255" name="name" id="name" class="form-control" value="{$info.name|escape}">
        </div>
    </div>
    <div class="mb-3 row">
        <label class="col-sm-3 col-form-label" for="dsn">{tr}DSN{/tr}</label>
        <div class="col-sm-9">
            <input type="text" maxlength="255" class="form-control" name="dsn" id="dsn" value="{$info.dsn|escape}">
        </div>
    </div>
    <div class="mb-3 text-center">
        <input type="submit" class="btn btn-primary" name="save" value="{tr}Save{/tr}">
    </div>
</form>
<h2>{tr}DSN{/tr}</h2>
<div class="{if $js}table-responsive{/if}"> {* table-responsive class cuts off css drop-down menus *}
    <table class="table table-striped table-hover">
        <tr>
            <th>
                <a href="tiki-admin_dsn.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'name_desc'}name_asc{else}name_desc{/if}">{tr}Name{/tr}</a>
            </th>
            <th>
                <a href="tiki-admin_dsn.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'dsn_desc'}dsn_asc{else}dsn_desc{/if}">{tr}DSN{/tr}</a>
            </th>
            <th></th>
        </tr>

        <tr>
            <td class="text">{tr}Local (Tiki database){/tr}</td>
            <td class="text">{tr}See db/local.php{/tr}</td>
            <td class="action" style="width:20px;">
                {permission_link mode=icon type=dsn id=local title=local}
            </td>
        </tr>
        {section name=user loop=$channels}
            <tr>
                <td class="text">{$channels[user].name}</td>
                <td class="text">{$channels[user].dsn}</td>
                <td class="action">
                    {actions}
                        {strip}
                            <action>
                                <a href="tiki-admin_dsn.php?offset={$offset}&amp;sort_mode={$sort_mode}&amp;dsnId={$channels[user].dsnId}">
                                    {icon name='edit' _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
                                </a>
                            </action>
                            <action>
                                {permission_link mode=text type=dsn id=$channels[user].name title=$channels[user].name}
                            </action>
                            <action>
                                <a id="delete-link" href="tiki-admin_dsn.php?offset={$offset}&amp;sort_mode={$sort_mode}&amp;remove={$channels[user].dsnId}" onclick="confirmPopup('{tr}Remove DSN?{/tr}', '{ticket mode=get}')">
                                    {icon name='remove' _menu_text='y' _menu_icon='y' alt="{tr}Remove{/tr}"}
                                </a>
                            </action>
                        {/strip}
                    {/actions}
                </td>
            </tr>
        {/section}
    </table>
</div>

{pagination_links cant=$cant_pages step=$prefs.maxRecords offset=$offset}{/pagination_links}

<h2>{tr}Content Authentication{/tr}</h2>
<form id="source-form" method="post" action="{service controller=auth_source}">
    {ticket}
    <fieldset>
        <legend>{tr}Identification{/tr}</legend>
        <div class="mb-3 row">
            <label class="col-sm-3 col-form-label">{tr}Identifier{/tr}</label>
            <div class="col-sm-4">
                <select name="existing" class="form-select">
                    <option value="">{tr}New{/tr}</option>
                </select>
            </div>
            <div class="col-sm-4">
                <input type="text" name="identifier" class="form-control">
            </div>
        </div>
        <div class="mb-3 row">
            <label class="col-sm-3 col-form-label" for="url">{tr}URL{/tr}</label>
            <div class="col-sm-4">
                <input type="url" name="url" id="url" class="form-control" />
            </div>
        </div>
        <div class="mb-3 row">
            <label class="col-sm-3 col-form-label" for="method">{tr}Type{/tr}</label>
            <div class="col-sm-4">
                <select name="method" id="method" class="form-select">
                    <option value="basic">{tr}HTTP Basic{/tr}</option>
                    <option value="post">{tr}HTTP Session / Login{/tr}</option>
                    <option value="get">{tr}HTTP Session / Visit{/tr}</option>
                    <option value="header">{tr}HTTP Header{/tr}</option>
                    <option value="body">{tr}Request body{/tr}</option>
                </select>
            </div>
        </div>
        <div class="mb-3 row">
            <label class="col-sm-3 col-form-label" for="method">
                {tr}User{/tr}
                <a class="tikihelp text-info" title="{tr}User:{/tr} {tr}Limit usage of this content authentication to a specific user, e.g. when importing/exporting a tracker via tabular with remote API. Leave blank for usage regardless of user context.{/tr}">
                    {icon name=information}
                </a>
            </label>
            <div class="col-sm-4">
                {user_selector id="user_selector_auth" realnames="n" user="" allowNone="y"}
            </div>
        </div>
    </fieldset>
    <fieldset class="method basic">
        <legend>{tr}HTTP Basic{/tr}</legend>
        <div class="mb-3 row">
            <label class="col-sm-3 col-form-label" for="basic_username">{tr}Username{/tr}</label>
            <div class="col-sm-9">
                <input type="text" name="basic_username" id="basic_username" class="form-control" autocomplete="off">
            </div>
        </div>
        <div class="mb-3 row">
            <label class="col-sm-3 col-form-label" for="basic_password">{tr}Password{/tr}</label>
            <div class="col-sm-9">
                <input type="password" name="basic_password" id="basic_password" class="form-control" autocomplete="new-password">
            </div>
        </div>
    </fieldset>
    <fieldset class="method post">
        <legend>{tr}HTTP Session / Login{/tr}</legend>
        <div class="mb-3 row">
            <label class="col-sm-3 col-form-label" for="post_url">{tr}URL{/tr}</label>
            <div class="col-sm-9">
                <input type="url" name="post_url" id="post_url" class="form-control">
            </div>
            <h5>{tr}Arguments{/tr}</h5>
        </div>
        <div class="mb-3 row post-arg-form">
            <div class="col-sm-3">
                <input type="text" name="post_new_field" class="form-control" placeholder="{tr}Name{/tr}">
            </div>
            <div class="col-sm-8">
                <input type="text" name="post_new_value" class="form-control" placeholder="{tr}Value{/tr}">
            </div>
            <div class="col-sm-1 pt-1">
                <input type="submit" class="btn btn-primary btn-sm" name="post_new_add" value="{tr}Add{/tr}">
            </div>
        </div>
    </fieldset>
    <fieldset class="method get">
        <legend>{tr}HTTP Session / Visit{/tr}</legend>
        <div class="mb-3 row">
            <label class="col-sm-3 col-form-label" for="get_url">{tr}URL{/tr}</label>
            <div class="col-sm-9">
                <input type="url" name="get_url" id="get_url" class="form-control">
            </div>
        </div>
    </fieldset>
    <fieldset class="method header">
        <legend>{tr}HTTP Header{/tr}</legend>
        <div class="mb-3 row">
            <label class="col-sm-3 col-form-label" for="header_name">{tr}Header Name{/tr}</label>
            <div class="col-sm-9">
                <input type="text" name="header_name" id="header_name" class="form-control" placeholder="e.g. Authorization">
            </div>
        </div>
        <div class="mb-3 row">
            <label class="col-sm-3 col-form-label" for="header">{tr}Header Value{/tr}</label>
            <div class="col-sm-9">
                <input type="text" name="header" id="header" class="form-control" placeholder="e.g. Bearer TOKEN-HERE">
            </div>
        </div>
    </fieldset>
    <fieldset class="method body">
        <legend>{tr}Request body{/tr}</legend>
        <div class="mb-3 row">
            <h5>{tr}Arguments appended with each request{/tr}</h5>
        </div>
        <div class="mb-3 row body-arg-form">
            <div class="col-sm-3">
                <input type="text" name="body_new_field" class="form-control" placeholder="{tr}Name{/tr}">
            </div>
            <div class="col-sm-8">
                <input type="text" name="body_new_value" class="form-control" placeholder="{tr}Value{/tr}">
            </div>
            <div class="col-sm-1 pt-1">
                <input type="submit" class="btn btn-primary btn-sm" name="body_new_add" value="{tr}Add{/tr}">
            </div>
        </div>
    </fieldset>
    <fieldset>
        <div class="mb-3 text-center">
            <input type="submit" class="btn btn-primary" name="save" value="{tr}Save{/tr}">
            <input type="submit" class="btn btn-danger" name="delete" value="{tr}Delete{/tr}">
        </div>
    </fieldset>
</form>
{jq}
$('#source-form').each(function () {
    var form = this,
        reload = function () {
            $('option.added', form).remove();
            $.getJSON($.service('auth_source', 'list'), function (entries) {
                $.each(entries, function (k, v) {
                    $(form.existing).append($('<option class="added"/>').text(v));
                });

                $(form.existing).trigger("change.select2");
            });
        },
        addPostRow = function (name, value, cl) {
            var row = $('<div class="mb-3 row ' + cl + '-arg">');
            row.append($('<label class="col-sm-3 col-form-label" for="' + name + '">').text(name));
            row.append($('<div class="col-sm-8 pt-2 overflow-hidden text-truncate" id="' + name + '">').text(value));
            row.append($('<div class="col-sm-1">{{icon name='remove' iclass='text-danger'}}</div>').css('cursor', 'pointer').on("click", function () {
                $(this).closest('div.row').remove();
                return false;
            }));
            $('fieldset.method.' + cl + ' .row.' + cl + '-arg-form', form).before(row);
        },
        fetchAuthentication = function(identifier) {
            $(form.identifier).hide();

            $.getJSON($.service('auth_source', 'fetch'), {
                identifier: identifier
            }, function (data) {
                var id = data.identifier;
                $(form.existing).val(id);
                $(form.identifier).val(id);
                $(form.method).val(data.method).trigger("change").trigger("change.select2");
                $(form.url).val(data.url);
                $(form.user).val(data.user);

                switch (data.method) {
                case 'basic':
                    $(form.basic_username).val(data.arguments.username);
                    $(form.basic_password).val(data.arguments.password);
                    break;
                case 'get':
                    $(form.get_url).val(data.arguments.url);
                    break;
                case 'post':
                    $(form.post_url).val(data.arguments.post_url);
                    $('fieldset.method.post .row.post-arg', form).remove();
                    $.each(data.arguments, function (key, value) {
                        if (key !== 'post_url') {
                            addPostRow(key, value, 'post');
                        }
                    });
                    break;
                case 'header':
                    $(form.header_name).val(data.arguments.header_name);
                    $(form.header).val(data.arguments.header);
                    break;
                case 'body':
                    $('fieldset.method.body .row.body-arg', form).remove();
                    $.each(data.arguments, function (key, value) {
                        addPostRow(key, value, 'body');
                    });
                    break;
                }
            });
        };

    $(form).on("submit", function () {
        return false;
    });

    $(form.existing).on("change", function () {
        var val = $(this).val();

        if (val.length) {
            fetchAuthentication($(form.existing).val());
        } else {
            $(form.identifier).show().val('').trigger("focus");
            $('input:not(:submit):not([name=ticket])', form).val('');
            $('fieldset.method.post .row.post-arg').remove();
            $('fieldset.method.body .row.body-arg').remove();
        }
    });

    $(form.method).on("change", function () {
        $('fieldset.method', form).hide();
        $('fieldset.method.' + $(this).val(), form).show();
    }).trigger("change");

    reload();

    $(form.save).on("click", function () {
        var data = {
            action: 'save',
            identifier: $(form.identifier).val(),
            url: $(form.url).val(),
            method: $(form.method).val(),
            user: $(form.user).val(),
            ticket: $(form.ticket).val()
        }, isNew = $(form.existing).val() === '';

        switch (data.method) {
        case 'basic':
            data['arguments~username'] = $(form.basic_username).val();
            data['arguments~password'] = $(form.basic_password).val();
            break;
        case 'get':
            data['arguments~url'] = $(form.get_url).val();
            break;
        case 'post':
            data['arguments~post_url'] = $(form.post_url).val();

            $('fieldset.method.post .post-arg').each(function () {
                data['arguments~' + $("label", this).text()] = $("div", this).first().text();
            });
            break;
        case 'header':
            data['arguments~header_name'] = $(form.header_name).val();
            data['arguments~header'] = $(form.header).val();
            break;
        case 'body':
            $('fieldset.method.body .body-arg').each(function () {
                data['arguments~' + $("label", this).text()] = $("div", this).first().text();
            });
            break;
        }

        $.post($(form).attr('action'), data, function () {
            if (isNew) {
                $(form.existing).append($('<option/>').text(data.identifier));
            }

            $(form.existing).val(data.identifier).trigger("change");
            $(form.existing).trigger("change.select2");
        }, 'json')
        .done(function (data) {
            location.href = location.href.replace(/\?.*$/, "") + '?identifier=' + encodeURIComponent(data.identifier);
        });
        return false;
    });

    $(form.delete).on("click", function () {
        if (confirm(tr('Delete authentication?'))) {
            $.post($(form).attr('action'), {
                action: 'delete',
                identifier: $(form.existing).val(),
                ticket: $(form.ticket).val()
            }, function () {
                $(form.existing).val('').trigger("change");
                reload();
            }, 'json')
            .done(function (data) {
                location.href = location.href.replace(/\?.*$/, "");
            });
            return false;
        }
    });

    $(form.post_new_add).on("click", function () {
        addPostRow($(form.post_new_field).val(), $(form.post_new_value).val(), 'post');
        $(form.post_new_field).val('').trigger("focus");
        $(form.post_new_value).val('');
        return false;
    });

    $(form.body_new_add).on("click", function () {
        addPostRow($(form.body_new_field).val(), $(form.body_new_value).val(), 'body');
        $(form.body_new_field).val('').trigger("focus");
        $(form.body_new_value).val('');
        return false;
    });

    if (location.href.indexOf('identifier=') > -1) {
        var oneparam = [], paramarray = {}, urlparams = location.href.slice(location.href.indexOf('?') + 1).split('&');
        for(var i = 0; i < urlparams.length; i++) {
            oneparam = urlparams[i].split('=');
            paramarray[oneparam[0]] = decodeURIComponent(oneparam[1]);
        }
        fetchAuthentication(paramarray['identifier']);
    }

});
{/jq}
