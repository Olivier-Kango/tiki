{foreach $fb as $info}
    {if $info.count > 0}
        {if $info.count == 1}
            {$poptitle = "{tr}Permission successfully changed{/tr}"}
            {if $info.type === 'global'}
                {$header = "{tr}Global permission{/tr}"}
            {else}
                {if $info.objid !== $info.objname}
                    {$header = "{tr _0=$info.objtype|escape _1="<em>{$info.objname|escape}</em>" _2=$info.objid|escape}Object permission for the %0 %1 (ID %2){/tr}"}
                {else}
                    {$header = "{tr _0=$info.objtype|escape _1="<em>{$info.objname|escape}</em>"}Object permission for the %0 %1{/tr}"}
                {/if}
            {/if}
        {else}
            {$poptitle = "{tr}Permissions successfully changed{/tr}"}
            {if $info.type === 'global'}
                {$header = "{tr}Global permissions{/tr}"}
            {else}
                {if $info.objid !== $info.objname}
                    {$header = "{tr _0=$info.objtype|escape _1="<em>{$info.objname|escape}</em>" _2=$info.objid|escape}Object permissions for the %0 %1 (ID %2){/tr}"}
                {else}
                    {$header = "{tr _0=$info.objtype|escape _1="<em>{$info.objname|escape}</em>"}Object permissions for the %0 %1{/tr}"}
                {/if}
            {/if}
        {/if}
        {remarksbox type='feedback' title="$poptitle"}
            <h5>{$header}{tr}:{/tr}</h5>
            {foreach $info.mes as $direction => $directionPermissionsChanged}
                {$direction|capitalize|escape}
                <ul>
                    {foreach $directionPermissionsChanged as $group => $groupPermissionsChanged}
                        {foreach $groupPermissionsChanged as $groupPermissionChanged}
                            <li>
                                {$group|escape}{tr}:{/tr} {$groupPermissionChanged|escape}
                            </li>
                        {/foreach}
                    {/foreach}
                </ul>
            {/foreach}
        {/remarksbox}
    {else}
        {remarksbox type='note' title="{tr}No permissions were changed{/tr}"}{/remarksbox}
    {/if}
{/foreach}
