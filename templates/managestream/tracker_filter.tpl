{extends $global_extend_layout|default:'layout_view.tpl'}
{block name="title"}
    {title}{assign var=title value="{tr}Tracker Rule{/tr}"}{$title|escape}{/title}
{/block}
{block name="content"}
    <form method="post" action="{service controller=managestream action=tracker_filter}">
        <div class="mb-3 row clearfix">
            <label for="sourceEvent" class="col-form-label col-md-3">
                {tr}Source Event{/tr}
            </label>
            <div class="col-md-9">
                <select name="sourceEvent" class="form-control">
                    {foreach from=$eventTypes item=eventName}
                        <option value="{$eventName|escape}"{if $rule.eventType eq $eventName} selected{/if}>{$eventName|escape}</option>
                    {/foreach}
                </select>
            </div>
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
            <label for="targetEvent" class="col-form-label col-md-3">
                {tr}Target Event{/tr}
            </label>
            <div class="col-md-9">
                <input type="text" name="targetEvent" value="{$targetEvent|escape}" class="form-control"/>
                <span class="form-text">
                    {tr}All event names are required to have at least 3 components.{/tr}
                </span>
            </div>
        </div>
        <div class="mb-3 row clearfix">
            <label for="tracker" class="col-form-label col-md-3">
                {tr}Tracker{/tr}
            </label>
            <div class="col-md-9">
                <select name="tracker" class="form-control">
                    {foreach from=$trackers.data item=tracker}
                        <option value="{$tracker.trackerId|escape}"{if $tracker.trackerId eq $targetTracker} selected{/if}>
                            {$tracker.name|escape}
                        </option>
                    {/foreach}
                </select>
            </div>
        </div>
        <div class="mb-3 row clearfix">
            <label for="parameters" class="col-form-label col-md-3">
                {tr}Parameters{/tr}
            </label>
            <div class="col-md-9">
                <textarea name="parameters" rows="3" class="form-control">{$parameters|escape}</textarea>
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
{/block}
