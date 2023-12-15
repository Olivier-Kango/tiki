{extends "layout_view.tpl"}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="navigation"}
    {include file='manager/nav.tpl'}
{/block}

{block name="content"}
    <h2>Instance {$instance->id}: {$instance->name}</h2>
    <a class="btn btn-link" href="{service controller=manager_patch action=apply instanceId=$instance->id}">
        {icon name="create"} {tr}Apply Patch{/tr}
    </a>
    <div class="table-responsive">
        <table class="table">
            <tr>
                <th>{tr}ID{/tr}</th>
                <th>{tr}Package{/tr}</th>
                <th>{tr}URL{/tr}</th>
                <th></th>
            </tr>
            {foreach $patches as $patch}
                <tr>
                    <td>{$patch->id}</td>
                    <td>{$patch->package|escape}</td>
                    <td>{$patch->url|escape}</td>
                    <td class="action">
                        {actions}{strip}
                            <action>
                                <a href="{bootstrap_modal controller=manager_patch action=delete patchId=$patch->id}">
                                    {icon name=times _menu_text='y' _menu_icon='y' alt="{tr}Delete{/tr}"}
                                </a>
                            </action>
                        {/strip}{/actions}
                    </td>
                </tr>
            {foreachelse}
                <tr>
                    <td colspan="4">{tr}No patches applied.{/tr}</td>
                </tr>
            {/foreach}
        </table>
    </div>
{/block}
