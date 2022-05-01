{extends "layout_view.tpl"}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
    <form method="post" action="{service controller=tabular action=select trackerId=$trackerId permName=$permName}">
        {if $columnIndex}<input type="hidden" name="columnIndex" value="{$columnIndex}">{/if}
        <div class="mb-3 row">
            <label class="col-form-label">{tr}Modes{/tr}</label>
            <select name="mode" class="form-select">
                {foreach $schema->getColumns() as $column}
                    <option value="{$column->getMode()|escape}" {if $mode eq $column->getMode()} selected="selected"{/if}>
                        {$column->getLabel()|escape} ({$column->getMode()|escape}{if $column->isReadOnly()}, {tr}Read-Only{/tr}{/if})
                    </option>
                {/foreach}
            </select>
        </div>
        <div class="submit">
            <input class="btn btn-primary" type="submit" value="{if $columnIndex}{tr}Edit{/tr}{else}{tr}Add{/tr}{/if}">
        </div>
    </form>
{/block}
