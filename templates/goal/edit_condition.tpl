{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
    {remarksbox title="{tr}Changes will not be saved{/tr}"}
        {tr}Your changes to conditions are not saved until you save the goal.{/tr}
    {/remarksbox}
    <form class="condition-form" method="post" action="{service controller=goal action=edit_condition}">
        <div class="mb-3 row">
            <label class="col-form-label col-md-3">{tr}Label{/tr}</label>
            <div class="col-md-9">
                <input type="text" class="form-control" name="label" value="{$condition.label|escape}">
            </div>
        </div>
        <div class="mb-3 row">
            <label class="col-form-label col-md-3">{tr}Operator{/tr}</label>
            <div class="col-md-9">
                <label>
                    <input type="radio" name="operator" value="atLeast" {if $condition.operator neq 'atMost'} checked {/if}>
                    {tr}At Least{/tr}
                </label>
                <label>
                    <input type="radio" name="operator" value="atMost" {if $condition.operator eq 'atMost'} checked {/if}>
                    {tr}At Most{/tr}
                </label>
            </div>
        </div>
        <div class="mb-3 row">
            <label class="col-form-label col-md-3">{tr}Count{/tr}</label>
            <div class="col-md-9">
                <input type="number" class="form-control" name="count" value="{$condition.count|escape}">
            </div>
        </div>
        <div class="mb-3 row">
            <label class="col-form-label col-md-3">{tr}Metric{/tr}</label>
            <div class="col-md-9">
                <select name="metric" class="form-select">
                    {foreach $metrics as $key => $metric}
                        <option value="{$key|escape}" {if $condition.metric eq $key} selected {/if} data-arguments="{$metric.arguments|json_encode|escape}">{$metric.label|escape}</option>
                    {/foreach}
                </select>
            </div>
        </div>
        <div class="mb-3 argument eventType">
            <label class="col-form-label col-md-3">{tr}Event Type{/tr}</label>
            <div class="col-md-9">
                <input type="text" class="form-control" name="eventType" value="{$condition.eventType|escape}">
            </div>
        </div>
        {if !empty($prefs.goal_badge_tracker)}
            <div class="mb-3 argument trackerItemBadge">
                <label class="col-form-label col-md-3">{tr}Badge{/tr}</label>
                <div class="col-md-9">
                    {object_selector _name=trackerItemBadge _value="trackeritem:`$condition.trackerItemBadge`" tracker_id=$prefs.goal_badge_tracker _class="form-control"}
                </div>
            </div>
        {/if}
        <div class="offset-md-3">
            <div class="form-check">
                <input type="checkbox" name="hidden" id="hidden" class="form-check-input" value="1" {if !empty($condition.hidden)}checked{/if}>
                <label class="form-check-label" for="hidden">
                    {tr}Hide condition from users{/tr}
                </label>
            </div>
        </div>
        <div class="submit offset-md-3">
            <input type="submit" class="btn btn-primary" title="{tr}Apply Changes{/tr}" value="{tr}Apply{/tr}">
        </div>
    </form>
    {jq}
        $('.condition-form select[name=metric]').on("change", function () {
            $('.condition-form .mb-3.argument').hide();

            $.each(this.selectedOptions, function (key, item) {
                $.each($(item).data('arguments'), function (key, arg) {
                    $('.condition-form .mb-3.argument.' + arg).show();
                });
            })
        }).trigger("change");
    {/jq}
{/block}
