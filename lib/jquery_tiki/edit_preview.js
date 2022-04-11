/**
 * New "in-tabs" edit previews
 */

if (typeof initEditPreview === "undefined") {
    function initEditPreview() {
        $(".edit-preview-zone").each(function () {
            let $this = $(this),
                $tabs = $this.find(".tabs"),
                $preview = $this.find(".textarea-preview"),
                $textarea = $("#" + $preview.attr("id").replace("preview_div_", ""));

            $('li:nth-child(2) a[data-bs-toggle="tab"]', $tabs).on('shown.bs.tab', function (event) {
                $.getJSON($.service("edit", "tohtml"), {
                        data: $textarea.val()
                    },
                    function (data) {
                        $preview.html(data.data);
                    }
                );
            });

            $('li:first-child a[data-bs-toggle="tab"]', $tabs).tab("show");
        });
    }
}

$(document).on("ready tiki.ajax.redraw tiki.modal.redraw", function () {
    initEditPreview();
});
