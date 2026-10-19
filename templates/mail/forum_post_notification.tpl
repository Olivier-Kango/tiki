{if $new_topic or $topic_changed}
{if $new_topic}
{tr _0=$prefs.mail_template_custom_text}A new message was posted in %0forum:{/tr} {$mail_forum}
{else}
{tr _0=$prefs.mail_template_custom_text}A message was updated in %0forum:{/tr} {$mail_forum}
{/if}

{tr}New topic:{/tr}
{if $mail_topic_changed}
-Old-: {$mail_old_topic}
*New*: {$mail_topic}
{else}{$mail_topic}{/if}
{tr}Author:{/tr} {if $mail_author}"{$mail_author|username}"
{else}{tr _0=$prefs.mail_template_custom_text}An anonymous %0user{/tr}{/if}
{tr}Title:{/tr}
{if $mail_title_changed}
-Old-: {$mail_old_title}
*New*: {$mail_title}
{else}{$mail_title}{/if}
{tr}Date:{/tr} {$mail_date|tiki_short_datetime:"":"n"}
{mailurl}{$topicId|sefurl:"forum post"}{if $threadId}#threadId={$threadId}{/if}{/mailurl}

{if $mail_contributions}{tr}Contribution:{/tr} {$mail_contributions}{/if}
{else}
{if $mail_author}"{$mail_author|username}"{else}{tr}An anonymous user{/tr}{/if} {if $thread_changed}{tr}has updated a thread you're watching.{/tr}{else}{tr}has posted a reply to a thread you're watching.{/tr}{/if} {tr}You can view the thread and reply at the following URL:{/tr}

{mailurl}{$topicId|sefurl:"forum post"}{if $threadId}#threadId={$threadId}{/if}{/mailurl}
{/if}

{tr}Message:{/tr}
{if $mail_message_changed}
-Old-:
{$mail_old_message}

*New*:
{$mail_message}
{else}
----------------------------------------------------------------------
{$mail_message}
{/if}
