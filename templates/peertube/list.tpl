{extends 'layout_view.tpl'}

{block name="title"}
  {title}{$title|escape}{/title}
{/block}

{block name="content"}
  <div class="peertube-media-list list-group">
    {foreach $entries as $item}
      {assign var=vid value=$item->shortUUID|default:$item->uuid|default:$item->id}
      <a href="#" class="list-group-item d-flex align-items-center pt-select" data-id="{$vid|escape}">
        {if $item->thumbUrl}
          <img class="me-2" src="{$item->thumbUrl|escape}" width="120" height="80" alt="{$item->description|default:''|escape}">
        {/if}
        <span>{$item->name|default:$item->title|escape}</span>
      </a>
    {/foreach}
    {if not $entries|@count}
      <div class="text-muted p-2">{tr}No videos found{/tr}</div>
    {/if}
  </div>

  {jq}
    $(".pt-select", ".peertube-media-list").on("click", function (e) {
      e.preventDefault();
      var id = $(this).data("id");
      var targetName = "{{$targetName|escape:'javascript'}}";
      var formId     = "{{$formId|escape:'javascript'}}";

      var $parent = window.parent ? $(window.parent.document) : $(document);
      var $form   = formId ? $parent.find('form#' + formId)
                          : $parent.find('input[name="'+targetName+'"]').closest('form');

      if (!$form.length) { alert('Form not found'); return; }

      $('<input type="hidden">').attr('name', targetName).val(id).appendTo($form);
      $parent.find('#bootstrap-modal').modal('hide');
    });
  {/jq}
{/block}
