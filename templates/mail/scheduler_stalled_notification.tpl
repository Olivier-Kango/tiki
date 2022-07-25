{tr}Notice{/tr}

{tr _0=$schedulerName _1=$stalledTimeout}Scheduler "%0" has been running for over %1 minutes and is now marked as stalled.{/tr}


{tr}Details{/tr}
{tr}Site Name:{/tr} {$siteName}
{if !empty($siteUrl)}
    {tr}Site URL:{/tr} {$siteUrl}
{/if}
{tr}Server:{/tr} {$server}
{tr}Webroot:{/tr} {$webroot}
