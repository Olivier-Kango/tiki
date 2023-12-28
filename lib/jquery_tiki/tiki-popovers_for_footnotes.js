$("[data-bs-toggle='popover']").each(function (index, element) {
  var contentElementId = $(element).data().target;
  var contentHtml = $(contentElementId).html();
  $(element).Popover({
    content: contentHtml,
    delay: { show: 0, hide: 10 },
    placement: $.tikiPopoverWhereToPlace,
  });
});
