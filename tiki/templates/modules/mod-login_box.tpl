<div class="box">
<div class="box-title">
{tr}Login{/tr}
</div>
<div class="box-data">
      <form action="tiki-login.php" method="post">

    <table border="0">
    {if $user}
      <tr><td>&nbsp;</td></tr>
      <tr><td><span class="user-box-text">{tr}logged as{/tr}: {$smarty.session.user}</span></td></tr>
      <tr><td><div class="button"><a class="link" href="tiki-logout.php">{tr}Logout{/tr}</a></div></td></tr>
      <tr><td>&nbsp;</td></tr>
    {else}
      <tr>
        <td>
          <table>
          <tr><td><span class="user-box-text">{tr}user{/tr}:</span></td><td><input type="text" name="user" size="10" /></td></tr>
          <tr><td><span class="user-box-text">{tr}pass{/tr}:</span></td><td><input type="password" name="pass" size="10" /></td></tr>
          <tr><td><input type="submit" name="login" value="{tr}login{/tr}" /></td>
          {if $allowRegister eq 'y'}
            <td valign="bottom"><a class="button" href="tiki-register.php">{tr}register{/tr}</a></td>
          {else}
            <td>&nbsp;</td>
          {/if}
          </tr>
          </table>
        </td>
      </tr>
    {/if}
    </table>
    </form>
</div>
</div>
