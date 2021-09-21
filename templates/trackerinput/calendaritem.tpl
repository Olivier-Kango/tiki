<div class="row">
    <div class="col-sm-4 align-baseline">
        {$datePickerHtml}
    </div>
    {if not empty($item.itemId)}
        {if $data.editUrl}
            <div class="col-sm text-center align-baseline">
                {if not empty($data.event.calitemId)}
                    {$label = '{tr}Edit Event{/tr}'}
                {else}
                    {$label = '{tr}Add Event{/tr}'}
                {/if}
                {button href=$data.editUrl _text=$label _id='calitem_'|cat:$field.fieldId _class='btn btn-primary btn-sm'}
                {jq}
                    $('#calitem_{{$field.fieldId}}').click($.clickModal(
                        {
                            size: "modal-lg",
                            open: function (data) {
                                // prevent default modal submit button handling
                                $(".submit", this).removeClass("submit");
                            }
                        },
                        "{{$data.editUrl}}"
                    ));
                {/jq}
            </div>
        {/if}
        {if $field.options_map.showEventIdInput}
            {$id = 'calitemId_'|cat:$field.fieldId}
            <div class="col-sm text-right align-baseline">
                <label class="col-form-label" for="{$id}">
                    {tr}Change Event{/tr}
                </label>
            </div>
            <div class="col-sm align-baseline">
                {object_selector _format='{title} id#:{object_id}' _simplevalue=$data.event.calitemId _simplename=$id _simpleid=$id type='calendaritem' calendar_id=$field.options_map.calendarId}
            </div>
        {/if}
    {/if}
</div>
