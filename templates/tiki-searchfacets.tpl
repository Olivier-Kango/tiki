{extends $global_extend_layout|default:'layout_view.tpl'}

{block name=title}
    {title help="Search" admpage="search"}{tr}Search{/tr}{/title}
{/block}

{block name=content}
<div class="nohighlight">
    <form id="search-form" class="d-flex flex-row flex-wrap align-items-center" method="get" action="tiki-searchindex.php">
        <div class="d-flex align-items-start mb-3">
            <div class="pe-1">
                <label class="sr-only" for="filter~content">{tr}Search Query{/tr}</label>
                <div class="input-group">
                    <input class="form-control" type="search" name="filter~content" value="{$filter.content|escape}"/>
                    <input type="submit" class="btn btn-info" value="{tr}Search{/tr}"/>
                </div>
                {foreach from=$facets item=facet}
                    <input type="hidden" name="filter~{$facet|escape}" value="{$postfilter[$facet]|default:$filter[$facet]|escape}"/>
                {/foreach}
            </div>
            {if $prefs.tracker_tabular_enabled eq 'y' && ! empty($smarty.get.tabularId)}
                <div class="px-1">
                    <input type="hidden" name="tabularId" value="{$smarty.get.tabularId|escape}">
                    <button class="tabular-export btn btn-secondary">
                        {icon name=export} {tr}Export{/tr}
                    </button>
                    {jq}
                        $(document).on('click', '.tabular-export', function (e) {
                            var href = $.service('tabular', 'export_search_csv', {
                                tabularId: "{{$smarty.get.tabularId}}"
                            });
                            e.preventDefault();
                            document.location.href = href + '&' + $(this).closest('form').serialize();
                        });
                    {/jq}
                </div>
            {elseif $prefs.tracker_tabular_enabled eq 'y' && ! empty($filter.tracker_id)}
                <div class="px-1">
                    <button class="tabular-export btn btn-secondary">
                        {icon name=export} {tr}Export{/tr}
                    </button>
                    {jq}
                        $(document).on('click', '.tabular-export', function (e) {
                            var href = $.service('tabular', 'export_search_csv', {
                                trackerId: "{{$filter.tracker_id}}"
                            });
                            e.preventDefault();
                            $.openModal({
                                remote: href + '&' + $(this).closest('form').serialize()
                            });
                        });
                    {/jq}
                </div>
            {/if}
            {if $prefs.storedsearch_enabled eq 'y' and $user}
                <div class="ps-1">
                    <input type="hidden" name="storeAs" value=""/>
                    <a href="{service controller=search_stored action=select modal=true}" id="store-query" class="btn btn-secondary">{tr}Save Search{/tr}</a>
                    <a href="{service controller=search_stored action=list}" class="btn btn-link">{tr}View Saved Searches{/tr}</a>
                    {jq}
                        $('#store-query').clickModal({
                            success: function (data) {
                                var form = $(this).closest('form')[0];

                                $(form.storeAs).val(data.queryId);
                                $(form).attr('method', 'post');
                                $(form).trigger("submit");
                            }
                        });
                    {/jq}
                </div>
            {/if}
        </div>
    </form>
</div><!--nohighlight-->
    {* do not change the comment above, since smarty 'highlight' outputfilter is hardcoded to find exactly this... instead you may experience white pages as results *}

{if isset($results)}
    {$results}
{/if}

<div class="clearfix"></div>
{/block}
