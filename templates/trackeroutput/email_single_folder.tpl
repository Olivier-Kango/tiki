<table class="table table-striped table-hover">
  <thead>
  <tr>
    <th>{tr}Sender{/tr}</th>
    <th>{tr}Recipient{/tr}</th>
    <th>{tr}Subject{/tr}</th>
    <th>{tr}Date{/tr}</th>
  </tr>
  </thead>
  <tbody>
  {foreach from=$emails item=email}
    <tr>
      <td>
        {if $email.sender}
          {$email.sender|escape}
        {else}
          {$email.from|escape}
        {/if}
      </td>
      <td>{$email.recipient|escape}</td>
      <td><a href="tiki-webmail.php?page=message&amp;uid={$email.fileId}&amp;list_path=tracker_folder_{$email.itemId}_{$email.fieldId}&amp;list_parent=tracker_{$email.trackerId}">{$email.subject|escape}</a></td>
      <td>{$email.date|tiki_short_datetime}</td>
    </tr>
  {/foreach}
  </tbody>
</table>
