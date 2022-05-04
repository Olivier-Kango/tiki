<div id="tikimanager_container_{$id}">
    {if in_array('create', $available_actions)}
    <a class="btn btn-link" href="{bootstrap_modal controller=manager action=create}">{icon name=create} {tr}New Instance{/tr}</a>
    {/if}
    {if in_array('info', $available_actions)}
    <a class="btn btn-link" href="{bootstrap_modal controller=manager action=info}">{icon name=info} {tr}Info{/tr}</a>
    {/if}
    <h2>Instances</h2>
    <table class="table">
    <tr>
        <th>{tr}ID{/tr}</th>
        <th>{tr}Type{/tr}</th>
        <th>{tr}Name{/tr}</th>
        <th>{tr}Branch{/tr}</th>
        <th>{tr}Revision{/tr}</th>
        <th>{tr}URL{/tr}</th>
        <th>{tr}Contact{/tr}</th>
        <th></th>
    </tr>
    {foreach $instances as $instance}
    <tr>
        <td>{$instance->id}</td>
        <td>{$instance->type|escape}</td>
        <td>{$instance->name|escape}</td>
        <td>{$instance->branch}</td>
        <td>{$instance->revision}</td>
        <td><a href="{$instance->weburl}" title="Visit website" target="_blank">{$instance->weburl}</a></td>
        <td>{$instance->contact|escape}</td>
        <td class="action">
            {actions}{strip}
                {if in_array('update', $available_actions)}
                <action>
                    <a href="{bootstrap_modal controller=manager action=update instanceId=$instance->id}" onclick="$('[data-bs-toggle=popover]').popover('hide');">
                        {icon name=import _menu_text='y' _menu_icon='y' alt="{tr}Update{/tr}"}
                    </a>
                </action>
                {/if}
                {if in_array('backup', $available_actions)}
                <action>
                    <a href="{bootstrap_modal controller=manager action=backup instanceId=$instance->id}" onclick="$('[data-bs-toggle=popover]').popover('hide');">
                        {icon name=download _menu_text='y' _menu_icon='y' alt="{tr}Backup{/tr}"}
                    </a>
                </action>
                {/if}
                {if in_array('fixpermissions', $available_actions)}
                <action>
                    <a href="{bootstrap_modal controller=manager action=fix instanceId=$instance->id modal=1}" onclick="$('[data-bs-toggle=popover]').popover('hide');">
                        {icon name=wrench _menu_text='y' _menu_icon='y' alt="{tr}Fix{/tr}"}
                    </a>
                </action>
                {/if}
                {if in_array('edit', $available_actions)}
                <action>
                    <a href="{bootstrap_modal controller=manager action=edit instanceId=$instance->id}" onclick="$('[data-bs-toggle=popover]').popover('hide');">
                        {icon name=edit _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
                    </a>
                </action>
                {/if}
                {if in_array('delete', $available_actions)}
                <action>
                    <a href="{bootstrap_modal controller=manager action=delete instanceId=$instance-id}" onclick="$('[data-bs-toggle=popover]').popover('hide');">
                        {icon name=times _menu_text='y' _menu_icon='y' alt="{tr}Delete{/tr}"}
                    </a>
                </action>
                {/if}
                {if in_array('console', $available_actions)}
                <action>
                    <a href="{bootstrap_modal controller=manager action=console instanceId=$instance->id}" onclick="$('[data-bs-toggle=popover]').popover('hide');">
                        {icon name=pen _menu_text='y' _menu_icon='y' alt="{tr}Console Command{/tr}"}
                    </a>
                </action>
                {/if}
            {/strip}{/actions}
        </td>
    </tr>
    {foreachelse}
    <tr>
        <td colspan="8">{tr}No instances defined.{/tr}</td>
    </tr>
    {/foreach}
    </table>
</div>
