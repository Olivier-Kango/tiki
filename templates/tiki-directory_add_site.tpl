{title url="tiki-directory_add_site.php?parent=$parent"}{tr}Add a new site{/tr}{/title}

{include file='tiki-directory_bar.tpl'}

{if $categs[0] eq ''}
    {icon name='error' style="vertical-align:middle" alt="{tr}Error{/tr}"} {tr}You cannot add sites until Directory Categories are setup.{/tr} <br>
    {if $tiki_p_admin_directory_cats ne 'y'}
        {tr}Please contact the Site Administrator{/tr}{else}{tr _0='<a href="tiki-directory_admin_categories.php">' _1="</a>"}%0Add a directory category now%1.{/tr}
    {/if}
{else}
    {if $save eq 'y'}
        <h2>{tr}Site added{/tr}</h2><br>
        <div class="mb-3 row">
            <div class="col-sm-12">
                <p class="lead">{icon name='ok' alt="{tr}OK{/tr}" style="vertical-align:middle" align="left"} {tr}The following site was added, but may require validation by the admin before appearing on the lists.{/tr}</p>
            </div>
        </div>
        <div>
            <div class="mb-3 row">
                <label class="col-sm-3 text-end">{tr}Name{/tr}</label>
                <div class="col-sm-7">
                    {$info.name}
                </div>
            </div>
            <div class="mb-3 row">
                <label class="col-sm-3 text-end">{tr}Description{/tr}</label>
                <div class="col-sm-7">
                    {$info.description}
                </div>
            </div>
            <div class="mb-3 row">
                <label class="col-sm-3 text-end">{tr}URL{/tr}</label>
                <div class="col-sm-7">
                    {$info.url}
                </div>
            </div>
            {if $prefs.directory_country_flag eq 'y'}
            <div class="mb-3 row">
                <label class="col-sm-3 text-end">{tr}Country{/tr}</label>
                <div class="col-sm-7">
                    {$info.country}
                </div>
            </div>
            {/if}
        </div>
    {else}

        {* Display a form to add or edit a site *}
        <h2>{if $siteId}{tr}Edit a site{/tr}{else}{tr}Add a site{/tr}{/if}</h2>
        <form action="tiki-directory_add_site.php" method="post">
            {ticket}
            <input type="hidden" name="parent" value="{$parent|escape}">
            <input type="hidden" name="siteId" value="{$siteId|escape}">

            <div class="mb-3 row">
                <label class="col-sm-3 col-form-label" for="name">{tr}Name{/tr}</label>
                <div class="col-sm-7">
                    <input type="text" id="name" name="name" value="{$info.name|escape}" class="form-control">
                </div>
            </div>
            <div class="mb-3 row">
                <label class="col-sm-3 col-form-label" for="description">{tr}Description{/tr}</label>
                <div class="col-sm-7">
                    <textarea rows="5" cols="60" id="description" name="description" class="form-control">{$info.description|escape}</textarea>
                </div>
            </div>
            <div class="mb-3 row">
                <label class="col-sm-3 col-form-label" for="url">{tr}URL{/tr}</label>
                <div class="col-sm-7">
                    <input type="text" size="60" id="url" name="url" id="url" value="{if $info.url ne ""}{$info.url|escape}{else}https://{/if}" class="form-control">
                </div>
            </div>
            <div class="mb-3 row">
                <label class="col-sm-3 col-form-label" for="siteCats">{tr}Directory Categories{/tr}</label>
                <div class="col-sm-7">
                    <select id="siteCats" name="siteCats[]" multiple="multiple" size="4" class="form-control">
                        {section name=ix loop=$categs}
                            <option value="{$categs[ix].categId|escape}" {if $categs[ix].belongs eq 'y' or $categs[ix].categId eq $addtocat}selected="selected"{/if}>
                                {$categs[ix].path|escape}
                            </option>
                        {/section}
                    </select>
                    {if $categs|@count ge '2'}
                        <br>
                        {remarksbox type="tip" title="{tr}Tip{/tr}"}{tr}Use Ctrl+Click to select multiple options{/tr}{/remarksbox}
                    {/if}
                </div>
            </div>
            {if $prefs.directory_country_flag eq 'y'}
            <div class="mb-3 row">
                <label class="col-sm-3 col-form-label" for="country">{tr}Country{/tr}</label>
                <div class="col-sm-7">
                    <select id="country" name="country" class="form-control">
                        {section name=ux loop=$countries}
                            <option value="{$countries[ux]|escape}" {if $info.country eq $countries[ux]}selected="selected"{/if}>{tr}{$countries[ux]}{/tr}</option>
                        {/section}
                    </select>
                    {if $categs|@count ge '2'}
                        <br>
                        {remarksbox type="tip" title="{tr}Tip{/tr}"}{tr}Use Ctrl+Click to select multiple options{/tr}{/remarksbox}
                    {/if}
                </div>
            </div>
            {else}
                <input type="hidden" name="country" value="None">
            {/if}
            <input name="isValid" type="hidden" value="">
            {if $prefs.feature_antibot eq 'y' && $user eq ''}
                {include file='antibot.tpl' td_style="formcolor"}
            {/if}
            <div class="mb-3 row">
                <div class="col-sm-7 offset-sm-3">
                    <input type="submit" class="btn btn-primary" name="save" value="{tr}Save{/tr}">
                </div>
            </div>
        </form>
    {/if}
{/if}
