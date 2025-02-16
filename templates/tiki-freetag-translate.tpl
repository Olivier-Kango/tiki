{title admpage=freetags}{tr}Tag translation{/tr}{if isset($data)}: {$data.pageName}{/if}{/title}
<div class="t_navbar mb-4">
    {if $tiki_p_admin_freetags eq 'y'}
        {button href="tiki-browse_freetags.php" _class="btn btn-link" _icon_name="list" _text="{tr}Browse Tags{/tr}"}
    {/if}
    {if isset($data)}
        {button href="tiki-index.php?page=$page_name" class="btn btn-primary" _text="{tr}View page{/tr}"}
    {/if}
</div>
{remarksbox type="tip" title="{tr}Note{/tr}"}
    {tr}Tags that were created on pages with no language set will remain universal (i.e. is the same tag in all languages) until a language has been set for the tag. Until then, they cannot be translated.{/tr}
{/remarksbox}
<form method="post" action="tiki-freetag_translate.php" class="form">
    <input type="hidden" name="type" value="{$type|escape}">
    <input type="hidden" name="objId" value="{$objId|escape}">
    <input type="hidden" name="offset" value="{$freetags_offset|escape}">
{jq}
$('#scblink').on("click", function(e){
    e.preventDefault();
    var table = document.getElementById( 'tagtranslationtable' );
    var list = table.getElementsByTagName( 'input' );
    for( key in list )
    {
        if( list[key].type == 'checkbox' )
        {
            list[key].style.display = 'inline';
        }
    }

    document.getElementById('scblink').style.display = 'none';

    return false;
});
{/jq}
    <nav>
        <ul class="pager">
            <li class="previous">
                {if $previous}
                    <a class="neatlink" href="{$previous|escape}">
                        {icon name="previous"}{tr}Previous{/tr}
                    </a>
                {/if}
            </li>
            <li>
                {button  _id="scblink" _text="{tr}Show checkboxes{/tr}"}
            </li>
            <li class="next">
                <a class="neatlink" href="{$next|escape}">
                    {tr}Next{/tr}{icon name="next"}
                </a>
            </li>
        </ul>
    </nav>

    <div class="table-responsive">
        <table class="table" id="tagtranslationtable">
            <thead>
                <tr>
                {foreach item=lang from=$languageList}
                    {if $lang neq ''}
                        <th class="text-center">{$lang}</th>
                    {/if}
                {/foreach}
                </tr>
            </thead>
            <tbody>
                {if !$tagList}
                    <tr>
                        <td colspan="{if in_array('',$languageList)}{($languageList|@count) - 1}{else}{$languageList|@count}{/if}">
                            {tr}There are no tags on this page in your preferred languages{/tr}
                        </td>
                    </tr>
                {/if}
                {foreach item=tag key=group from=$tagList}
                    <tr>
                        {if $tag[$blank] eq ''}
                            {foreach item=lang from=$languageList}
                                {if $lang neq ''}
                                    <td>
                                        {if !$tag[$lang]}
                                            <div>
                                                <input type="text" name="newtag[{$group}][{$lang}]" value="{$newtags[$group][$lang]}" class="form-control">
                                                <input type="hidden" name="rootlang[{$group}][{$lang}]" value="{$rootlang[$group]}">
                                            </div>
                                        {else}
                                            <div class="text-center form-check">
                                                <label class="form-check-label">{$tag[$lang].tag}
                                                    <input class="form-check-input" style="display: none" type="checkbox" name="clear[]" value="{$tag[$lang].tagId}">
                                                </label>
                                            </div>
                                        {/if}
                                    </td>
                                {/if}
                            {/foreach}
                        {else}
                            {assign var=btag value=$tag[$blank]}

                            <td colspan="{if in_array('',$languageList)}{($languageList|@count) - 1}{else}{$languageList|@count}{/if}">
                                <div class="col-sm-3">
                                    {$btag.tag} - {tr}Set language{/tr}
                                </div>
                                <div class="col-sm-9">
                                    <select name="setlang[{$btag.tagId}]" class="form-control">
                                        <option value="">{tr}Universal{/tr}</option>
                                        {foreach item=lang from=$languageList}
                                            {if $lang neq ''}
                                                <option value="{$lang}"{if $setlang[$btag.tagId] eq $lang} selected="selected"{/if}>{$lang}</option>
                                            {/if}
                                        {/foreach}
                                    </select>
                                </div>
                            </td>
                        {/if}
                    </tr>
                {/foreach}
                <tr>
                    <td colspan="{if in_array('',$languageList)}{($languageList|@count) - 1}{else}{$languageList|@count}{/if}">
                        <div class="text-center">
                            <input type="submit" class="btn btn-primary" name="save" value="{tr}Save{/tr}">
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="tiki-form-group row">
        <label for="additional_languages" class="control-lable">
            {tr}Displayed languages:{/tr}
        </label>
        <div class="input-group">
            <select multiple="multiple" name="additional_languages[]" class="form-select">
                {foreach item=lang from=$fullLanguageList}
                    <option value="{$lang.value}"{if in_array($lang.value, $languageList)} selected="selected"{/if}>{$lang.name}</option>
                {/foreach}
            </select>
            <input type="submit" class="btn btn-primary" value="{tr}Select{/tr}">
        </div>
    </div>
</form>
