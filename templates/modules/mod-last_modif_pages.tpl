{* $Id$ *}

{tikimodule error=$module_params.error title=$tpl_module_title name="last_modif_pages" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}
    {if $modLastModifTable eq 'y'}
        <div class="table-responsive">
            <table class="table">
                <tr>
                    {if $date eq 'y'}<th>{tr}Date{/tr}</th>{/if}
                    <th>{tr}Page{/tr}</th>
                    {if $action eq 'y'}<th>{tr}Action{/tr}</th>{/if}
                    {if $modif_user eq 'y'}<th>{tr}User{/tr}</th>{/if}
                    {if $comment eq 'y'}<th>{tr}Comment{/tr}</th>{/if}
                </tr>

                {foreach $modLastModif as $page}
                    <tr>
                        {if $date eq 'y'}
                            <td>
                                {if $prefs.jquery_timeago eq 'y'}{$sameday='n'}{else}{$sameday=$prefs.tiki_same_day_time_only}{/if}
                                {$page.lastModif|tiki_short_datetime:'':'n'}{if $prefs.wiki_authors_style ne 'lastmodif'}{/if}
                            </td>
                        {/if}
                        <td>
                            <a class="linkmodule"
                                {if $absurl eq 'y'}
                                    href="{$base_url}tiki-index.php?page={$page.pageName|escape:"url"}"
                                {else}
                                    href="{$page.pageName|sefurl}"
                                {/if}
                                {* jquery_timeago doesn't work in a title atribute *}
                                {if $prefs.jquery_timeago eq 'y'}{$sameday='n'}{else}{$sameday=$prefs.tiki_same_day_time_only}{/if}
                                title="{$page.lastModif|tiki_short_datetime:'':'n'}{if $prefs.wiki_authors_style ne 'lastmodif'}, {tr}by{/tr} {$page.user|username}{/if}{if (strlen($page.pageName) > $maxlen) && ($maxlen > 0)}, {$page.pageName|escape}{/if}"
                            >
                                {if $maxlen > 0}{* 0 is default value for maxlen eq to 'no truncate' *}
                                    {if $namespaceoption eq 'n'}
                                        {$data=$prefs.namespace_separator|explode:$page.pageName}
                                        {if empty($data['1'])}
                                            {$pagename=$data['0']}
                                        {else}
                                            {$pagename=$data['1']}
                                        {/if}
                                        {$pagename|escape|truncate:$maxlen:"...":true}
                                    {else}
                                        {$data=$prefs.namespace_separator|explode:$page.pageName}
                                        {if sizeof($data) == 1}
                                            {$pagename=$page.pageName|escape}
                                        {else}
                                            {$pagename=$page|escape}
                                        {/if}
                                        {$pagename|truncate:$maxlen:"...":true}
                                    {/if}
                                {else}
                                    {$data=$prefs.namespace_separator|explode:$page.pageName}
                                    {if $namespaceoption eq 'n'}
                                        {if empty($data['1'])}
                                            {$data['0']}
                                        {else}
                                            {$data['1']}
                                        {/if}
                                    {else}
                                        {$page.pageName|escape}
                                    {/if}
                                {/if}
                            </a>
                        </td>

                        {if $action eq 'y'}
                            <td>{if $page.action eq 'Updated'}{tr}Updated{/tr}{else if $page.action eq 'Created'}{tr}Created{/tr}{else}{$page.action}{/if}</td>
                        {/if}

                        {if $modif_user eq 'y'}
                            <td>{$page.user|username}</td>
                        {/if}

                        {if $comment eq 'y'}
                            {if $maxcomment > 0}
                                <td>{$page.comment|truncate:$maxcomment:"...":true}</td>
                            {else}
                                <td>{$page.comment}</td>
                            {/if}
                        {/if}
                    </tr>

                {/foreach}

            </table>
        </div>
        <a class="linkmodule ms-3" href="{$url}">...{tr}more{/tr}</a>
    {else}
        {modules_list list=$modLastModif nonums=$nonums}
            {foreach $modLastModif as $page}
                <li>
                    <a class="linkmodule"
                        {if $absurl eq 'y'}
                            href="{$base_url}tiki-index.php?page={$page.pageName|escape:"url"}"
                        {else}
                            href="{$page.pageName|sefurl}"
                        {/if}
                        {* jquery_timeago doesn't work in a title atribute *}
                        {if $prefs.jquery_timeago eq 'y'}{$sameday='n'}{else}{$sameday=$prefs.tiki_same_day_time_only}{/if}
                        title="{$page.lastModif|tiki_short_datetime:'':'n'}{if $prefs.wiki_authors_style ne 'lastmodif'}, {tr}by{/tr} {$page.user|username}{/if}{if (strlen($page.pageName) > $maxlen) && ($maxlen > 0)}, {$page.pageName|escape}{/if}"
                    >

                        {if $maxlen > 0}{* 0 is default value for maxlen eq to 'no truncate' *}
                            {if $namespaceoption eq 'n'}
                                {$data=$prefs.namespace_separator|explode:$page.pageName}
                                {if empty($data['1'])}
                                    {$pagename=$data['0']}
                                {else}
                                    {$pagename=$data['1']}
                                {/if}
                                {$pagename|escape|truncate:$maxlen:"...":true}
                            {else}
                                {$data=$prefs.namespace_separator|explode:$page.pageName}
                                {if sizeof($data) == 1}
                                    {$pagename=$page.pageName|escape}
                                {else}
                                    {$pagename=$page|escape}
                                {/if}
                                {$pagename|truncate:$maxlen:"...":true}
                            {/if}
                        {else}
                            {$data=$prefs.namespace_separator|explode:$page.pageName}
                            {if $namespaceoption eq 'n'}
                                {if empty($data['1'])}
                                    {$data['0']}
                                {else}
                                    {$data['1']}
                                    {/if}
                            {else}
                                {$page.pageName|escape}
                            {/if}
                        {/if}

                    </a>
                </li>
            {/foreach}
        {/modules_list}
        <a class="linkmodule ms-3" href="{$url}">...{tr}more{/tr}</a>
    {/if}
{/tikimodule}
