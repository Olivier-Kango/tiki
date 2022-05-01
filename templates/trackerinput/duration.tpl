<div data-field-type="{$field.type}">
    {foreach item=unit from=$data.units|array_reverse}
        {if $field.options_map.$unit}
            <div class="input-group mb-1">
                <input type="number" class="numeric form-control duration-field" name="{$field.ins_id|escape}[{$unit}]" value="{$data.amounts.$unit|escape}" id="{$field.ins_id}_{$unit}">
                <span class="input-group-text">{$unit}</span>
            </div>
        {/if}
    {/foreach}
</div>
