<select name="{$field.ins_id|escape}" class="form-select">
    {foreach from=$data.languages key=code item=label}
        <option value="{$code|escape}"
            {if $code eq $field.value}selected="selected"{/if}>
            {$label|escape}
        </option>
    {/foreach}
</select>
