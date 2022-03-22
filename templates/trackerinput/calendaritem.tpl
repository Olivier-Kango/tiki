<div class="row">
    <div class="col-sm align-baseline">
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
    {/if}
</div>
{if not empty($item.itemId) and $field.options_map.showEventIdInput}
    <div class="row">
        {$id = 'calitemId_'|cat:$field.fieldId}
        <div class="col-sm-3">
            <label class="col-form-label" for="{$id}">
                {tr}Change Event{/tr}
            </label>
        </div>
        <div class="col-sm-9">
            {object_selector _format='{title} (id# {object_id} recurrence# {recurrence_id})' _simplevalue=$data.event.calitemId _simplename=$name _simpleid=$id type='calendaritem' calendar_id=$field.options_map.calendarId _current_selection=''}
            {jq}
// this strips out repeated instances of the same recurrence id so we attach only the first one to the trascker item
$(document).on("ready.object_selector", function (event, container) {
    let done = [];

    if ($(container).find(".btn.search").length === 0) {
        $(container).find("select option").each(function () {
            let $this = $(this),
            text = $this.text(),
            recurrenceId = text.match(/recurrence# (\d+)/);

            if (recurrenceId) {
                if (done.indexOf(recurrenceId[1]) === -1) {
                    done.push(recurrenceId[1]);
                } else {
                    $this.remove();
                }
            } else {
                $this.text(text.replace(" recurrence# ", ""));
            }
        });

    } else {
        $(container).find(".form-check").each(function () {
            let $this = $(this),
            text = $this.find("label").text(),
            recurrenceId = text.match(/recurrence# (\d+)/);

            if (recurrenceId) {
                if (done.indexOf(recurrenceId[1]) === -1) {
                    done.push(recurrenceId[1]);
                } else {
                    $this.remove();
                }
            } else {
                $this.find("label").text(text.replace(" recurrence# ", ""));
            }
        });
    }
});

            {/jq}
        </div>
    </div>
{/if}
