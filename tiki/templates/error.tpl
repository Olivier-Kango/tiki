{* Index we display a wiki page here *}
{include file="header.tpl"}
<div id="tiki-main">
  <div id="tiki-top">
    {include file="tiki-top_bar.tpl"}
  </div>
  <div id="tiki-mid">
    <table border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
      <td width="150px" valign="top"><div id="left">
      {section name=homeix loop=$left_modules}
      {$left_modules[homeix].data}
      {/section}
      </div></td>
      <td valign="top"><div id="center">
      <br/>
	<h2>Error! <a href="index.php" class="wiki">(return to home page)</a></h2>
	<div class="wikitext">{$msg}</div>
      </div></td>
      <td width="150px" valign="top"><div id="right">
      {section name=homeix loop=$right_modules}
      {$right_modules[homeix].data}
      {/section}
      </div></td>
    </tr>
    </table>
  </div>
  <div id="tiki-bot">
    {include file="tiki-bot_bar.tpl"}
  </div>
</div>
{include file="footer.tpl"}
