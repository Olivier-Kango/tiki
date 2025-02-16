{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="navigation"}
    <div class="navbar btn-group">
        {permission name=goal_admin}
            <a class="btn btn-primary" href="{service controller=goal action=admin}">{tr}Goal Administration{/tr}</a>
            <a class="btn btn-primary" href="{service controller=goal action=edit goalId=$goal.goalId}">{tr}Edit Goal{/tr}</a>
        {/permission}
        <a class="btn btn-primary" href="{service controller=goal action=show goalId=$goal.goalId}">{tr}View Goal{/tr}</a>
    </div>
{/block}

{block name="content"}
    <form method="post" action="{service controller=goal action=edit_eligible goalId=$goal.goalId}">
        <div class="mb-3 row">
            <label for="eligible" class="col-form-label col-md-3">
                {if $goal.type eq 'group'}
                    {tr}Groups{/tr}
                {else}
                    {tr}Members of{/tr}
                {/if}
            </label>
            <div class="col-md-9">
                <select name="eligible[]" class="form-select" multiple>
                    {foreach $groups as $groupName}
                        <option value="{$groupName|escape}" {if in_array($groupName, $goal.eligible)} selected {/if}>{$groupName|escape}</option>
                    {/foreach}
                </select>
            </div>
        </div>
        <div class="mb-3 row">
            <div class="offset-md-3 col-md-9">
                <input type="submit" class="btn btn-primary" value="{tr}Save{/tr}">
                <a href="{service controller=goal action=show goalId=$goal.goalId}" class="btn btn-link">{tr}Cancel{/tr}</a>
            </div>
        </div>
    </form>
{/block}
