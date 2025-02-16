/**
 * Tiki wrapper for elFinder
 *
 * (c) Copyright by authors of the Tiki Wiki CMS Groupware Project

 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
 *
 *
 */

/**
 * Open a dialog with elFinder in it
 * @param element    unused?
 * @param options    object containing jquery-ui and elFinder dialog options
 * @return {Boolean}
 */

openElFinderDialog = function(element, options = {}) {
    options = $.extend({
        height : 500,
        eventOrigin: this,
        uploadCallback: null
    }, options);


    if (options.eventOrigin) {    // save it for later
        $("body").data("eventOrigin", options.eventOrigin);    // sadly adding data to the dialog kills elfinder :(
        delete options.eventOrigin;
    }

    const elfoptions = initElFinder(options);

    $.openModal({
        title: tr("Browse Files"),
        size: "modal-lg",
        dialogVariants: ["centered", "scrollable"],
        open: function () {
            $(window).data('elFinderDialog', this);
            const modalBody = $(this).find('.modal-body');
            modalBody.html('<div class="elFinderDialog" />');
            const elf = modalBody.find('.elFinderDialog');
            elf.elfinder(elfoptions).elfinder('instance');
            if (options.uploadCallback) {
                // note: elfinder('instance') is not a jQuery object and still uses bind for events
                elf.elfinder('instance').bind("upload", options.uploadCallback);
            }
        }
    });

    return false;
};

/**
 * Set up elFinder for tiki use
 *
 * @param options {Object} Tiki ones: defaultGalleryId, deepGallerySearch & getFileCallback
 *             also see https://github.com/Studio-42/elFinder/wiki/Client-configuration-options
 * @return {Object}
 */

function initElFinder(options) {

    options = $.extend({
        getFileCallback: null,
        defaultGalleryId: 0,
        defaultVolumeId: 0,
        deepGallerySearch: true,
        url: $.service('file_finder', 'finder'), // connector URL
        // lang: 'ru',                                // language (TODO)
        customData: {
            defaultGalleryId: options.defaultGalleryId,
            deepGallerySearch: options.deepGallerySearch,
            ticket: options.ticket
        },
        commandsOptions: {
            info: {                // tiki specific additions for the file info dialog
                custom: {
                    hits: {
                        label: tr("Hits"),
                        tpl: '<div class="elfinder-info-hits"><span class="elfinder-info-spinner"></span></div>',
                        //     mimes : ['text', 'image/jpeg', 'directory'],
                        //     hashRegex : /^l\d+_/,
                        action: function (file, fm, dialog) {
                            fm.request({
                                data: {cmd: 'info', target: file.hash, content: ""},    // get all the info in one call
                                preventDefault: true
                            })
                                .fail(function () {
                                    dialog.find('div.elfinder-info-hits').html(fm.i18n('unknown'));
                                    dialog.find('div.elfinder-info-fileid').html(fm.i18n('unknown'));
                                    dialog.find('div.elfinder-info-user').html(fm.i18n('unknown'));
                                    dialog.find('div.elfinder-info-description').html(fm.i18n('unknown'));
                                    dialog.find('div.elfinder-info-syntax').html(fm.i18n('unknown'));
                                    dialog.find('div.elfinder-info-edit').html(fm.i18n('unknown'));
                                })
                                .done(function (data) {
                                    var edit, id;
                                    if (file.mime === "directory") {
                                        id = data.info.galleryId;
                                        edit = "tiki-list_file_gallery.php?view=list&edit_mode=1&galleryId=" + id;
                                    } else {
                                        id = data.info.fileId;
                                        edit = "tiki-upload_file.php?fileId=" + id;
                                    }

                                    edit =  '<a href="' + edit + '">' + tr("Edit Properties") + "</a>";

                                    dialog.find('a').first().parent().html(data.info.link);
                                    dialog.find('div.elfinder-info-hits').html(data.info.hits);
                                    dialog.find('div.elfinder-info-fileid').html(id);
                                    dialog.find('div.elfinder-info-user').html(data.info.user || "");
                                    dialog.find('div.elfinder-info-description').html(data.info.description || "");
                                    dialog.find('div.elfinder-info-syntax').html(data.info.wiki_syntax || "");
                                    dialog.find('div.elfinder-info-edit').html(edit);
                                });
                        }
                    },
                    fileId: {
                        label: tr("ID"),
                        tpl: '<div class="elfinder-info-fileid"><span class="elfinder-info-spinner"></span></div>',
                    },
                    user: {
                        label: tr("User"),
                        tpl: '<div class="elfinder-info-user"><span class="elfinder-info-spinner"></span></div>',
                    },
                    syntax: {
                        label: tr("syntax"),
                        tpl: '<div class="elfinder-info-syntax"><span class="elfinder-info-spinner"></span></div>',
                    },
                    description: {
                        label: tr("Description"),
                        tpl: '<div class="elfinder-info-description"><span class="elfinder-info-spinner"></span></div>',
                    },
                    edit: {
                        label: tr("Properties"),
                        tpl: '<div class="elfinder-info-edit"><span class="elfinder-info-spinner"></span></div>',
                    }
                }
            }

        }
    }, options);

    var lang = jqueryTiki.language;
    if (lang && typeof elFinder.prototype.i18[lang] !== "undefined" && !options.lang) {
        if (lang === 'cn') {
            lang = 'zh_CN';
        } else if (lang === 'pt-br') {
            lang = 'pt_BR';
        }
        options.lang = lang;
    }

    if (options.defaultGalleryId > 0) {
        // reset the url hash just in case
        location.hash = "";
        // if it's the "root" gallery then adding the prefix makes elFinder hang
        let prefix = (options.defaultGalleryId === options.defaultVolumeId) ? "" : "d_";
        options.startPathHash = 'f' + options.defaultVolumeId + '_' + btoa(prefix + options.defaultGalleryId)
            .replace(/\+/g, '-').replace(/\//g, '_')
            .replace(/=/g, '.').replace(/\.+$/, '');
    }

    delete options.defaultGalleryId;        // moved into customData
    delete options.defaultVolumeId;
    delete options.deepGallerySearch;
    delete options.ticket;


    // turn off some elfinder commands - not many left to do...
    var remainingCommands = elFinder.prototype._options.commands, idx;
    var disabled = ['mkfile', 'edit', 'archive', 'resize'];
    // done 'rm', 'duplicate', 'rename', 'mkdir', 'upload', 'copy', 'cut', 'paste', 'extract',
    $.each(disabled, function (i, cmd) {
        (idx = $.inArray(cmd, remainingCommands)) !== -1 && remainingCommands.splice(idx, 1);
    });
    return options;
}

