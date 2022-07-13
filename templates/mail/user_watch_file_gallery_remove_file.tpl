{* $Id$ *}{tr _0=$prefs.mail_template_custom_text}A file was removed from the %0file gallery:{/tr} {$galleryName}

{tr}Removed by:{/tr} {$author|username}
{tr}Name:{/tr} {$fname}
{tr}File Name:{/tr} {$filename}
{tr}Date:{/tr} {$mail_date|tiki_short_datetime:"":"n"}

You can view the updated file gallery at:
{$mail_machine}/{$galleryId|sefurl:'file gallery'}

