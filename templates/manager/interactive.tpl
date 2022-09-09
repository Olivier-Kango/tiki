{extends "layout_view.tpl"}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="navigation"}
    {include file='manager/nav.tpl'}
{/block}

{block name="content"}
    <div class="rounded bg-dark text-light p-3" id="ws-response-container"></div>
{/block}

{jq}
var tikiWS = tikiOpenWS('console');
tikiWS.onmessage = function(e) {
  $('#ws-response-container').append(e.data.trim().replaceAll("\n", "<br>\n") + "<br>");
};
tikiWS.onopen = function(e) {
  tikiWS.send({{$command|json_encode}});
};
tikiWS.onerror = function(e) {
  $('#ws-response-container').append('<span class="error">Error connecting to realtime communication server. It it isn\'t set up correctly, you can disable realtime setting from Tiki admin.</span>');
};
{/jq}
