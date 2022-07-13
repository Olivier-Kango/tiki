/**
 * New "in-tabs" edit previews
 */

if (typeof initEditPreview === "undefined") {
    function initEditPreview() {
        $(".edit-preview-zone").each(function () {
            const $this = $(this),
                $tabs = $this.find(".tabs"),
                $preview = $this.find(".textarea-preview"),
                textAreaId = $preview.attr("id").replace("preview_div_", ""),
                $textarea = $("#" + textAreaId);

            $('li:nth-child(2) a[data-bs-toggle="tab"]', $tabs).on('show.bs.tab', function (event) {
                let data = "", ed;

                if (typeof CKEDITOR === 'object') {
                    for (ed in CKEDITOR.instances) {
                        if (CKEDITOR.instances.hasOwnProperty(ed)) {
                            const editor = CKEDITOR.instances[ed];
                            if (editor.element.getId() === textAreaId) {
                                data = editor.getData();
                                break;
                            }
                        }
                    }
                } else {
                    data = $textarea.val();
                }

                $.getJSON($.service("edit", "tohtml"), {
                        data: data
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
