<form method="post" action="tiki-page_contribution.php" class="mb-4">
    <input type="hidden" name="page" id="page" value="{$page}">
    <fieldset>
        <legend>{tr}Process{/tr}</legend>
        <div class="mb-3 row">
            <div class="form-check">
                <label class="form-check-label"><input class="form-check-input" type="radio" name="process" id="process0" value="0"{if $process==0} checked="checked"{/if} title="{tr}Original wiki text{/tr}" />{tr}Original wiki text{/tr}</label><br>
                <label class="form-check-label"><input class="form-check-input" type="radio" name="process" id="process1" value="1"{if $process!=0 and $process!=2} checked="checked"{/if} title="{tr}Parsed Text (HTML){/tr}">{tr}Parsed Text (HTML){/tr}</label><br>
                <label class="form-check-label"><input class="form-check-input" type="radio" name="process" id="process2" value="2"{if $process==2} checked="checked"{/if} title="{tr}Output text only (No HTML tags){/tr}">{tr}Output text only (No HTML tags){/tr}</label><br>
            </div>
        </div>
    </fieldset>
    <fieldset>
        <legend>{tr}Options{/tr}</legend>
        <div class="mb-3 row">
            <div class="form-check">
                <label class="form-check-label"><input class="form-check-input" type="checkbox" name="showstatistics" value="1"{if $showstatistics==1} checked="checked"{/if} title="{tr}Show statistics{/tr}"> {tr}Show statistics{/tr}</label><br>
                <label class="form-check-label"><input class="form-check-input" type="checkbox" name="showpage" value="1"{if $showpage==1} checked="checked"{/if} title="{tr}Visualize page changes{/tr}"> {tr}Visualize page changes{/tr}</label><br>
                <label class="form-check-label"><input class="form-check-input" type="checkbox" name="showpopups" value="1" {if $showpopups==1} checked="checked"{/if} title="{tr}Visualize page changes{/tr}"> {tr}Show popups{/tr}</label><br>
                {if $prefs.feature_source eq 'y' and $tiki_p_wiki_view_source eq 'y'}<label class="form-check-label"><input class="form-check-input" type="checkbox" name="escape" value="1" {if $escape==1} checked="checked"{/if} title="{tr}Escape HTML / Wiki syntax in page changes{/tr}"> {tr}Escape HTML / Wiki syntax in page changes{/tr}</label><br>{/if}
            </div>
        </div>
    </fieldset>
    <fieldset>
        <legend>{tr}Version{/tr}</legend>
        <div class="table-responsive">
            <table class="table">
                <tr>
                    <th>{tr}Version{/tr}</th>
                    <th>{tr}Date{/tr}</th>
                    <th>{tr}User{/tr}</th>
                </tr>
                <tr class="odd">
                    <td><label><input type="radio" name="lastversion" value="{$info.version}"{if $lastversion==$info.version or $lastversion==0} checked="checked"{/if} title="{tr}Version{/tr} {$info.version}"> <strong>{$info.version}</strong></label></td>
                    <td><strong>{$info.lastModif|tiki_short_datetime}</strong></td>
                    <td><strong>{$info.user|userlink}</strong></td>
                </tr>
                {foreach name=hist item=element from=$history}
                <tr>
                    <td class="text"><label><input type="radio" name="lastversion" value="{$element.version}"{if $lastversion==$element.version} checked="checked"{/if} title="{tr}Version{/tr} {$info.version}"> {$element.version}</label></td>
                    <td class="date">{$element.lastModif|tiki_short_datetime}</td>
                    <td class="text">{$element.user|userlink}</td>
                </tr>
                {/foreach}
            </table>
        </div>
    </fieldset>
    <div>
        <input type="submit" class="btn btn-primary btn-sm" name="show" value="{tr}Show contributions{/tr}">
        {button href="tiki-index.php?page=$page" _text="{tr}View page{/tr}" _class="btn-sm"}
    </div>
</form>
