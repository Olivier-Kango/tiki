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
        <form method="post" action="{service controller=manager action=apply }">
            <input required class="form-control" id="instanceId" value="{$instanceId}" type="hidden" name="instanceId">
            <div class="form-group row">
                <label class="col-form-label col-sm-3">{tr}Profile{/tr}</label>
                <div class="col-sm-9">
                    <input required placeholder="{tr}e.g. Personal_Blog_and_Profile_21plus{/tr}" class="form-control" id="profile" type="text" name="profile">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-form-label col-sm-3">{tr}Repository{/tr}</label>
                <div class="col-sm-9">
                    <input placeholder="{tr}e.g. profiles.tiki.org{/tr}" class="form-control" id="repository" type="text" name="repository">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-form-label col-sm-3"></label>
                <div class="col-sm-9">
                    <input class="btn btn-primary" type="submit" name="apply" value="{tr}Apply profile{/tr}">
                </div>
            </div>
        </form>
    {/if}
    
{/block}
