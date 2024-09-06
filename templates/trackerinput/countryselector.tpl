{if $field.options_map.inputtype eq 't'}
    {jstransfer_list data=$field.flags defaultSelected=$field.value fieldName="{$field.html_name}" filterable=$field.options_map.filterable filterPlaceholder=$field.options_map.filterPlaceholder sourceListTitle=$field.options_map.sourceListTitle targetListTitle=$field.options_map.targetListTitle ordering=$field.options_map.ordering cardinalityParam=$field.validationParam validationMessage=$field.validationMessage}
{else}
    <select class="form-select" name="{$field.html_name}" {{if $field.options_map.multiple }}multiple="multiple"{{/if}}>
        {if $field.isMandatory ne 'y' || empty($field.value)}
            <option value=""{if $field.value eq '' or $field.value eq 'None'} selected="selected"{/if}>&nbsp;</option>
        {/if}
        {if empty($field.itemChoices)}
            <option value="Other"{if $field.value eq 'None'} selected="selected"{/if}{if $field.options_array[0] ne '1'} style="background: url('img/flags/Other.png') no-repeat;padding-left:25px;padding-bottom:3px;"{/if}>{tr}Other{/tr}</option>
        {/if}

        {foreach key=flagicon item=flag from=$field.flags}
            {if $flagicon ne 'None' and $flagicon ne 'Other' and ( ! isset($field.itemChoices) || $field.itemChoices|@count eq 0 || in_array($flagicon, $field.itemChoices) )}
                <option value="{$flagicon|escape}" {if $field.value eq $flagicon or in_array($flagicon, $field.value)}selected="selected"{elseif $flagicon eq $field.defaultvalue}selected="selected"{/if}{if $field.options_array[0] ne '1'} style="background: url('img/flags/{$flagicon}.png') no-repeat;padding-left:25px;padding-bottom:3px;"{/if}>{$flag|escape}</option>
            {/if}
        {/foreach}
    </select>
{/if}
