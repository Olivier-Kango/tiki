{title url=''}{tr}Directory batch upload{/tr}{/title}

<div class="t_navbar btn-group mb-3">
    {if $galleryId}
        {button href="tiki-list_file_gallery.php?galleryId=$galleryId" class="btn btn-primary" _class="me-3" _text="{tr}Browse File Gallery{/tr}"}
        {button href="tiki-upload_file.php?galleryId=$galleryId" class="btn btn-primary" _text="{tr}Upload From Disk{/tr}"}
    {else}
        {button href="tiki-list_file_gallery.php" class="btn btn-primary" _text="{tr}Browse File Gallery{/tr}"}
        {button href="tiki-upload_file.php" class="btn btn-primary" _class="me-3" _text="{tr}Upload From Disk{/tr}"}
    {/if}
</div>

{remarksbox type="tip" title="{tr}Tip{/tr}"}{tr}Please do not use this feature to upload data into the database.{/tr}{/remarksbox}

{if count($feedback)}<div class="alert alert-warning">{section name=i loop=$feedback}{$feedback[i]}<br>{/section}</div>{/if}
{$totalsize = 0}
<h2>{tr}Available Files{/tr}</h2>
<form method="post" action="tiki-batch_upload_files.php" name="f" id="batchUploadForm">
    <div class="table-responsive">
        <table class="table table-stripped" id="filelist">
            <tr>
                <td>{select_all checkbox_names='files[]'}</td> {* th changed to td to prevent ARIA empty header error *}
                <th>{tr}Filename{/tr}</th>
                <th width="80">{tr}File size{/tr}</th>
                <th width="80">{tr}File type{/tr}</th>
                <td class="text-center">{icon name='permission' title="{tr}File permissions{/tr}"}</td> {* th changed to td to prevent ARIA empty header error *}
            </tr>

            {foreach key=k item=it from=$filelist}
                <tr>
                    <td class="checkbox-cell">
                        <input type="checkbox" class="form-check-input" aria-label="{tr}Select{/tr}" name="files[]" value="{$it.file}" id="box_{$k}">
                    </td>
                    <td><label for="box_{$k}">{$it.file|replace:$prefs.fgal_batch_dir:''}</label></td>
                    <td>{$it.size|kbsize}</td>
                    <td>{$it.ext}</td>
                    <td class="text-center">{if !empty($it.writable)}{icon name='success' title="{tr}File is writable{/tr}"}{else}{icon name='ban' title="{tr}File is not writable{/tr}"}{/if}</td>
                </tr>
                {$totalsize = $totalsize + $it.size}
            {/foreach}
            <tr>
                <td></td>
                <td><strong>{tr _0=$filelist|count}Total: %0{/tr}</strong></td>
                <td><em>{$totalsize|kbsize}</em></td>
                <td></td>
                <td></td>
            </tr>
        </table>
    </div>
    <hr>
    <div class="mb-3 row">
        <label class="col-sm-4 col-form-label" for="galleryId">{tr}Select a File Gallery{/tr}</label>
        <div class="col-sm-4">
            <select name="galleryId" id="galleryId" class="form-select">
                <option value="{$treeRootId}" {if $treeRootId eq $galleryId}selected="selected"{/if} style="font-style:italic; border-bottom:1px dashed #666;">{tr}Root{/tr}</option>
                {section name=idx loop=$galleries}

                    {if ($galleries[idx].individual eq 'n') or ($galleries[idx].individual_tiki_p_batch_upload_file_dir eq 'y')}
                        <option value="{$galleries[idx].id}" {if $galleries[idx].id eq $galleryId}selected="selected"{/if}>{$galleries[idx].parentName}: {$galleries[idx].name}</option>
                    {/if}
                {/section}
            </select>
        </div>
    </div>
    <div class="mb-3 row">
        <label class="col-sm-4 form-check-label" for="subdirToSubgal">{tr}Upload into galleries according to sub-directories{/tr}</label>
        <div class="col-sm-8">
            <div class="form-check">
                <input type="checkbox" name="subdirToSubgal" class="form-check-input" value="true" id="subdirToSubgal">
            </div>
            <div class="form-text">
                {tr}eg. for "misc/screenshots/digicam0001.jpg" the file will be uploaded into a gallery called "screenshots" in the called "misc" inside the chosen gallery if it exists{/tr}
            </div>
        </div>
    </div>
    <div class="mb-3 row create-subgals" style="display:none;">
        <label class="col-sm-4 form-check-label" for="createSubgals">{tr}Create sub-galleries?{/tr}</label>
        <div class="col-sm-8">
            <div class="form-check">
                <input type="checkbox" name="createSubgals" class="form-check-input" value="true" id="createSubgals">
            </div>
            <div class="form-text">
                {tr}Sub-galleries will be automatically created if they don't exist and the user has permission. Note that these galleries will have the global file gallery permissions set.{/tr}
            </div>
        </div>
        <div class="mb-3 row">
            <label class="col-sm-4 form-check-label" for="subdirIntegerToSubgalId">{tr}Upload into galleries according sub-directory as galleryId{/tr}</label>
            <div class="col-sm-8">
                <div class="form-check">
                    <input type="checkbox" name="subdirIntegerToSubgalId" class="form-check-input" value="true" id="subdirIntegerToSubgalId">
                </div>
                <div class="form-text">
                    {tr}eg. for "42/digicam0001.jpg" the file will be uploaded into a gallery with the Id "42" if it exists{/tr}
                </div>
            </div>
        </div>
        {jq}
$("#subdirToSubgal").on("change", function () {
    if ($(this).prop("checked")) {
        $(".create-subgals").show();
    } else {
        $(".create-subgals").hide();
    }
}).trigger("change");

$("#subdirIntegerToSubgalId").on("change", function () {
    if ($(this).prop("checked")) {
        $("#createSubgals").prop("checked", false).prop("disabled", true);
    } else {
        $("#createSubgals").prop("disabled", false);
    }
}).trigger("change");

$("#batchUploadForm").on("submit", function () {
    return $("input[name='files[]']:checked").length > 0;
});

        {/jq}
    </div>
    <div class="mb-3 row">
        <label class="col-sm-4 form-check-label" for="subToDesc">{tr}Use the last sub-directory name as description{/tr}</label>
        <div class="col-sm-8">
            <div class="form-check">
                <input type="checkbox" name="subToDesc" class="form-check-input" value="true" id="subToDesc">
            </div>
            <div class="form-text">
                {tr}eg. from "misc/screenshots/digicam0001.jpg" a description "screenshots" will be created{/tr}r}
            </div>
        </div>
    </div>
    <div class="mb-3 row">
        <label class="col-sm-4 form-check-label"></label>
        <div class="col-sm-8">
            <input type="submit" class="btn btn-primary btn-sm" name="batch_upload" value="{tr}Process files{/tr}">
        </div>
    </div>
</form>
