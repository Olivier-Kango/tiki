{tr _0=$prefs.mail_template_custom_text}An object was removed from %0category{/tr} {$categoryName}

{tr}Removed by:{/tr} {$author|username}
{tr}Date:{/tr} {$mail_date|tiki_short_datetime:"":"n"}
{tr}Name:{/tr} {$categoryName}
{tr}Path:{/tr} {$categoryPath}
{$mail_machine}/{$categoryId|sefurl:category}

{tr}Object:{/tr} {$objectName}
{tr}Object type:{/tr} {$objectType}
{$mail_machine}/{$objectUrl}
