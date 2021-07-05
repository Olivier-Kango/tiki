/**
 * Support utils for tracker rules
 */

(function($) {

    $.fn.actionEditable = function(editable) {
        let disabled = ! editable;
        $(this).find("input:not(.chosen-search-input),textarea,select").each(function () {
            let $this = $(this);
            if ($this.is("input[type=hidden]")) {    // multiselect with chosen or object selector?
                let $select = $this.next("select");
                $select.prop("disabled", disabled).trigger("chosen:updated");
            } else {
                if (disabled) {
                    let $shadow = $("<input type=hidden>").attr("name", $this.attr("name"));
                    if ($this.is("input[type=checkbox]") && $this.is(":checked")) {
                        $shadow.val("on");
                    } else {
                        $shadow.val($this.val());
                    }
                    $this.before($shadow);
                } else {
                    $this.prev("input[type=hidden][name=" + $this.attr("name") + "]").remove();
                }
                $this.prop("disabled", disabled).trigger("chosen:updated");
            }
        });
    };

    $.fn.setValue = function(value) {
        $(this).find("input:not(.chosen-search-input),textarea,select").each(function () {
            let $this = $(this);
            if ($this.is("input[type=hidden]")) {    // multiselect with chosen or object selector?
                let $select = $this.next("select");
                $select.val(value).trigger("chosen:updated");
            } else if ($this.is("input[type=checkbox]")) {
                if (! isNaN(value)) {
                    value = parseInt(value);
                } else if (typeof value === "string") {
                    value = value.toLowerCase();
                    value = (value === "y" || value === "yes" || value === "on");
                }
                $this.prop("checked", value);
            } else if ($this.is("input[type=radio]")) {
                $("input[type=radio][name=" + $this.attr("name") + "][value=" + value + "]", $this.form())
                    .prop("checked", true);
            } else {
                $this.val(value).trigger("chosen:updated");
            }
        });
    };
})(jQuery);
