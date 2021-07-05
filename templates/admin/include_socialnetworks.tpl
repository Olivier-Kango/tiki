<form action="tiki-admin.php?page=socialnetworks" method="post">
    {ticket}

    <div class="row">
        <div class="form-group col-lg-12 clearfix">
            {include file='admin/include_apply_top.tpl'}
        </div>
    </div>

    {tabset}
        {tab name="{tr}General{/tr}"}
            <legend>{tr}Social network integration{/tr}</legend>
            {preference name=feature_socialnetworks visible="always"}

            <ol>
                {foreach $prefs["`$socPreffix`enabledProviders"] as $k => $pNum}
                    {$providerName = $socnetsAll[$pNum]}
                    {$prefname="`$socPreffix``$providerName`_socnetEnabled" }
                    {$prefs[$prefname] = 'y'}
                    <strong><li>{$providerName}  {* debug pNum={$pNum} k={$k} *}</li></strong>
                {/foreach}
            </ol>

            <fieldset>
                <div class="adminoptionbox">
                    {$prefName = "`$socPreffix`enabledProviders"}
                    {preference name=$prefName visible="always"}
                </div>

            </fieldset>

            {remarksbox type="note" title="{tr}Note{/tr}"}
                {tr}To use socnets integration and/or login you need at least{/tr}:
                <ol>
                    <li>{tr}Register your site as a web application at the corresponding socnets site(s){/tr}.</li>
                    <li>{tr}Enable that socnet settings with a tick in the ENABLED tab below{/tr}.</li>
                    <li>{tr}Copy and enter{/tr} a) <strong>your app id</strong> {tr}and{/tr} b) <strong>your app secret</strong> {tr}from those sites and into the corresponding fields here below{/tr}.</li>
                    <li>{tr}Copy your site's URLs as shown in the settings below as callbacks to the corresponding socnets sites{/tr}.</li>
                    <li>{tr}Configure - enable login and other (some are optional!) settings - for the corresponding socnet in the SETTINGS tab below{/tr}.</li>
                 </ol>
                P.S.
                <ol>
                     <li> {tr}If you cannot see or want to change appearance of login buttons for the corresponding socnets you need to tweak login module mod-login.tpl and/or CSS{/tr}.</li>
                     <li> {tr}If you see only number 1. but not the enabled socnets or encounter other problems, then, first of all, you need to clear Tiki caches and rebuild index{/tr}.</li>
                     <li> {tr}Also, if some settings become disabled (like user preffix), you need to execute following sequence: disable-apply-enable-apply for ther corresponding socnet.{/tr}</li>
                </ol>
            {/remarksbox}
        {/tab}
        {tab name="{tr}Settings{/tr}"}

            <ol>
                {foreach $prefs["`$socPreffix`enabledProviders"] as $k => $pNum}
                    {$providerName = $socnetsAll[$pNum]}
                    {* TODO check in which cases is needed lower *}
                    {$providername = $providerName|lower}
                    <strong><em>{$providerName}</em></strong>

                    <!-- START of adminoptionsbox for {$providerName} -->
                    <div class="adminoptionbox {$providername} card">
                        <ol>
                            <br>
                            {foreach from=$socBasePrefs key=basePref item=prefItem}
                                {$prefname="`$socPreffix``$providerName``$basePref`"}
                                {if ($basePref === '_socnetEnabled')}
                                    {* skip this iteration *}
                                    {continue}
                                {elseif ($basePref === '_loginEnabled') }
                                    {* if we use closing buttons again... *}
                                    <!-- start of _loginEnabled for {$providerName} -->
                                    <div class="col-sm-12 {$providername} _loginEnabled" style="padding-top:5px;">
                                        {preference name=$prefname}
                                        <button class="{$providername} socbutton btn-secondary" style="border: none;">
                                            more/less... <i class="{$providername} fa fa-caret-right"></i>
                                        </button>
                                    </div> <!-- end of _loginEnabled for {$providerName} -->
                                {else}
                                    <div class="col-sm-12 {$providername} _else_loginEnabled">
                                        <li>{preference name=$prefname}</li>
                                    </div>
                                {/if}
                            {/foreach}
                        </ol>

                        <div class="col-sm-12 {$providername} _else_loginEnabled">
                            {remarksbox type="note" title="{tr}Urls for {/tr}{$providerName}"}
                            Login&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;url: {$callbackUrl}?provider={$providerName}<br>
                            Remove&nbsp;url: {$callbackUrl}?remove={$providerName}
                            {/remarksbox}
                        </div>
                    </div> <!-- END of adminoptionsbox for {$providerName} -->

                {/foreach}
            </ol>

            {jq}
 $("._else_loginEnabled").hide();

 var chev = $(".socbutton");
 chev.click(function (ev){
     var allclass = $(ev.target).attr('class');
     var netname1 = allclass.split(' ')[0];
     var cl2 = $(this).children('.fa');
     cl2.toggleClass('fa-caret-right fa-caret-down');
     var logch = $( "." + netname1 + "._else_loginEnabled");
        cl2.is('.fa-caret-down') ?  logch.show() : logch.hide();
     ev.preventDefault();
 });

 var chk = $("input, input:checkbox","._loginEnabled");
 chk.change( function (ev) {
     var netname = ev.target.name.split('_')[1];
     var ch2 = $("i."+netname+".fa");
     var logch = $( "." + netname + "._else_loginEnabled");
     if ($(this).is( ":checked" )) {
         logch.show();
         ch2.removeClass('fa-caret-right');
         ch2.addClass('fa-caret-down');
     } else {
         ch2.removeClass('fa-caret-down');
         ch2.addClass('fa-caret-right');
         logch.hide();
     }
     ev.preventDefault();
 });
            {/jq}


            {************************************}
            <fieldset class="mt-5">
                <legend>{tr}Debug and Logs{/tr}</legend>
                <div class="adminoptionbox">
                    {$prefname = "`$socPreffix`socLoginBaseUrl"}
    {*                {$prefs[$prefname]}*}
                    {preference name=$prefname}
                </div>
            </fieldset>
        {/tab}
        {tab name="{tr}bit.ly{/tr}"}
            <br>
            {remarksbox type="note" title="{tr}Note{/tr}"}
                <p>
                    {tr}There is no need to set up a site-wide bit.ly account; every user can have his or her own, but this allows for site-wide statistics{/tr}<br>
                    {tr}Go to{/tr} <a class="alert-link" href="http://bit.ly/a/sign_up">http://bit.ly/a/sign_up</a> {tr}to sign up for an account{/tr}.<br>
                    {tr}Go to{/tr} <a class="alert-link" href="http://bit.ly/a/your_api_key">http://bit.ly/a/your_api_key</a> {tr}to retrieve the API key{/tr}.
                </p>
            {/remarksbox}
            <div class="adminoptionbox">
                {preference name=socialnetworks_bitly_login}
                {preference name=socialnetworks_bitly_key}
                {preference name=socialnetworks_bitly_sitewide}
            </div>
        {/tab}
        {tab name="{tr}Share This{/tr}"}
            <br>
            <div class="adminoptionbox">
                {preference name=feature_wiki_sharethis}
                <div class="adminoptionboxchild" id="feature_wiki_sharethis_childcontainer">
                    {preference name=blog_sharethis_publisher}
                    {preference name=wiki_sharethis_encourage}
                </div>
            </div>
        {/tab}
        {tab name="{tr}Legacy Integrations{/tr}"}
            <fieldset>
                <legend>{tr}Twitter{/tr}</legend>
                <br>
                <div class="adminoptionbox">
                    {preference name=socialnetworks_twitter_site_name}
                    {preference name=socialnetworks_twitter_site_image}
                </div>
                {remarksbox type="note" title="{tr}Note{/tr}"}
                    <p>
                        {tr}To use Twitter integration, you must register this site as an application at{/tr}
                        <a class="alert-link" href="http://twitter.com/oauth_clients/" target="_blank">http://twitter.com/oauth_clients/</a>
                        {tr}and allow write access for the application{/tr}.<br>
                        {tr}Enter &lt;your site URL&gt;tiki-socialnetworks.php as callback URL{/tr}.
                    </p>
                {/remarksbox}
                <div class="adminoptionbox">
                    {preference name=socialnetworks_twitter_consumer_key}
                    {preference name=socialnetworks_twitter_consumer_secret}
                </div>
            </fieldset>
            <fieldset>
                <legend>{tr}Facebook{/tr}</legend>
                <br>
                <div class="adminoptionbox">
                    {preference name=socialnetworks_facebook_site_name}
                    {preference name=socialnetworks_facebook_site_image}
                </div>
                {remarksbox type="note" title="{tr}Note{/tr}"}
                    <p>
                        {tr}To use Facebook integration, you must register this site as an application at{/tr}
                        <a class="alert-link" href="http://developers.facebook.com/setup/" target="_blank">http://developers.facebook.com/setup/</a>
                        {tr}and allow extended access for the application{/tr}.<br>
                        {tr}Enter &lt;your site URL&gt;tiki-socialnetworks.php?request_facebook as Site URL and &lt;your site&gt; as Site Domain{/tr}.
                    </p>
                {/remarksbox}
                <div class="adminoptionbox">
                    {preference name=socialnetworks_facebook_application_id}
                    {preference name=socialnetworks_facebook_application_secr}
                    {preference name=socialnetworks_facebook_login}
                    {preference name=socialnetworks_facebook_autocreateuser}
                    <div class="adminoptionboxchild" id="socialnetworks_facebook_autocreateuser_childcontainer">
                        {preference name=socialnetworks_facebook_firstloginpopup}
                        {preference name=socialnetworks_facebook_email}
                        {preference name=socialnetworks_facebook_create_user_trackeritem}
                        {preference name=socialnetworks_facebook_names}
                    </div>
                    {remarksbox type="note" title="{tr}Note{/tr}"}
                        {tr}The following preferences affect what permissions the user is asked to allow Tiki to do by Facebook when authorizing it.{/tr}
                    {/remarksbox}
                    {preference name=socialnetworks_facebook_publish_stream}
                    {preference name=socialnetworks_facebook_manage_events}
                    {preference name=socialnetworks_facebook_manage_pages}
                    {preference name=socialnetworks_facebook_sms}
                </div>
            </fieldset>
            <fieldset>
                <legend>{tr}LinkedIn{/tr}</legend>
                <br>
                {remarksbox type="note" title="{tr}Note{/tr}"}
                <p>
                    {tr}To use LinkedIn integration, you must register this site as an application at{/tr}
                    <a class="alert-link" href="https://www.linkedin.com/developer/apps" target="_blank">https://www.linkedin.com/developer/apps</a>
                    {tr}and allow necessary permissions for the application{/tr}.<br>
                    {tr}Enter &lt;your site URL&gt;tiki-socialnetworks_linkedin.php as Authorized OAuth Redirect URLs{/tr}.
                </p>
                {/remarksbox}
                <div class="adminoptionbox">
                    {preference name=socialnetworks_linkedin_client_id}
                    {preference name=socialnetworks_linkedin_client_secr}
                    {preference name=socialnetworks_linkedin_login}
                    {preference name=socialnetworks_linkedin_autocreateuser}
                    <div class="adminoptionboxchild" id="socialnetworks_linkedin_autocreateuser_childcontainer">
                        {preference name=socialnetworks_linkedin_email}
                        {preference name=socialnetworks_linkedin_create_user_trackeritem}
                        {preference name=socialnetworks_linkedin_names}
                    </div>
                </div>
            </fieldset>
        {/tab}
    {/tabset}
    {include file='admin/include_apply_bottom.tpl'}
</form>
