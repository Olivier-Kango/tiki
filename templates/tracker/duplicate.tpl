{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title|escape}{/title}
{/block}

{block name="content"}
    <form class="form" name="duplicate_tracker" action="{service controller=tracker action=duplicate}" method="post">
        <div class="mb-3 row mx-0">
            <label class="col-form-label" for="name">
                {tr}Name{/tr}
            </label>
            <input type="text" name="name" id="name" class="form-control" placeholder="Name of the new tracker" required="required">
        </div>
        <div class="mb-3 row mx-0">
            <label class="col-form-label" for="trackerId">
                {tr}Tracker{/tr}
            </label>
            <select name="trackerId" id="trackerId" class="form-select" required="required">
                {foreach from=$trackers item=tr key=k}
                    <option value="{$tr.trackerId|escape}">{$tr.name|escape}</option>
                {/foreach}
            </select>
        </div>
        {if $prefs.feature_categories eq 'y'}
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="dupCateg" id="dupCateg" value="1">
                <label class="form-check-label"for="dupCateg">
                    {tr}Duplicate categories{/tr}
                </label>
            </div>
        {/if}
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="dupPerms" id="dupPerms" value="1">
            <label class="form-check-label" for="dupPerms">
                {tr}Duplicate permissions{/tr}
            </label>
        </div>
        <div class="submit text-center">
            <input type="hidden" name="confirm" value="1">
            <input type="submit" class="btn btn-primary" value="{tr}Duplicate{/tr}">
        </div>
    </form>
{/block}
