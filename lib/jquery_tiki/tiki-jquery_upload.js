/*
 * jQuery File Upload connector for Tiki
 *
 * https://github.com/blueimp/jQuery-File-Upload
 *
 *
 */

$(function () {

    var url = $.service("file", "upload_multiple"),    // upload handler:
        uploadButton = $('<button/>')
            .addClass('btn btn-primary upload')
            .prop('disabled', true)
            .text(tr('Processing...'))
            .on('click', function () {
                var $this = $(this),
                    data = $this.data();
                $this
                    .off('click')
                    .text(tr('Cancel'))
                    .on('click', function () {
                        $this.parents(".buttons").append(cancelButton.clone(true).data(data).text(tr('Clear')));
                        $this.remove();
                        data.abort();
                    })
                    .next("button").hide();

                data.submit().always(function () {
                    $this.next("button").show().text(tr('Clear'));
                    $this.remove();
                });
            }),
        cancelButton = $('<button/>')
            .addClass('btn btn-secondary')
            .text(tr('Cancel'))
            .on('click', function () {
                $(this).parents("div.file-list").remove();
            });
       var image_x=0;
       if($("#image_max_size_x").val())
         image_x=parseInt($("#image_max_size_x").val());
    var image_y=0;
       if($("#image_max_size_y").val())
         image_y=parseInt($("#image_max_size_y").val());

    function acceptedFileTypes() {
        var type = $("#gallery_type").val(), mimeType;

        if (type == 'vidcast') {
            mimeType = /video/;
        } else if (type == 'podcast') {
            mimeType = /audio/;
        }

        return mimeType;
    };

    $('#fileupload').fileupload({

        url: url,
        dataType: 'json',
        autoUpload: false,
        //maxFileSize: 999000,
        // Enable image resizing, except for Android and Opera,
        // which actually support image resizing, but fail to
        // send Blob objects via XHR requests:
        disableImageResize: /Android(?!.*Chrome)|Opera/
            .test(window.navigator.userAgent),

            previewMaxWidth: 100,
            previewMaxHeight: 100,
            previewCrop: true,

        singleFileUploads: false,

        filesContainer: $('div.files'),
        uploadTemplateId: null,
        downloadTemplateId: null,
        uploadTemplate: null,
        downloadTemplate: null

    }).on('fileuploadadd', function (e, data) {
        $(this).fileupload('option', 'acceptFileTypes', acceptedFileTypes());

            image_x=parseInt($("#image_max_size_x").val());
            image_y=parseInt($("#image_max_size_y").val());
            $('#fileupload').fileupload({
            imageMaxWidth: image_x,
            imageMaxHeight: image_y
             });
        var maxfile = $("#max_file_uploads");
        if (data.files.length <= maxfile.val()) {
            data.context = $('<div/>').addClass("file-list panel").prependTo('#files');
            $.each(data.files, function (index, file) {
                var node = $('<p/>').addClass("file-to-upload")
                    .append("<br>")
                    .append($('<span/>').text(file.name));

                node.appendTo(data.context);

                if (index === data.files.length - 1) {
                    var $div = $("<div/>")
                        .addClass("buttons")
                        .append(uploadButton.clone(true).data(data))
                        .append(cancelButton.clone(true).data(data));
                    data.context.append($div);
                }
            });
        } else {
            feedback(
                    tr("\"You can not upload more than "+maxfile.val()+" files\"" ),
                    'warning',
                    false,
                    tr('File upload failed')
                );
        }
    }).on('fileuploadprocessalways', function (e, data) {
        var index = data.index,
            file = data.files[index],
            context = data.context;

        if (!context.length) {    // context seems to fail here
            context = $("div:contains("+file.name+")", "#files");
        }
        var node = $(context.children("p")[index]);

        if (file.preview) {
            if (! $(file.preview).is("canvas")) {
                node.css("width", "auto");
            }
            node.prepend(file.preview);
        } else {
            var type = "file";
            if (file.type.match(/pdf/)) {
                type = "pdf";
            } else if (file.type.match(/video/)) {
                type = "video";
            } else if (file.type.match(/audio/)) {
                type = "audio";
            } else if (file.type.match(/zip/)) {
                type = "zip";
            } else if (file.type.match(/(msword|wordprocessingml\.document)/)) {
                type = "word";
            } else if (file.type.match(/(ms-excel|openxmlformats-officedocument\.spreadsheetml\.sheet)$/)) {
                type = "excel";
            } else if (file.type.match(/(ms-powerpoint|presentationml\.presentation)$/)) {
                type = "powerpoint";
            } else if (file.type.match(/css/)) {
                type = "css";
            } else if (file.type.match(/plain/)) {
                type = "txt";
            } else if (file.type.match(/html/)) {
                type = "html";
            } else if (file.type.match(/message/)) {
                type = "mailbox";
            } else if (file.type.match(/php/)) {
                type = "php";
            } else if (file.type.match(/javascript/)) {
                type = "js";
            } else if (file.type.match(/font/)) {
                type = "font";
            }
            node.prepend($("#" + type + "_icon").clone().removeAttr("id"));
        }
        if (file.error) {
            node.append($('<span class="text-danger"/>').text(file.error));
        }
        if (index === data.files.length - 1) {
            context.find('button').first()
                .text(tr('Upload'))
                .prop('disabled', !!data.files.error);

            var $progdiv = $('<div class="progress mb-2"/>')
                .append('<div class="progress-bar progress-bar-success progress-bar-striped active" role="progressbar"/>');

            context.find(".buttons").prepend($progdiv);

            if ($("input[name=autoupload]:checked").length) {
                context.find('button').first().trigger("click");
            }
        }
    }).on('fileuploaddone', function (e, data) {
        $.each(data.result.files, function (index, file) {

            var context = data.context;

            if (file.fileId) {
                var display = file.type.match(/^image\//) ? "display" : "dl",
                    link = $('<a>')
                    .attr('target', '_blank');

                if (file.type.match(/^image\//)) {
                    link.prop('href', "tiki-download_file.php?display&fileId=" + file.fileId);    // view an image
                } else {
                    link.prop('href', "tiki-list_file_gallery.php?galleryId=" + file.galleryId);    // show gallery for other files
                }

                $(context.children("p")[index])
                    .children()
                    .wrap(link)
                    .find("span").text(file.info.name);

                var match = location.search.match(/filegals_manager=([^&]+)/),
                    message = "";
                if (match && window.opener) {
                    var insertHandler = function () {
                        var syntax = processFgalSyntax(file);        // in files.js
                        window.opener.insertAt(match[1], syntax, false, false, true);
                        checkClose();
                        return false;
                    };
                    $(context).find("a").on("click", insertHandler).attr("title", tr("Click here to use the file"));
                    if (context.find(".buttons").find('input[name=insert]').length == 0) {
                        $('<input type="button">')
                            .attr('name', 'insert')
                            .attr('value', tr('Insert'))
                            .addClass('btn btn-primary')
                            .on("click", insertHandler)
                            .insertAfter(context.find(".progress"));
                    }
                    message = tr("Click on the icon to use the file");
                } else {
                    $(context).find("a").last()
                        .after("<br><code>" + file.syntax + "</code>");

                    if (jqueryTiki.colorbox && file.type.match(/^image\//)) {
                        context.find("a").colorbox({photo: true});
                    }
                }
                if (data.result.files.length > 1) {
                    var messageParam = data.result.files.length + ' ' + tr('files uploaded');
                } else {
                    var messageParam = tr("File \"") + file.info.name + tr("\" uploaded");
                }
                if (message.length) {
                    messageParam = [messageParam, message];
                }
                // show success message
                feedback(messageParam, "success", false, tr("File upload"), null, true);

            } else if (file.error) {
                var error = $('<span class="text-danger"/>').text(file.error);

                $(context.children()[index])
                    .append('<br>')
                    .append(error);
            }
            if (index === data.files.length - 1) {
                context.find(".progress").delay(1000).fadeOut("fast");
            }

        });
        //update anti-CSRF ticket in form
        $('form#file_0 > input[name=ticket]').val(data.result.ticket);

        e.preventDefault();

    }).on('fileuploadfail', function (e, data) {
        $.each(data.files, function (index) {
            var error = $('<span class="text-danger"/>').text(tr('File upload failed: ') + data.errorThrown),
                context = data.context;

            $(context.children()[index])
                .append('<br>')
                .append(error);

            if (index === data.files.length - 1) {
                context.find(".progress").delay(1000).fadeOut("fast");
            }
        });
        return false;
    }).parents("form").off("submit").on("submit", function (e) {
        // submitting the form seems to happen automatically resulting in a white page - not sure how still but this stops it...
        return false;

    }).prop('disabled', !$.support.fileInput)
        .parent().addClass($.support.fileInput ? undefined : 'disabled');

});
