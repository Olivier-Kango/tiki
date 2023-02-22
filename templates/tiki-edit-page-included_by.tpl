<ul>
  {foreach from=$included_by item=include}
  <li>
    {object_type type=$include.type}:
    {object_link type=$include.type objectId=$include.itemId class="alert-link"}
    {if $include.start || $include.end} - {/if}
    {if !empty($include.start)}
    {tr}from{/tr} "{$include.start}"
    {/if}
    {if !empty($include.stop)}
    {tr}to{/tr} "{$include.stop}"
    {/if}
    {if !empty($include.version)}
      {tr}Version Of page{/tr} "{$include.version}"
    {/if}

  </li>
  {/foreach}
</ul>
