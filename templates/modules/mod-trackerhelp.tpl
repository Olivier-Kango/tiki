{tikimodule error=$module_params.error title=$tpl_module_title name="trackerhelp" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}
    {autocomplete element=".trackername" type="trackername"}
    <form method="post">
        <div class="mb-3 row mx-0">
            <label class="col-sm-3 col-form-label" for="trackerhelp_name">{tr}Tracker name:{/tr}</label>
            <div class="col-sm-9">
                <input type="text" name="trackerhelp_name" id="trackerhelp_name" class="form-control trackername"{if isset($smarty.session.trackerhelp_name)} value="{$smarty.session.trackerhelp_name|escape}"{/if} />
            </div>
        </div>
        <div class="mb-3 row mx-0">
            <div class="col-sm-9 offset-sm-3">
                <input type="submit" class="btn btn-primary btn-sm" name="trackerhelp" value="{tr}Go{/tr}" />
            </div>
        </div>
    </form>

    {if !empty($smarty.session.trackerhelp_text)}
        {tr}ID:{/tr} {$smarty.session.trackerhelp_id}<div style="float:right"><a onclick="insertAt('editwiki', '{foreach from=$smarty.session.trackerhelp_pretty item=line}{$line|escape} {/foreach}')" class="'tips" title=":{tr}Insert fields in wiki textarea{/tr}">{icon name='add'}</a></div><br>
        {textarea _simple='y' _toolbars='n' cols=$module_params.cols rows=$module_params.height}{foreach from=$smarty.session.trackerhelp_text item=line}{$line|escape}
        {/foreach}{/textarea}
    {/if}
{/tikimodule}
