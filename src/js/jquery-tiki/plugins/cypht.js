$("a:not(.inline-cypht a)").each(function () {
    const href = $(this).attr("href");
    this.href = "#";
    $(this).on("click", () => {
        if ($(this).attr("target") === "_blank") {
            window.open(href, "_blank");
        } else {
            window.location.href = href;
        }
    });
});

$(document).on("click", ".inline-cypht .menu_contacts a", function (e) {
    e.preventDefault();
    window.location.href = "tiki-contacts.php";
});
