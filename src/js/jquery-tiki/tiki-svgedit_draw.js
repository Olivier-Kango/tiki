import Editor from "svgedit/dist/editor/Editor.js";
import "svgedit/dist/editor/svgedit.css";

$.fn.drawFullscreen = function () {
    var win = $(window);
    var me = $(this);
    me.trigger("saveDraw");

    let fullscreen = $("#svg-fullscreen");

    if (fullscreen.length === 0) {
        me.data("origParent", me.parent());

        var menuHeight = $("#drawMenu").height();
        $("body").addClass("full_screen_body");
        $("body,html").scrollTop(0);

        fullscreen = $('<div id="svg-fullscreen" />').html(me).prependTo("body");

        var fullscreenSvgEdit = fullscreen.find("#svgedit");

        win.on("resize", function () {
            fullscreen.height(win.height()).width(win.width());

            fullscreenSvgEdit.height(fullscreen.height() - menuHeight);
        }).trigger("resize");
    } else {
        me.data("origParent").append(me);
        win.off("resize");
        fullscreen.remove();
        $("body").removeClass("full_screen_body");
    }

    return this;
};

$.fn.replaceDraw = function (o) {
    var me = $(this);
    if (o.error) {
        alert("error " + o.error);
    } else {
        $.tikiModal(tr("Saving..."));
        $.post(
            "tiki-edit_draw.php",
            {
                galleryId: o.galleryId,
                fileId: o.fileId,
                imgParams: o.imgParams,
                name: o.name,
                data: o.data,
            },
            function (fileId) {
                fileId = fileId ? fileId : o.fileId;
                o.fileId = fileId;

                me.data("fileId", o.fileId);
                me.data("galleryId", o.galleryId);
                me.data("imgParams", o.imgParams);
                me.data("name", o.name);

                $.tikiModal(tr("Saved file id") + o.fileId + "!");

                if ($.wikiTrackingDraw) {
                    $.wikiTrackingDraw.params.id = o.fileId;
                    $.tikiModal(tr("Updating Wiki Page"));
                    $.post("tiki-wikiplugin_edit.php", $.wikiTrackingDraw, function () {
                        me.trigger("savedDraw", o);
                        $.tikiModal();
                    });
                } else {
                    me.trigger("savedDraw", o);
                    $.tikiModal();
                }
            }
        );
    }

    return this;
};

$.fn.saveDraw = function () {
    var me = $(this);
    var svgString = me.data("editor").svgCanvas.getSvgString();
    me.replaceDraw({
        data: svgString,
        fileId: me.data("fileId"),
        galleryId: me.data("galleryId"),
        imgParams: me.data("imgParams"),
        name: me.data("name"),
    });

    try {
        me.data("editor").svgCanvas.undoMgr.resetUndoStack();
    } catch (e) {}

    return this;
};

$.fn.saveAndBackDraw = function () {
    $(this)
        .saveDraw()
        .one("savedDraw", function () {
            window.history.back();
        });
};

$.fn.renameDraw = function () {
    var me = $(this);
    var name = me.data("name");
    var newName = prompt(tr("Enter new name"), name);

    if (newName) {
        if (newName !== name) {
            name = newName;
            me.data("name", name);
            me.data("editor").title = name + ".svg";
            me.data("editor").topPanel.update();
            me.trigger("renamedDraw", name);
            me.saveDraw();
        }
    }

    return this;
};

$.drawInstance = 0;

$.fn.loadDraw = function (o) {
    var me = $(this);

    // Prevent from happening over and over again
    if (me.data("drawLoaded")) return me;

    me.data("drawLoaded", true);

    var drawFrame = $('<div id="svgedit" style="width:100%;height:100vh"></div>').appendTo(me);

    $(document).ready(function () {
        me.data("drawInstance", $.drawInstance)
            .data("fileId", o.fileId ? o.fileId : 0)
            .data("galleryId", o.galleryId ? o.galleryId : 0)
            .data("imgParams", o.imgParams ? o.imgParams : {})
            .data("name", o.name ? o.name : "");

        var svgEditor = new Editor(document.getElementById("svgedit"));
        svgEditor.setConfig({
            allowInitialUserOverride: true,
            extensions: [],
            noDefaultExtensions: true,
            userExtensions: [],
            canvas_expansion: 2,
            lang: $.lang ? $.lang : "en",
            imgPath: "node_modules/svgedit/dist/editor/images/",
            extPath: "node_modules/svgedit/dist/editor/extensions/",
        });
        svgEditor.init();

        // Wait for the initialization and assignment of the svgCanvas within the svgEditor object.
        var checkSvgCanvas = setInterval(function () {
            if (svgEditor.svgCanvas) {
                clearInterval(checkSvgCanvas);
                me.data("editor", svgEditor);

                o.data = o.data.trim();
                if (o.data && o.fileId.toString() !== "0") {
                    me.data("editor").svgCanvas.setSvgString(o.data);
                }

                me.data("editor").onbeforeunload = function () {};

                $(window).on("beforeunload", function () {
                    try {
                        if (me.data("editor") && me.data("editor").svgCanvas.undoMgr.getUndoStackSize() > 1) {
                            return tr("There are unsaved changes, leave page?");
                        }
                    } catch (e) {}
                });

                drawFrame.height($(window).height() * 0.9);
                $.drawInstance++;

                me.trigger("loadedDraw");

                $.getJSON(
                    "tiki-ajax_services.php",
                    {
                        controller: "draw",
                        action: "removeButtons",
                    },
                    function (data) {
                        if (data.removeButtons) {
                            if (!$.isArray(data.removeButtons)) data.removeButtons = data.removeButtons.split(",");
                            for (let id in data.removeButtons) {
                                me.data("doc")
                                    .find("#" + data.removeButtons[id].trim())
                                    .wrap('<div style="display:none;"/>');
                            }
                        }
                    }
                );

                me.data("editor").updateCanvas();
            }
        }, 100);
    });

    return me;
};
