{if $status}
    <div class="tracker-field-group mb-3">
        <label for="trackerinput_status">{tr}Status{/tr}</label>
        <div id="trackerinput_status">
            {include 'trackerinput/status.tpl' status_types=$status_types status=$status}
        </div>
    </div>
{/if}
{$jscal = 0}
{foreach from=$fields item=field}
    {if $field.visibleInEditMode eq 'y'}
        <div class="tracker-field-group mb-3">
            {if empty($field.options_map.labelasplaceholder)}
                <label for="trackerinput_{$field.fieldId|escape}" {if $field.type eq 'h'}class="h{$field.options_map.level}" {/if}>
                    {$field.name|tra|escape}
                    {if $field.isMandatory eq 'y'}
                        <strong class='mandatory_star text-danger tips' title=":{tr}This field is mandatory{/tr}">*</strong>
                    {/if}
                </label>
            {/if}
            <div id="trackerinput_{$field.fieldId|escape}">
                {trackerinput field=$field item=$item}
                {if !empty($field.description) && $field.type ne 'S'}
                    {if $field.descriptionIsParsed eq 'y'}
                        <div class="description form-text">{wiki objectId=$field.fieldId objectType="trackerfield" fieldName="description"}{$field.description}{/wiki}</div>
                    {else}
                        <div class="description form-text">{$field.description|tra|escape}</div>
                    {/if}
                {/if}
            </div>
        </div>
    {/if}
    {if $field.type == 'j'}{$jscal = 1}{/if}
{/foreach}
{jq}$('label').on("click", function() {$('input, select, textarea', '#'+$(this).attr('for')).trigger("focus");});{/jq}
{if $jscal}
    {js_insert_icon type="jscalendar"}
{/if}
