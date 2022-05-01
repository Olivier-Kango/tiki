{extends "layout_view.tpl"}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="navigation"}
    {include file='manager/nav.tpl'}
{/block}

{block name="content"}
   <div style="background: #ccc;" class="rounded p-3">
        <p>You can't run this command on web browser, copy it and run it in your terminal!</p>
        <div class="rounded bg-dark p-3 d-flex align-items-center justify-content-between"> 
            <span style="color: #00ffff" id="command">php tiki-manager.php instance:access</span>
            <svg id="copy" onclick="copyCommand()" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clipboard2" viewBox="0 0 16 16" color="#fff" style="cursor: pointer;">
                <path d="M3.5 2a.5.5 0 0 0-.5.5v12a.5.5 0 0 0 .5.5h9a.5.5 0 0 0 .5-.5v-12a.5.5 0 0 0-.5-.5H12a.5.5 0 0 1 0-1h.5A1.5 1.5 0 0 1 14 2.5v12a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 2 14.5v-12A1.5 1.5 0 0 1 3.5 1H4a.5.5 0 0 1 0 1h-.5Z"/>
                <path d="M10 .5a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5.5.5 0 0 1-.5.5.5.5 0 0 0-.5.5V2a.5.5 0 0 0 .5.5h5A.5.5 0 0 0 11 2v-.5a.5.5 0 0 0-.5-.5.5.5 0 0 1-.5-.5Z"/>
            </svg>
            <svg id="check" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check2" viewBox="0 0 16 16" color="#fff">
                <path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0z"/>
            </svg>
        </div>
   </div>
{/block}

{jq notonready=true}
    $('#check').hide();
    $('#copy').show();
    function copyCommand() {
        var $temp = $("<input>");
        $("body").append($temp);
        $temp.val($('#command').html()).select();
        document.execCommand("copy");
        $temp.remove();

        $('#copy').hide();
        $('#check').show();
    }
{/jq}
