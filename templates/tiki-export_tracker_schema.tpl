
<form id='form' method='get' action='tiki-export_tracker_schema.php'>
<div class='row'>
    <div class='col-md-6'>
            <div class='form-check'>
                <input class='form-check-input submit' type='checkbox' name='skipAttributes' id='skipAttributes' >
                <label class='form-check-label' for='skipAttributes'>{tr}Completely skip attributes{/tr}</label>
            </div>
            <div class='form-check'>
                <input class='form-check-input submit' type='checkbox' name='skipRelations' id='skipRelations' >
                <label class='form-check-label' for='skipRelations'>{tr}Completely skip relationships{/tr}</label>
            </div>
            <div class='form-check'>
                <input class='form-check-input submit' type='checkbox' name='includePermNames' id='includePermNames' >
                <label class='form-check-label' for='includePermNames'>{tr}Include field permanent names{/tr}</label>
                <div class='text-center p-3' id='loader' style="display:none;"><i class='icon icon-spinner fas fa-spinner fa-spin' alt='Loading...'></i></div>
            </div>
            {if $goback eq 'tracker'}
            <a href='tiki-view_tracker.php?trackerId={$idTracker}' class='btn btn-link' >{tr}Go back to tracker{/tr}</a><br>
            {else}
            <a href='tiki-list_trackers.php' class='btn btn-link' >{tr}Go back to trackers{/tr}</a><br>
            {/if}
            {foreach from=$requestedTrackerIds item=trackerId}
            <input type='hidden' value='{$trackerId}' name='trackerIds[]'>
            {/foreach}
    </div>
    <div class='col-md-5'>
        <div class='form-check'>
            <input class='form-check-input' type='radio' name='export' id='svgFormat' value='svgFormat' checked>
            <label class='form-check-label' for='svgFormat'>
            {tr}ER Diagram Display{/tr}
            </label>
        </div>
        <div class='form-check'>
            <input class='form-check-input' type='radio' name='export' id='textPlain' value='textPlain'>
            <label class='form-check-label' for='textPlain'>
            {tr}Raw mermaid text{/tr}
            </label>
        </div>
        <div class='form-check'>
            <input class='form-check-input' type='radio' name='export' id='imgSvg' value='imgSvg'>
            <label class='form-check-label' for='imgSvg'>
            {tr}SVG for export{/tr}
            </label>
        </div>
        <button class='btn btn-primary mt-2 mb-2' id='button' type='submit'>{tr}View in this format{/tr}</button>
        <button class='btn btn-primary mt-2 mb-2' id='buttonExport'>{tr}Export{/tr}</button>
    </div>
</div>
</form>

<div id='contentmain'>{$contentmain}</div>
