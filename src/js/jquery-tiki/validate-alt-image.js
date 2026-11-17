// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//
// This JavaScript file is used in tiki-edit_article.php and tiki-edit_submission.php.
// It provides client-side validation to ensure that when "Use Own Image" is enabled,
// an Alt Text value is required before allowing the form to be submitted.

(function ($) {
    var form = document.getElementById("editpageform");
    var $altInput = $("#image_alt");
    var $useImageCheckbox = $("#useImage");

    $(form).on("submit", function (event) {
        var action = $(document.activeElement).attr("name");

        if (action === "save" || action === "submitarticle" || action === "preview") {
            // Explicit Logic: If "Use Own Image" is Checked AND "Alt Text" is empty
            if ($useImageCheckbox.is(":checked") && $altInput.val().trim() === "") {
                event.preventDefault();
                event.stopPropagation();

                $altInput.attr("required", "required");
                form.classList.add("was-validated");
                $altInput[0].setCustomValidity(" ");

                // Clear custom validity as soon as the user supplies a value.
                $altInput.one("input", function () {
                    this.setCustomValidity("");
                });

                var $tabPane = $altInput.closest(".tab-pane");
                if ($tabPane.length && !$tabPane.hasClass("active")) {
                    var selector =
                        '[data-bs-toggle="tab"][href="#' +
                        $tabPane.attr("id") +
                        '"], ' +
                        '[data-bs-toggle="tab"][data-bs-target="#' +
                        $tabPane.attr("id") +
                        '"]';
                    var $tabLink = $(selector);
                    if ($tabLink.length && window.bootstrap && bootstrap.Tab) {
                        $tabLink.one("shown.bs.tab", function () {
                            $altInput.trigger("focus");
                            $altInput[0].scrollIntoView({ behavior: "smooth", block: "center" });
                        });
                        bootstrap.Tab.getOrCreateInstance($tabLink[0]).show();
                    }
                } else {
                    $altInput.trigger("focus");
                    $altInput[0].scrollIntoView({ behavior: "smooth", block: "center" });
                }

                return false;
            }
        }
    });
})(jQuery);
