import { parseData } from "./formSubmission.helpers";

export default function (textarea) {
    const form = textarea.closest("form");
    form.data("should-parse-editor-data", true);
    textarea.closest("form").on("submit", function (e) {
        if (!form.data("should-parse-editor-data") || textarea.summernote("codeview.isActivated")) {
            return;
        }
        e.preventDefault();
        form.data("submitted", false);

        parseData(textarea, () => {
            form.data("should-parse-editor-data", false);
            textarea.data("is-submitting", true);
            const submitter = e.originalEvent?.submitter;
            if (submitter) {
                $(submitter).trigger("click");
            } else {
                this.submit();
            }
        });
    });
}
