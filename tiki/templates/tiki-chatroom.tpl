<h2>{tr}Chatroom{/tr}: {$channelName}</h2>
<table class="chatroom">
<tr>
  <td class="chatchannels" width="20%" valign="top">
  <div class="chattitle">{tr}Active Channels{/tr}:</div>
  {section name=chan loop=$channels}
    <a class="link" href="tiki-chatroom.php?nickname={$nickname}&amp;channelId={$channels[chan].channelId}">{$channels[chan].name}</a><br/>
  {/section}
  <br/>
  <div class="chattitle">{tr}Users in this channel{/tr}:</div>
  <iframe frameborder="0" height="200" scrolling="auto" marginwidth="0" marginheight="0" width="100%" src="tiki-chat_users.php?channelId={$channelId}"></iframe>
  </td>
  <td class="chatarea" valign="top" width="80%">
  <iframe width="100%" name="chatdata" scrolling="auto" frameborder="0" height="360" src="tiki-chat_center.html">Browser not supported</iframe>
  <iframe width='0' height='0' frameborder="0" src="tiki-chat_loader.php?refresh={$refresh}&amp;enterTime={$now}&amp;nickname={$nickname}&amp;channelId={$channelId}">Browser not supported</iframe>
  </td>
  <!--
  <td width="20%" valign="top">
    <div class="texthead">{tr}Channel Information{/tr}<a class="link" href="chat.php">(re)</a></div>
    <div class="text">
    Channel: {$channel_info.name}<br/>
    Ratio: {$channel_info.ratio} <br/><br/>
    Desc: {$channel_info.description}
    </div>
  </td>
  -->
</tr>
</table>
<table class="chatform">
<tr>
  <td class="tdchatform">
  <iframe name="chatbox" scrolling="no" width="100%" height="52" frameborder="0" src="tiki-chat_box.php?nickname={$nickname}&amp;channelId={$channelId}">Browser not supported</iframe>
  </td>
</tr>  
</table>
<table class="chatform">
<tr>
  <td class="tdchatform">
   <small>
   {tr}Use ":nickname:message" for private messages{/tr}<br/> 
   {tr}Use "[URL|description] or [URL] for links"{/tr}<br/>
   {tr}Use "(:name:) for smileys{/tr} (smile, biggrin, cool, evil, frown, rolleyes, confused, cry, eek, exclaim, idea, mad, surprised, lol, redface, neutral, sad, twisted, wink)"<br/>
   </small>
  </td>
</tr>  
</table>


          
