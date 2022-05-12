{extends "layout_view.tpl"}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="navigation"}
    {include file='manager/nav.tpl'}
{/block}

{block name="content"}
    {if not empty($info)}
        <div class="rounded bg-dark text-light p-3">{$info|nl2br}</div>
    {else}
        <form method="post" action="{service controller=manager action=create }">
            <div class="form-group row">
                <label class="col-form-label col-sm-3">{tr}Instance type{/tr}</label>
                <div class="col-sm-9">
                    <select class="form-control" id="instance_type" name="instance_type">
                        {foreach item=type from=$inputValues['types']}
                            <option value="{$type|escape}" {if $inputValues['selected_type'] eq $type}selected="selected"{/if}>{$type|upper}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-form-label col-sm-3">{tr}Instance name{/tr}</label>
                <div class="col-sm-9">
                    <input required value="{$inputValues['instance_name']}" class="form-control" id="instance_name" type="text" name="instance_name">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-form-label col-sm-3">{tr}Instance URL{/tr}</label>
                <div class="col-sm-9">
                    <input required value="{$inputValues['url']}" class="form-control" id="instance_url" type="url" name="instance_url" placeholder="example.org">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-form-label col-sm-3">{tr}Email{/tr}</label>
                <div class="col-sm-9">
                    <input required value="{$inputValues['email']}" class="form-control" id="email" type="email" name="email" placeholder="johndoe@example.org">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-form-label col-sm-3">{tr}Instance Webroot{/tr}</label>
                <div class="col-sm-9">
                    <input required value="{$inputValues['webroot']}" class="form-control" id="webroot" type="text" name="webroot" placeholder="/var/www/html">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-form-label col-sm-3">{tr}Temp dir{/tr}</label>
                <div class="col-sm-9">
                    <input required value="{$inputValues['temp_dir']}" class="form-control" id="tempdir" type="text" name="tempdir">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-form-label col-sm-3">{tr}Tiki branch{/tr}</label>
                <div class="col-sm-9">
                    <select class="form-control" id="branch" name="branch">
                        {foreach item=branch from=$inputValues['branches']}
                            <option value="{$branch|escape}" {if $inputValues['selected_branch'] eq $branch}selected="selected"{/if}>{$branch}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-form-label col-sm-3">{tr}Backup User{/tr}</label>
                <div class="col-sm-9">
                    <input required value="{$inputValues['backup_user']}" class="form-control" id="backup_user" type="text" name="backup_user">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-form-label col-sm-3">{tr}Backup Group{/tr}</label>
                <div class="col-sm-9">
                    <input required value="{$inputValues['backup_group']}" class="form-control" id="backup_group" type="text" name="backup_group">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-form-label col-sm-3">{tr}Backup Permission{/tr}</label>
                <div class="col-sm-9">
                    <input required value="{$inputValues['backup_permission']}" placeholder="777" class="form-control" id="backup_permission" type="text" name="backup_permission">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-form-label col-sm-3">{tr}DB Host{/tr}</label>
                <div class="col-sm-9">
                    <input required value="{$inputValues['db_host']}" placeholder="localhost" class="form-control" id="db_host" type="text" name="db_host">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-form-label col-sm-3">{tr}DB User{/tr}</label>
                <div class="col-sm-9">
                    <input required value="{$inputValues['db_user']}" placeholder="root" class="form-control" id="db_user" type="text" name="db_user">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-form-label col-sm-3">{tr}DB Password{/tr}</label>
                <div class="col-sm-9">
                    <input required value="{$inputValues['db_pass']}" placeholder="root" class="form-control" id="db_pass" type="text" name="db_pass">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-form-label col-sm-3">{tr}DB Prefix{/tr}</label>
                <div class="col-sm-9">
                    <input required value="{$inputValues['db_prefix']}" placeholder="root" class="form-control" id="db_prefix" type="text" name="db_prefix">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-form-label col-sm-3"></label>
                <div class="col-sm-9">
                    <input class="btn btn-primary" type="submit" name="create" value="{tr}Create a new instance{/tr}">
                </div>
            </div>
        </form>
    {/if}
    
{/block}
