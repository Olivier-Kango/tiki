{$prefs.mail_template_custom_text}{$mail_data.name}
---

{$mail_data.description}

{tr}From:{/tr}    {$mail_data.start|tiki_long_datetime}
{tr}to:{/tr}        {$mail_data.end|tiki_long_datetime}

{tr}View item calendar at:{/tr}
{mailurl}{service controller='calendar' action='view_item' calitemId=$mail_calitemId calIds[]=$mail_data.calendarId}{/mailurl}
