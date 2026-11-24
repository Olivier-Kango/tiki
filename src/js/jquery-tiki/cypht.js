$("a:not(.inline-cypht a)").each(function () {
    $(this).data("external", true);
});

$(document).on("click", ".inline-cypht .menu_contacts a", function (e) {
    e.preventDefault();
    window.location.href = "tiki-contacts.php";
});

if (hm_mobile()) {
    if (!$("body").hasClass("mobile")) $("body").addClass("mobile");
}

const setInlineMessageTop = function () {
    const header = document.querySelector("header.sticky-top");
    const headerHeight = header && getComputedStyle(header).position === "sticky" ? header.offsetHeight : 0;
    $(".inline-cypht").css("--cypht-inline-message-top", `${headerHeight + 20}px`);
};
setInlineMessageTop();
$(window).on("resize", setInlineMessageTop);
