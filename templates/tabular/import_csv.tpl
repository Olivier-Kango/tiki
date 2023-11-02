{extends "layout_view.tpl"}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="navigation"}
    <div class="navbar d-inline-flex">
        {permission name=admin_trackers}
            <a class="btn btn-link" href="{service controller=tabular action=create}">{icon name=create} {tr}New{/tr}</a>
            <a class="btn btn-link" href="{service controller=tabular action=manage}">{icon name=list} {tr}Manage{/tr}</a>
        {/permission}
    </div>
{/block}

{block name="content"}
    {if $completed}
        {remarksbox type=confirm title="{tr}Import Completed{/tr}"}
            {tr}Your import was completed successfully.{/tr}
        {/remarksbox}
    {else}
        <form class="no-ajax" method="post" action="{service controller=tabular action=import_csv tabularId=$tabularId}" enctype="multipart/form-data">
            {if $odbc}
            <p>{tr}Import from remote ODBC source.{/tr}</p>
            {elseif $api}
            <p>{tr}Import from remote API source.{/tr}</p>
                {foreach $placeholders as $field}
                    <div class="input-group mb-3">
                        <span class="input-group-text">{$field}</span>
                        <div>
                            <input type="text" name="placeholders[{$field}]" value="" class="form-control">
                        </div>
                    </div>
                {/foreach}
            {elseif $format eq 'json'}
            <div class="input-group mb-3">
                <span class="input-group-text" id="inputGroupText">{tr}JSON File{/tr}</span>
                <div class="custom-file">
                    <input type="file" name="file" accept="application/json" class="custom-file-input" id="inputFile" aria-describedby="inputGroupText"
                        onchange="$(this).next('.custom-file-label').text($(this).val().replace('C:\\fakepath\\', ''));">
                    <label class="form-label custom-file-label" for="inputFile">Choose file</label>
                </div>
            </div>
            {elseif $format eq 'ndjson'}
            <div class="input-group mb-3">
                <span class="input-group-text" id="inputGroupText">{tr}NDJSON File{/tr}</span>
                <div class="custom-file">
                    <input type="file" name="file" accept="application/x-ndjson" class="custom-file-input" id="inputFile" aria-describedby="inputGroupText"
                        onchange="$(this).next('.custom-file-label').text($(this).val().replace('C:\\fakepath\\', ''));">
                    <label class="form-label custom-file-label" for="inputFile">Choose file</label>
                </div>
            </div>
            {elseif $format eq 'ical'}
            <div class="input-group mb-3">
                <span class="input-group-text" id="inputGroupText">{tr}iCal File{/tr}</span>
                <div class="custom-file">
                    <input type="file" name="file" accept="text/calendar" class="custom-file-input" id="inputFile" aria-describedby="inputGroupText"
                        onchange="$(this).next('.custom-file-label').text($(this).val().replace('C:\\fakepath\\', ''));">
                    <label class="form-label custom-file-label" for="inputFile">Choose file</label>
                </div>
            </div>
            {else}
            <div class="input-group mb-3">
                <span class="input-group-text" id="inputGroupText">{tr}CSV File{/tr}</span>
                <div class="custom-file">
                    <input type="file" name="file" accept="text/csv" class="custom-file-input" id="inputFile" aria-describedby="inputGroupText"
                        onchange="$(this).next('.custom-file-label').text($(this).val().replace('C:\\fakepath\\', ''));">
                    <label class="form-label custom-file-label" for="inputFile">Choose file</label>
                </div>
            </div>
            {/if}
            <div class="submit">
                <input class="btn btn-primary" type="submit" value="{tr}Import{/tr}">
            </div>
        </form>
    {/if}
{/block}
