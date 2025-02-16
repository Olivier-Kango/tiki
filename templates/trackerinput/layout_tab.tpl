{tabset name="tracker_section_output"|cat:$tracker_info.trackerId}
    {foreach $sections as $pos => $sect}
        {tab name=$sect.heading}
            {if ! $pos && $status}
                <div class="tracker-field-group mb-3">
                    <label for="trackerinput_status">{tr}Status{/tr}</label>
                    <div id="trackerinput_status">
                        {include 'trackerinput/status.tpl' status_types=$status_types status=$status}
                    </div>
                </div>
            {/if}
            {foreach from=$sect.fields item=field}
                <div class="tracker-field-group mb-3">
                    {if empty($field.options_map.labelasplaceholder)}
                        <label for="trackerinput_{$field.fieldId|escape}">
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
                                <div class="description form-text">{wiki}{$field.description}{/wiki}</div>
                            {else}
                                <div class="description form-text">{$field.description|tra|escape}</div>
                            {/if}
                        {/if}
                    </div>
                </div>
            {/foreach}
        {/tab}
    {/foreach}
{/tabset}
{jq}$('label').on("click", function() {$('input, select, textarea', '#'+$(this).attr('for')).trigger("focus");});{/jq}
