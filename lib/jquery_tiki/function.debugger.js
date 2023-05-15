function copyToClipboard(elem) {
    if ("clipboard" in navigator) {
        return navigator.clipboard
            .writeText(elem.textContent)
            .then(() => true)
            .catch(() => false);
    } else {
        let targetId = "_hiddenCopyText_";
        let isInput = elem.tagName === "INPUT" || elem.tagName === "TEXTAREA";
        let origSelectionStart, origSelectionEnd;
        var target;
        if (isInput) {
            target = elem;
            origSelectionStart = elem.selectionStart;
            origSelectionEnd = elem.selectionEnd;
        } else {
            target = document.getElementById(targetId);
            if (!target) {
                target = document.createElement("textarea");
                target.style.position = "absolute";
                target.style.left = "-9999px";
                target.style.top = "0";
                target.id = targetId;
                document.body.appendChild(target);
            }
            target.textContent = elem.textContent;
        }
        let currentFocus = document.activeElement;
        target.focus();
        target.setSelectionRange(0, target.value.length);
        let succeed;
        try {
            succeed = document.execCommand("copy");
        } catch (e) {
            succeed = false;
        }
        if (currentFocus && typeof currentFocus.focus === "function") {
            currentFocus.focus();
        }

        if (isInput) {
            elem.setSelectionRange(origSelectionStart, origSelectionEnd);
        } else {
            target.textContent = "";
        }
        return succeed;
    }
}

$("#copyButton").click(function () {
    const success = copyToClipboard(document.getElementById("command_example"), "msg");
    if (success) {
        $("#success_copy_icon").fadeIn();
        setTimeout(function () {
            $("#success_copy_icon").fadeOut();
        }, 1000);
    }
});

const command_details = {
    "features [partial-name]": [tr("Show features on/off state"), "features wiki"],
    "perm [partial-name]": [tr("Show current permissions in a convenient way"), "perm tiki_p_admin_wiki"],
    "print $var1 $var2 ...": [tr("Print PHP variable."), "print $_REQUEST"],
    slist: [tr("Display list of Smarty variables."), "slist"],
    "sprint $var1 $var2 $var3 ...": [tr("Print Smarty variable."), "sprint user"],
    "sql [sql-query]": [tr("Exec SQL query on Tiki DB"), "sql select * from tiki_preferences"],
    test: ["", ""],
    "tikitables  [partial-name]": [tr("Show list of Tiki tables in DB schema"), "tikitables user"],
    "watch (add|rm) $php_var1 smarty_var2 $php_var3 smarty_var4 ...": [tr("Manage variables watch list"), "watch add $user tiki_p_view"],
};

$("#command_preselect").on("change", function (e) {
    $("#command_input").val(e.target.value);
    $("#command_example").text(command_details[e.target.value][1]);
    $("#command_description").text(command_details[e.target.value][0]);
    $("#command_meta").fadeIn();
});
$("#command_input").on("keyup", function (e) {
    $("#command_preselect").val(""); //make sure nothing is preselected on the dropdown when user start typing
    $("#command_meta").fadeOut();
});
window.viewHelp = function (event) {
    event.preventDefault();

    $("#command_input").val("help");
    $("#command_form").trigger("submit");
};

$(document).on("ready", function () {
    var position = { x: 0, y: 0 };
    const initPos = JSON.parse(localStorage.getItem("debugconsole_position"));
    if (initPos) {
        position = { x: initPos[0], y: initPos[1] };
    }
    $("#debugconsole").css("transform", `translate(${position.x}px, ${position.y}px)`); //position the console on it previous saved position
    const debugger_shown = $("#debugconsole").css("display");
    if (debugger_shown == "block") {
        $("#debugconsole").animate({ opacity: 1 }, 400);
    }
    interact("#debugconsole")
        .resizable({
            //resize from left and top
            edges: { left: true, right: false, bottom: false, top: true },

            listeners: {
                move(event) {
                    var target = event.target;
                    var x = parseFloat(target.getAttribute("data-x")) || 0;
                    var y = parseFloat(target.getAttribute("data-y")) || 0;

                    // update the element's style
                    target.style.width = event.rect.width + "px";
                    target.style.height = event.rect.height + "px";

                    position.x += event.dx;
                    position.y += event.dy;

                    event.target.style.transform = `translate(${position.x}px, ${position.y}px)`;
                    localStorage.setItem("debugconsole_position", JSON.stringify([position.x, position.y])); //save pos to cookie for next tick

                    target.setAttribute("data-x", x);
                    target.setAttribute("data-y", y);
                },
            },
            modifiers: [
                // keep the edges inside the parent
                interact.modifiers.restrictEdges({
                    outer: "parent",
                }),

                // minimum size
                interact.modifiers.restrictSize({
                    min: { width: 320, height: 250 },
                }),
            ],

            inertia: true,
        })
        .draggable({
            ignoreFrom: ".selectable",
            inertia: true,
            modifiers: [
                interact.modifiers.restrictRect({
                    restriction: "parent",
                    endOnly: true,
                }),
            ],
            listeners: {
                start(event) {
                    //called when drag start
                },
                move(event) {
                    position.x += event.dx;
                    position.y += event.dy;

                    event.target.style.transform = `translate(${position.x}px, ${position.y}px)`;
                    localStorage.setItem("debugconsole_position", JSON.stringify([position.x, position.y])); //save pos to cookie for next tick
                },
            },
        });
});
