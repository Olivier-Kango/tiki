{strip}
    {if $field.type eq 'R'}
        {foreach $field.possibilities as $value => $label}
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="{$field.ins_id|escape}" id="{$field.ins_id|escape}-{$label@iteration}" value="{$value|escape}" {if $field.value eq "$value"}checked="checked"{/if}>
                <label class="form-check-label" for="{$field.ins_id|escape}-{$label@iteration}">
                    {$label|tr_if|escape}
                </label>
            </div>
        {/foreach}
    {elseif $field.type eq 'M'}
        {if empty($field.options_map.inputtype)}
            {foreach $field.possibilities as $value => $label}
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="{$field.ins_id|escape}[]" id="{$field.ins_id|escape}-{$label@iteration}" value="{$value|escape}" {if in_array("$value", $field.selected)}checked="checked"{/if}>
                    <label class="form-check-label" for="{$field.ins_id|escape}-{$label@iteration}">
                        {$label|tr_if|escape}
                    </label>
                </div>
                {/foreach}
        {elseif $field.options_map.inputtype eq 'm'}
            {if $prefs.jquery_select2 neq 'y'}<small>{tr}Hold "Ctrl" in order to select multiple values{/tr}</small><br>{/if}
            <select name="{$field.ins_id}[]" multiple="multiple" class="form-select">
                {foreach $field.possibilities as $value => $label}
                    <option value="{$value|escape}" {if in_array("$value", $field.selected)}selected="selected"{/if}>{$label|escape}</option>
                {/foreach}
            </select>
        {elseif $field.options_map.inputtype eq 't'}
            {jstransfer_list fieldName="{$field.ins_id|escape}[]" defaultSelected=$field.selected
            data=$field.possibilities sourceListTitle=$field.options_map.sourceListTitle
            targetListTitle=$field.options_map.targetListTitle filterable=$field.options_map.filterable
            filterPlaceholder=$field.options_map.filterPlaceholder ordering=$field.options_map.ordering cardinalityParam=$field.validationParam validationMessage=$field.validationMessage}
        {/if}
        <input type="hidden" name="{$field.ins_id}_old" value="{$field.value|escape}">
    {else}
        <select name="{$field.ins_id|escape}" class="form-select{if $field.type eq 'D'} group_{$field.ins_id|escape}{/if}">
            {if $field.isMandatory ne 'y' || $field.value eq ''}
                <option value=""></option>
            {/if}
            {foreach $field.possibilities as $value => $label}
                {if $value !== 0  and ($value eq 'other' or $value eq "{tr}other{/tr}")}
                    {assign var=otherLabel value={$label|escape}}
                    {continue}{* TODO: Ignores options which would have "other" as key, which is not documented. Avoids displaying 2 "other" options, since the option needed to be specified manually prior to Tiki 18. *}
                {/if}
                <option value="{$value|escape}"
                {if (isset($field.value) && $field.value ne '') && ($field.value eq "$value")}selected="selected"{/if}>
                    {$label|tr_if|escape}
                </option>
            {/foreach}
            {if $field.type eq 'D'}
                {if ! isset($otherLabel)}
                    {assign var=otherLabel value="{tr}Other{/tr}"}
                {/if}
                <option value="other" style="font-style: italic">
                    {$otherLabel}
                </option>
            {/if}
        </select>

        {if $field.type eq 'D'}
            <div class="offset-md-1">
                <label for="other_{$field.ins_id}" {if !isset($field.possibilities[$field.value]) && $field.value} style="display:inherit;"{else} style="display:none;"{/if}>
                    {tr}Other:{/tr}
                    <input type="text" class="group_{$field.ins_id|escape} form-control" name="other_{$field.ins_id}" id="other_{$field.ins_id}" value="{if !isset($field.possibilities[$field.value])}{$field.value|escape}{/if}">
                </label>
            </div>
            {jq}
            $(function () {
                var $select = $('select[name="{{$field.ins_id|escape}}"]'),
                    $other = $('input[name="other_{{$field.ins_id|escape}}"]');
                {{if !isset($field.possibilities[$field.value]) && $field.value}}
                if (!$('> [selected]', $select).length) {
                    $select.val('other').trigger("change.select2");
                }
                {{/if}}
                $select.on("change", function() {
                    if ($select.val() != 'other') {
                        $other.data('tiki_never_visited', '');
                        $other.val('').parent().hide();
                    } else {
                        $other.data('tiki_never_visited', 'tiki_never_visited');
                        $other.parent().show();
                    }
                });
                $other.on("change", function(){
                    $other.data('tiki_never_visited', '');
                    if ($(this).val()) {
                        $select.val(tr('other')).trigger("change.select2");
                    }
                });
                $other.on("focusout", function(){
                    $other.data('tiki_never_visited', '');
                });
            });
            {/jq}
        {/if}

    {/if}
{/strip}
