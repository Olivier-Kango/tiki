{if $new_topic}
{tr}A new message was posted to {$prefs.mail_template_custom_text}forum:{/tr} {$mail_forum}

{tr}New topic:{/tr} {$mail_topic}
{tr}Author:{/tr} {if $mail_author}"{$mail_author|username}"
{else}{tr}An anonymous {$prefs.mail_template_custom_text}user{/tr}{/if}
{tr}Title:{/tr} {$mail_title}
{tr}Date:{/tr} {$mail_date|tiki_short_datetime:"":"n"}
{mailurl}{$topicId|sefurl:"forum post"}{if $threadId}#threadId={$threadId}{/if}{/mailurl}

{if $mail_contributions}{tr}Contribution:{/tr} {$mail_contributions}{/if}
{else}
{if $mail_author}"{$mail_author|username}"{else}{tr}An anonymous user{/tr}{/if} {tr}has posted a reply to a thread you're watching.
You can view the thread and reply at the following URL:{/tr}

{mailurl}{$topicId|sefurl:"forum post"}{if $threadId}#threadId={$threadId}{/if}{/mailurl}
{/if}


{tr}Message:{/tr}
----------------------------------------------------------------------
{$mail_message}
