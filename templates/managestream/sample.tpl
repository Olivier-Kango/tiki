{extends $global_extend_layout|default:'layout_view.tpl'}
{block name="title"}
    {title}
        {assign var=title value="{tr}Sample Rule{/tr}"}{$title|escape}
    {/title}
{/block}
{block name="content"}
    <form method="post" action="{service controller=managestream action=sample}">
        {remarksbox title="{tr}Tip{/tr}"}
            {tr}Cached sample data helps to view available variables for event types{/tr}
        {/remarksbox}
        <div class="mb-3 row clearfix">
            <label for="event" class="col-form-label col-md-3">
                {tr}Event{/tr}
            </label>
            <div class="col-md-9">
                <select id="eventType" name="event" class="form-control">
                    {foreach from=$eventTypes item=event}
                        <option value="{$event.eventType|escape}"{if $rule.eventType eq $event.eventType} selected{/if}>{$event.eventType|escape} {if !empty($event.sample)} ({tr}Sample available{/tr}){/if}</option>
                    {/foreach}
                </select>
            </div>
            </label>
        </div>
        <div class="mb-3 row clearfix">
            <label for="notes" class="col-form-label col-md-3">
                {tr}Description{/tr}
            </label>
            <div class="col-md-9">
                <textarea name="notes" class="form-control">{$rule.notes|escape}</textarea>
            </div>
        </div>
        <div class="mb-3 row clearfix">
            <label for="sample" class="col-form-label col-md-3">
                {tr}Sample{/tr}
            </label>
            <div id="sample" class="col-md-9">
                <pre>{if $data}{$data|escape}{else}{tr}Sample currently not available{/tr}{/if}</pre>
            </div>
        </div>
        <div class="mb-3 row clearfix">
            <label for="rule" class="col-form-label col-md-3">
                {tr}Rule{/tr}
            </label>
            <div class="col-md-9">
                <textarea name="rule" class="form-control" rows="3" readonly>{$rule.rule|escape}</textarea>
            </div>
        </div>
        <div class="submit">
            {ticket mode='confirm'}
            <input type="hidden" name="ruleId" value="{$rule.ruleId|escape}"/>
            <input type="submit" class="btn btn-primary" value="{tr}Save{/tr}"/>
        </div>
    </form>
{jq}
    $('#eventType').on("change", function(event) {
        var eventType = $('#eventType').val();
        var result = $.ajax({
            type: 'GET',
            url: 'tiki-ajax_services.php?',
            dataType: 'json',
            data: {
                controller: 'managestream',
                action: 'sample',
                eventType: 'eventType'
            },
            success: function (data) {
                $('#sample').html(data);
            }
        });
    });
{/jq}
{/block}
