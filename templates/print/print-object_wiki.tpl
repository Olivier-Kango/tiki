{if $prefs.feature_page_title ne 'n'}<h1>{$info.pageName}</h1>{/if}
<div class="wikitext">{$info.parsed}</div>
<div id="comment-container-{$info.page_id|escape}" data-bs-target="{service controller=commnet action=list type="wiki page" objectId=$info.pageName}"></div>
{jq}
    var id = '#comment-container-{{$info.page_id}}';
    $(id).comment_load($(id).data('bs-target'));
    $(document).on("ajaxComplete", function(){$(id).tiki_popover();});
{/jq}
