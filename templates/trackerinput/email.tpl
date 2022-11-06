<div{if !empty($field.options_map.labelasplaceholder)} class="input-group"{/if}>
    <input type="email" name="{$field.ins_id}" id="{$field.ins_id}" value="{$field.value|escape}"
        class="form-control{if !empty($field.options_map.labelasplaceholder)} labelasplaceholder{/if}"
        {if !empty($field.options_map.labelasplaceholder)}placeholder="{$field.name}"{/if}
    >
    {if $field.options_map.labelasplaceholder and $field.isMandatory eq 'y'}
        <span class="input-group-text">
            <strong class='mandatory_star text-danger tips' title=":{tr}This field is mandatory{/tr}" style="font-size: 100%">{icon name='asterisk'}</strong>
        </span>
    {/if}
</div>
