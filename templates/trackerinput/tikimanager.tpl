{if $field.error}
<div class="error">{$field.error|escape}</div>
{/if}
{if !$field.id}
    {remarksbox type="info" title="{tr}Item must be created first{/tr}" close="n"}
        <p>{tr}You will be able to manage Tiki instances here once you create the item.{/tr}</p>
    {/remarksbox}
{else}
    {if $field.instances}
        {foreach $field.instances as $instance}
            <div class="tikimanager-instance">
                <label>Type:</label> {$instance->type|escape}<br/>
                <label>Name:</label> {$instance->name|escape}<br/>
                <label>Branch:</label> {$instance->branch}<br/>
                <label>Revision:</label> {$instance->revision}<br/>
                <div class="btn-group" role="group">
                    <a class="btn btn-info btn-sm dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" href="#" aria-expanded="false">Actions</a>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="{$instance->weburl}" target="_blank">
                            <span class="icon icon-file-archive-open fas fa-folder-open "></span> Open
                        </a>
                        {if in_array('update', $field.available_actions)}
                            <a class="dropdown-item" href="{bootstrap_modal controller=manager action=update instanceId=$instance->id}">
                                {icon name=import _menu_text='y' _menu_icon='y' alt="{tr}Update{/tr}"}
                            </a>
                            <a class="dropdown-item" href="{bootstrap_modal controller=manager action=update instanceId=$instance->id mode=bg}">
                                {icon name=import _menu_text='y' _menu_icon='y' alt="{tr}Update (background){/tr}"}
                            </a>
                        {/if}
                        {if in_array('backup', $field.available_actions)}
                            <a class="dropdown-item" href="{bootstrap_modal controller=manager action=backup instanceId=$instance->id}">
                                {icon name=download _menu_text='y' _menu_icon='y' alt="{tr}Backup{/tr}"}
                            </a>
                        {/if}
                        {if in_array('fixpermissions', $field.available_actions)}
                            <a class="dropdown-item" href="{bootstrap_modal controller=manager action=fix instanceId=$instance->id modal=1}">
                                {icon name=wrench _menu_text='y' _menu_icon='y' alt="{tr}Fix{/tr}"}
                            </a>
                        {/if}
                        {if in_array('edit', $field.available_actions)}
                            <a class="dropdown-item" href="{bootstrap_modal controller=manager action=edit instanceId=$instance->id}">
                                {icon name=edit _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
                            </a>
                        {/if}
                        {if in_array('delete', $field.available_actions)}
                            <a class="dropdown-item" href="{bootstrap_modal controller=manager action=delete instanceId=$instance->id}">
                                {icon name=times _menu_text='y' _menu_icon='y' alt="{tr}Delete{/tr}"}
                            </a>
                        {/if}
                        {if in_array('console', $field.available_actions)}
                            <a class="dropdown-item" href="{bootstrap_modal controller=manager action=console instanceId=$instance->id}">
                                {icon name=pen _menu_text='y' _menu_icon='y' alt="{tr}Console Command{/tr}"}
                            </a>
                        {/if}
                    </div>
                </div>
            </div>
        {/foreach}
    {/if}
    {if in_array('create', $field.available_actions) and !$field.has_created_one}
        <div class="btn-group" role="group">
            <a class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" aria-expanded="false" href="#">
                {icon name=create _menu_text='y' _menu_icon='y' alt="{tr}Create new instance{/tr}"}
            </a>
            <div class="dropdown-menu">
                {foreach $field.versions as $version}
                    <a class="dropdown-item" href="{bootstrap_modal controller=manager_field action=create itemId=$field.id fieldId=$field.fieldId version=$version}">
                        {$version}
                    </a>
                {/foreach}
            </div>
        </div>
    {/if}
{/if}