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
        <form method="post" action="{service controller=manager action=clone }">
            <div class="form-group row p-2">
                <label class="col-form-label col-sm-3">{tr}Source Instace{/tr}</label>
                <div class="col-sm-9">
                    <select class="form-control" id="source" name="source">
                        {foreach item=instance from=$inputValues['instances']}
                            <option value="{$instance->id}" {if {$inputValues['source']} eq $instance->id}selected="selected"{/if}>{$instance->name|escape}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
            <div class="form-group row p-2">
                <label class="col-form-label col-sm-3">{tr}Target Instace{/tr}</label>
                <div class="col-sm-9">
                    <select class="form-control" id="target" name="target">
                        {foreach item=instance from=$inputValues['instances']}
                            <option value="{$instance->id}" {if {$inputValues['target']} eq $instance->id}selected="selected"{/if}>{$instance->name|escape}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
            <div class="form-group row p-2">
                <label class="col-form-label col-sm-3">{tr}Tiki branch{/tr}</label>
                <div class="col-sm-9">
                    <select class="form-control" id="branch" name="branch">
                        {foreach item=branch from=$inputValues['branches']}
                            <option value="{$branch|escape}" {if $inputValues['selected_branch'] eq $branch}selected="selected"{/if}>{$branch}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
            <div class="form-group row p-2">
                <label class="col-form-label col-sm-3">{tr}Skip Reindex{/tr}</label>
                <div class="col-sm-9">
                    <input value="1" class="form-check-input" id="skipreindex" type="checkbox" name="skipreindex" placeholder="" {if $inputValues['skip-reindex']} checked="checked"{/if}>
                </div>
            </div>
            <div class="form-group row p-2">
                <label class="col-form-label col-sm-3">{tr}Skip cache warmup{/tr}</label>
                <div class="col-sm-9">
                    <input value="1" class="form-check-input" id="skipcachewarmup" type="checkbox" name="skipcachewarmup" {if $inputValues['skip-cache-warmup']} checked="checked"{/if}>
                </div>
            </div>
            <div class="form-group row p-2">
                <label class="col-form-label col-sm-3">{tr}Live reindex{/tr}</label>
                <div class="col-sm-9">
                    <input value="1" class="form-check-input" id="livereindex" type="checkbox" name="livereindex" {if $inputValues['live-reindex']} checked="checked"{/if}>
                </div>
            </div>

            <div class="form-group row p-2">
                <label class="col-form-label col-sm-3">{tr}Keep backup{/tr}</label>
                <div class="col-sm-9">
                    <input value="1" class="form-check-input" id="keepbackup" type="checkbox" name="keepbackup" {if $inputValues['keep-backup']} checked="checked"{/if}>
                </div>
            </div>

            <div class="form-group row p-2">
                <label class="col-form-label col-sm-3">{tr}Use last backup{/tr}</label>
                <div class="col-sm-9">
                    <input value="1" class="form-check-input" id="uselastbackup" type="checkbox" name="uselastbackup" {if $inputValues['use-last-backup']} checked="checked"{/if}>
                </div>
            </div>

            <div class="form-group row p-2">
                <label class="col-form-label col-sm-3">{tr}DB Host{/tr}</label>
                <div class="col-sm-9">
                    <input value="{$inputValues['db_host']}" placeholder="localhost" class="form-control" id="db_host" type="text" name="db_host">
                </div>
            </div>

            <div class="form-group row p-2">
                <label class="col-form-label col-sm-3">{tr}DB User{/tr}</label>
                <div class="col-sm-9">
                    <input value="{$inputValues['db_user']}" placeholder="root" class="form-control" id="db_user" type="text" name="db_user">
                </div>
            </div>

            <div class="form-group row p-2">
                <label class="col-form-label col-sm-3">{tr}DB Password{/tr}</label>
                <div class="col-sm-9">
                    <input value="{$inputValues['db_pass']}" placeholder="root" class="form-control" id="db_pass" type="text" name="db_pass">
                </div>
            </div>

            <div class="form-group row p-2">
                <label class="col-form-label col-sm-3">{tr}DB Prefix{/tr}</label>
                <div class="col-sm-9">
                    <input value="{$inputValues['db_prefix']}" placeholder="" class="form-control" id="db_prefix" type="text" name="db_prefix">
                </div>
            </div>

            <div class="form-group row p-2">
                <label class="col-form-label col-sm-3">{tr}DB Name{/tr}</label>
                <div class="col-sm-9">
                    <input value="{$inputValues['db_name']}" placeholder="" class="form-control" id="db_name" type="text" name="db_name">
                </div>
            </div>

            <div class="form-group row p-2">
                <label class="col-form-label col-sm-3">{tr}Stash{/tr}</label>
                <div class="col-sm-9">
                    <input value="1" placeholder="" class="form-check-input" id="stash" type="checkbox" name="stash" {if $inputValues['stash']} checked="checked"{/if}>
                </div>
            </div>

            <div class="form-group row p-2">
                <label class="col-form-label col-sm-3">{tr}Timeout{/tr}</label>
                <div class="col-sm-9">
                    <input value="{$inputValues['timeout']}" placeholder="" class="form-control" id="timeout" type="text" name="timeout">
                </div>
            </div>

            <div class="form-group row p-2">
                <label class="col-form-label col-sm-3"></label>
                <div class="col-sm-9">
                    <input class="btn btn-primary" type="submit" name="clone" value="{tr}Clone instance{/tr}">
                </div>
            </div>
        </form>
    {/if}
    
{/block}
