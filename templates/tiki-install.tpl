<div id="siteheader" class="clearfix">
	<div id="header-top">
		<div id="sitelogo" style="padding-left:70px"><h1><img style="border:medium none; vertical-align:middle" alt="{tr}TikiWiki CMS/Groupware{/tr}" src="img/tiki/tiki3.png" />
			<span style="vertical-align:middle">{tr}Tiki installer{/tr} v{$tiki_version_name} <a title="{tr}Help{/tr}" href="http://doc.tikiwiki.org/Installation" target="help"><img style="border:0" src='img/icons/help.gif' alt="{tr}Help{/tr}" /></a></span></h1>
		</div>
	</div>
</div>

<div id="middle" class="clearfix">
	<div id="c1c2" class="clearfix">
		<div id="wrapper" class="clearfix">
			<div id="col1" class="marginleft">
				<div id="tiki-center" class="clearfix content">

{if $install_step eq '0' or !$install_step}
{* start of installation *}
<h1>{tr}Welcome{/tr}</h1>
<div class="clearfix">
	<p>{tr}Welcome to the Tiki installation and upgrade script.{/tr} {tr}Use this script to install a new Tiki database or upgrade your existing database to release{/tr} <strong>{$tiki_version_name}</strong></p>
	<ul>
		<li>{tr}For the latest information about this release, please read the{/tr} <a href="http://tikiwiki.org/tiki-index.php?page=ReleaseNotes{$tiki_version_name|urlencode}" target="_blank">{tr}Release Notes{/tr}</a>.</li>
		<li>{tr}For complete documentation, please visit{/tr} <a href="http://doc.tikiwiki.org" target="_blank">http://doc.tikiwiki.org</a>.</li>
		<li>{tr}For more information about Tiki, please visit{/tr} <a href="http://tikiwiki.org" target="_blank">http://tikiwiki.org</a>.</li>
	</ul>

	<form action="tiki-install.php" method="post">
		{tr}Select your language:{/tr}
		<select name="lang" id="general-lang" onchange="javascript:submit()">
			{section name=ix loop=$languages}
				<option value="{$languages[ix].value|escape}"
					{if $prefs.site_language eq $languages[ix].value}selected="selected"{/if}>{$languages[ix].name}</option>
			{/section}
		</select>
		<input type="hidden" name="install_step" value="1" />
		{if $multi}		<input type="hidden" name="multi" value="{$multi}" />{/if}
	</form>
</div>
<div align="center" style="margin-top:1em;">
	<form action="tiki-install.php" method="post">
{if $multi}		<input type="hidden" name="multi" value="{$multi}" />{/if}
{if $lang}		<input type="hidden" name="lang" value="{$lang}" />{/if}
		<input type="hidden" name="install_step" value="1" />
		<input type="submit" value=" {tr}Continue{/tr} " />
	</form>
</div>

{elseif $install_step eq '1'}
<h1>{tr}Read the License{/tr}</h1>
<p>{tr}Tiki is software distributed under the LGPL license.{/tr} {tr} <a href="http://creativecommons.org/licenses/LGPL/2.1/" target="_blank">Here is a human-readable summary of the license below, including many translations.</a>{/tr}</p>
<div align="center" style="margin-top:1em;">
<iframe src="license.txt" width="700px" height="400px"> </iframe>
	<form action="tiki-install.php" method="post">
{if $multi}			<input type="hidden" name="multi" value="{$multi}" />{/if}
{if $lang}			<input type="hidden" name="lang" value="{$lang}" />{/if}
		<input type="hidden" name="install_step" value="2" />
		<input type="submit" value=" {tr}Continue{/tr} " />
	</form>
</div>

{elseif $install_step eq '2'}
<h1>{tr}Review the System Requirements{/tr}</h1>
<div style="float:left;width:60px"><img src="img/webmail/compose.gif" alt="{tr}Review{/tr}" /></div>
<div class="clearfix">
	<p>{tr}Before installing Tiki, <a href="http://doc.tikiwiki.org/tiki-index.php?page=Requirements+and+Setup&bl=y" target="_blank">review the documentation</a> and confirm that your system meets the minimum requirements.{/tr}</p>
	<p>{tr}This installer will perform some basic checks automatically.{/tr}</p>
	<br />
	<h2>{tr}Memory{/tr}</h2>
{if $php_memory_limit <= 0}
	<div style="border: solid 1px #000; padding: 5px; background: #a9ff9b;">
		<p align="center"><img src="pics/icons/accept.png" alt="{tr}Success{/tr}" style="vertical-align:middle"/> {tr}Tiki has not detected your PHP memory_limit.{/tr} {tr}This probably means you have no set limit (all is well).{/tr} </p>
	</div>	
{elseif $php_memory_limit < 32 * 1024 * 1024}
	<div style="border-style: solid; border-width: 1; padding: 5px; background: #FF0000">
		<p align="center"><img src="pics/icons/delete.png" alt="{tr}Alert{/tr}" style="vertical-align:middle" /> {tr}Tiki has detected your PHP memory limit at:{/tr} {$php_memory_limit|kbsize:true:0}</p>
	</div>
	<p>{tr}Tiki requires <strong>at least</strong> 32MB of PHP memory for script execution.{/tr} {tr}Allocating too little memory will cause Tiki to display blank pages.{/tr}</p>
	<p>{tr}To change the memory limit, use the <strong>memory_limit</strong> key in your <strong>php.ini </strong> file (for example: memory_limit = 32M) and restart your webserver.{/tr}</p>

{else}
	<div style="border: solid 1px #000; padding: 4px; background-color: #a9ff9b;">
		<p align="center">
		  <span style="font-size: large; padding: 4px;">
		  <img src="pics/icons/accept.png" alt="{tr}Success{/tr}" style="vertical-align:middle"/> {tr}Tiki has detected your PHP memory_limit at:{/tr} {$php_memory_limit|kbsize:true:0}. 
		  </span>
		</p>
	</div>	
{/if}			

	<br />
	<h2>{tr}Mail{/tr}</h2>
	<p>{tr}Tiki uses the PHP <strong>mail</strong> function to send email notifications and messages.{/tr}</p>
{if $perform_mail_test ne 'y'}
	<p>{tr}To test your system configuration, Tiki will attempt to send a test message to you.{/tr}</p>
	<div>
	<form action="tiki-install.php#mail" method="post">
		<div style="padding:1em 7em;">
			<label for="admin_email_test">{tr}Test email:{/tr}</label>
			<input type="text" size="40" name="email_test_to" id="email_test_to" value="{if isset($email_test_to)}{$email_test_to}{/if}"/>
			{if isset($email_test_err)}<span class="attention"><em>{$email_test_err}</em></span>
			{else}<em>{tr}Email address to send test to.{/tr}</em>{/if}
			<br /><br />
			<input type="checkbox" name="email_test_cc" checked="checked" value="1" />
			<em>{tr}Copy test mail to {/tr} {$email_test_tw}?</em>
		</div>
		<input type="hidden" name="install_step" value="2" />
		<input type="hidden" name="perform_mail_test" value="y" />
		<div align="center">
			<input type="submit" value=" {tr}Send Test Message{/tr} " />
		</div>
{if $multi}		<input type="hidden" name="multi" value="{$multi}" />{/if}
{if $lang}		<input type="hidden" name="lang" value="{$lang}" />{/if}
		
	</form>
	</div>
{else}
	
{if $mail_test eq 'y'}
	<div style="border: solid 1px #000; padding: 5px; background: #a9ff9b;">
		<p align="center"><img src="pics/icons/accept.png" alt="{tr}Success{/tr}" style="vertical-align:middle" /> {tr}Tiki was able to send a test message to {$email_test_to}.{/tr}</p>
	</div>
	<p>&nbsp;</p>
{else}
	<div style="border: solid 1px #000; padding: 5px; background: #FF0000">
		<p align="center"><img src="pics/icons/delete.png" alt="{tr}Alert{/tr}" style="vertical-align:middle" /> {tr}Tiki was not able to send a test message.{/tr} {tr}Review your mail log for details.{/tr}</p>
	</div>
	<p>{tr}Review the mail settings in your <strong>php.ini</strong> file (for example: confirm that the <strong>sendmail_path</strong> is correct).{/tr} {tr}If your host requires SMTP authentication, additional configuration may be necessary.{/tr}</p>
{/if}
{/if}
	<br />
	<h2>{tr}Image Processing{/tr}</h2>
{if $gd_test eq 'y'}
	<div style="border: solid 1px #000; padding: 5px; background: #a9ff9b;">
		<p align="center"><img src="pics/icons/accept.png" alt="{tr}Success{/tr}" style="vertical-align:middle" /> {tr}Tiki detected:{/tr} <strong>GD {$gd_info}</strong>.</p>
	</div>
{else}
	<div style="border: solid 1px #000; padding: 5px; background: #FF0000">
		<p align="center"><img src="pics/icons/delete.png" alt="{tr}Alert{/tr}" style="vertical-align:middle" /> {tr}Tiki was not able to detect the GD library.{/tr}</p>
	</div>
	<p>&nbsp;</p>
{/if}
	<p>{tr}Tiki uses the GD library to process images for the Image Gallery and CAPTCHA support.{/tr}</p>
</div>

<div align="center" style="margin-top:1em;">
<form action="tiki-install.php" method="post">
	<input type="hidden" name="install_step" value="3" />
	<input type="submit" value=" {tr}Continue{/tr} " />
{if $multi}		<input type="hidden" name="multi" value="{$multi}" />{/if}
{if $lang}		<input type="hidden" name="lang" value="{$lang}" />{/if}
</form>
</div>

{elseif $install_step eq '3' or ($dbcon eq 'n' or $resetdb eq 'y')}
{* we do not have a valid db connection or db reset is requested *}
<h1>{tr}Set the Database Connection{/tr}</h1>
<div style="float:left; width:60px"><img src="pics/large/stock_line-in48x48.png" alt="{tr}Database{/tr}" /></div>
<div class="clearfix">
	<p>{tr}Tiki requires an active database connection.{/tr} {tr}You must create the database and user <em>before</em> completing this page.{/tr}</p>
{if $dbcon ne 'y'}
	<div align="center" style="padding:1em">
		<img src="pics/icons/delete.png" alt="{tr}Alert{/tr}" style="vertical-align:middle" /> <span style="font-weight:bold">{tr}Tiki cannot find a database connection.{/tr}</span> {tr}This is normal for a new installation.{/tr}
	</div>
{else}
	<div align="center" style="padding:1em">
		<img src="pics/icons/information.png" alt="{tr}Information{/tr}" style="vertical-align:middle" /> {tr}Tiki found an existing database connection in your local.php file.{/tr}
	<form action="tiki-install.php" method="post">
		<input type="hidden" name="install_step" value="4" />
{if $multi}		<input type="hidden" name="multi" value="{$multi}" />{/if}
{if $lang}		<input type="hidden" name="lang" value="{$lang}" />{/if}
		<input type="submit" value=" {tr}Use Existing Connection{/tr} " />
	</form>
	</div>
{/if}		
	
{if $tikifeedback}
	<br />
{section name=n loop=$tikifeedback}
	<div class="simplebox {if $tikifeedback[n].num > 0} highlight{/if}">
		<img src="pics/icons/{if $tikifeedback[n].num > 0}delete.png" alt="{tr}Error{/tr}"{else}accept.png" alt="{tr}Success{/tr}"{/if} style="vertical-align:middle"/> {$tikifeedback[n].mes}
	</div>
{/section}
{/if}
	<p>{tr}Use this page to create a new database connection, or use the <a href="http://doc.tikiwiki.org/Manual+Installation" target="_blank" title="manual installation">manual installation process</a>.{/tr} <a href="http://doc.tikiwiki.org/Manual+Installation" target="_blank" title="{tr}Help{/tr}"><img src="pics/icons/help.png" alt="{tr}Help{/tr}" /></a></p>
	<form action="tiki-install.php" method="post">
		<input type="hidden" name="install_step" value="4" />
{if $multi}		<input type="hidden" name="multi" value="{$multi}" />{/if}
{if $lang}		<input type="hidden" name="lang" value="{$lang}" />{/if}
		<fieldset><legend>{tr}Database information{/tr}</legend>
		<p>{tr}Enter your database connection information.{/tr}</p>
		<div style="padding:5px">
			<label for="db">{tr}Database type:{/tr}</label> 
			<div style="margin-left:1em">
			<select name="db" id="db">
{foreach key=dsn item=dbname from=$dbservers}
				<option value="{$dsn}">{$dbname}</option>
{/foreach}
			</select> <a href="javascript:void(0)" onclick="flip('db_help');" title="{tr}Help{/tr}"><img src="pics/icons/help.png" alt="{tr}Help{/tr}" /></a>
			<div style="display:none" id="db_help">
				<p>{tr}Select the type of database to use with Tiki.{/tr}</p>
				<p>{tr}Only databases supported by your PHP installation are listed here. If your database is not in the list, try to install the appropriate PHP extension.{/tr}</p>
			</div>
			</div>
		</div>
		<div style="padding:5px">
			<label for="host">{tr}Host name:{/tr}</label>
			<div style="margin-left:1em">
			<input type="text" name="host" id="host" value="{if isset($smarty.request.host)}{$smarty.request.host|escape:"html"}{else}localhost{/if}" size="40" /> <a href="javascript:void(0)" onclick="flip('host_help');" title="{tr}Help{/tr}"><img src="pics/icons/help.png" alt="{tr}Help{/tr}" /></a>
			<br /><em>{tr}Enter the host name or IP for your database.{/tr}</em>
			<div style="display:none;" id="host_help">
				<p>{tr}Use <strong>localhost</strong> if the database is running on the same machine as Tiki.{/tr} {tr}For SQLite, enter the path and filename to your database file.{/tr}</p>
			</div>
			</div>
		</div>
		<div style="padding:5px;">
			<label for="name">{tr}Database name:{/tr}</label>
			<div style="margin-left:1em;">
			<input type="text" id="name" name="name" size="40" value="{$smarty.request.name|escape:"html"}" /> <a href="javascript:void(0)" onclick="flip('name_help');" title="{tr}Help{/tr}"><img src="pics/icons/help.png" alt="{tr}Help{/tr}" /></a>
		
			<br /><em>{tr}Enter the name of the database that Tiki will use.{/tr}</em> 
			<div style="margin-left:1em;display:none;" id="name_help">
				<p>{tr}The database must already exist. You can create the database using mysqladmin, PHPMyAdmin, cPanel, or ask your hosting provider.  Normally Tiki tables won't conflict with other product names.{/tr}</p>
				<p>{tr}For Oracle:{/tr}
				<ul>
					<li>{tr}Enter your TNS Name here and leave Host empty.{/tr}<br />
					{tr}or{/tr}</li>
					<li>{tr}Override tnsnames.ora and put your SID here and enter your hostname:port in the Host field.{/tr}</li>
				</ul></p>
			</div>
			</div>
		</div>
		</fieldset><br />
		<fieldset><legend>{tr}Database user{/tr}</legend>
		<p>{tr}Enter a database user with administrator permission for the Database.{/tr}</p>
		<div style="padding:5px;">
			<label for="user">{tr}User name:{/tr}</label> <input type="text" id="user" name="user" value="{$smarty.request.user|escape:"html"}" />
		</div>
		<div style="padding:5px;">
			<label for="pass">{tr}Password:{/tr}</label> <input type="password" id="pass" name="pass" />
		</div>
		</fieldset>
		<input type="hidden" name="resetdb" value="{$resetdb}" />
		<div align="center" style="margin-top:1em;"><input type="submit" name="dbinfo" value=" {tr}Continue{/tr} " /></div>	 
	</form>
</div>

{elseif $install_step eq '4'}
<h1>{if $tikidb_created}{tr}Install &amp; Update Profile{/tr}{else}{tr}Install Profile{/tr}{/if}</h1>
<div style="float:left; width:60px"><img src="pics/large/profiles48x48.png" alt="{tr}Profiles{/tr}" /></div>
<div class="clearfix">
<p>
{if $tikidb_created}
	{tr}Select the installation (or upgrade) profile to use. This profile will populate (or upgrade) the database.{/tr}
{else}
	{tr}Select the installation profile to use. This profile will populate the database.{/tr}
{/if}
</p>
<p>{tr}Profiles can be used to pre-configure your site with specific features and settings.{/tr} {tr}Visit <a href="http://profiles.tikiwiki.org" target="_blank">http://profiles.tikiwiki.org</a> for more information.{/tr}</p> 
	  {if $dbdone eq 'n'}
		  {if $logged eq 'y'}
		    {* we are logged if no admin account is found or if the admin user is logged in*}
		    <form method="post" action="tiki-install.php">
		    	<input type="hidden" name="install_step" value="5" />
				{if $multi}<input type="hidden" name="multi" value="{$multi}" />{/if}
				{if $lang}<input type="hidden" name="lang" value="{$lang}" />{/if}
	  <br />
<table class="admin">
	<tr>
		<td valign="top">
			<fieldset><legend>{tr}Install{/tr}</legend>
{if $tikidb_created}
			<script type="text/javascript">
			<!--//--><![CDATA[//><!--
				{literal}
				function install() {
					document.getElementById('install-link').style.display='none';
					document.getElementById('install-table').style.visibility='';
				}
				{/literal}
			//--><!]]>
			</script>
			<div id="install-link">
			
			<p style="text-align:center"><a class="button" href="javascript:install()">{tr}Reinstall the database{/tr}</a></p>
			<p style="text-align:center"><img src="pics/icons/sticky.png" alt="{tr}Warning{/tr}" style="vertical-align:middle" /> <strong>{tr}Warning:{/tr}</strong> {tr}This will destroy your current database.{/tr}</p>
			</div>
		    <div id="install-table" style="visibility:hidden">
			{else}
		    <div id="install-table">
			{/if}
			 {if $tikidb_created}<p style="text-align:center"><img src="pics/icons/sticky.png" alt="{tr}Warning{/tr}" style="vertical-align:middle"/> <strong>{tr}Warning:{/tr}</strong> {tr}This will destroy your current database.{/tr}</p>{/if}
			{if $has_internet_connection eq 'y'}
			  <p>{tr}Create a new database (clean install) with profile:{/tr}</p>
			<select name="profile" size="6">
			<option value="" selected="selected">{tr}Bare-bones default install{/tr}</option>
			<option value="Personal_Blog_and_Profile">{tr}Personal Blog and Profile{/tr}</option>
			<option value="Small_Organization_Web_Presence">{tr}Small Organization Web Presence{/tr}</option>
			<option value="Company_Intranet">{tr}Company Intranet{/tr}</option>
			<option value="Customer_Extranet">{tr}Customer Extranet{/tr}</option>
			<option value="Collaborative_Community">{tr}Collaborative community{/tr}</option>
			</select>
			 <p>{tr}See the documentation for <a target="_blank" href="http://profiles.tikiwiki.org/Profiles_in_30_installer" class="link" title="Description of available profiles.">descriptions of the available profiles.{/tr}</a></p>
			{else}
			  <p style="text-align:center; color:red">{tr}The installer could not connect to the Profiles repository.{/tr}</p>
			  <p style="text-align:center">{tr}The default installation profile will be used.{/tr}</p>
			<input type="hidden" name="profile" value="" />
			{/if}
			 <p>&nbsp;</p>
				<div align="center">
					<input type="submit" name="scratch" value=" {tr}Install{/tr} " />
				</div>

			</div>
			</fieldset>
		</td>
			{if $tikidb_created}
			<td width="50%" valign="top">
			<fieldset><legend>{tr}Upgrade{/tr}</legend>
			<p>{tr}Automatically upgrade your existing database to v{/tr}{$tiki_version_name}.</p>
			<p align="center"><input type="submit" name="update" value=" {tr}Upgrade{/tr} " /></p>
			</fieldset>
			</td>
			{/if}
		</tr></table>
		    </form>
 {else}
			{* we are not logged then no admin account found and user not logged *}
			<p><img src="pics/icons/delete.png" alt="{tr}Alert{/tr}" style="vertical-align:middle" />  <span style="font-weight:bold">{tr}This site has an admin account configured.{/tr}</span></p>
		   <p>{tr}Please login with your admin password to continue.{/tr}</p>

     <form name="loginbox" action="tiki-install.php" method="post">
			<input type="hidden" name="login" value="admin" />
			{if $multi}<input type="hidden" name="multi" value="{$multi}" />{/if}
			{if $lang}<input type="hidden" name="lang" value="{$lang}" />{/if}
          <table>
          <tr><td class="module">{tr}User:{/tr}</td><td><input value="admin" disabled="disabled" size="20" /></td></tr>
          <tr><td class="module">{tr}Pass:{/tr}</td><td><input type="password" name="pass" size="20" /></td></tr>
          <tr><td colspan="2"><p align="center"><input type="submit" name="login" value="{tr}Login{/tr}" /></p></td></tr>
          </table>
      </form>

		  {/if}
{/if}
</div>

{elseif $install_step eq '5' or ($dbdone ne 'n')}
<h1>{if isset($smarty.post.update)}{tr}Review the Upgrade{/tr}{else}{tr}Review the Installation{/tr}{/if}</h1>
		<div style="margin: 10px 0 5px 0; border: solid 1px #000; padding: 5px; background: #a9ff9b;">
		<p style="text-align:center; font-size: large;">{if isset($smarty.post.update)}{tr}Upgrade complete{/tr}{else}{tr}Installation complete{/tr}{/if}.</p>
		<p>{tr}Your database has been configured and Tiki is ready to run!{/tr} 
      {if isset($smarty.post.scratch)}
        {tr}If this is your first install, your admin password is <strong>admin</strong>.{/tr}
      {/if} 
      {tr}You can now log in into Tiki as user <strong>admin</strong> and start configuring the application.{/tr}
		</p>
		</div>
{if $installer->success|@count gt 0}
	<p><img src="pics/icons/accept.png" alt="{tr}Success{/tr}" style="vertical-align:middle"/> <span style="font-weight:bold">
	{if isset($smarty.post.update)}
		{tr}Upgrade operations executed successfully:{/tr}
	{else}
		{tr}Installation operations executed successfully:{/tr}
	{/if}
	</span>
	{$installer->success|@count} {tr}SQL queries.{/tr}</p>
{else}
	<p><img src="pics/icons/accept.png" alt="{tr}Success{/tr}" style="vertical-align:middle"/> <span style="font-weight: bold">{tr}Database was left unchanged.{/tr}</span></p>
{/if}
{if $installer->failures|@count > 0}
			<script type="text/javascript">
			<!--//--><![CDATA[//><!--
				{literal}
				function sql_failed() {
					document.getElementById('sql_failed_log').style.display='block';
				}
				{/literal}
			//--><!]]>
			</script>

<p><img src="pics/icons/delete.png" alt="{tr}Failed{/tr}" style="vertical-align:middle"/> <strong>{tr}Operations failed:{/tr}</strong> {$installer->failures|@count} {tr}SQL queries.{/tr}
<a href="javascript:sql_failed()">{tr}Display details.{/tr}</a>

<div id="sql_failed_log" style="display:none">
 <p>{tr}During an upgrade, it is normal to have SQL failures resulting with <strong>Table already exists</strong> messages.{/tr}</p>
    		<textarea rows="15" cols="80">
{foreach from=$installer->failures item=item}
{$item[0]}
{$item[1]}
{/foreach}
    		</textarea>

</div>
{/if}

{if isset($htaccess_error)}
<h3>{tr}.htaccess File{/tr} <a title="{tr}Help{/tr}" href="http://doc.tikiwiki.org/Installation" target="help"><img style="border:0" src='img/icons/help.gif' alt="{tr}Help{/tr}" /></a></h3>
{tr}We recommend enabling the <strong>.htaccess</strong> file for your Tiki{/tr}. {tr}This will enable you to use SEFURLs (search engine friendly URLs) and help improve site security{/tr}. 
<p>{tr}To enable this file, simply rename the <strong>_htaccess</strong> file (located in the main directory of your Tiki installation) to <strong>.htaccess</strong>.{/tr}</p>
{/if}

<p>&nbsp;</p>
<div align="center">
<form action="tiki-install.php" method="post">
	<input type="hidden" name="install_step" value="{if isset($smarty.post.update)}7{else}6{/if}" />
	<input type="submit" value=" {tr}Continue{/tr} " />
{if $multi}		<input type="hidden" name="multi" value="{$multi}" />{/if}
{if $lang}		<input type="hidden" name="lang" value="{$lang}" />{/if}
</form>
</div>


{elseif $install_step eq '6'}
<h1>{tr}Configure General Settings{/tr}</h1>
<form action="tiki-install.php" method="post">
<div style="float:left; width:60px"><img src="pics/large/icon-configuration48x48.png" alt="{tr}Configure General Settings{/tr}" /></div>
<div class="clearfix">
	<p>{tr}Complete these fields to configure common, general settings for your site.{/tr} {tr}The information you enter here can be changed later.{/tr}</p>
	<p>{tr}Refer to the <a href="http://doc.tikiwiki.org/Admin+Panels" target="_blank">documentation</a> for complete information on these, and other, settings.{/tr}</p>
	<br />
	<fieldset><legend>{tr}General{/tr} <a href="http://doc.tikiwiki.org/general+admin&amp;bl=y" target="_blank" title="{tr}Help{/tr}"><img src="pics/icons/help.png" alt="{tr}Help{/tr}" /></a></legend>
<div style="padding:5px; clear:both"><label for="browsertitle">{tr}Browser title:{/tr}</label>
		<div style="margin-left:1em"><input type="text" size="40" name="browsertitle" id="browsertitle" value="{$prefs.browsertitle|escape}" />
			<br /><em>{tr}This will appear in the browser title bar.{/tr}</em></div>
		</div>
		<div style="padding:5px; clear:both"><label for="sender_email">{tr}Sender email:{/tr}</label>
			<div style="margin-left:1em"><input type="text" size="40" name="sender_email" id="sender_email" value="{$prefs.sender_email|escape}" />
			<br /><em>{tr}Email sent by your site will use this address.{/tr}</em>
			</div>
		</div>
	</fieldset>
<br />
	<fieldset><legend>{tr}Secure Login{/tr} <a href="http://doc.tikiwiki.org/login+config&amp;bl=y" target="_blank" title="{tr}Help{/tr}"><img src="pics/icons/help.png" alt="{tr}Help{/tr}" /></a></legend>
		<div style="padding:5px; clear:both"><label for="https_login">{tr}HTTPS login:{/tr}</label>
	<select name="https_login" id="https_login" onchange="hidedisabled('httpsoptions',this.value);">
		<option value="disabled"{if $prefs.https_login eq 'disabled'} selected="selected"{/if}>{tr}Disabled{/tr}</option>
		<option value="allowed"{if $prefs.https_login eq 'allowed'} selected="selected"{/if}>{tr}Allow secure (https) login{/tr}</option>
		<option value="encouraged"{if $prefs.https_login eq 'encouraged' or ($prefs.https_login eq '' and $detected_https eq 'on' ) } selected="selected"{/if}>{tr}Encourage secure (https) login{/tr}</option>
		<option value="force_nocheck"{if $prefs.https_login eq 'force_nocheck'} selected="selected"{/if}>{tr}Consider we are always in HTTPS, but do not check{/tr}</option>
		<option value="required"{if $prefs.https_login eq 'required'} selected="selected"{/if}>{tr}Require secure (https) login{/tr}</option>
	</select>
		</div>
<div id="httpsoptions" style="display:{if $prefs.https_login eq 'disabled' or ( $prefs.https_login eq '' and $detected_https eq '') }none{else}block{/if};">
		<div style="padding:5px">
			<label for="https_port">{tr}HTTPS port:{/tr}</label> <input type="text" name="https_port" id="https_port" size="5" value="{$prefs.https_port|escape}" />
		</div>
<div style="padding:5px;clear:both">
	<div style="float:left"><input type="checkbox" id="feature_show_stay_in_ssl_mode" name="feature_show_stay_in_ssl_mode" {if $prefs.feature_show_stay_in_ssl_mode eq 'y'}checked="checked"{/if}/></div>
	<div style="margin-left:20px;"><label for="feature_show_stay_in_ssl_mode"> {tr}Users can choose to stay in SSL mode after an HTTPS login.{/tr}</label></div>
</div>
<div style="padding:5px;clear:both">
	<div style="float:left"><input type="checkbox" id="feature_switch_ssl_mode" name="feature_switch_ssl_mode" {if $prefs.feature_switch_ssl_mode eq 'y'}checked="checked"{/if}/></div>
	<div style="margin-left:20px;"><label for="feature_switch_ssl_mode">{tr}Users can switch between secured or standard mode at login.{/tr}</label></div>
</div>
</div>
</fieldset>
<br />
<fieldset><legend>{tr}Administrator{/tr}</legend>
<div style="padding:5px"><label for="admin_email">{tr}Admin email:{/tr}</label>
	<div style="margin-left:1em"><input type="text" size="40" name="admin_email" id="admin_email" />
	<br /><em>{tr}This is the email address for your administrator account.{/tr}</em></div>
</div>
</fieldset>
</div>

<div align="center" style="margin-top:1em;">
{if $multi}		<input type="hidden" name="multi" value="{$multi}" />{/if}
{if $lang}		<input type="hidden" name="lang" value="{$lang}" />{/if}
	<input type="hidden" name="install_step" value="7" />
	<input type="hidden" name="general_settings" value="y" />
	<input type="submit" value=" {tr}Continue{/tr} " />
</div>
</form>

{elseif $install_step eq '7'}
<h1>{tr}Enter Your Tiki{/tr}</h1>
<div style="float:left; width:60px"><img src="pics/large/stock_quit48x48.png" alt="{tr}Login{/tr}" /></div>
<div class="clearfix">
	<p>{tr}The installation is complete!{/tr} {tr}Your database has been configured and Tiki is ready to run.{/tr} </p>
	<p>{tr}Tiki is an opensource project, <em>you</em> can <a href='http://info.tikiwiki.org/Join+the+Community' target='_blank'>join the community</a> and help <a href='http://info.tikiwiki.org/tiki-index.php?page=Develop+Tiki' target='_blank'>develop Tiki</a>.{/tr} </p>
	<p>
{if isset($smarty.post.scratch)}	{tr}If this is your first install, your admin password is <strong>admin</strong>.{/tr} 
{/if} 
	{tr}You can now log in into Tiki as user <strong>admin</strong> and start configuring the application.{/tr}
	</p>

{if isset($smarty.post.scratch)}
	<h3><img src="pics/icons/information.png" alt="{tr}Note{/tr}" style="vertical-align:middle"/> {tr}Installation{/tr}</h3>
	<p>{tr}If this is a first time installation, go to <strong>tiki-admin.php</strong> after login to start configuring your new Tiki installation.{/tr}</p>
{/if}

{if isset($smarty.post.update)}
	<h3><img src="pics/icons/information.png" alt="{tr}Note{/tr}" style="vertical-align:middle"/> {tr}Upgrade{/tr}</h3>
	<p>{tr}If this is an upgrade, clean the Tiki caches manually (the <strong>templates_c</strong> directory) or by using the <strong>Admin &gt; System</strong> option from the Admin menu.{/tr}</p>
{/if}

{if $tikidb_is20}
		<span class="button"><a href="tiki-install.php?lockenter">{tr}Enter Tiki and Lock Installer{/tr} ({tr}Recommended{/tr})</a></span>
		<span class="button"><a href="tiki-index.php">{tr}Enter Tiki Without Locking Installer{/tr}</a></span>
{/if}

</div>
{/if}
</div>
			</div>
				</div>
<div id="col2">
	<div class="content">
{if $virt}
		<div class="box-shadow">
			<div class="box">
				<h3 class="box-title">{tr}MultiTiki Setup{/tr} <a title="{tr}Help{/tr}" href="http://doc.tikiwiki.org/MultiTiki" target="help"><img style="border:0" src="img/icons/help.gif" alt="{tr}Help{/tr}" /></a></h3>
				<div class="clearfix box-data">
				<div><a href="tiki-install.php">{tr}Default Installation{/tr}</a></div>
{foreach key=k item=i from=$virt}
				<div>
					<tt>{if $i eq 'y'}<strong style="color:#00CC00">{tr}DB OK{/tr}</strong>{else}<strong style="color:#CC0000">{tr}No DB{/tr}</strong>{/if}</tt>
{if $k eq $multi}
					<strong>{$k}</strong>
{else}
					<a href="tiki-install.php?multi={$k}" class="linkmodule">{$k}</a>
{/if}
				</div>
{/foreach}

<br />
<div><strong>{tr}Adding a new host:{/tr}</strong></div>
{tr}To add a new virtual host run the setup.sh with the domain name of the new host as a last parameter.{/tr}

{if $multi} <h2> ({tr}MultiTiki{/tr}) {$multi|default:"{tr}Default{/tr}"} </h2> {/if}

	
				</div>
			</div>
		</div>
{/if}

{if $dbcon eq 'y' and ($install_step eq '0' or !$install_step)}
		<div class="box-shadow">
			<div class="box">
				<h3 class="box-title"><img src="pics/icons/information.png" alt="{tr}Information{/tr}" style="vertical-align:middle" /> {tr}Upgrade{/tr}</h3>
				<div class="clearfix box-data">
				{tr}Are you upgrading an existing Tiki site?{/tr}
				{tr}Go directly to the <strong>Install/Upgrade</strong> step.{/tr}
				</div>
			</div>
		</div>

	
{/if}	



		<div class="box-shadow">
			<div class="box">
				<h3 class="box-title">{tr}Installation{/tr}</h3>
				<div class="clearfix box-data">
				<ol>
					<li>{if $install_step eq '0'}<strong>{else}<a href="tiki-install.php?reset=y{if $multi}&multi={$multi}{/if}{if $lang}&lang={$lang}{/if}" title="{tr}Welcome{/tr} / {tr}Restart the installer.{/tr}">{/if}{tr}Welcome{/tr}{if $install_step eq '0'}</strong>{else}</a>{/if}</li>
					<li>{if $install_step eq '1'}<strong>{else}<a href="tiki-install.php?install_step=1{if $multi}&multi={$multi}{/if}{if $lang}&lang={$lang}{/if}" title="{tr}Read the License{/tr}">{/if}{tr}Read the License{/tr}{if $install_step eq '1'}</strong>{else}</a>{/if}</li>
					<li>{if $install_step eq '2'}<strong>{elseif $install_step ge '3' or $dbcon eq 'y'}<a href="tiki-install.php?install_step=2{if $multi}&multi={$multi}{/if}{if $lang}&lang={$lang}{/if}" title="{tr}Review the System Requirements{/tr}">{/if}{tr}Review the System Requirements{/tr}{if $install_step eq '2'}</strong>{elseif $install_step ge '3' or $dbcon eq 'y'}</a>{/if}</li>
					<li>{if $install_step eq '3'}<strong>{elseif $dbcon eq 'y'}<a href="tiki-install.php?install_step=3{if $multi}&multi={$multi}{/if}{if $lang}&lang={$lang}{/if}" title="{tr}Database Connection{/tr}">{/if}{if $dbcon eq 'y'}{tr}Reset the Database Connection{/tr}{else}{tr}Database Connection{/tr}{/if}{if $install_step eq '3'}</strong>{elseif $dbcon eq 'y'}</a>{/if}</li>
					<li>{if $install_step eq '4'}<strong>{elseif $dbcon eq 'y' or isset($smarty.post.scratch) or isset($smarty.post.update)}<a href="tiki-install.php?install_step=4{if $multi}&multi={$multi}{/if}{if $lang}&lang={$lang}{/if}" title="{if $tikidb_created}{tr}Install/Upgrade{/tr}{else}{tr}Install Profile{/tr}{/if}">{/if}{if $tikidb_created}{tr}Install/Upgrade{/tr}{else}{tr}Install Profile{/tr}{/if}{if $install_step eq '4'}</strong>{elseif ($dbcon eq 'y') or (isset($smarty.post.scratch)) or (isset($smarty.post.update))}</a>{/if}</li>
					<li>{if $install_step eq '5'}<strong>{elseif $tikidb_is20}<a href="tiki-install.php?install_step=5{if $multi}&multi={$multi}{/if}{if $lang}&lang={$lang}{/if}" title="{if isset($smarty.post.update)}{tr}Review the Upgrade{/tr}{else}{tr}Review the Installation{/tr}{/if}">{/if}{if isset($smarty.post.update)}{tr}Review the Upgrade{/tr}{else}{tr}Review the Installation{/tr}{/if}{if $install_step eq '5'}</strong>{elseif $tikidb_is20}</a>{/if}</li>
					<li>{if $install_step eq '6'}<strong>{elseif $tikidb_is20 and !isset($smarty.post.update)}<a href="tiki-install.php?install_step=6{if $multi}&multi={$multi}{/if}{if $lang}&lang={$lang}{/if}" title="{tr}Configure the General Settings{/tr}">{/if}{tr}Configure the General Settings{/tr}{if $install_step eq '6'}</strong>{elseif $tikidb_is20 and !isset($smarty.post.update)}</a>{/if}</li>
					<li>{if $install_step eq '7'}<strong>{elseif $tikidb_is20}<a href="tiki-install.php?install_step=7{if $multi}&multi={$multi}{/if}{if $lang}&lang={$lang}{/if}" title="{tr}Enter Your Tiki{/tr}">{/if}{tr}Enter Your Tiki{/tr}{if $install_step eq '7'}</strong>{elseif $tikidb_is20}</a>{/if}</li>
				</ol>
				</div>
			</div>
		</div>
		<div class="box-shadow">
			<div class="box">
				<h3 class="box-title">{tr}Help{/tr}</h3>
				<div class="clearfix box-data">
				<p><img src="favicon.png" alt="{tr}Tiki Icon{/tr}" style="vertical-align:middle" /> <a href="http://tikiwiki.org" target="_blank">{tr}TikiWiki Project Web Site{/tr}</a></p>
				<p><img src="pics/icons/book_open.png" alt="{tr}Documentation{/tr}" style="vertical-align:middle" /> <a href="http://doc.tikiwiki.org" target="_blank">{tr}Documentation{/tr}</a></p>
				<p><img src="pics/icons/group.png" alt="{tr}Forums{/tr}" style="vertical-align:middle" /> <a href="http://tikiwiki.org/forums" target="_blank">{tr}Support Forums{/tr}</a></p>
				</div>
			</div>
		</div>
	</div>
</div>			
			
	  	</div>
</div>
<hr />
<p align="center"><a href="http://tikiwiki.org" target="_blank" title="{tr}Powered by{/tr} {tr}TikiWiki CMS/Groupware Project{/tr} &#169; 2002&#8211;{$smarty.now|date_format:"%Y"} "><img src="img/tiki/tikibutton2.png" alt="{tr}Powered by TikiWiki{/tr}" style="width:80px; height:31px; border:0" /></a></p>
