{$prefs.mail_template_custom_text}{tr}Wiki page renamed{/tr} {tr}by{/tr} {$mail_user|username}.

{tr}Date:{/tr} {$mail_date|tiki_short_datetime:"":"n"}

{tr}Old name:{/tr} {$mail_oldname}
{tr}New name:{/tr} {$mail_newname}

{tr}If you don't want to receive these notifications follow this link:{/tr}
{mailurl}tiki-user_watches.php?id={$watchId}{/mailurl}
