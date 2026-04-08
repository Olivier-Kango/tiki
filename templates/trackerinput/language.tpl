<select name="{$field.ins_id|escape}" class="form-select{if !empty($field.options_map.labelasplaceholder)} labelasplaceholder{/if}">
    {if !empty($field.options_map.labelasplaceholder)}
        <option value="" disabled {if $field.value eq ''}selected{/if} hidden>{tr}Select{/tr} {$field.name|escape}</option>
    {/if}
    {foreach from=$data.languages key=code item=label}
        <option value="{$code|escape}"
            {if $code eq $field.value}selected="selected"{/if}>
            {$label|escape}
        </option>
    {/foreach}
</select>
