{extends "layout_view.tpl"}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="navigation"}
    <div class="mb-3 row">
        {permission name=admin_machine_learning}
            <a class="btn btn-link" href="{service controller=ml action=create}">{icon name=create} {tr}New{/tr}</a>
        {/permission}
    </div>
{/block}

{block name="content"}
    <div class="table-responsive">
        <table class="table">
            <tr>
                <th>{tr}ID{/tr}</th>
                <th>{tr}Name{/tr}</th>
                <th>{tr}Description{/tr}</th>
                <th>{tr}Source{/tr}</th>
                <th></th>
            </tr>
            {foreach $models as $row}
                <tr>
                    <td>{$row.mlmId|escape}</td>
                    <td>{$row.name|escape}</td>
                    <td>{$row.description|escape|nl2br}</td>
                    <td>{object_link type=tracker id=$row.sourceTrackerId}</td>
                    <td class="action">
                        {actions}{strip}
                            <action>
                                <a href="{service controller=ml action=use mlmId=$row.mlmId}">
                                    {icon name=hammer _menu_text='y' _menu_icon='y' alt="{tr}Use{/tr}"}
                                </a>
                            </action>
                            {permission name=admin_machine_learning}
                            <action>
                                <a href="{service controller=ml action=test mlmId=$row.mlmId}">
                                    {icon name='dot-circle' _menu_text='y' _menu_icon='y' alt="{tr}Test{/tr}"}
                                </a>
                            </action>
                            <action>
                                <a href="{service controller=ml action=train mlmId=$row.mlmId}">
                                    {icon name=swimmer _menu_text='y' _menu_icon='y' alt="{tr}Train{/tr}"}
                                </a>
                            </action>
                            <action>
                                <a href="{service controller=ml action=clone mlmId=$row.mlmId}">
                                    {icon name=copy _menu_text='y' _menu_icon='y' alt="{tr}Clone{/tr}"}
                                </a>
                            </action>
                            <action>
                                <a href="{service controller=ml action=edit mlmId=$row.mlmId}">
                                    {icon name=edit _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
                                </a>
                            </action>
                            <action>
                                <a class="text-danger" href="{bootstrap_modal controller=ml action=delete mlmId=$row.mlmId}">
                                    {icon name=delete _menu_text='y' _menu_icon='y' alt="{tr}Delete{/tr}"}
                                </a>
                            </action>
                            {/permission}
                        {/strip}{/actions}
                    </td>
                </tr>
            {foreachelse}
                <tr>
                    <td colspan="3">{tr}No models defined.{/tr}</td>
                </tr>
            {/foreach}
        </table>
    </div>
{/block}
