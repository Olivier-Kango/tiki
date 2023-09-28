{tr _0=$mentionedBy _1=$type _2=$objectTitle}%0 mentioned you in %1 "%2"{/tr}


{if count($listUrls) > 1}
{tr}You can view the mentions by following these links:{/tr}
{else}
{tr}You can view the mention by following this link:{/tr}
{/if}
{foreach $listUrls as $url}
{mailurl}{$url}{/mailurl}{* the empty line below is necessary to create separate URLs *}

{/foreach}
