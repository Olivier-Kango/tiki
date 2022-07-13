{* $Id$ *}{tr _0=$prefs.mail_template_custom_text _1=$mail_page _2=$mail_user|username _3=$mail_date|tiki_short_datetime:"":"n"}The %0map %1 was changed by %2 at %3{/tr}

{tr}You can view the updated map following this link:{/tr}
{$mail_machine_raw}/tiki-map.php?mapfile={$mail_page}

{tr}You can edit the map following this link:{/tr}
{$mail_machine}?mapfile={$mail_page}

{tr}If you don't want to receive these notifications follow this link:{/tr}
{$mail_machine_raw}/tiki-user_watches.php?id={$watchId}
