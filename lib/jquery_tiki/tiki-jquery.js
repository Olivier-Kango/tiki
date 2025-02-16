/* global $ */

// JavaScript glue for jQuery in Tiki
//
// Tiki 6 - $ is now initialised in jquery.js
// but let's keep $jq available too for legacy custom code

var legacyLoad = jQuery.fn.load;
jQuery.fn.load = function (url, _data, _complete) {
    var element = this;
    element.show();

    element.tikiModal(tr('Loading...'));
    if (typeof _data === "function") {
        _complete = _data;
        _data = "";
    }
    var complete = function (responseText, textStatus, jqXHR) {
        element.tikiModal();
        if (textStatus === 'error') {
            element.html('<div class="alert alert-danger alert-dismissible" role="alert">'
                + '<button type="button" class="close" data-bs-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'
                + '<span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>'
                + 'Error loading content.</div>');
            return;
        }
        if (typeof _complete === "function") {
            _complete.call(this, responseText, textStatus, jqXHR);
        }
    };
    return legacyLoad.call(this, url, _data, complete);
};

var $jq = $,
    $window = $(window),
    $document = $(document);

// Check / Uncheck all Checkboxes
function switchCheckboxes (tform, elements_name, state, hiddenToo) {
    // checkboxes need to have the same name elements_name
    // e.g. <input type="checkbox" name="my_ename[]">, will arrive as Array in php.
    if (hiddenToo == undefined) {
        hiddenToo = false;
    }
    var closeTag;
    if (hiddenToo) {
        closeTag = '"]';
    } else {
        closeTag = '"]:visible';
    }
    $(tform).contents().find('input[name="' + jQuery.escapeSelector(elements_name) + closeTag).prop('checked', state).trigger("change");
}

// add id's of any elements that don't like being animated here
var jqNoAnimElements = ['help_sections', 'ajaxLoading'];

function show(foo, f, section) {
    if ($.inArray(foo, jqNoAnimElements) > -1 || typeof jqueryTiki === 'undefined') {        // exceptions that don't animate reliably
        $("#" + foo).show();
    } else if ($("#" + foo).hasClass("tabcontent")) {        // different anim prefs for tabs
        showJQ("#" + foo, jqueryTiki.effect_tabs, jqueryTiki.effect_tabs_speed, jqueryTiki.effect_tabs_direction);
    } else {
        showJQ("#" + foo, jqueryTiki.effect, jqueryTiki.effect_speed, jqueryTiki.effect_direction);
    }
    if (f) {setCookie(foo, "o", section);}
}

function hide(foo, f, section) {
    if ($.inArray(foo, jqNoAnimElements) > -1 || typeof jqueryTiki === 'undefined') {        // exceptions
        $("#" + foo).hide();
    } else if ($("#" + foo).hasClass("tabcontent")) {
        hideJQ("#" + foo, jqueryTiki.effect_tabs, jqueryTiki.effect_tabs_speed, jqueryTiki.effect_tabs_direction);
    } else {
        hideJQ("#" + foo, jqueryTiki.effect, jqueryTiki.effect_speed, jqueryTiki.effect_direction);
    }
    if (f) {
//        var wasnot = getCookie(foo, section, 'x') == 'x';
        setCookie(foo, "c", section);
//        if (wasnot) {
//            history.go(0);    // used to reload the page with all menu items closed - broken since 3.x
//        }
    }
}

// flip function... unfortunately didn't use show/hide (ay?)
function flip(foo, style) {
    var $foo = $("#" + foo);
    if (style && style !== 'block' || foo === 'help_sections' || foo === 'fgalexplorer' || typeof jqueryTiki === 'undefined') {    // TODO find a better way?
        $foo.toggle();    // inlines don't animate reliably (yet) (also help)
        if ($foo.css('display') === 'none') {
            setSessionVar('show_' + encodeURIComponent(foo), 'n');
        } else {
            setSessionVar('show_' + encodeURIComponent(foo), 'y');
        }
    } else {
        if ($foo.css("display") === "none") {
            setSessionVar('show_' + encodeURIComponent(foo), 'y');
            show(foo);
        }
        else {
            setSessionVar('show_' + encodeURIComponent(foo), 'n');
            hide(foo);
        }
    }
}

// handle JQ effects
function showJQ(selector, effect, speed, dir) {
    if (effect === 'none') {
        $(selector).show();
    } else if (effect === '' || effect === 'normal') {
        $(selector).show(400);    // jquery 1.4 no longer seems to understand 'nnormal' as a speed
    } else if (effect == 'slide') {
        $(selector).slideDown(speed);
    } else if (effect === 'fade') {
        $(selector).fadeIn(speed);
    } else if (effect.match(/(.*)_ui$/).length > 1) {
        $(selector).show(effect.match(/(.*)_ui$/)[1], {direction: dir}, speed);
    } else {
        $(selector).show();
    }
}

function hideJQ(selector, effect, speed, dir) {
    if (effect === 'none') {
        $(selector).hide();
    } else if (effect === '' || effect === 'normal') {
        $(selector).hide(400);    // jquery 1.4 no longer seems to understand 'nnormal' as a speed
    } else if (effect === 'slide') {
        $(selector).slideUp(speed);
    } else if (effect === 'fade') {
        $(selector).fadeOut(speed);
    } else if (effect.match(/(.*)_ui$/).length > 1) {
        $(selector).hide(effect.match(/(.*)_ui$/)[1], {direction: dir}, speed);
    } else {
        $(selector).hide();
    }
}

// ajax loading indicator

function ajaxLoadingShow(destName) {
    var $dest, $loading, pos, x, y, w, h;

    if (typeof destName === 'string') {
        $dest = $('#' + destName);
    } else {
        $dest = $(destName);
    }
    if ($dest.length === 0 || $dest.parents(":hidden").length > 0) {
        return;
    }
    $loading = $('#ajaxLoading');

    // find area of destination element
    pos = $dest.offset();
    // clip to page
    if (pos.left + $dest.width() > $window.width()) {
        w = $window.width() - pos.left;
    } else {
        w = $dest.width();
    }
    if (pos.top + $dest.height() > $window.height()) {
        h = $window.height() - pos.top;
    } else {
        h = $dest.height();
    }
    x = pos.left + (w / 2) - ($loading.width() / 2);
    y = pos.top + (h / 2) - ($loading.height() / 2);


    // position loading div
    $loading.css('left', x).css('top', y);
    // now BG
    x = pos.left + ccsValueToInteger($dest.css("margin-left"));
    y = pos.top + ccsValueToInteger($dest.css("margin-top"));
    w = ccsValueToInteger($dest.css("padding-left")) + $dest.width() + ccsValueToInteger($dest.css("padding-right"));
    h = ccsValueToInteger($dest.css("padding-top")) + $dest.height() + ccsValueToInteger($dest.css("padding-bottom"));
    $('#ajaxLoadingBG').css('left', pos.left).css('top', pos.top).width(w).height(h).fadeIn("fast");

    show('ajaxLoading');


}

function ajaxLoadingHide() {
    hide('ajaxLoading');
    $('#ajaxLoadingBG').fadeOut("fast");
}


function ajaxSubmitEventHandler(successCallback, dataType) {
    return function (e) {
        e.preventDefault();
        let form = this,
            act = $(form).attr('action'),
            modal = $(form).closest('.modal-dialog'),
            formData = null;

        if (! act) {
            if (typeof url !== "undefined") {
                act = url;
            } else {
                return false;
            }
        }

        dataType = dataType || 'json';

        if (typeof $(form).valid === "function") {
            if (!$(form).valid()) {
                return false;
            } else if ($(form).validate().pendingRequest > 0) {
                $(form).validate();
                setTimeout(function() {$(form).trigger("submit");}, 500);
                return false;
            }
        }

        modal.tikiModal(tr('Loading...'));

        // if there is a file is included in form, use FormData, otherwise, serialize the form input values.
        // FormData still has issues in IE, though they've been fixed in Edge.
        if ($(form).find("input[type=file]").length){
            formData = new FormData(form);
        } else {
            formData = $(form).serialize();
        }

        let formSubmission = {
            type: 'POST',
            data: formData,
            dataType: dataType,
            success: function (data) {
                successCallback.apply(form, [data]);
            },
            error: function (jqxhr) {
                //     Headers sent from Feedback class already handled through an ajaxComplete
                if (! jqxhr.getResponseHeader('X-Tiki-Feedback')) {
                    modal.tikiModal();
                    $(form).showError(jqxhr);
                }
            },
            complete: function () {
                modal.tikiModal();
            }
        };

        // if the encryption type on the form is set to 'multipart/form-data' or formData is a FormData object
        // we must set contentType and processData to false on the ajax submission
        if (form.enctype === "multipart/form-data" || formData.constructor === FormData) {
            formSubmission.contentType = false;
            formSubmission.processData = false;
        }

        $.ajax(act, formSubmission);
        return false;
    };
}

/**
 * Check checkboxes of rows having duplicate content in a table
 *
 * @param {HTMLElement} button the button that triggered the action
 * @param {Number[] | String} ignoreColIndexes the indexes of the columns to not consider for the comparison
 */
function checkDuplicateRows(button, ignoreColIndexes = []) {
    const $rows = $(button).parents("table").first().find("tr").filter((index) => index > 0);

    const getRowText = function ($row) {
        return $row.find("td").map(function (index, element) {
            return ignoreColIndexes.includes(index) ? "" : $(element).text();
        }).get().join("");
    };

    $rows.each(function (_, element) {
        if ($("input:checked", element).length === 0) {
            $rows.each(function (_, element2) {
                if (element !== element2 && $("input:checked", element2).length === 0) {
                    if (getRowText($(element)) === getRowText($(element2))) {
                        $(":checkbox:first", element2).prop("checked", true);
                    }
                }
            });
        }
    });
}

function scrollToSimilarAnchor() {
    var percent = 0;
    var similar_anchor = '';
    var hash = window.location.hash.substring(1);
    if(window.location.hash) {
        var anchors = getAnchors();
        if (anchors.length > 0 && !anchors.includes(hash)) {
            anchors.forEach(current_anchor => {
                if (similarity(hash, current_anchor) >= percent) {
                    percent = similarity(hash, current_anchor);
                    similar_anchor = current_anchor;
                }
            });
            // scroll to similar anchor
            document.location.hash = similar_anchor;
        }
    }
}

function getAnchors() {
    var links = document.querySelectorAll("a[href]");
    var anchors = [];
    for (let index = 0; index < links.length; index++) {
        if (links[index].href.includes("#") && links[index].href.split('#')[1] !== "" && links[index].href.split('#')[0] == self.location.href.split('#')[0] && !anchors.includes(links[index].href.split('#')[1])) {
            anchors.push(links[index].href.split('#')[1]);
        }
    }

    return anchors;
}

// From https://stackoverflow.com/a/36566052
function similarity(s1, s2) {
    var longer = s1;
    var shorter = s2;
    if (s1.length < s2.length) {
        longer = s2;
        shorter = s1;
    }
    var longerLength = longer.length;
    if (longerLength == 0) {
        return 1.0;
    }

    return (longerLength - editDistance(longer, shorter)) / parseFloat(longerLength);
}

// See https://stackoverflow.com/a/36566052
function editDistance(s1, s2) {;
    var costs = new Array();

    for (var i = 0; i <= s1.length; i++) {
        var lastValue = i;
        for (var j = 0; j <= s2.length; j++) {
            if (i == 0) {
                costs[j] = j;
            } else {
                if (j > 0) {
                    var newValue = costs[j - 1];
                    if (s1.charAt(i - 1) != s2.charAt(j - 1)) {
                        newValue = Math.min(Math.min(newValue, lastValue), costs[j]) + 1;
                    }
                    costs[j - 1] = lastValue;
                    lastValue = newValue;
                }
            }
        }
        if (i > 0) {
            costs[s2.length] = lastValue;
        }
    }

    return costs[s2.length];
}

$.fn.tiki_popover = function () {
    let $list;
    const $container = this;

    // To allow table elements etc in tips and popovers
    if ($.fn.tooltip.Constructor && typeof $.fn.tooltip.Constructor.Default.allowList === "object") {
        const myDefaultAllowList = $.fn.tooltip.Constructor.Default.allowList;

        myDefaultAllowList.table = [];
        myDefaultAllowList.thead = [];
        myDefaultAllowList.tbody = [];
        myDefaultAllowList.tr = [];
        myDefaultAllowList.th = [];
        myDefaultAllowList.td = [];
        myDefaultAllowList.form = ["action", "method"];
        myDefaultAllowList.input = ["name", "value", "type"];
        myDefaultAllowList.button = ["type", "disabled", "name", "value", "onclick"];
        myDefaultAllowList.time = ["datetime"];    // for timeago
        myDefaultAllowList.a = [
            "target",
            "href",
            "title",
            "rel",
            "data-tiki-bs-toggle",
            "data-bs-toggle",
            "data-bs-backdrop",
            "data-bs-target",
            "data-bs-size",
            "data-size",
            "onclick",
        ];    // data items for smarty_function_bootstrap_modal
    }

    /*
     * Prepare the data so all elements so the data is all in the right format for bootstrap popovers
     */
    let config = {};

    $list = $container.find('.tips[title!=""], .tikihelp[title!=""]');

    // FIXME temporary fix of https://github.com/twbs/bootstrap/issues/35020
    // $list.filter('.slow').attr('data-bs-delay', { "show": 1000, "hide": 500 });
    $list.filter('.slow').attr('data-bs-delay', 1000);

    $list.each(function () {
        const $element = $(this);

        if ($element.attr('title') && !$element.hasClass('tikihelp-prefs')) {
            $.each(['|', ':', '<br/>', '<br>'], function (key, sep) {
                const parts = $element.attr('title').split(sep);
                if (parts.length > 1) {
                    $element.attr('title', parts.shift());
                    // note setting the jQuery data object doesn't update the DOM, which is what bs5 needs now
                    $element.attr('data-bs-content', parts.join(sep));
                    // Aria-label
                    $element.attr('aria-label', parts.join(sep));
                    $element.attr('href', $element.attr('href') + '#' + encodeURI(parts.join(sep)));
                }
            });
        } else {
            $element.attr('title', '');
        }

        if (!$element.data('bs-trigger')) {
            $element.attr('data-bs-trigger', 'hover focus');
            config.trigger = 'hover focus';
            $element.attr("tabindex", 0);   // should make the pop
        }
        // default Tiki delay
        if (!$element.data('bs-delay')) {
            config.delay = {"show": 250, "hide": 500};
        }
    });

    $.merge($list, $container.find("a[data-bs-toggle=popover]:not(.tips[title!='']):not(.tikihelp[title!=''])"));

    $list.filter('.bottom').attr('data-bs-placement', 'bottom');
    $list.filter('.left').attr('data-bs-placement', 'left');
    $list.filter('.right').attr('data-bs-placement', 'right');

    $list.find('img').attr('title', ''); // Remove the img title to avoid browser tooltip
    $list.filter('[data-bs-trigger="click"]')
        .on("click", function (e) {
            e.preventDefault();
        });

    // FIXME temporary fix of https://github.com/twbs/bootstrap/issues/35020
    $list.filter('[data-bs-delay^="{"]').each(function () {
        config.delay = $(this).data('bs-delay');
    });

    // Handle common cases
    $list.popover(
        $.extend({
            container: 'body',
            html: true,
            boundary: "window",
            placement: $.tikiPopoverWhereToPlace
        }, config)
    );

    $container.find('.ajaxtips').each(function() {
        var me = $(this),
            trigger = me.data('bs-trigger') || 'hover focus';

        $(this).popover({
            trigger: trigger,
            html: true,
            //bsDelay: { "show": 500, "hide": 500 },
            delay: 500,
            placement: $.tikiPopoverWhereToPlace,
            boundary: "window",
            content: function (link) {
                let content = $(link).data('bs-content');

                if (! content) {
                    $.get($(link).data('ajaxtips'), function (data) {
                        content = data;

                        link.dataset.bsContent = content;
                        $(link).attr('data-bs-content', content);
                        bootstrap.Popover.getInstance(link).dispose();
                        bootstrap.Popover.getOrCreateInstance(link, {
                            trigger: trigger,
                            html: true,
                            delay: 500,
                            placement: $.tikiPopoverWhereToPlace,
                            boundary: "window"
                        });
                        bootstrap.Popover.getInstance(link).show();
                    });

                    // display a spinner while waiting for the ajax call to return the content
                    content = "<div class='text-center p-3'><i class='icon icon-spinner fas fa-spinner fa-spin' alt='Loading...'></i></div>";
                }

                return content;
            }
        });
    });

    // only have one popover showing at a time
    $document.on("show.bs.popover", function ( e ) {
        var event = e;
        $('.popover:visible:not(.tour-tour)').each(function () {
            if (this.previousElementSibling !== event.target) {
                $(this).popover('hide');
            }
        });
    });

    $document.on("hide.bs.popover", function ( e ) {
        var $popover = $('.popover:visible:not(.tour-tour)');

        // if mouse is over the popover
        if ($popover.find(":hover").length) {
            // change the leave event to be when leaving the popover
            $popover.on("mouseleave", function () {
                $(this).popover("hide");
            });
            // and cancel the hide event
            e.preventDefault();
            return false;
        }
    });

    return $container;
};

$.tikiPopoverWhereToPlace = function (pop, el) {
    var pxNum = function(str) {
            return (str || '').replace('px', '') * 1;
        },
        $win = $(window),
        $el = $(el),
        width = $el.offsetParent().width(),
        height = $el.offsetParent().height(),
        $pop = $(pop),
        allowedImgWidth = width * 0.60,
        allowedImgHeight = height * 0.60,
        manualImageWidth = $el.data('width'),
        leftPos = $el.offset().left,
        rightPos = leftPos + $el.outerWidth(),
        bottomPos = $el.offset().top + $el.outerHeight() - $win.scrollTop(),
        $img = $pop.find('div[style*="background-image"],img').first(),
        $imgContainer = $img.parent(),
        $imgPopover = $imgContainer.parent(),
        imgWidth = pxNum($img.css('width')),
        imgHeight = pxNum($img.css('height')),
        newImgWidth,
        newImgHeight,
        widthBuffer,
        heightBuffer;

    if ($el.data("bs-placement")) {
        return $el.data("bs-placement");    // element already has popover placement set
    }

    if (manualImageWidth) {
        $img.css({
            width: manualImageWidth + 'px'
        });

        $pop.css({
            "max-width" : '100%'
        });

        imgWidth = manualImageWidth;
    }

    //lets check the size of the popover img
    if (imgWidth > allowedImgWidth || imgHeight > allowedImgHeight) {
        widthBuffer = (pxNum($imgContainer.css('padding-left')) + pxNum($imgContainer.css('margin-left')) + pxNum($imgContainer.css('border-left-width'))) * 2;
        heightBuffer = (pxNum($imgContainer.css('padding-top')) + pxNum($imgContainer.css('margin-top')) + pxNum($imgContainer.css('border-top-width'))) * 2;

        // proportionate the image relative to what is allowed
        if(allowedImgWidth/imgWidth > allowedImgHeight/imgHeight){
            newImgWidth = allowedImgWidth;
            newImgHeight = imgHeight*(allowedImgWidth/imgWidth);
        } else {
            newImgWidth = imgWidth*(allowedImgHeight/imgHeight);
            newImgHeight = allowedImgHeight;
        }

        $img.css({
            backgroundSize: newImgWidth + 'px ' + newImgHeight + 'px',
            width: newImgWidth + 'px',
            height: newImgHeight + 'px'
        });

        $imgPopover.css({
            maxWidth: (newImgWidth + widthBuffer) + 'px',
            maxHeight: (newImgHeight + heightBuffer) +'px'
        });
    }


    var $popTemp = $("<div class='popover temp'><div class='popover-body'>" + $el.data("bs-content") + "</div></div>");
    $("body").append($popTemp);
    var popWidth = $popTemp.outerWidth(),
        popHeight = $popTemp.outerHeight();

    $popTemp.remove();

    if (width - leftPos < popWidth && width - rightPos < popWidth) {
        if (bottomPos > popHeight ||
            bottomPos + popHeight > $win.height()) {
            return 'top';
        } else {
            return 'bottom';
        }
    } else if (width - leftPos > popWidth) {
        return 'left';
    } else if (width - rightPos > popWidth) {
        return 'right';
    }
    if (imgWidth && width - leftPos + imgWidth > width) return 'bottom';

    return 'auto';
};

$(function() { // JQuery's DOM is ready event - before onload
    if (!window.jqueryTiki) window.jqueryTiki = {};

    // Reflections
    if (jqueryTiki.reflection) {
        $("img.reflect").reflect({});
    }

    if (jqueryTiki.tooltips) {
        $(document).tiki_popover();
    }

    // URL Fragment Guesser
    if (jqueryTiki.url_fragment_guesser) {
        scrollToSimilarAnchor();
    }

    $.fn.applyColorbox = function() {
        $(this).find("a[data-box*='box']").colorbox({
            rel: function(){
                return $(this).attr('data-box');
            },
            transition: "elastic",
            maxHeight:"95%",
            maxWidth:"95%",
            overlayClose: true,
            current: jqueryTiki.cboxCurrent
        });

        function pausePlaying(type)
        {
            $(type).each(function () {
                $(this).get(0).pause();
            });
        }

        $(this).find('.cboxInlineMedia').colorbox({
            inline: true,
            rel: 'lightbox',
            width: function () {
                return $(this).attr("data-box-width") ?? '60%';
            },
            height: function () {
                return $(this).attr("data-box-height") ?? '80%';
            },
            onClosed: function () {
                pausePlaying('video');
                pausePlaying('audio');
            }
        });

        $(this).on('click', '#cboxPrevious, #cboxNext', function () {
            pausePlaying('video');
            pausePlaying('audio');
        });

        // now, first let suppose that we want to display images in ColorBox by default:

        // this matches data-box attributes containing type=img or no type= specified
        $(this).find("a[data-box*='box'][data-box*='type=img'], a[data-box*='box'][data-box!='type=']:not([data-is-text])").colorbox({
            photo: true
        });

        // data-box attributes containing slideshow (this one must be without #col1)
        $(this).find("a[data-box*='box'][data-box*='slideshow']").colorbox({
            photo: true,
            slideshow: true,
            slideshowSpeed: 3500,
            preloading: false,
            width: "100%",
            height: "100%"
        });
        // this are the defaults matching all *box links which are not obviously links to images...
        // (if we need to support more, add here... otherwise it is possible to override with type=iframe in data-box attribute of a link)
        //  (from here one to speed it up, matches any link in #col1 only - the main content column)
        $(this).find("#col1 a[data-box*='box']:not([data-box*='type=img']):not([href*='display']):not([href*='preview']):not([href*='thumb']):not([data-box*='slideshow']):not([href*='image']):not([href$='\.jpg']):not([href$='\.jpeg']):not([href$='\.png']):not([href$='\.gif'])").colorbox({
            iframe: true,
            width: "95%",
            height: "95%"
        });
        // hrefs starting with ftp(s)
        $(this).find("#col1 a[data-box*='box'][href^='ftp://'], #col1 a[data-box*='box'][href^='ftps://']").colorbox({
            iframe: true,
            width: "95%",
            height: "95%"
        });
        // data-box attributes with type=iframe (if someone needs to override anything above)
        $(this).find("#col1 a[data-box*='box'][data-box*='type=iframe']").colorbox({
            iframe: true
        });
        // inline content: hrefs starting with #
        $(this).find("#col1 a[data-box*='box'][href^='#']").colorbox({
            inline: true,
            width: "50%",
            height: "50%",
            href: function(){
                return $(this).attr('href');
            }
        });

        // titles (for captions):

        // by default get title from the title attribute of the link (in all columns)
        $(this).find("a[data-box*='box'][title]").colorbox({
            title: function(){
                return $(this).attr('title');
            }
        });
        // but prefer the title from title attribute of a wrapped image if any (in all columns)
        $(this).find("a[data-box*='box'] img[title]").colorbox({
            title: function(){
                return $(this).attr('title');
            },
            photo: true,                // and if you take title from the image you need photo
            href: function(){            // and href as well (for colobox 1.3.6 tiki 5.0)
                return $(this).parent().attr("href");
            }
        });

        /* Shadowbox params compatibility extracted using regexp functions */
        var re, ret;
        // data-box attributes containing title param overrides title attribute of the link (shadowbox compatible)
        $(this).find("#col1 a[data-box*='box'][data-box*='title=']").colorbox({
            title: function () {
                re = /(title=([^;\"]+))/i;
                ret = $(this).attr("data-box").match(re);
                return ret[2];
            }
        });
        // data-box attributes containing height param (shadowbox compatible)
        $(this).find("#col1 a[data-box*='box'][data-box*='height=']").colorbox({
            height: function () {
                re = /(height=([^;\"]+))/i;
                ret = $(this).attr("data-box").match(re);
                return ret[2];
            }
        });
        // data-box attributes containing width param (shadowbox compatible)
        $(this).find("#col1 a[data-box*='box'][data-box*='width=']").colorbox({
            width: function () {
                re = /(width=([^;\"]+))/i;
                ret = $(this).attr("data-box").match(re);
                return ret[2];
            }
        });

        // links generated by the {COLORBOX} plugin
        if (jqueryTiki.colorbox) {
            $(this).find("a[data-box^='shadowbox[colorbox']").each(function () {$(this).attr('savedTitle', $(this).attr('title'));});
            $(this).find("a[data-box^='shadowbox[colorbox']").colorbox({
                title: function() {
                    return $(this).attr('savedTitle');    // this fix not required is colorbox was disabled
                }
            });
        }
    };

    $.applyColorbox = function() {
        if (jqueryTiki.colorbox) {
            $('body').applyColorbox();
        }
    };

    // ColorBox setup (Shadowbox, actually "<any>box" replacement)
    if (jqueryTiki.colorbox && !jqueryTiki.mobile) {
        $().on('cbox_complete', function(){
            $("#cboxTitle").wrapInner("<div></div>");
        });

        // Add the data-box attribute to the allowed Bootstrap sanitizer list so it doesn't get stripped off
        bootstrap.Tooltip.Default.allowList['*'].push('data-box');

        $.applyColorbox();
    }    // end if (jqueryTiki.colorbox)

    // Colorbox can be applied to Bootstrap popover elements
    $(document).on('shown.bs.popover', function () {
        $.applyColorbox();
    });

    if (jqueryTiki.zoom) {
        $("a[data-box*=zoom]").each(function () {
            $(this)
                .wrap('<span class="img_zoom"></span>')
                .parent()
                .zoom({
                    url: $(this).attr("href")
                });
        });
    }

    if (jqueryTiki.smartmenus) {
        // Init all menus
        var $navbars = $('ul.navbar-nav');
        var options = {
            noMouseOver: jqueryTiki.smartmenus_open_close_click,
            hideOnClick: jqueryTiki.smartmenus_open_close_click,
            collapsibleBehavior: jqueryTiki.smartmenus_collapsible_behavior,
            subIndicators: true,
            collapsibleShowFunction: function($ul, complete) { $ul.slideDown(200, complete); },
            collapsibleHideFunction: function($ul, complete) { $ul.slideUp(250, complete); }
        };
        $.SmartMenus.Bootstrap.init($navbars, options);
    }

    // Select2
    $.fn.applySelect2 = function () {
        if (jqueryTiki.select2) {
            $("select:not(.allow_single_deselect):not(.noselect2)").tiki("select2");
        }
    };

    $.applySelect2 = function() {
        return $('body').applySelect2();
    };

    if (jqueryTiki.select2) {
        $.applySelect2();
    }

    $( function() {
        $("#keepOpenCbx").on("click", function() {
            if (this.checked) {
                setCookie("fgalKeepOpen", "1");
            } else {
                setCookie("fgalKeepOpen", "");
            }
        });
        var keepopen = getCookie("fgalKeepOpen");
        $("#keepOpenCbx").prop("checked", !! keepopen);
    });
    // end fgal fns


    $.paginationHelper();

    // bind clickModal to links with or in click-modal class
    $(document).on('click', 'a.click-modal, .click-modal a', $.clickModal({
        size: 'modal-lg',
        backdrop: 'static',
        success: function (data) {
            let redirect = $(this).data('modal-submit-redirect-url') || $(this).parent().data('modal-submit-redirect-url');
            if (! redirect && data.url) {
                redirect = data.url;
            }
            let redirectPage = (redirect !== undefined) ? redirect.replace(/#.*/,'') : "";
            let currentPage = window.location.href.match(/[^\/]+$/, '')[0].replace(/#.*/,'');
            window.location.href = redirect || window.location.href.replace(/#.*$/, '');
            if (redirectPage == currentPage) {
                window.location.reload();
            }
        }
    }));

    if (jqueryTiki.numericFieldScroll === "none" || jqueryTiki.numericFieldScroll === null){
        // disable mousewheel on a input number field when in focus
        // (to prevent  browsers change the value when scrolling)
        $(document).on('focus', 'input[type=number]', function (e) {
            $(this).on('wheel.disableScroll', function (e) {
                e.preventDefault();
            });
        });
        $(document).on('blur', 'input[type=number]', function (e) {
            $(this).off('wheel.disableScroll');
        });
    }

    // File tags
    $(document).on('click', '[data-file-ref-id]', function () {
        const fileRefId = $(this).data('file-ref-id');
        const fileInTheDom = $(`[data-object="${fileRefId}"]`);
        const container = fileInTheDom.parents().first();
        const tabpanel = container.closest('[role="tabpanel"]');

        if (tabpanel.length) {
            const tab = $(`[href="#${tabpanel.attr('id')}"]`);
            tab.tab('show');
        }

        $('html, body').animate({
            scrollTop: fileInTheDom.offset().top - 200,
            class: 'animating'
        },
        {
            easing: 'linear',
            complete: function () {
                container.addClass('highlight');
                setTimeout(() => {
                    container.removeClass('highlight');
                }, 1000);
            }
        }, 100);
    });
});        // end $document.ready

//For ajax/custom search
$document.on('pageSearchReady', function() {
    $.paginationHelper();
});

// moved from tiki-list_file_gallery.tpl in tiki 6
function checkClose() {
    if (!$("#keepOpenCbx").prop("checked")) {
        window.close();
    } else {
        window.blur();
        if (window.opener) {
            window.opener.focus();
        }
    }
}


/*
 * JS only textarea fullscreen function (for Tiki 5+)
 */

$(function() {    // if in translation-diff-mode go fullscreen automatically
    if ($("#diff_outer").length && !$(".wikipreview .wikitext").html().trim().length) {    // but not if previewing (TODO better)
        toggleFullScreen("editwiki");
    }
});

function sideBySideDiff() {
    if ($('.side-by-side-fullscreen').length) {
        $('.side-by-side-fullscreen').remove();
        return;
    }

    var $diff = $('#diff_outer').remove(), $zone = $('.edit-zone');
    $zone.after($diff.addClass('side-by-side-fullscreen'));
    $diff.find('#diff_history').height('');
}

function toggleFullScreen(area_id) {

    if ($("input[name=wysiwyg]").val() === "y" && $("input[name=syntax]").val() !== 'markdown') {        // quick fix to disable side-by-side translation for ckeditor wysiwyg
        $("#diff_outer").attr('style', 'height: 400px !important').css({
            position: "inherit",
            overflowX: "auto"
        });
        return;
    }

    var textarea = $("#" + area_id);

    //codemirror interation and preservation
    var textareaEditor = syntaxHighlighter.get(textarea);
    if (textareaEditor) {
        syntaxHighlighter.fullscreen(textarea);
        sideBySideDiff();
        return;
    }

    var toolbar = $('#editwiki_toolbar'),
        preview = $("#autosave_preview"),
        comment = $("#comment").parents("fieldset").first(),
        screen = $('.TextArea-fullscreen'),
        zone = $('.edit-zone', screen);

    screen.add(textarea).css('height', '');

    //removes wiki command buttons (save, cancel, preview) from fullscreen view
    $('.TextArea-fullscreen .actions').remove();
    if (textarea.parent().hasClass("ui-wrapper")) {
        textarea.resizable("destroy");    // if codemirror is off, jquery-ui resizable messes this up
    }

    var textareaParent = textarea.parents(".tab-content").first().toggleClass('TextArea-fullscreen');

    if (textareaParent.hasClass('TextArea-fullscreen')) {
        $('body').css('overflow', 'hidden');
        $('.tabs,.rbox-title').toggle();
        $('#fullscreenbutton').hide();

        var win = $window
            .data('cm-resize', true),
            diff = $("#diff_outer"),
            msg = $(".translation_message"),
            actions = $('.actions', textarea.parents("form"));

        //adds wiki command buttons (save, cancel, preview) to fullscreen view
        actions.clone().appendTo('.TextArea-fullscreen');
        actions = $('.actions', $('.TextArea-fullscreen'));

        comment.css({   // fix comments fieldset to bottom and hide others (like contributions)
            position: "absolute",
            bottom: actions.outerHeight() + "px",
            width: "100%"
        }).nextAll("fieldset").hide();

        preview.css({
            position: "absolute",
            top: 0,
            left: 0
        });

        win.on("resize", function() {
            screen = $('.TextArea-fullscreen');
            actions = $('.actions', screen);
            comment = $("#comment").parents("fieldset").first();
            if (win.data('cm-resize') && screen) {
                screen.css('height', win.height() + 'px');
                var swidth = win.width() + "px";
                var commentMargin = parseInt(comment.css("paddingTop").replace("px", "")) * 4;
                commentMargin += parseInt(comment.css("borderBottomWidth").replace("px", "")) * 2;
                var innerHeight = win.height() - comment.outerHeight() - commentMargin - actions.outerHeight();
                         // reducing innerHeight by 85px in prev line makes the "Describe the change you made:" and
                         // "Monitor this page:" edit fields visible and usable. Tested in all 22 themes in Tiki-12 r.48429

                if (diff.length) {
                    swidth = (screen.width() / 2) + "px";
                    innerHeight -= msg.outerHeight();
                    msg.css("width", (screen.width() / 2 - msg.css("paddingLeft").replace("px", "") - msg.css("paddingRight").replace("px", "")) + "px");
                    diff.css({
                        width: swidth,
                        height: innerHeight + 'px'
                    });
                    $('#diff_history').height(innerHeight + "px");
                }
                textarea.css("width", swidth);
                toolbar.css('width', swidth);
                zone.css("width", swidth);
                preview.css("width", swidth);
                textarea.css('height', (innerHeight - toolbar.outerHeight()) + "px");
            }
        });
        setTimeout(function () {$window.trigger("resize");}, 500);    // some themes (coelesce) don't show scrollbars unless this is delayed a bit
    } else {
        textarea.css("width", "");
        toolbar.css('width', "");
        zone.css({ width: "", height: ""});
        screen.css("width", "");
        comment.css({ position: "", bottom: "", width: "" }).nextAll("fieldset").show();
        preview.css({ position: "", top: "", left: "" });
        $('body').css('overflow', '');
        $('.tabs,.rbox-title').toggle();
        $('#fullscreenbutton').show();
        $window.removeData('cm-resize');
    }

    sideBySideDiff();
}

/* Simple tiki plugin for jQuery
 * Helpers for autocomplete and sheet
 */
var xhrCache = {}, lastXhr;    // for jq-ui autocomplete

$.fn.tiki = function(func, type, options, excludepage) {
    var opts = {}, opt;

    //Get the current page name
    var urlParams = new URLSearchParams(window.location.search);
    excludepage = urlParams.get('page');

    switch (func) {
        case "autocomplete":
            if (jqueryTiki.autocomplete && jqueryTiki.ui) {
                if (typeof type === 'undefined') { // func and type given
                    // setup error - alert here?
                    return null;
                }
                options = options || {};
                var requestData = {}, _renderItem = null;
                var url = "";
                switch (type) {
                    case "pagename":
                        url = "tiki-listpages.php?listonly&initial=" + (options.initial ? options.initial + "&nonamespace" : "")+"&exclude_page="+excludepage;
                        _renderItem = function(ul, item) {
                            var listItem = $("<li>");
                            if (item.is_alias) {
                                listItem
                                    .append("<div>" + item.label + " <span class='icon icon-link fas fa-link'></span></div>")
                                    .css("color", "red");
                            } else {
                                if (item.label != excludepage) {
                                    listItem.append("<div>" + item.label + "</div>");
                                }
                            }

                            return listItem.appendTo(ul);
                        };
                        break;
                    case "groupname":
                        url = "tiki-ajax_services.php?listonly=groups";
                        break;
                    case "username":
                        url = "tiki-ajax_services.php?listonly=users";
                        break;
                    case "usersandcontacts":
                        url = "tiki-ajax_services.php?listonly=usersandcontacts";
                        break;
                    case "userrealname":
                        url = "tiki-ajax_services.php?listonly=userrealnames";
                        break;
                    case "tag":
                        url = "tiki-ajax_services.php?listonly=tags&separator=+";
                        break;
                    case "icon":
                        url = null;
                        opts.source = Object.keys(jqueryTiki.iconset.icons).concat(jqueryTiki.iconset.defaults);

                        _renderItem = function(ul, item) {
                            return $("<li>")
                                    .attr("data-value", item.value )
                                    .append($().getIcon(item.value))
                                    .append(" ")
                                    .append(item.label)
                                    .appendTo(ul);
                        };
                        break;
                    case 'trackername':
                        url = "tiki-ajax_services.php?listonly=trackername";
                        break;
                    case 'calendarname':
                        url = "tiki-ajax_services.php?listonly=calendarname";
                        break;
                    case 'trackervalue':
                        if (typeof options.fieldId === "undefined") {
                            // error
                            return null;
                        }
                        $.extend( requestData, options );
                        options = {};
                        url = "list-tracker_field_values_ajax.php";
                        break;
                    case "reference":
                        url = "tiki-ajax_services.php?listonly=references";
                        break;
                }
                var multiple = options.multiple && (type == 'usersandcontacts' || type == 'userrealname' || type == 'username' || type == 'reference');
                opts = $.extend({        //  default options for autocompletes in tiki
                    minLength: 2,
                    source: function( request, response ) {
                        if( multiple ) {
                            request.term = (''+request.term).split( /,\s*/ ).pop();
                        }
                        if (options.tiki_replace_term) {
                            request.term = options.tiki_replace_term.apply(null, [request.term]);
                        }
                        var cacheKey = "ac." + type + "." + request.term;
                        if ( cacheKey in xhrCache ) {
                            response( xhrCache[ cacheKey ] );
                            return;
                        }
                        request.q = request.term;
                        $.extend( request, requestData );
                        lastXhr = $.getJSON( url, request, function( data, status, xhr ) {
                            xhrCache[ cacheKey ] = data;
                            if ( xhr === lastXhr ) {
                                response( data, function (item) {
                                    return item;
                                });
                            }});
                    },
                    focus: function(ev) {
                        // Important for usability handling below to prevent non-valid selections
                        ev.preventDefault();
                    },
                    search: function() {
                        if( multiple ) {
                            // custom minLength
                            var term = (''+this.value).split( /,\s*/ ).pop();
                            if ( term.length < 2 ) {
                                return false;
                            }
                        }
                    },
                    select: function(e, ui) {
                        if (type == 'pagename' && ($(this).attr('name') == 'highlight' || /^search_mod_input_\d|highlight$/.test($(this).attr('id')))) {
                            var url = ui.item.label;
                            e.preventDefault();
                            var a = document.createElement('a');
                            a.href = url;
                            if (e.which == 2) {
                                a.setAttribute('target', '_blank');
                            }
                            a.trigger("click");

                            return false;
                        }

                        if( multiple ) {
                            var terms = ''+this.value;
                            terms = terms.replace(';', ',');
                            terms = terms.split( /,\s*/ );
                            // remove the current input
                            terms.pop();
                            // add the selected item
                            terms.push( ui.item.value );
                            // add placeholder to get the comma-and-space at the end
                            terms.push( "" );
                            this.value = terms.join( ", " );
                            return false;
                        } else {
                            $(this).data('selected', true);
                        }
                    },
                    open: function(e, ui) {
                        $(this).autocomplete('widget').css({
                            'width': ($(this).width() + 'px')
                        });
                    },
                }, opts);
                $.extend(opts, options);

                if(options.mustMatch && multiple) {
                    // Control editing of autocomplete to avoid messing with selection
                    this.on("keydown", function (e) {
                        if (e.which === 8 || e.which === 46) {
                            e.preventDefault();
                            var terms = ''+this.value;
                            terms = terms.replace(';', ',');
                            terms = terms.split( /,\s*/ );
                            // remove the current input and the last previous item
                            var lastterm = terms.pop();
                            if (lastterm === '') {
                                terms.pop();
                            }
                            // add placeholder to get the comma-and-space at the end
                            terms.push( "" );
                            this.value = terms.join( ", " );
                        } else if (e.which === 37 || e.which === 39) {
                            e.preventDefault();
                        }
                    });
                    this.on("focus click", function() {
                        var currentVal = $(this).val();
                        $(this).val('').val(currentVal);
                    });
                } else if (options.mustMatch) {
                    if ($(this).val()) {
                        // if there is value to begin then consider as selected
                        $(this).data('selected', true);
                    }
                    $(this).on("blur", function() {
                        if (! $(this).data('selected')) {
                            $(this).val('');
                        }
                    });
                    $(this).on("keydown", function(e) {
                        if ($(this).data('selected') && (e.which === 8 || e.which === 46)) {
                            e.preventDefault();
                            $(this).val('');
                            $(this).data('selected', false);
                        } else if ($(this).data('selected') && (e.which > 47 || e.which === 32)) {
                            e.preventDefault();
                        } else if (e.which === 13 && !$(this).data('selected')) {
                            e.preventDefault();
                        }
                    });
                }

                 return this.each(function() {
                    var $element = $(this).autocomplete(opts).on("blur", function() {
                        $(this).removeClass( "ui-autocomplete-loading").trigger("change");
                    });
                    if (_renderItem && $element.length) {
                        $element.autocomplete("instance")._renderItem = _renderItem;
                    }
                });
            }
            break;
        case "datepicker":
        case "datetimepicker":
            if (jqueryTiki.ui) {
                switch (type) {
                    case "jscalendar":    // replacements for jscalendar
                                        // timestamp result goes in the options.altField
                        if (typeof options.altField === "undefined") {
                            alert("jQuery.ui datepicker jscalendar replacement setup error: options.altField not set for " + $(this).attr("id"));
                            debugger;
                        }
                        opts = {
                            showOn: "both",
                            buttonText: "",
                            changeMonth: jqueryTiki.changeMonth,
                            changeYear: jqueryTiki.changeYear,
                            dateFormat: jqueryTiki.shortDateFormat,
                            timeFormat: jqueryTiki.shortTimeFormat,
                            showButtonPanel: true,
                            altFormat: "@",
                            altFieldTimeOnly: false,
                            onClose: function (dateText, inst) {
                                $.datepickerAdjustAltField(func, inst);
                            },
                            // We temporarily position the date picker on the modal with the function below
                            // due to current limitations, where the datepicker is appended directly to the body instead of the modal.
                            // Needs review for a more robust solution.
                            // @see [jQuery UI Datepicker](https://github.com/jquery/jquery-ui/blob/main/ui/widgets/datepicker.js#L2211)
                            beforeShow: function (input, inst) {
                                if ($(input).closest(".modal").length > 0) {
                                    var $picker = $(inst.dpDiv);
                                    var $input = $(input);
                                    var $modalBody = $input.closest(".modal");
                                    var updatePosition = function () {
                                        var inputOffset = $input.offset();
                                        var modalBodyOffset = $modalBody.offset();
                                        var inputHeight = $input.outerHeight();
                                        var left = inputOffset.left - modalBodyOffset.left;
                                        var top = inputOffset.top - modalBodyOffset.top + inputHeight;
                                        $picker.css({
                                            position: "absolute",
                                            left: left + "px",
                                            top: top + "px",
                                        });
                                    };
                                    updatePosition();
                                    $modalBody.on("scroll", updatePosition);
                                }
                            },
                        };
                        break;
                    default:
                        opts = {
                            showOn: "both",
                            buttonText: '',
                            dateFormat: jqueryTiki.shortDateFormat,
                            showButtonPanel: true,
                            firstDay: jqueryTiki.firstDayofWeek
                        };
                        break;
                }
                $.extend(opts, options);
                if (func === "datetimepicker") {
                    return this.each(function() {
                            $(this).datetimepicker(opts);
                        });
                } else {
                    return this.each(function() {
                        $(this).datepicker(opts);
                    });
                }
            }
            break;
        case "select2":
            if (jqueryTiki.select2) {
                var selects = this;

                opts = {
                    containerCssClass: 'select2-selection-tiki',
                    dropdownCssClass: 'select2-dropdown-tiki dropdown-animate',
                    theme: 'bootstrap-5',
                    dir: $('html').attr('dir') || 'ltr',
                    language: {
                        noResults: function () {
                            return tr('No results match');
                        }
                    },
                    templateSelection: function (obj) {
                        var $el = $(`<span class="select2-selection__choice__value" data-option-value="${obj.id}"></span>`);
                        $el.text(obj.text);
                        return $el;
                    },
                };
                // Adds and/or merge more options
                $.extend(opts, options);

                selects.each(function() {
                    let $select = $(this),
                        multiple = $select.prop('multiple'),
                        required = $select.prop('required'),
                        placeholder = $select.find("option[value='']").text().trim();

                    if (! placeholder) {
                        placeholder = multiple ? tr('Select Some Options') : tr('Select an Option');
                    }
                    // <select data-placeholder="some placeholder">...</select> overrides option placeholder
                    opts.allowClear = ! multiple && ! required;
                    opts.closeOnSelect = ! multiple;
                    opts.placeholder = placeholder;
                    opts.width = $select.parent().hasClass('input-group') ? 'resolve' : '100%';
                    // Fix Boostrap4 Modal scrolling - move dropdown menu to modal-body
                    opts.dropdownParent = $select.closest('.modal-body').length > 0 ? $select.closest('.modal-body') : $(document.body);

                    // Disables search option for single selects
                    if ($select.hasClass('select2-nosearch')) {
                        opts.minimumResultsForSearch = Infinity;
                    }
                    // Initialize select2 with created options
                    $select.select2(opts);

                    // Fix Boostrap5 modal jumping up while Select is opened
                    if ($select.closest('.modal-body').length > 0) {
                        $select
                            .on('select2:open', function () {
                                $select.closest('.modal').css('overflow', 'hidden');
                            });
                    }

                    // Delete searched text but leave selection open for multiple selects
                    if (multiple) {
                        $select.select2({
                            templateResult: function (data) {
                                if (data.element && data.element.selected) {
                                    return null;
                                }
                                return data.text;
                            }
                        });
                        $select.on('select2:select', function () {
                            var $searchfield = $(this).parent().find('.select2-search__field');
                            $searchfield.val("").trigger("focus");
                            $select.select2('open');
                        });
                    }

                    // Disables search option for multiple selects
                    if (multiple && $select.hasClass('select2-nosearch')) {
                        $select.on('select2:opening select2:closing', function (event) {
                            let $searchfield = $(this).parent().find('.select2-search__field');
                            $searchfield.prop('disabled', true);
                        });
                    }

                    if (jqueryTiki.select2_sortable && multiple) {
                        $select
                            .next('.select2-container')
                            .find('.select2-selection--multiple ul.select2-selection__rendered')
                            .parents("form:not(.customsearch_form)").first()
                            .on("submit", function () {
                                if (!$(this).data("select2-multi-submitted") && !$select.hasClass("noselect2")) {
                                    sortOptionsBySortableOrder();
                                    $select.trigger('change');
                                    $(this).data("select2-multi-submitted", true);
                                }
                            })
                        ;
                        Sortable.create($select.next('.select2-container').find('.select2-selection--multiple ul.select2-selection__rendered')[0], {
                            onUpdate: () => {
                                sortOptionsBySortableOrder();
                                console.log('onUpdate');
                            }
                        });
                    }

                    function sortOptionsBySortableOrder() {
                        $select
                            .next('.select2-container')
                            .find("li.select2-selection__choice:not(.select2-search--inline) .select2-selection__choice__display")
                            .each(function () {
                                const value = $(this).text().trim();
                                moveElementToEndOfParent($select.find(`option:contains("${value}")`));
                            });

                        function moveElementToEndOfParent($element) {
                            let parent = $element.parent();
                            $element.detach();
                            parent.append($element);
                        }
                    }
                }).on("select2:opening", function (e) {
                    // set the overflow of any parents to visible
                    $(this).parents().each(function () {
                        if (! $(this).is("body") && $(this).css("overflow-y") !== "visible") {
                            $(this)
                                .data("old-overflow", $(this).css("overflow"))
                                .css("overflow", "visible");
                        }
                    }).one("select2:closing", function () {
                        // and put it back after
                        $(this).parents().each(function () {
                            if ($(this).data("old-overflow")) {
                                $(this)
                                    .css("overflow", $(this).data("old-overflow"))
                                    .removeData("old-overflow");
                            }
                        });
                    });
                });
            }
            break;
        case "copy":
            /**
             * How to use:
             * 1. The target element (copy button) is present when the page is loaded
             *  - Provide a function to get the content to copy
             *      eg. copyBtn.tiki('copy', () => 'content to copy', successCallback, errorCallback)
             *  - The content to copy is available in the DOM
             *     eg. copyBtn.tiki('copy', null, successCallback, errorCallback)
             *      # Make sure the copy button points to the element with the content to copy via data-clipboard-target="selector"
             *      # This approach is useful when you want to manage the copying of multiple contents with a single type of copy button
             * 2. The target element (copy button) is added dynamically
             *     eg. $(document).tiki('copy', () => 'content to copy', successCallback, errorCallback, '.copy-btn')
             */
            return (getText, onSuccess, onError, selector) => {
                if (!this.length && !selector) return this;
                if (ClipboardJS.isSupported()) {
                    if (! selector && ! $(this).attr('id')) {
                        $(this).attr('id', 'copy-' + Math.random().toString(16).slice(2));
                    }
                    const thisElementSelector = selector || ($(this).attr('id') ? '#' + $(this).attr('id') : '.' + $(this).attr('class').trim().replace(/ /g, '.'));

                    const initializeClipboard = (selector) => {
                        const clipboard = new ClipboardJS(selector, {
                            text: getText
                        });
                        if (typeof onSuccess === "function") clipboard.on('success', onSuccess.bind(this));
                        if (typeof onError === "function") clipboard.on('error', onError.bind(this));
                    };

                    if (thisElementSelector.startsWith('#')) {
                        initializeClipboard(thisElementSelector);
                        return this;
                    }

                    $(document).find(thisElementSelector).each(function () {
                        if (typeof getText !== "function") {
                            getText = (trigger) => {
                                const dataClipboardTarget = $(trigger).data('clipboard-target');
                                return $(dataClipboardTarget).text();
                            };
                        }

                        const uniqueId = "copy-" + Math.random().toString(16).slice(2);
                        $(this).attr('id', uniqueId);
                        initializeClipboard(`#${uniqueId}`);
                    });
                }
                return this;
            };
    }    // end switch(func)
};

(function($) {
    $.datepickerAdjustAltField = function(func, inst) {
        $.datepicker._updateAlternate(inst);    // make sure the hidden field is up to date
        var val = $(inst.settings.altField).val(), timestamp;
        if (func === "datetimepicker") {
            val = val.substring(0, val.indexOf(" "));
            timestamp = parseInt(val / 1000, 10);
            if (!timestamp || isNaN(timestamp)) {
                $.datepicker._setDateFromField(inst);    // seems to need reminding when starting empty
                $.datepicker._updateAlternate(inst);
                val = $(inst.settings.altField).val();
                val = val.substring(0, val.indexOf(" "));
                timestamp = parseInt(val / 1000, 10);
            }
            if (timestamp && inst.settings && inst.settings.timepicker) {    // if it's a datetimepicker add on the time
                let date = new Date();
                date.setTime(timestamp * 1000);
                date.setHours(inst.settings.timepicker.hour, inst.settings.timepicker.minute);

                timestamp = date.getTime() / 1000;
            }
        } else {
            timestamp = parseInt(val / 1000, 10);
        }
        $(inst.settings.altField).val(timestamp ? timestamp : "").trigger("change");
    };

    // the jquery.ui _gotoToday function doesn't seem to work any more, so override that and add a call to _setDate
    $.datepicker._jquibase_gotoToday = $.datepicker._gotoToday;
    $.datepicker._gotoToday = function (id) {
        var inst = this._getInst($(id)[0]);
        this._jquibase_gotoToday(id);
        this._setDate(inst, new Date());
        // the alternate field gets updated when the dialog closes
    };


    /**
     * Adds annotations to the content of text in ''container'' based on the
     * content found in selected dts.
     *
     * Used in comments.tpl
     */
    $.fn.addnotes = function( container ) {
        return this.each(function(){
            var comment = this;
            var text = $('dt:contains("note")', comment).next('dd').text();
            var title = $('h4', comment).first().clone();
            var body = $('.comment-body', comment).first().clone();
            body.find('dt:contains("note")').closest('dl').remove().addClass('card');

            if( text.length > 0 ) {
                var parents = container.find(':contains("' + text + '")').parent();
                var node = container.find(':contains("' + text + '")').not(parents)
                    .addClass('note-editor-text alert-info')
                    .each( function() {
                        var child = $('dl.note-list',this);
                        if( ! child.length ) {
                            child = $('<dl class="note-list list-group-item-info"/>')
                                .appendTo(this)
                                .hide();

                            $(this).on("click", function() {
                                child.toggle();
                            } );
                        }

                        child.append( title )
                            .append( $('<dd/>').append(body) );
                    } );
            }
        });
    };

    /**
     * Convert a zone to a note editor by attaching handlers on mouse events.
     */
    $.fn.noteeditor = function (link) {
        const annotate = $(link);
        const url = $.service('comment', 'post', {type: 'wiki page', objectId: $('title').text().split('|')[0].trim(), title: tr('Post a comment')});

        annotate.attr('href', url);
        annotate.appendTo(document.body);

        annotate.clickModal({
            open: function () {
                const annotation = annotate.attr('annotation');

                let msg = "";
                if (annotation.length < 20) {
                    msg = `<div class="alert alert-warning" role="alert">${tr("The text you have selected is quite short. Select a longer piece to ensure the note is associated with the correct text.")}</div>`;
                }

                msg += `<div class="alert alert-info" role="alert">
                <strong>${tr("Tip")}:</strong> ${tr("Leave the first line as it is, starting with")} <strong>;note:</strong> </br>
                ${tr("This is required.")}</div>`;

                msg = "<p class='description comment-info'>" + msg + "</p>";
                $('.modal-body', this).prepend($(msg));

                $('textarea', this)
                .val(';note:' + annotation + "\n\n");
            },
            success: function () {
                $.closeModal();
                $.notify(tr("Comment posted successfully"));
            }
        });

        $(this).on("mouseup", function( e ) {
            var range;
            if( window.getSelection && window.getSelection().rangeCount ) {
                range = window.getSelection().getRangeAt(0);
            } else if( window.selection ) {
                range = window.selection.getRangeAt(0);
            }

            if( range ) {
                var str = range.toString().trim();

                if( str.length && -1 === str.indexOf( "\n" ) ) {
                    annotate.attr('annotation', str);
                    annotate.fadeIn(100).position( {
                        of: e,
                        at: 'bottom left',
                        my: 'top left',
                        offset: '20 20'
                    } );
                } else {
                    if (annotate.css("display") != "none") {
                        annotate.fadeOut(100);
                    }
                    if ($("form.comments").css("display") == "none") {
                        $("form.comments").show();
                    }
                }
            }
        });
    };

    $(document).ready(function() {
        const $top = $('#top');
        $('.note-list', $top).remove();
        if (jqueryTiki.useInlineComment && ! jqueryTiki.useInlineAnnotations) {
            $('.comment.inline dt:contains("note")', this)
                .closest('.comment')
                .addnotes($top);

            $top.noteeditor('#note-editor-comment');
        }
    });

    $.fn.browse_tree = function () {
        this.each(function () {
            $('.treenode:not(.done)', this)
                .addClass('done')
                .each(function () {
                    if (getCookie($('ul', this).first().attr('data-id'), $('ul', this).first().attr('data-prefix')) !== 'o') {
                        $('ul', this).first().css('display', 'none');
                    }
                    var $placeholder = $('span.ui-icon:not(.control)', this).first();
                    if ($('ul', this).first().length) {
                        var dir = $('ul', this).first().css('display') === 'block' ? 's' : 'e';
                        if ($placeholder.length) {
                            $placeholder.replaceWith('<span class="flipper ui-icon ui-icon-triangle-1-' + dir + '" style="float: left;margin-top:.2em;"/>');
                        } else {
                            $(this).prepend('<span class="flipper ui-icon ui-icon-triangle-1-' + dir + '" style="float: left;margin-top:.2em;"/>');
                        }
                    } else {
                        if ($placeholder.length) {
                            $placeholder.replaceWith('<span style="float:left;width:16px;height:16px;margin-top:.2em;"/>');
                        } else {
                            $(this).prepend('<span style="float:left;width:16px;height:16px;margin-top:.2em;"/>');
                        }
                    }
                    if ($('div.checkbox', this).length) {
                        $('div.checkbox', this).css("margin-left", "16px");
                    }
                });
            $('.flipper:not(.done)')
                .addClass('done')
                .css('cursor', 'pointer')
                .on("click", function () {
                    var body = $(this).parent().find('ul').first();
                    var category_li = body.parent().parent().find('> li');
                    for (var category_li_counter = 0; category_li_counter < category_li.length; category_li_counter++) {
                        if (category_li[category_li_counter] === $(this).parent()[0]) {
                            if ('block' === body.css('display')) {
                                $(this).removeClass('ui-icon-triangle-1-s').addClass('ui-icon-triangle-1-e');
                                body.slideUp('fast');
                                setCookie(body.data("id"), "", body.data("prefix"));
                            } else {
                                $(this).removeClass('ui-icon-triangle-1-e').addClass('ui-icon-triangle-1-s');
                                body.slideDown('fast');
                                setCookie(body.data("id"), "o", body.data("prefix"));
                            }
                        } else {
                            category_li.eq(category_li_counter).find('span').first().removeClass('ui-icon-triangle-1-s').addClass('ui-icon-triangle-1-e');
                            category_li.find('ul').first().eq(category_li_counter).hide('fast');
                            setCookie(category_li.find('ul').first().eq(category_li_counter).data("id"), "", category_li.find('ul').first().eq(category_li_counter).data("prefix"));
                        }
                    }
                });
        });

        return this;
    };

    $.initTrees = function () {
        $(".tree.root:not(.init)").browse_tree().addClass("init");
    };
    setTimeout($.initTrees, 100);

    var fancy_filter_create_token = function(value, label) {
        var close, token;
        console.log();

        close = $('<span class="ui-icon ui-icon-close"/>')
            .on("click", function () {
                var ed = $(this).parent().parent();
                $(this).parent().remove();
                ed.trigger("change");
                return false;
            });

        token = $('<span class="token"/>')
            .attr('data-value', value)
            .text(label)
            .attr('contenteditable', false)
            .disableSelection()
            .append(close);

        return token[0];
    };

    var fancy_filter_build_init = function(editable, str, options) {
        if (str === '') {
            str = '&nbsp;';
        }

        editable.html(str.replace(/(\d+)/g, '<span>$1</span>'));

        if (options && options.map) {
            editable.find('span').each(function () {
                var val = $(this).text();
                $(this).replaceWith(fancy_filter_create_token(val, JSON.parse(options.map)[val] ? JSON.parse(options.map)[val] : val));
            });
        }
    };

    $.fn.fancy_filter = function (operation, options) {
        this.each(function () {
            switch (operation) {
            case 'init':
                var editable = $('<div class="fancyfilter form-control"/>'), input = this;

                if (editable[0].contentEditable !== null) {
                    fancy_filter_build_init(editable, $(this).val(), options);
                    editable.attr('contenteditable', true);
                    $(this).after(editable).hide();
                }

                editable
                    .on("keyup", function() {
                        $(this).trigger("change");
                        $(this).trigger("mouseup");
                    })
                    .on("change", function () {
                        $(input).val($('<span/>')
                            .html(editable.html())
                            .find('span').each(function() {
                                $(this).replaceWith(' ' + $(this).attr('data-value') + ' ');
                            })
                            .end().text().replace(/\s+/g, ' '));
                    })
                    .on("mouseup", function () {
                        input.lastRange = window.getSelection().getRangeAt(0);
                    });

                break;
            case 'add':
                var editable = $(this).next();
                editable.find("span[data-value='"+ options.token +"']").remove();
                var node = fancy_filter_create_token(options.token, options.label);
                editable.append(node);
                editable.trigger("change");
                break;
            }
        });

        return this;
    };

    $.fn.drawGraph = function () {
        this.each(function () {
            var $this = $(this);
            var width = $this.width();
            var height = $this.height() ? $this.height() : Math.ceil( width * 9 / 16 );
            var nodes = $this.data('graph-nodes');
            var edges = $this.data('graph-edges');

            var g = new Graph;
            $.each(nodes, function (k, i) {
                g.addNode(i);
            });
            $.each(edges, function (k, i) {
                var style = { directed: true };
                if( i.preserve ) {
                    style.color = 'red';
                }
                g.addEdge( i.from, i.to, style );
            });

            var layouter = new Graph.Layout.Spring(g);
            layouter.layout();

            var renderer = new Graph.Renderer.Raphael($this.attr('id'), g, width, height );
            renderer.draw();
        });

        return this;
    };

    /**
     * Handle textarea and input text selections
     * Code from:
     *
     * jQuery Autocomplete plugin 1.1
     * Copyright (c) 2009 Jörn Zaefferer
     *
     * Dual licensed under the MIT and GPL licenses:
     *   http://www.opensource.org/licenses/mit-license.php
     *   http://www.gnu.org/licenses/gpl.html
     *
     * Now deprecated and replaced in Tiki 7 by jquery-ui autocomplete
     */
    $.fn.selection = function (start, end) {
        if (start !== undefined) {
            if (end === undefined) {
                end = start;
            }
            return this.each(function () {
                if (this) {
                    this.focus();
                    if (this.setSelectionRange) {
                        this.focus();

                        // scroll to the selected text - from https://stackoverflow.com/a/53082182
                        const fullText = this.value;
                        this.value = fullText.substring(0, end);
                        const scrollHeight = this.scrollHeight;
                        this.scrollTop = this.scrollHeight;
                        this.value = fullText;
                        let scrollTop = scrollHeight;
                        const textareaHeight = this.clientHeight;
                        if (scrollTop > textareaHeight) {
                            // scroll selection to center of textarea
                            scrollTop -= textareaHeight / 2;
                        } else {
                            scrollTop = 0;
                        }
                        this.scrollTop = scrollTop;

                        this.setSelectionRange(start, end);
                    } else if (this.selectionStart) {
                        this.selectionStart = start;
                        this.selectionEnd = end;
                    } else if (this.createTextRange ) {
                        var selRange = this.createTextRange();
                        if (start == end) {
                            selRange.move("character", start);
                            selRange.select();
                        } else {
                            selRange.collapse(true);
                            selRange.moveStart("character", start);
                            selRange.moveEnd("character", end - start);    // moveEnd is relative
                            selRange.select();
                        }
                    }
                }
            });
        }
        var field = this[0];
        if (field?.selectionStart !== undefined) {
            return {
                start: field.selectionStart,
                end: field.selectionEnd
            };
        } else if (field?.createTextRange ) {
            // from http://the-stickman.com/web-development/javascript/finding-selection-cursor-position-in-a-textarea-in-internet-explorer/
            // The current selection
            var range = document.selection.createRange();
            // We'll use this as a 'dummy'
            var stored_range = range.duplicate();
            // Select all text
            stored_range.moveToElementText(field);
            // Now move 'dummy' end point to end point of original range
            stored_range.setEndPoint('EndToEnd', range);
            // Now we can calculate start and end points
            var textProperty = range.htmlText ? "htmlText" : "text";    // behaviour changed in IE10 (approx) so htmlText has unix line-ends which works (not 100% sure why)
            var selectionStart = stored_range[textProperty].length - range[textProperty].length;
            var selectionEnd = selectionStart + range[textProperty].length;
            return {
                start: selectionStart,
                end: selectionEnd
            };

        }
    };

    $.fn.comment_toggle = function () {
        this.each(function () {
            var $target = $(this.hash);
            $target.hide();

            $(this).on("click", function () {
                if ($target.is(':visible')) {
                    $target.hide(function () {
                        $(this).empty();
                    });
                } else {
                    $target.comment_load($(this).attr('href'));
                }

                return false;
            });
            if (location.search.indexOf("comzone=show") > -1 || location.hash.match(/threadId=?(\d+)/)) {
                var comButton = this;
                setTimeout(function() {
                    $(comButton).trigger("click");
                }, 500);
            }
        });

        return this;
    };

    $.fn.comment_load = function (url) {
        var $top = $('#top');
        $('.note-list', $top).remove();

        this.each(function () {
            var comment_container = this;
            if (! comment_container.reload) {
                comment_container.reload = function () {
                    $(comment_container).empty().comment_load(url);
                };
            }
            $(this).addClass('comment-container');
            $(this).load(url, function (response, status) {
                $(this).show();
                $('.button.comment-form.autoshow a').addClass('autoshown').trigger("click").removeClass('autoshown'); // allow autoshowing of comment forms through autoshow css class

                var match = location.hash.match(/threadId=?(\d+)/);
                if (match) {
                    var $tab = $(this).parents(".tab-pane"),
                        $tabContent = $(this).parents(".tab-content");

                    // if we're in an inactive tab then show the right one for this comment threadId
                    if ($tab.length && ! $tab.is(".active")) {
                        $tabContent.find(".tab-pane").each(function (index) {
                            if (this === $tab[0]) {
                                $(".nav-tabs li:nth(" + index + ") a", $tabContent.parent()).tab("show");
                            }
                        });
                    }
                    var $comment = $(".comment[data-comment-thread-id=" + match[1] + "]");
                    var top = $comment.offset().top;
                    $('html, body').animate({
                        scrollTop: top
                    },{
                    duration: 2000,
                    complete: function() {
                            $comment.addClass("comment-highlight");
                        }
                    });

                }
            });
        });

        return this;
    };

    $(document).on('click', '.comment-form.buttons > a.btn', function () {
        var comment_container = $(this).closest('.comment-container, .ui-dialog-content')[0];

        $('.comment-form form:not(.commentRatingForm)', comment_container).each(function() {        // remove other forms apart from the ratings vote form
            var $p = $(this).parent();
            $p.empty().addClass('button').addClass('buttons').append($p.data('previous'));
        });
        if (!$(this).hasClass('autoshown')) {
            $(".comment").each(function () {
                $("article > *:not(ol)", this).each(function () {
                    $(this).css("opacity", 0.6);
                });
            });
        }
        $(this).parents('.comment').first().find("*").css("opacity", 1);

        var $formContainer = null;
        if ($(this).data('target')) {
            $formContainer = $($(this).data('target'));
        } else {
            $formContainer = $(this).parents('.buttons');
        }
        $(this).parents('.buttons').data('previous', $(this).siblings().addBack()).empty().removeClass('buttons').removeClass('button');

        // Update buttons if loaded as a modal
        $('.modal.fade.show').trigger('tiki.modal.redraw');

        $formContainer.load($(this).attr('href'), function () {
            var form = $('form', this).on("submit", function () {
                var errors, current = this;
                $(current).tikiModal(tr("Saving..."));
                //Synchronize textarea and codemirror before comment is posted
                if (typeof syntaxHighlighter.sync === 'function') {
                    syntaxHighlighter.sync($(current).find("textarea.wikiedit"));
                }
                $.post($(current).attr('action'), $(current).serialize(), function (data, st) {
                    $(current).tikiModal();
                    if (data.threadId) {
                        let threadId = data.threadId;
                        $(current).closest('.comment-container').reload();
                        $('span.count_comments').each(function () {

                            var action = $(current).attr('action').match(/action=(\w*)/),
                                count = parseInt($(this).text());

                            if (action) {
                                //noinspection FallThroughInSwitchStatementJS
                                switch (action[1]) {
                                    case "remove":
                                        count--;
                                        break;
                                    case "post":
                                        count++;
                                        // fall through to adjust threadId if necessary
                                    case "edit":
                                    case "moderate":
                                        location.hash = location.hash.replace(/threadId=\d+/, "threadId=" + threadId);
                                        break;
                                }
                                $(this).text(count);
                            }
                        });
                        if (data.feedback && data.feedback[0]) {
                            alert(data.feedback.join("\n"));
                        }
                    } else {
                        errors = $('ul.alert-warning', form).empty();
                        if (!errors.length) {
                            $(':submit', current).after(errors = $('<ul class="alert-warning"/>'));
                        }

                        $.each(data.errors, function (k, v) {
                            errors.append($('<li/>').text(v));
                        });
                    }
                }, 'json');
                return false;
            });

            //allow syntax highlighting
            if ($.fn.flexibleSyntaxHighlighter) {
                window.codeMirrorEditor = [];
                form.find('textarea.wikiedit').flexibleSyntaxHighlighter();
            }
            $(document).trigger("tiki.ajax.redraw");
        });
        return false;
    });

    // scroll to post if #threadId on url in forums
    if ($("body.tiki_forums").length) {
        let match = location.hash.match(/threadId=?(\d+)/);
        if (match) {
            let $comment = $("#" + match[0].replace("=", "") + ".post");
            let top = $comment.offset().top;
            $('html, body').animate({
                scrollTop: top
            }, 2000, function () {
                $comment.animate({
                    backgroundColor: "#ff8"
                }, 250, function () {
                    $comment.animate({
                        backgroundColor: ""
                    }, 1000);
                });
            });
        }
    }

    $.fn.input_csv = function (operation, separator, value) {
        this.each(function () {
            var values = $(this).val().split(separator);
            if (values[0] === '') {
                values.shift();
            }

            if (operation === 'add' && -1 === values.indexOf("" + value)) {
                values.push(value);
            } else if (operation === 'delete') {
                value = String(value);
                while (-1 !== $.inArray(value, values)) {
                    values.splice($.inArray(value, values), 1);
                }
            }

            $(this).val(values.join(separator));
        });

        return this;
    };

    $.service = function (controller, action, query) {
        var append = '';

        if (query) {
            append = '?' + $.buildParams(query);
        }

        if (action) {
            return 'tiki-' + controller + '-' + action + append;
        } else {
            return 'tiki-' + controller + '-x' + append;
        }
    };

    $.serviceUrl = function (options) {
        var o = $.extend({}, options), controller = options.controller, action = options.action;
        delete(o.controller);
        delete(o.action);
        return $.service(controller, action, o);
    };

    $.buildParams = function (query, prefix, suffix) {
        prefix = prefix || '';
        suffix = suffix || '';

        if (typeof query == 'string') {
            return query;
        }

        return $.map(query, function (v, k) {
            if ($.isPlainObject(v)) {
                return $.buildParams(v, k + '[', ']');
            } else {
                return prefix + k + suffix + '=' + encodeURIComponent(v);
            }
        }).join('&');
    };

    $.fn.loadService =  function (data, options) {
        var $dialog = this, controller = options.controller, action = options.action, url;

        this.each(function () {
            if (! this.reload) {
                this.reload = function () {
                    $(this).loadService(data, options);
                };
            }
        });

        if (typeof data === "string") {
            data = parseQuery(data);
        }
        if (data && data.controller) {
            controller = data.controller;
        }

        if (data && data.action) {
            action = data.action;
        }

        if (options.origin && $(options.origin).is('a')) {
            url = $(options.origin).attr('href');
        } else if (options.url) {
            url = options.url;
        } else {
            url = $.service(controller, action);
        }

        $dialog.tikiModal(tr("Loading..."));

        $.ajax(url, {
            data: data,
            error: function (jqxhr) {
                $dialog.html(jqxhr.responseText);
            },
            success: function (data) {
                $dialog.html(data);
                $dialog.find('.ajax').on("click", function (e) {
                    $dialog.loadService(null, {origin: this});
                    return false;
                });
                $dialog.find('.service-dialog').on("click", function (e) {
                    $.closeModal();
                    return true;
                });

                $dialog.find('form .submit').hide();

                $dialog.find('form:not(.no-ajax)').off("submit").on("submit", ajaxSubmitEventHandler(function (data) {
                    data = (data ? data : {});

                    if (data.FORWARD) {
                        $dialog.loadService(data.FORWARD, options);
                    } else {
                        $.closeModal();
                    }

                    if (options.success) {
                        options.success.apply(options.origin, [data]);
                    }
                }));

                if (options.load) {
                    options.load.apply($dialog[0], [data]);
                }

                $('.confirm-prompt', this).requireConfirm({
                    success: function (data) {
                        if (data.FORWARD) {
                            $dialog.loadService(data.FORWARD, options);
                        } else {
                            $dialog.loadService(options.data, options);
                        }
                    }
                });
            },
            complete: function () {
                $dialog.tikiModal();
            }
        });
    };

    $.fn.confirmationDialog = function (options) {
        const modal = $('.footer-modal:not(.show)').first(),
            modalFooterConfirm = $('<button type="button" class="btn btn-success">' + tr('Confirm') + '</button>'),
            modalFooterClose = $('<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' + tr('Close') + '</button>');

        let modalHeader = modal.find('.modal-header'),
            modalBody = modal.find('.modal-body'),
            modalFooter = modal.find('.modal-footer');

        if (modalHeader.length === 0) {
            modal.find('.modal-content').append('<div class="modal-header"><h4></h4></div>');
            modalHeader = modal.find('.modal-header');
        }
        if (modalBody.length === 0) {
            modalBody = $('<div class="modal-body"><p></p></div>');
            modalHeader.after(modalBody);
        }
        if (modalFooter.length === 0) {
            modalFooter = $('<div class="modal-footer"></div>');
            modalBody.after(modalFooter);
        }

        if (options.title) {
            modalHeader.find('h4').text(options.title);
        } else {
            modalHeader.find('h4').text(tr('Confirmation Modal'));
        }

        let bodyPara = modalBody.find('p');
        if (bodyPara.length === 0) {
            bodyPara = modalBody.append('<p>');
        }
        if (options.message) {
            bodyPara.text(options.message);
        } else {
            bodyPara.text(tr('Please confirm you want to perform this action.'));
        }

        modalFooter.empty().append(modalFooterClose, modalFooterConfirm);

        modal.on('hidden.bs.modal', function (e) {
            if (options.close) {
                options.close();
            }
        });

        modalFooterConfirm.on('click', function (e) {
            modal.modal('hide');
            if (options.success) {
                options.success();
            }
        });

        modal.modal('show');

        return this;
    };

    $.fn.requireConfirm = function (options) {
        this.on("click", function (e) {
            e.preventDefault();
            $(this).doConfirm(options);
            return false;
        });

        return this;
    };

    $.fn.doConfirm = function (options) {
        var message = options.message, link = this;

        if (! message) {
            message = $(this).data('confirm');
        }

        if (confirm (message)) {
            var $this = $(this);
            $this.tikiModal(" ");

            $.ajax($(this).attr('href'), {
                type: 'POST',
                dataType: 'json',
                data: {
                    'confirm': 1
                },
                success: function (data) {
                    $this.tikiModal();
                    options.success.apply(link, [data]);
                },
                error: function (jqxhr) {
                    $this.tikiModal();
                    $(link).closest('form').showError(jqxhr);
                }
            });
        }
    };

    $.fn.showError = function (message) {
        if (message.responseText) {
            if (message.getResponseHeader("Content-Type").indexOf("text/html") === -1) {
                var data = JSON.parse(message.responseText);
                message = data.message;
            } else {
                message = $(message.responseText).text();    // can be html
            }
        } else if (typeof message !== 'string') {
            message = "";
        }
        this.each(function () {
            var parts, that = this;
            if (parts = message.match(/^<!--field\[([^\]]+)\]-->(.*)$/)) {
                field = parts[1];
                message = parts[2];

                if (that[field]) {
                    that = that[field];
                }
            }

            var validate = false, errors = {}, field, $additional = $('<ul>');

            if (jqueryTiki.validate) {
                validate = $(that).closest('form').validate();
            }

            if (validate) {
                if (! $(that).attr('name')) {
                    $(that).attr('name', $(that).attr('id'));
                }

                if (that !== validate.currentForm) {
                    field = $(that).attr('name');
                }

                if (field) {
                    errors[field] = message;
                    validate.showErrors(errors);
                } else {
                    // No specific field, assign as form error
                    $additional.append($('<li>').text(message));
                }

                setTimeout(function () {
                    $('#tikifeedback li').filter(function () {
                        return $(this).text() === message;
                    }).remove();

                    if ($('#tikifeedback ul').is(':empty')) {
                        $('#tikifeedback').empty();
                    }
                }, 100);
            } else {
                $additional.append($('<li>').text(message));
            }

            if (! $additional.is(':empty')) {
                // Write form errors at the top, please stop removing them
                $('.ajax-errors', this).remove();
                $('<div class="ajax-errors alert alert-danger alert-dismissable"><button type="button" class="close" data-bs-dismiss="alert" aria-hidden="true">&times;</button></div>')
                    .prependTo(this)
                    .append($additional);
            }

            // Style the bootstrap form-group as an error
            $('.tracker-field-group').removeClass('has-error')
                .find('label.error:visible')
                .addClass('form-text')
                .prepend('<span class="fas fa-flag"/> ')
                .closest('.tracker-field-group').addClass('has-error');
        });

        return this;
    };

    $.fn.clearError = function () {
        this.each(function () {
            $(this).closest('form').find('label.error[for="' + $(this).attr('name') + '"]').remove();
            $(this).closest('form').find('.tracker-field-group.has-error').removeClass('has-error');
        });

        return this;
    };

    // sort result containing all galleries
    function sortResult(result) {
        result.sort(function(a, b) {
            if (a.parent_title) {
                var titleA = a.parent_title.toUpperCase(); // ignore upper and lowercase
                var titleB = b.parent_title.toUpperCase(); // ignore upper and lowercase
                if (titleA < titleB) {
                    return -1;
                }
                if (titleA > titleB) {
                    return 1;
                }
            }

            // names must be equal
            return 0;
        });

    }

    // search the title of each parent gallery
    function parentTitle(result, parent) {
        var title='';
        $.each(result, function(key, value) {
            if(value.object_id === parent){
                title= value.title + ' > ';
            }
        });
        return title;
    }

    function loadSelectorData(filter, args, success) {
        if (! $.object_selector_cache) {
            $.object_selector_cache = {};
        }

        if (typeof args === "function") {
            success = args;
            args = {};
        }

        var item, url;

        url = $.service('search', 'lookup', $.extend(args, {
            filter: filter
        }));

        if (item = $.object_selector_cache[url]) {
            if (item.data) {
                success(item.data);
            } else {
                item.queue.push(success);
            }
        } else {
            item = $.object_selector_cache[url] = {
                data: null,
                queue: [success]
            };
            $.getJSON(url, function (data) {
                item.data = data;
                $.each(item.queue, function (k, f) {
                    f(data);
                });
                item.queue = [];
            });
        }
    }
    $._object_selector_add_item = function (type, $select, $results, parent_title, item, title, status_icon, selected) {
        var checkname = $select.closest('.object-selector, .object-selector-multi')
            .find('.primary').attr('id') + '_sel';
        var suffix = $results.find('.form-check').lenght || 0;

        $('<option>')
            .val(item)
            .data('label', title)
            .text((typeof parent_title === 'undefined' || parent_title === null ) ? title.replace(/\n/g, " / ") : parent_title + '' + title.replace(/\n/g, " / "))  //replace newline with a slash since it's in a select
            .prop('selected', selected)
            .appendTo($select);

        $('<div class="form-check"><input type="' + type + '" class="form-check-input" ><label class="form-check-label"></label></div>')
            .find('label').append(status_icon ? status_icon + ' ' + title : title).end()
            .find(':radio, :checkbox')
                .attr('name', checkname)
                .prop('checked', selected)
                .val(item)
            .end()
            .appendTo($results);
    };

    $.fn._object_selector_update_results = function (type, result, initial) {
        var $container = this,
            $results = $container.find('.results'),
            $select = $container.find('select'),
            $noresults = $('.no-result', this),
            selection = [];

        this.find(':radio:checked, :checkbox:checked')
            .not('.protected')
            .each(function () {
                selection.push($(this).val());
            });

        this.find(':radio:not(:checked), :checkbox:not(:checked)')
            .not('.protected')
            .closest('.form-check')
            .remove();

        $select
            .find('option')
            .not('.protected')
            .remove();

        $noresults.toggleClass('d-none', selection.length !== 0);

        // add all galleries parent titles
        $.each(result, function (key, value) {
            if (value.parent_id) {
                value.parent_title = parentTitle(result, value.parent_id);
            }
            result[key] = value;
        });

        // sort result by galleries parent titles
        sortResult(result);

        $.each(result, function (key, value) {
            var current = value.object_type + ':' + value.object_id;
            var selected = false;

            if (value.object_id === '') {
                current = value.object_type;
            }

            var currentValue = $select.val();
            if (currentValue === current) {
                selected = true;
            }

            if (selection.indexOf(current) === -1) {
                if (initial) {
                    $._object_selector_add_item(type, $select, $([]), value.parent_title, current, value.title, value.status_icon, selected);
                } else {
                    $._object_selector_add_item(type, $select, $results, value.parent_title, current, value.title, value.status_icon, selected);
                }
            } else {
                const option = $("option[value='" + current + "']", $select);
                if (!option.length) {
                    $._object_selector_add_item(type, $select, $results, value.parent_title, current, value.title, value.status_icon, true);
                } else {
                    option.text(value.title);
                }
            }
        });

        $select.trigger("change.select2");
    };

    $.fn.object_selector = function (action, value, title) {
        var args = arguments;

        this.each(function () {
            var input = this
                , $simple = $(this).prev()
                , filter = $(input).data('filters')
                , wildcard = $(input).data('wildcard')
                , threshold = $(input).data('threshold')
                , format = $(input).data('format') || ''
                , sort = $(input).data('sort') || 'score_desc'
                , parentobject = $(input).data('parent')
                , parentkey = $(input).data('parentkey')
                , searchField = $(input).data('searchfield') || 'title'
                , relationshipTrackerId = $(input).data('relationshiptrackerid')
            ;

            $(input).addClass('primary').hide();
            $simple.hide();

            var $spinner = $(this).parent(),
                $container = $(input).closest('.object-selector'),
                $select = $container.find('select').first(),
                $filter = $container.find(':text.filter').first(),
                $search = $container.find('input.search').first(),
                $panel = $container.find('.card').first();

            if (action === 'set') {
                $select.val(value);
                if ($select.val() !== value && title) {
                    // for multilingual, object returned is JSON. try to parse and split
                    // with ' / ', otherwise catch and use title as is.
                    try
                    {
                        var titleObj = JSON.parse(title);
                        var titleArr = $.map(titleObj, function(el) { return el; });
                        title = titleArr.join(" / ");
                    }
                    catch(e)
                    {
                        // do nothing
                    }
                    $._object_selector_add_item('radio', $select, $container.find('.results'), null, value, title, null, true);

                    $select.trigger("change.select2");
                }

                $(input)
                    .val(value)
                    .data('label', title)
                    .trigger("change");

                return;
            }

            if (action === 'setfilter') {
                filter[args[1]] = args[2];
                $(input).data('filters', filter);
                $container.find('.too-many').hide();
                $search.trigger("click");
                return;
            }

            if (parentobject && parentkey) {
                filter[parentkey] = $(parentobject).val();
                $(parentobject).on('change', function () {
                    $(input)
                        .data('use-threshold', 1)
                        .object_selector('setfilter', parentkey, $(this).val());
                });
            }

            let triggerReady = function () {
                $(document).trigger(
                    "ready.object_selector",
                    [$container]
                );
            };

            if (threshold !== -1) {
                $spinner.tikiModal(" ");
                loadSelectorData(filter, {maxRecords: threshold, format: format, sort_order: sort}, function (data) {
                    $container._object_selector_update_results('radio', data.resultset.result, true);

                    $spinner.tikiModal();

                    if (data.resultset.count <= threshold) {
                        $select.parent().removeClass('d-none');
                    } else {
                        $panel.removeClass('d-none');
                    }
                    triggerReady();
                });
            } else {
                $panel.removeClass('d-none');
                triggerReady();
            }
            $panel.on('click', ':radio', function () {
                if ($(this).is(':checked')) {
                    $(input).object_selector('set', $(this).val(), $(this).parent().text());
                    if (relationshipTrackerId) {
                        var $labelContainer = $(this).parent().find('.form-check-label');
                        if ($labelContainer.find('.btn').length == 0) {
                            $labelContainer.append($container.find('.metadata-icon-template').html());
                            $labelContainer.find('.btn')
                                .attr('href', $labelContainer.find('.btn').attr('href') + '&refreshObject=' + $(this).val())
                                .attr('data-object', $(this).val());
                        }
                    }
                }
                if (relationshipTrackerId) {
                    $panel.find(':radio:not(:checked)').each(function (i, radio) {
                        $(radio).parent().find('.form-check-label .btn').remove();
                    });
                }
            });

            $(input).on("change", function () {
                var val = $(this).val(), id = null;
                if (val) {
                    var splitarray = val.split(':');
                    id = splitarray[1];
                    if(splitarray.length > 2){
                        for(var i = 2; i < splitarray.length; i++)
                        {
                            id = id + ':' + splitarray[i];
                        }
                    }
                }

                if ($simple.val() != id) {
                    $simple.val(id).trigger("change");
                }
            });
            $simple.on("change", function () {
                var target = filter.type + ':' + $(this).val();

                if (filter.type && $(input).val() != target) {
                    $(input).val(target).trigger("change");
                }
            });
            $select.on("change", function () {
                if ($(input).val() != $select.val()) {
                    $(input).data('label', $select.find('option:selected').text());
                    $(input).val($select.val()).trigger("change");
                }
            });

            $search.on("click", function () {
                $spinner = $container.tikiModal(" ");
                var selectorArgs = {format: format, sort_order: sort};
                if ($(input).data('use-threshold') && threshold !== -1) {
                    selectorArgs.maxRecords = threshold;
                    $(input).data('use-threshold', 0);
                }
                if ($filter.val()) {
                    if (wildcard === 'y') {
                        filter[searchField] = '*' + $filter.val() + '*';
                    } else {
                        filter[searchField] = $filter.val();
                    }
                }
                loadSelectorData(filter, selectorArgs, function (data) {
                    $container._object_selector_update_results('radio', data.resultset.result, false);

                    $spinner.tikiModal();
                    triggerReady();
                });
            });

            $filter.keypress(function (e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $search.trigger("click");
                }
            });
        });

        return this;
    };

    $.fn.object_selector_multi = function (action) {
        var args = arguments;
        this.each(function () {
            const $textarea = $(this).hide().addClass('primary')
                , $container = $(this).closest('.object-selector-multi')
                , $select = $container.find('select')
                , $simpleinput = $textarea.prev(':text').hide()
                , $basic = $container.find('.basic-selector')
                , $panel = $container.find('.card')
                , $search = $container.find('input.search').first()
                , $filter = $container.find(':text.filter').first()
                , filter = $textarea.data('filters')
                , wildcard = $textarea.data('wildcard')
                , threshold = $textarea.data('threshold')
                , format = $textarea.data('format') || ''
                , parentobject = $textarea.data('parent')
                , parentkey = $textarea.data('parentkey')
                , sort = $textarea.data('sort') || 'score_desc'
                , initialValues = $select.val()
                , separator = $simpleinput.data('separator')
                , use_permname = $simpleinput.data('use_permname')
                , searchField = $textarea.data('searchfield') || 'title'
                , extratype = $textarea.data('extratype')
                , relationshipTrackerId = $textarea.data('relationshiptrackerid')
                ;

            if (action === 'setfilter') {
                filter[args[1]] = args[2];
                $textarea.data('filters', filter);
                $container.find('.too-many').hide();
                $search.trigger("click");
                return;
            }

            if (parentobject && parentkey) {
                filter[parentkey] = $(parentobject).val();
                $(parentobject).on('change', function () {
                    $textarea
                        .data('use-threshold', 1)
                        .object_selector_multi('setfilter', parentkey, $(this).val());
                });
            }

            let triggerReady = function () {
                $(document).trigger(
                    "ready.object_selector_multi",
                    [$container]
                );
            };

            if (threshold !== -1) {
                $container.tikiModal(' ');
                loadSelectorData(filter, {maxRecords: threshold, format: format, sort_order: sort, use_permname: use_permname}, function (data) {
                    $container.tikiModal('');
                    var results = data.resultset.result;
                    if (extratype) {
                        var objectIndex = results.length;
                        $.each(extratype, function(key, value) {
                            results[objectIndex] = {"object_type": key, "object_id": "", "title": value};
                            objectIndex++;
                        });
                    }
                    $container._object_selector_update_results('checkbox', results, true);

                    if (data.resultset.count <= threshold) {
                        $basic.removeClass('d-none');
                    } else {
                        $panel.removeClass('d-none');
                        // add .noselect2 here even though Select2 has been applied previously so select2_sortable doesn't update the value
                        $select.addClass("noselect2");
                    }
                    triggerReady();
                });
            } else {
                $panel.removeClass('d-none');
                triggerReady();
            }

            $filter.keypress(function (e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $search.trigger("click");
                }
            });

            $search.on("click", function () {
                $container.tikiModal(" ");
                const selectorArgs = {format: format, sort_order: sort};
                if ($textarea.data('use-threshold') && threshold !== -1) {
                    selectorArgs.maxRecords = threshold;
                    $textarea.data('use-threshold', 0);
                }
                if ($filter.val()) {
                    if (wildcard === 'y') {
                        filter[searchField] = '*' + $filter.val() + '*';
                    } else {
                        filter[searchField] = $filter.val();
                    }
                }
                loadSelectorData(filter, selectorArgs, function (data) {
                    $container._object_selector_update_results('checkbox', data.resultset.result, false);
                    $container.tikiModal();
                    triggerReady();
                });
            });

            $panel.on('click', ':checkbox', function () {
                var list = $.makeArray($container.find(':checkbox:checked').map(function () {
                    return $(this).val();
                }));
                $textarea.val(list.join("\n")).trigger("change");
                if (relationshipTrackerId) {
                    $container.find(':checkbox:not(:checked)').each(function (i, cb) {
                        $(cb).parent().find('.form-check-label .btn').remove();
                    });
                    $container.find(':checkbox:checked').each(function (i, cb) {
                        if ($(cb).parent().find('.form-check-label .btn').length === 0) {
                            var $parent = $(cb).parent().find('.form-check-label');
                            $parent.append($container.find('.metadata-icon-template').html());
                            $parent.find('.btn')
                                .attr('href', $parent.find('.btn').attr('href') + '&refreshObject=' + $(cb).val())
                                .attr('data-object', $(cb).val());
                        }
                    });
                }
            });
            $select.on('change', function () {
                var list = $(this).val() || [];
                $textarea.val(list.join("\n")).trigger("change");
            });

            if (separator) {
                $textarea.on('change', function () {
                    var lines = $(this).val().split("\n"), ids = [];
                    $.each(lines, function (k, line) {
                        var parts = line.split(':');
                        if (parts.length === 2) {
                            ids.push(parts[1]);
                        }
                    });
                    $simpleinput.val(ids.join(separator)).trigger("change");
                });
            }
        });
    };

    $.fn.object_selector_refresh_meta = function(data) {
        $(this).attr('href', data.editHref).attr('title', data.editTitle);
        var meta = $('[name="' + data.refreshMeta + '"]').val();
        meta = JSON.parse(meta);
        if (Array.isArray(meta) && meta.length == 0) {
            meta = {};
        }
        meta[$(this).data('object')] = data.itemId;
        $('[name="' + data.refreshMeta + '"]').val(JSON.stringify(meta));
    };

    $.fn.sortList = function () {
        var list = $(this), items = list.children('li').get();

        items.sort(function(a, b) {
            var compA = $(a).text().toUpperCase();
            var compB = $(b).text().toUpperCase();
            return (compA < compB) ? -1 : (compA > compB) ? 1 : 0;
        });

        $.each(items, function(idx, itm) {
            list.append(itm);
        });
    };
    $.localStorage = {
        store: function (key, value) {
            var fullKey = this._build(key);
            if (window.localStorage) {
                if (value) {
                    window.localStorage[fullKey] = JSON.stringify({
                        date: Date.now(),
                        data: value
                    });
                } else {
                    delete window.localStorage[fullKey];
                }
            }
        },
        load: function (key, callback, fetch, duration) {
            var payload, fullKey = this._build(key);

            if (window.localStorage && window.localStorage[fullKey]) {
                payload = JSON.parse(window.localStorage[fullKey]);

                if (duration) {
                    // Expired, refetch
                    if (payload.date + duration*1000 < Date.now()) {
                        fetch(function (data) {
                            $.localStorage.store(key, data);
                            callback(data);
                        });
                        return;
                    }
                }

                callback(payload.data);
            } else {
                fetch(function (data) {
                    $.localStorage.store(key, data);
                    callback(data);
                });
            }
        },
        _build: function (key) {
            // Use an alternate key to ensure old data structure
            // does not collide
            return key + "_2";
        }
    };

    var favoriteList = [];
    $.fn.favoriteToggle = function () {
        this
            .each(function () {
                var type, obj, isFavorite, link = this;
                type = $(this).queryParam('type');
                obj = $(this).queryParam('object');


                isFavorite = function () {
                    var ret = false;
                    $.each(favoriteList, function (k, v) {
                        if (v === type + ':' + obj) {
                            ret = true;
                            return false;
                        }
                    });

                    return ret;
                };

                $(this).find('span').remove(); //removes the previous star icon
                $(this).prepend($('<span />').attr({
                    'class' : isFavorite() ? 'fas fa-star fa-fw' : 'far fa-star fa-fw',
                    'title' : isFavorite() ? tr('Remove from favorites') : tr('Add to favorites')
                }));

                if (isFavorite()) {
                    $(this).addClass( 'favorite_selected' );
                    $(this).removeClass( 'favorite_unselected' );
                } else {
                    $(this).addClass( 'favorite_unselected' );
                    $(this).removeClass( 'favorite_selected' );
                }
                $(this)
                    .filter(':not(".register")')
                    .addClass('register')
                    .on("click", function () {
                        $.post($(this).attr('href'), {
                            target: isFavorite() ? 0 : 1
                        }, function (data) {
                            favoriteList = data.list;
                            $.localStorage.store($(link).data('key'), favoriteList);

                            $(link).favoriteToggle();
                        }, 'json');
                        return false;
                    });
            });
        return this;
    };

    $.fn.queryParam = function (name) {
        name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
        var regex = new RegExp("[\\?&]" + name + "=([^&#]*)");
        var results = regex.exec(this[0].href);

        if(results == null) {
            return "";
        } else {
            return decodeURIComponent(results[1].replace(/\+/g, " "));
        }
    };

    $(function () {
        var list = $('.favorite-toggle');

        if (list.length > 0) {
            $.localStorage.load(
                list.data('key'),
                function (data) {
                    favoriteList = data;
                    list
                        .favoriteToggle()
                        .removeClass('favorite-toggle');
                },
                function (recv) {
                    $.getJSON($.service('favorite', 'list'), recv);
                },
                3600 // Valid for 1h
            );
        }
    });

    // global ajax event handlers
    $document.on("ajaxComplete", function () {
        $('.favorite-toggle')
            .favoriteToggle()
            .removeClass('favorite-toggle');
    });

    $document.on("ajaxError", function (event, jqxhr, settings, thrownError) {

        if (settings.preventGlobalErrorHandle) {
            return;
        }

        // elFinder handles it's own errors so should not close bootstrap modals
        if (settings.url.indexOf("tiki-file_finder") === 0 || settings.url.indexOf("tiki-ajax_services.php?controller=file_finder") === 0) {
            return;
        }

        $(".modal.fade.show").modal("hide");
        var message;
        if (!thrownError && jqxhr.status !== 200) {
            $('.tiki-modal').hide();
            if (jqxhr.status) {
                message = jqxhr.status + " " + jqxhr.statusText;
            } else {
                message = "AJAX: " + jqxhr.statusText + " " + jqxhr.status + " (" + jqxhr.state() + ") for URL: " + settings.url;
            }
        }
        if (message) {
            $("#tikifeedback").showError(message);
        }
    });

    /**
     * Show a loading spinner on top of a button (or whatever)
     *
     * @param $spinner empty or jq object $spinner        if empty, spinner is added and returned and element "disabled"
     *                                             if spinner then spinner is removed and element returned to normal
     *
     * @return jq object $spinner being shown or null when removing
     */

    $.fn.showBusy = function( $spinner ) {
        if (!$spinner) {
            var pos = $(this).position();
            $spinner = $("<i alt='" + tr("Wait") + "' class='ajax-spinner icon icon-spinner fas fa-spinner fa-spin'></i>").
                    css({
                        "position": "absolute",
                        "top": pos.top + ($(this).height() / 2),
                        "left": pos.left + ($(this).width() / 2) - 8
                    }).data("target", this);
            $(this).parent().find(".ajax-spinner").remove();
            $(this).parent().append($spinner);
            $(this).attr("disabled", true).css("opacity", 0.5);
            return $spinner;
        } else {
            $($spinner.data("target")).attr("disabled", false).css("opacity", 1);
            $spinner.remove();
            return null;
        }
    };

    //    copy tracker action column to 1st row if table has horizontal scrolling
    //  exclude tables where tablesorter is being applied
    $('.table-responsive:not(.article-types):not(.ts-wrapperdiv)').each(function () {
        var table = $(this);
        // mobile friendly tables
        if (table.find('table:not(.tablesorter) tbody tr').width() - 10 > table.width()) {
            if (!table.hasClass('large-table-no-wrap')) table.addClass('large-table-no-wrap');
            if (screen.width <= 991) {
                $('<div class="toggle-table-wrap d-lg-none"><button type="button" class="btn btn-link fw-bold fa-solid fa-toggle-off"></button></div>').insertBefore(table);
                var checkall = false;
                table.find('table.table:not(.caltable) th').each(function (e) {
                    var header = $(this).html();
                    if ($(this).find('div').hasClass('form-check') || ($(this).find('input').hasClass('form-check-input') && e == 0)) {
                        $(this).addClass('visible-header');
                        header = "";
                    }

                    // page history exception
                    if ($(this).find('input[name=compare]').length) {
                        $(this).addClass('visible-header').addClass('compare-pages');
                        header = "";
                    }

                    table.find('table.table tbody tr').each(function () {
                        var cell = $(this).find('td').eq(e);
                        if (checkall) cell.addClass('checkmargin');
                        if (cell.html() != undefined) {
                            if (cell.html().trim() == '') {
                                cell.addClass('hidecell');
                            } else {
                                cell.prepend("<div class='header' style='display:none'>" + header + "</div>");
                            }
                        }
                        if (e == 0) {
                            if ((cell.hasClass('checkbox-cell') || cell.find('input:checkbox')) && cell.find('.header').html() == '') {
                                cell.addClass('checkall').removeClass('checkmargin');
                                checkall = true;
                            }
                        }
                    });
                });

                // this is for calendars
                table.find('table.caltable > tbody > tr').first().find('> td.heading').each(function (e) {
                    var header = $(this).html();
                    table.find('table.caltable > tbody > tr').filter((index) => index !== 0).each(function () {
                        var cell = $(this).find('> td').eq(e);
                        cell.prepend("<div class='header' style='display:none'>" + header + "</div>");
                    });
                });
            }
        }

        // action column
        if (table.find('table').width() - 5 > table.width()) {
            if (screen.width > 767) {
                if ($('table tr td:last-child').hasClass('action')) {
                    table.find('table td.action').each(function () {
                        $(this).parent().prepend($(this).clone());
                    });
                    table.find('table tr').eq(0).prepend('<th style="width:20px;"></th>');
                }
            }
        }
    });

    // mobile: wrap large table data on click
    $('.toggle-table-wrap button').each(function () {
        $(this).on('click', function () {
            if ($(this).hasClass('fa-toggle-on')) {
                $(this).removeClass('fa-toggle-on').addClass('fa-toggle-off');
                $(this).parent().nextAll('.table-responsive').first().removeClass('large-table').addClass('large-table-no-wrap');
            } else {
                $(this).removeClass('fa-toggle-off').addClass('fa-toggle-on');
                $(this).parent().nextAll('.table-responsive').first().removeClass('large-table-no-wrap').addClass('large-table');
            }
        });
    });

})(jQuery);

// Prevent memory leaks in IE
// Window isn't included so as not to unbind existing unload events
// More info:
//    - http://isaacschlueter.com/2006/10/msie-memory-leaks/
if ( window.attachEvent && !window.addEventListener ) {
    window.attachEvent("onunload", function() {
        for ( var id in jQuery.cache ) {
            var item = jQuery.cache[ id ];
            if ( item.handle ) {
                if ( item.handle.elem === window ) {
                    for ( var type in item.events ) {
                        if ( type !== "unload" ) {
                            // Try/Catch is to handle iframes being unloaded, see #4280
                            try {
                                jQuery.event.remove( item.handle.elem, type );
                            } catch(e) {}
                        }
                    }
                } else {
                    // Try/Catch is to handle iframes being unloaded, see #4280
                    try {
                        jQuery.event.remove( item.handle.elem );
                    } catch(e) {}
                }
            }
        }
    });
}

$.tikiModal = function(msg) {
    return $('body').tikiModal(msg);
};

//Makes modal over window or object so ajax can load and user can't prevent action
$.fn.tikiModal = function(msg) {
    var obj = $(this);
    if (!obj.length) {
        return null;            // happens after search index rebuild in some conditions
    }
    var lastModal = obj.data('lastModal');

    if (!lastModal) {
        lastModal = Math.floor(Math.random() * 1000);
        obj.data('lastModal', lastModal);
    }
    var box = {
        top: obj.offset().top,
        left: obj.offset().left,
        height: obj.outerHeight(),
        width: obj.outerWidth()
    };
    var modal = $('body').find('#modal_' + lastModal);
    var spinner = $('<i class="icon icon-spinner fas fa-spinner fa-spin" style="vertical-align: top; margin-right: .5em;"></i>');

    if (!msg) {
        modal
            .fadeOut(function() {
                $(this).remove();
            });
        obj.removeData('lastModal');
        return obj;
    }

    if (modal.length) {
        modal
            .find('.dialog')
            .empty()
            .html(spinner)
            .append(msg);
        return obj;
    }

    modal = $('<div id="modal_' + lastModal + '" class="tiki-modal">' +
        '<div class="mask" />' +
        '<div class="dialog"></div>' +
        '</div>')
        .appendTo('body');

    var zIndex = 0;
    if (obj.is("body")) {
        zIndex = 2147483646 - 1;    // maximum
        box.top = obj.offset().top + $window.scrollTop();
        box.left = obj.offset().left + $window.scrollLeft();
    } else {
        obj.parents().addBack().each(function () {
            var z = $(this).css("z-index");
            if (z && z !== 'auto' && z > zIndex) {
                zIndex = Number(z);
            }
        });
    }

    //Set height and width to mask to fill up the whole screen or the single element
    modal
        .width(box.width)
        .height(box.height)
        .css('top',     box.top + 'px')
        .css('left',     box.left + 'px')
        .find('.mask')
            .height(box.height)
            .fadeTo(1000, 0.6)
        .parent()
        .find('.dialog')
            .hide()
            .append(spinner)
            .append(msg);
    var dialog = modal.find('.dialog');
    if (obj.is("body")) {
        dialog.css({
            top: (box.top + $window.innerHeight()/2 - $window.scrollTop()) + "px",
            left: (box.left + $window.innerWidth()/2 - $window.scrollLeft()) + "px"
        });
    }
    dialog.css({
        marginTop: (dialog.height() / -2) + "px",
        marginLeft: (dialog.width() / -2) + "px"
    }).show();

    if (zIndex) {
        modal.css("z-index", zIndex + 1);
    }
    return obj;
};

//makes the width of an input change to the value
$.fn.valWidth = function() {
    var me = $(this);
    return me.ready(function() {
        var h = me.height();
        if (!h) {
            h = me.offsetParent().css("font-size");
            if (h) {
                h = parseInt(h.replace("px", ""));
            }
        }
        me.on("keyup", function() {
            var width = me.val().length * h;

            me
                .stop()
                .animate({
                    width: (width > h ? width : h)
                }, 200);
        })
        .trigger("keyup");
    });
};

//For making pagination have the ability to enter page/offset number and go
$.paginationHelper = function() {
    $('.pagenums').each(function() {
        var me = $(this);
        var step = me.find('input.pagenumstep');
        var endOffset = (me.find('input.pagenumend').val() - 1) * step.data('step');
        var url = step.data('url');
        var offset_jsvar = step.data('offset_jsvar');
        var offset_arg = step.data('offset_arg');

        me.find('span.pagenumstep').replaceWith(
            $('<input type="text" style="font-size: inherit; " />')
                .val(step.val())
                .on("change", function() {
                    var newOffset = step.data('step') * ($(this).val() - 1);

                    if (newOffset >= 0) {
                        //make sure the offset isn't too high
                        newOffset = (newOffset > endOffset ? endOffset : newOffset);

                        //THis is for custom/ajax search handling
                        window[offset_jsvar] = newOffset;
                        if (step[0]) {
                            if (step.attr('onclick')) {
                                step[0].onclick();
                                return;
                            }
                        }

                        //if the above behavior isn't there, we update location
                        document.location = url + offset_arg + "=" + newOffset;
                    }
                })
                .on("keyup", function(e) {
                    switch(e.which) {
                        case 13: $(this).trigger("blur");
                    }
                })
                .valWidth()
        );
    });
};

//a sudo "onvisible" event
$.fn.visible = function(fn, isOne) {
    if (fn) {
        $(this).each(function() {
            var me = $(this);
            if (isOne) {
                me.one('visible', fn);
            } else {
                me.on('visible', fn);
            }

            function visibilityHelper() {
                if (!me.is(':visible')) {
                    setTimeout(visibilityHelper, 500);
                } else {
                    me.trigger('visible');
                }
            }

            visibilityHelper();
        });
    } else {
        $(this).trigger('visible');
    }

    return this;
};

$.download = function(url, data, method){
    //url and data options required
    if( url && data ){
        //data can be string of parameters or array/object
        data = typeof data == 'string' ? data : jQuery.param(data);
        //split params into form inputs
        var inputs = '';
        jQuery.each(data.split('&'), function(){
            var pair = this.split('=');
            inputs+='<input type="hidden" name="'+ pair[0] +'" value="'+ pair[1] +'" />';
        });
        //send request
        jQuery('<form action="'+ url +'" method="'+ (method||'post') +'">'+inputs+'</form>')
        .appendTo('body').trigger("submit").remove();
    }
};

$.uiIcon = function(type) {
    return $('<div style="width: 1.4em; height: 1.4em; margin: .2em; display: inline-block; cursor: pointer;">' +
        '<span class="ui-icon ui-icon-' + type + '">&nbsp;</span>' +
    '</div>')
    .on("mouseenter",function(){
        $(this).addClass('ui-state-highlight');
    }).on("mouseleave", function() {
        $(this).removeClass('ui-state-highlight');
    });
};

$.uiIconButton = function(type) {
    return $.uiIcon(type).addClass('ui-state-default ui-corner-all');
};

$.rangySupported = function(fn) {
    if (window.rangy) {
        rangy.init();
        var cssClassApplierModule = rangy.modules.CssClassApplier;
        return fn();
    }
};

$.fn.rangy = function(fn) {
    var me = $(this);
    $.rangySupported(function() {
        $document.on("mouseup", function(e) {
            if (me.data('rangyBusy')) return;

            var selection = rangy.getSelection();
            var html = selection.toHtml();
            var text = selection.toString();

            if (text.length > 3 && rangy.isUnique(me[0], text)) {
                    if (fn)
                        if (typeof fn === "function")
                            fn({
                                text: text,
                                x: e.pageX,
                                y: e.pageY
                            });
            }
        });
    });
    return this;
};

$.fn.rangyRestore = function(phrase, fn) {
    var me = $(this);
    $.rangySupported(function() {
        phrase = rangy.setPhrase(me[0], phrase);

        if (fn)
            if (typeof fn === "function")
                fn(phrase);
    });
    return this;
};

$.fn.rangyRestoreSelection = function(phrase, fn) {
    var me = $(this);
    $.rangySupported(function() {
        phrase = rangy.setPhraseSelection(me[0], phrase);

        if (fn)
            if (typeof fn === "function")
                fn(phrase);
    });
    return this;
};

$.fn.realHighlight = function() {
    var o = $(this);
    $.rangySupported(function() {
        rangy.setPhraseBetweenNodes(o.first(), o.last(), document);
    });
    return this;
};

$.notify = function(msg, settings) {
    settings = $.extend({
        speed: 10000
    },settings);

    var notify = $('#notify');

    if (!notify.length) {
        notify = $('<div id="notify" />')
            .css('top', '5px')
            .css('right', '5px')
            .css('position', 'fixed')
            .css('z-index', 9999999)
            .css('padding', '5px')
            .width($window.width() / 5)
            .prependTo('body');
    }

    var note = $('<div class="notify ui-state-error ui-corner-all ui-widget ui-widget-content" />')
        .append(msg)
        .css('padding', '5px')
        .css('margin', '5px')
        .on("mousedown", function() {
            return false;
        })
        .on("mouseenter",function() {
            $(this)
                .stop()
                .fadeTo(500, 0.3);
        }).on("mouseleave", function() {
            $(this)
                .stop()
                .fadeTo(500, 1);
        })
        .prependTo(notify);

    setTimeout(function() {
        note
            .fadeOut()
            .slideUp();

        //added outside of fadeOut to ensure removal
        setTimeout(function() {
            note.remove();
        }, 1000);

    }, settings.speed);
};

function delayedExecutor(delay, callback)
{
    var timeout;

    return function () {
        var args = arguments;
        if (timeout) {
            clearTimeout(timeout);
            timeout = null;
        }

        timeout = setTimeout(function () {
            callback.apply(this, args);
        }, delay);
    };
}

$(function () {

        // Show/hide the sidebars //
        ////////////////////////////

    $(".toggle_zone").on("click", function () {
        var $this = $(this), zone="",
            icon_left = "toggle-left", icon_right = "toggle-right";

        if ($this.is(".right")) {
            zone = "right";
            icon_left = "toggle-right";
            icon_right = "toggle-left";
        } else if ($this.is(".left")) {
            zone = "left";
        }
        if ($this.find(".icon-" + icon_left).length) {    // hide it
            $this.find(".icon").setIcon(icon_right);
            setCookie("hide_zone_" + zone, 'y');
            $("body").addClass("hide_zone_" + zone);
        } else {
            $this.find(".icon").setIcon(icon_left);
            deleteCookie("hide_zone_" + zone);
            $("body").removeClass("hide_zone_" + zone);
        }

        $(window).trigger("resize");

        return false; // do not modify URL by adding # on the click
    });
});

// try and reposition the menu ul within the browser window
$.fn.moveToWithinWindow = function() {
    var $el = $(this);
    var h = $el.height(),
    w = $el.width(),
    o = $el.offset(),
    po = $el.parent().offset(),
    st = $window.scrollTop(),
    sl = $window.scrollLeft(),
    wh = $window.height(),
    ww = $window.width();

    if (w + o.left > sl + ww) {
        $el.animate({'left': sl + ww - w - po.left}, 'fast');
    }
    if (h + o.top > st + wh) {
        $el.animate({'top': st + wh - (h > wh ? wh : h) - po.top}, 'fast');
    } else if (o.top < st) {
        $el.animate({'top': st - po.top}, 'fast');
    }
};

$.fn.scaleImg = function (max) {
    $(this).each(function() {
        //Here we want to make sure that the displayed contents is the right size
        var h, w, img = $(this),
        actual = {
            height: img.height(),
            width: img.width()
        },
        original = $(this).clone(),
        parent = img.parent();

        var winner = '';

        if (actual.height > max.height) {
            winner = 'height';
        } else if (actual.width > max.width) {
            winner = 'width';
        }

        //if there is no winner, there is no need to resize
        if (winner) {
            //we resize both images and svg, we check svg first
            var g = img.find('g');
            if (g.length) {
                img
                    .attr('preserveAspectRatio', 'xMinYMin meet');

                parent
                    .css('overflow', 'hidden')
                    .width(max.width)
                    .height(max.height);

                g.attr('transform', 'scale( ' + (100 / (actual[winner] / max[winner]) * 0.01)  + ' )');
            } else {
                //now we resize regular images
                if (actual.height > actual.width) {
                    h = max.height;
                    w = Math.ceil(actual.width / actual.height * max.height);
                } else {
                    w = max.width;
                    h = Math.ceil(actual.height / actual.width * max.width);
                }
                img.css({ height: h, width: w });
            }

            img
                .css('cursor', "url(img/icons/zoom.gif),auto")
                .on("click", function () {
                    $.openModal({
                        title: original.attr('title'),
                        content: original
                    });
                    return false;
                });
        }
    });

    return this;
};


// Compatibility to old jquery to resolve a bug in fullcalendar
$.curCSS = function (element, property) {
    return $(element).css(property);
};

// Select all days or select working days for calendar
  $(document).ready(function() {
    $('#select-all-days').on('change', function() {
      $('input[name="viewdays[]"]').prop('checked', this.checked);
    });
    $('#select-working-days').on('change', function() {
      $('input[name="viewdays[]"]').slice(1, -1).prop('checked', this.checked);
    });
  });

$.fn.registerFacet = function () {
    this.each(function () {
        const $this = $(this),
            $input = $($this.data('for'));

        if ($input.length === 0) {
            console.error(`Custom search facet form input "${$this.data('for')}" not found`);
            return;
        }
        let entries = $input.val().split(" " + $(this).data('join') + " ")
            .map(function (value) {
                return (value.charAt(0) === '"') ? value.substring(1, value.length - 2) : value;
            });

        function applyFilter(value) {
            if (value) {
                value = $.makeArray(value);
                value = value
                    .map(function (value) {
                        return (-1 === value.indexOf(' ')) ? value : ('"' + value + '"');
                    })
                    .join(" " + $this.data('join') + " ");
            }
            $($this.data('for')).val(value).change();
        }

        if ($this.is('select')) {
            $this
                .val(entries)
                .trigger("change.select2") // for Select2
                .on("change", function () {
                    var value = $(this).val();
                    applyFilter(value);
                });
        } else if ($this.has(':checkbox').length) {
            $(':checkbox', $this)
                .each(function () {
                    if (-1 !== $.inArray($(this).val(), entries)) {
                        $(this).prop('checked', true);
                    }
                })
                .on('click', function () {
                    applyFilter($(':checked', $this).map(function () {
                        return $(this).val();
                    }));
                });
        }

        var selected = $('option:selected, :checkbox:checked', this).length,
            all = $('option, :checkbox', this).length;

        if (all === 1 && selected === 0) {
            $(this).closest('.facet-hide-group').hide();
        }
    });

    return this;
};

$.fn.reload = function () {
    this.each(function () {
        if (this.reload) {
            this.reload();
        } else if($(this).data('reload')) {
            $(this).loadService({}, {
                url: $(this).data('reload')
            });
        }
    });
    return this;
};

$(document).on('mouseover', '.media[data-href]', function () {
    $(this).css('cursor', 'pointer');
});
$(document).on('mouseout', '.media[data-href]', function () {
    $(this).css('cursor', 'default');
});
$(document).on('click', '.media[data-href]', function () {
    document.location.href = $(this).data('href');
});

let modalCounter = 0;
$(document).on('show.bs.modal', '.footer-modal', function () {
    const modal = $(this);
    modal.attr('counter', ++modalCounter);
    // if the modal counter attribute is upper than 1, then we need to hide the previous modal
    const currentModalCounter = modal.attr('counter');
    if(currentModalCounter > 1) {
        for (let i = 1; i < currentModalCounter; i++) {
            const toBeHidden = $('.footer-modal[counter="' + i + '"]');
            const dialog = toBeHidden.find('.modal-dialog');
            /* keep track of the size of the modal because it will be reset when the modal is hidden.
            the size is set via a class name on the modal-dialog, so keep its classes
            */
            const dialogClasses = dialog.attr('class');
            toBeHidden.attr('dialog-classes', dialogClasses);
            toBeHidden.modal('hide');
        }
        // when the current modal is hidden, we need to show the previous modals
        modal.one('hidden.bs.modal', function () {
            modalCounter = 0;
            const toBeShown = $('.footer-modal[counter="' + (currentModalCounter - 1) + '"]');
            if(!toBeShown.attr('need-close')) {
                const dialogClasses = toBeShown.attr('dialog-classes');
                toBeShown.find('.modal-dialog').attr('class', dialogClasses); // restore the size of the modal
                toBeShown.modal('show');
            }
            toBeShown.removeAttr('need-close');
        });
    }
});

$(document).on('submit', '.modal-body form:not(.no-ajax):not(.ajax-reuse-modal)', ajaxSubmitEventHandler(function (data) {
    //if FORWARD is set in the returned data, load the passed service into the modal
    // rather than close the modal and refresh the page.
    if (data && data.FORWARD) {
        var $this = $(this);
        if ($this.is("form")) {
            $this = $this.parent();
        }
        $this.children().remove();
        $this.loadService(data.FORWARD, {
            origin: this,
            load: function () {
                $(this).closest('.modal').trigger('tiki.modal.redraw');
            }
        });
    } else if (data && data.extra == 'close') {
        // closing modal from a service shouldn't reload the page
        $.closeModal();
    } else {
        // reload() causes a request to update the browser cache - similar to pressing the reload button.
        // so we must not reload() but set the href. This behaves simililar to clicking a link - which keeps the browser cache.
        // The difference is: NOT loading about 50+ js / css files!
        //document.location.reload();
        document.location.href = document.location.href.replace(/#.*$/, "");    // remove the hash from the URL if there is one otherwise the page doesn't reload
    }
    if (data && data.refreshObject) {
        $('.metadata-insert-item[data-object="' + data.refreshObject + '"]').object_selector_refresh_meta(data);
    }
}));

$(document).on('submit', '.modal-body form.ajax-reuse-modal', ajaxSubmitEventHandler(function (data) {
    $(this).closest('.modal .modal-body').html(data);
}, 'html'));


// When data-size is set on the toggle-link, alter the size of the modal
$(document).on('click', '[data-bs-toggle=modal][data-size]', function () {
    var target = $(this).data('bs-target'), size = $(this).data('size');

    $(target)
        .one('hidden.bs.modal', function () {
            $('.modal-dialog', this).removeClass(size);
        })
        .find('.modal-dialog').addClass(size)
        ;
});

$(document).on('click', '[data-bs-toggle=modal][data-modal-title]', function () {
    var target = $(this).data('bs-target'), title = $(this).data('modal-title');

    $(target)
        .one('loaded.bs.modal', function () {
            $('.modal-title', this).text(title);
        })
        ;
});

// Custom handler for BS modals to take care of backdrop residing in the modal div itself after BS5
$(document).on('click', '[data-tiki-bs-toggle=modal]', function (e) {
    var target = $(this).data('bs-target'),
        backdrop = $(this).data('bs-backdrop'),
        size = $(this).data('size'),
        href = $(this).attr('href') || $(this).attr('formaction'),
        form = $(this).attr('form');

    if (size) {
        $(target)
            .one('hidden.bs.modal', function () {
                $('.modal-dialog', this).removeClass(size);
            })
            .find('.modal-dialog').addClass(size)
            ;
    }

    $(target)
        .one('hidden.bs.modal', function () {
            $(this).removeAttr('data-bs-backdrop');
            setTimeout(function() {
                // due to a limitation of BS5 backdrop handling, closing a second modal removes all backdrops
                // including the one of the still open first modal
                if ($('.modal.fade.show').length > 0 && $('.modal-backdrop.fade.show').length == 0) {
                    $('<div/>').addClass('modal-backdrop fade show').appendTo('body');
                }
            }, 100);
        })
        .one('shown.bs.modal', function () {
            var $modal = $(this);
            const $modalContent = $modal.find('.modal-content');
            const $placeholder = $('<div />');
            $placeholder.load(href, $('#' + form).serialize(), function() {
                const title = $placeholder.find(".modal-content-storage > .title").text();
                if (title) {
                    $modalContent.find(".modal-header > .modal-title").text(title);
                }

                const body = $placeholder.find(".modal-content-storage > .body");
                if (body.length) {
                    $modalContent.find(".modal-body").html(body.html());
                }
                $modal.trigger("tiki.modal.redraw");
            });
        })
        .attr('data-bs-backdrop', backdrop)
        .modal('show');

    return false;
});

$(document).on('loaded.bs.modal', '.modal.fade', function () {
    $(this).trigger('tiki.modal.redraw');
});

$(document).on('shown.bs.modal', '.modal', function (event) {
    const $button = $(event.relatedTarget); // Button that triggered the modal
    const remote = $button.data('remote'); // Extract info from data-* attributes
    const $modal = $(this);

    if ($button.data("size")) {
        // from smarty_function_bootstrap_modal
        $modal.find(".modal-dialog").addClass($button.data("size"));
    }

    const href = remote ? remote : $button.attr("href");

    if (href) {
        $modal.find('.modal-content').load(href, function () {
            try {
                const data = JSON.parse($(this).text());
                if (data.extra === 'close') {
                    $.closeModal();
                    return;
                } else if (data.extra === 'refresh') {
                    window.location.href = window.location.href.replace(/#.*$/, "");;
                    return;
                }
            } catch (e) {
                // normal html output stays in the modal
            }
            $(this).trigger("tiki.modal.redraw");
        });
    } else {
        $modal.trigger("tiki.modal.redraw");
    }
});

$(document).on('tiki.modal.redraw', '.modal.fade', function () {
    var modal = this, $button;

    // On Modal show, find all buttons part of a .submit block and create
    // proxies of them in the modal footer
    $('.modal-footer .auto-btn', modal).remove();
    $('div.submit .btn', modal).each(function () {
        var $submit = $(this);
        if ($submit.hasClass('dropdown-toggle') && $submit.parent().hasClass('dropdown')) {
            var $form = $submit.parents('form');
            $button = $submit.parent();
            $button.find('button.dropdown-item').click(function(e) {
                e.preventDefault();
                if ($(this).data("alt_controller") && $(this).data("alt_action")) {
                    $form.attr("action", $.service($(this).data("alt_controller"), $(this).data("alt_action")));
                }
                var $hidden = null;
                if ($(this).data('alt_param')) {
                    $hidden = $('<input type="hidden">').attr('name', $(this).data('alt_param')).val($(this).data('alt_param_value'));
                    $form.append($hidden);
                }
                if ($(this).data('confirm')) {
                    $(this).confirmationDialog({
                        title: tr("Proceed with this request?"),
                        message: $(this).data('confirm'),
                        success: function () {
                            $form.submit();
                        },
                        close: function() {
                            if ($hidden) {
                                $form.find('input[name=' + $hidden.attr('name') + ']').remove();
                            }
                        }
                    });
                } else {
                    $form.submit();
                }
                return false;
            });
        } else if ($submit.is('a:not(.custom-handling)')) {
            $button = $submit;
        } else {
            $submit.hide();
            $button = $('<button>')
                .text($submit.val() || $submit.text())
                .attr('class', $submit.attr('class'))
                .addClass('auto-btn')
                .on("click", function () {
                    if ($submit.data("alt_controller") && $submit.data("alt_action")) {
                        $submit.parents("form").attr("action", $.service($submit.data("alt_controller"), $submit.data("alt_action")));
                    }
                    $submit.trigger("click");
                    if (typeof $submit.parents("form").validate !== "function") {
                        // make the button look disabled and ignore further clicks
                        $button.off("click").css("opacity", 0.3);
                    }
                });
        }
        $('.modal-footer', modal).append($button);
    });

    if ($.fn.flexibleSyntaxHighlighter) {
        $('textarea', modal).flexibleSyntaxHighlighter();
    }

    $(".nav-tabs", this).each(function () {
        if ($(".active", this).length === 0) {
            $("li:first-child a", this).tab("show");
        }
    });

    if ($.applySelect2) {
        $(this).applySelect2();
    }

    if (jqueryTiki.colorbox) {
        $(this).applyColorbox();
    }

    if (jqueryTiki.tooltips) {
        $(this).tiki_popover();
    }
    $.initTrees();

    // START BOOTSTRAP 4 CHANGE

    $('.modal-body :input', modal).first().trigger("focus");
    $('.modal-backdrop.show:not(.fade)').remove(); // Bootstrap keeps adding more of these

    // handle $ajaxtimer for alerts that are not using the confirmAction handler
    if ($("#timer-seconds", modal).length) {
        var $seconds = $("#timer-seconds"),
            counter = $seconds.text(),
            timer = setInterval(function () {
                $seconds.text(--counter);
                if (counter === 0 || counter < 0) {
                    $.closeModal();
                    window.location = window.location.href;
                }
            }, 1000);
    }
    // END BOOTSTRAP 4 CHANGE
});

/**
 * Make .depends elements show or hide depending on the state of the "on" element
 * for checkboxes and select boxes with empty value
 */
$(document).ready(registerDepends).on("tiki.modal.redraw", registerDepends);
function registerDepends() {
    $(".depends").each(function () {
        var $depends = $(this),
            on = $depends.data("on");

        $("[name=" + on + "]").on("change", function () {
            if ($(this).is("input[type=checkbox]") && $(this).is(":checked")) {
                $depends.show();
            } else if ($(this).is("select") && $(this).val()) {
                $depends.show();
            } else if ($(this).is("input[type=text]") && $(this).val()) {
                $depends.show();
            } else {
                $depends.hide();
            }
        }).trigger("change");
    });
}

$(function () {
    const $tabs = $('a[data-bs-toggle=tab][href="' + document.location.hash + '"]');
    let tabShown = false,
        notShown = [];

    if (document.location.search.match(/cookietab=/)) {
        tabShown = true;
    } else if (document.location.hash && $tabs.length) {
        $tabs.tab('show');
        tabShown = true;
    } else {
        $(".tabs").each(function () {
            const name = $(this).data("name"),
                hrefFromCookie = name ? getCookie(name, "tabs", false) : false;

            // class "active" set serverside from $cookietab var
            let $tab = $('a[data-bs-toggle=tab].active', this);

            if (hrefFromCookie && $tab.length === 0) {
                $tab = $('a[data-bs-toggle=tab][href="' + hrefFromCookie + '"]');
            } else {
                $tab.tab('show');
                tabShown = true;
                return; // active was set serverside, job done
            }

            if ($tab.length && ! $tab.is(".active")) {
                const scroll = $window.scrollTop();    // prevent window jumping to tabs on click
                $tab.tab('show');
                $window.scrollTop(scroll);
                tabShown = true;
            } else if (name) {
                notShown.push(name);
            }
        });
    }
    if (typeof $().tab === "function") {
        if (! tabShown && ! notShown.length) {
            $("a[data-bs-toggle=tab]").first().tab("show");
        } else if (notShown.length) {
            for (let i = 0; i < notShown.length; i++) {
                const scroll = $window.scrollTop();    // prevent window jumping to tabs on click
                $(".tabs[data-name=" + notShown[i] + "] a[data-bs-toggle=tab]").first().tab("show");
                $window.scrollTop(scroll);
            }
        }
    }

    $('a[data-bs-toggle="tab"]').on('show.bs.tab', function (e) {
        if ($(this).parents(".tab-content").length === 0) {
            document.location.hash = $(e.target).attr("href");
        }
        setCookieBrowser($(this).parents(".tabs").first().data("name"), $(e.target).attr("href"), "tabs");
    }).on("click", function () {
        const scroll = $window.scrollTop();    // prevent window jumping to tabs on click
        $(this).tab('show');
        $window.scrollTop(scroll);
    });

    $("input[name='session_protected']").on('click', function () {
        if (
            $(this).prop('type') === 'checkbox' &&
            $(this).data('tiki-admin-child-block') === '#session_protected_childcontainer'
        ) {
            var checkbox = $("input[name='session_protected']");
            if (checkbox.prop('checked') && location.protocol !== 'https:') {
                $(this).confirmationDialog({
                    title: tr('Warning - Protect all sessions with HTTPS'),
                    message: tr('You seem to be accessing the website using HTTP only, if your HTTPS settings are not correct, you will be locked out of the website'),
                    success: function () {
                        checkbox.prop('checked', true);
                    }
                });
                return false;
            }
        }
    });
});

/**
 * Opens a Bootstrap modal dialog with the given options.
 * @param {Object} options - The options for the modal dialog.
 * @param {string} options.remote - The URL to load the modal dialog content from.
 * @param {string} options.size - The size of the modal dialog. One of 'modal-sm', 'modal-lg', 'modal-xl', 'modal-fullscreen'.
 * @param {string} options.title - The title of the modal dialog. If no remote is used to load content from.
 * @param {HTMLElement} options.content - The content of the modal dialog. If no remote is used to load content from.
 * @param {Array} options.dialogVariants - The variants to apply to the dialog. i.e. "centered" for vertical centering, "scrollable" for scrollable
 *     content.
 * @param {Object[]} options.buttons - The buttons to add to the modal dialog. Each button is an object with the following properties:
 * @param {string} options.buttons[].text - The text of the button.
 * @param {string} options.buttons[].type - The type of the button. One of Bootstrap's button styles.
 * @param {Function} options.buttons[].onClick - The function to call when the button is clicked.
 */
$.openModal = function (options) {
    let href = options.remote || "";

    if (href && href.indexOf("modal=") === -1) {
        if (-1 === href.indexOf('?')) {
            href += '?modal=1';
        } else {
            href += '&modal=1';
        }
    }

    const $modal = $('.footer-modal.fade:not(.show)').first()
        // Bind a single event to trigger as soon as the form appears
        .one('hidden.bs.modal', options.close || function () {});


    // Allow applying classes to the dialog that influence its behavior. i.e. modal-dialog-centered, modal-dialog-scrollable
    if (Array.isArray(options.dialogVariants)) {
        options.dialogVariants.forEach((variant) => {
            $modal.find(".modal-dialog").addClass(`modal-dialog-${variant}`);
        });
    }

    const $spinner = $modal.tikiModal(tr('Loading...'));

    const $modalContent = $modal.find(".modal-content");

    if (options.title) {
        $modalContent.find(".modal-header > .modal-title").text(options.title);
    }

    if (options.size) {
        $modal.find('.modal-dialog').addClass(options.size);
        $modal.one('hidden.bs.modal', function () {
            $('.modal-dialog', this).removeClass(options.size);
        });
    }

    if (href) {
        // Make the form load remote content
        const $placeholder = $('<div />');
        $placeholder
            .load(href, function () {

                // Replace the modal content with the loaded content
                const title = $placeholder.find(".modal-content-storage > .title").text();
                if (title) {
                    $modalContent.find(".modal-header > .modal-title").text(title);
                }

                const body = $placeholder.find(".modal-content-storage > .body");
                if (body.length) {
                    $modalContent.find(".modal-body").html(body.html());
                }

                $spinner.tikiModal();

                if ($modal.is(':visible')) {
                    $modal.trigger("tiki.modal.redraw");
                } else {
                    $modal.modal("show");
                }

                if (options.open) {
                    options.open.apply($modal.get(0));
                }
            });
    } else {
        $modal.find('.modal-footer').children().not("[data-bs-dismiss='modal']").remove();
        if (Array.isArray(options.buttons)) {
            const $footer = $modal.find('.modal-footer');
            options.buttons.forEach(({text, type, onClick}) => {
                const btnStyle = type ? `btn-${type}` : 'btn-primary';
                const $button = $(`<button class='btn ${btnStyle}'>${text}</button>`)
                    .on('click', onClick.bind($modal.get(0)));
                $footer.append($button);
            });
        }

        if (options.content) {
            $modalContent.find('.modal-body').html(options.content);
        }

        const theModal = new bootstrap.Modal($modal.get(0), options);
        theModal.show();

        if (options.open) {
            options.open.apply($modal.get(0));
        }
        $spinner.tikiModal();
    }

};

$.closeModal = function (options) {
    options = options || {};
    var done = options.done;
    if (done) {
        done = function () {
            // Wait until the event loop ends before considering really done
            setTimeout(options.done, 0);
        };
    }

    if(options.all) {
        $('.modal.fade').each(function(index) {
            $(this).attr('need-close', 'true').modal('hide');
            if (index === $('.modal.fade').length - 1) {
                $(this).one('hidden.bs.modal', function () {
                    if (done) {
                        done();
                    }
                }).modal('hide');
            }
        });
    } else {
        $('.modal.fade.show').last()    // BOOTSTRAP 4 CHANGE
        .one('hidden.bs.modal', done || function () {})
        .modal('hide');
    }
};

$.fn.clickModal = function (options, href) {
    this.on("click", $.clickModal(options, href));
    return this;
};

$.clickModal = function (options, href) {
    return function (e) {
        var control = this, url;
        if (! href) {
            url = $(this).attr('href');
        } else {
            url = href;
        }
        if (typeof e.preventDefault === "function") {
            e.preventDefault();
        }
        if ($(this).data("modal-size")) {
            options.size = $(this).data("modal-size");
        }

        $.openModal({
            ...options,
            title: options.title,
            size: options.size,
            remote: url,
            backdrop: (typeof options.backdrop === "undefined") ? true : options.backdrop,
            keyboard: (typeof options.keyboard === "undefined") ? true : options.keyboard,
            focus: (typeof options.focus === "undefined") ? true : options.focus,
            show: (typeof options.show === "undefined") ? true : options.show,
            open: function () {
                if (options.open) {
                    options.open.apply(this, []);
                }

                $('form:not(.no-ajax)', this)
                    .addClass('no-ajax') // Remove default ajax handling, we replace it
                    .on("submit", ajaxSubmitEventHandler(function (data) {
                        if (options.success) {
                            options.success.apply(control, [data]);
                        }
                    }));
            }
        });
    };
};

/**
 * Open a tab on the current page, e.g.:
 * <a href="#" onclick="showTab(2); return false;">Open tab 2 on this page</a>
 *
 * Assumes the tab is in the main column of the page and that one tab is already showing.
 *
 * @param tabNumber         number of the tab on the current page to show
 * @returns {boolean}
 */
function showTab(tabNumber) {
    var thisTabId = $('#col1').find('.tab-pane.active').attr('id'),
        tabNames = thisTabId.substr(0, thisTabId.indexOf('-') + 1)
    ;
    $('a[href="#' + tabNames + tabNumber + '"]').tab('show');
}

/**
 * Send feedback to a popup modal or to div#tikifeedback using bootstrap alert variations (error, warning, success, info)
 *
 * @param mes           array       The message
 * @param type          string      Type of alert: error, warning, success or info (default)
 * @param modal         boolean     true for popup modal, false (default) to use the div#tikifeedback that is on every page
 * @param title         string      Custom message title
 * @param icon          string      Custom icon
 * @param killall       boolean     true for removing other feedbacks already open, false (default) (only for non modal)
 * @param custom        string      Custom target in jquery selection notation (only for non modal)
 */
function feedback (mes, type, modal, title, icon, killall, custom)
{
    mes = mes || [];
    if (!$.isArray(mes)) {
        mes = [mes];
    }
    if (mes.length == 1) {
        var meshtml = [mes][0];
    } else {
        var meshtml = '<ul>';
        $.each(mes, function(i, val) {
            if (val) {
                meshtml += '<li>' + val + '</li>';
            }
        });
        meshtml += '</ul>';
    }
    type = type || 'info'; modal = modal || false; killall = killall || false; custom = $(custom).length ? $(custom).first() : null;
    var target, map =
    {
        'error': {title:tr('Error'), class:'danger', icon:'error'},
        'warning': {title:tr('Warning'), class:'warning', icon:'warning'},
        'success': {title:tr('Success'), class:'success', icon:'success'},
        'info': {title:tr('Note'), class:'info', icon:'information'}
    };
    var check = ['error', 'warning', 'success', 'info'];
    type = $.inArray(type, check) > -1 ? type : 'info';
    title = title || map[type]['title'];
    icon = icon || map[type]['icon'];
    icon = $.fn.getIcon(icon);
    if (modal) {
        if (mes.length > 0) {
            meshtml = '<div class="alert alert-dismissable alert-' + map[type]['class'] + '">' + meshtml + '</div>';
        } else {
            meshtml = '';
        }
        target = $('.footer-modal.fade:not(.show)').first();
        $('.modal-content', target).html(
            '<div class="modal-header">' +
                '<h4 class="text-' + map[type]['class'] + '">' + icon[0].outerHTML + ' ' + title + '</h4>' +
                '<button type="button" class="close btn-close pull-right" aria-label="{tr}Close{/tr}" data-bs-dismiss="modal"></button>' +
            '</div>' +
            '<div class="modal-body">' +
                meshtml +
            '</div>'
        );
        target.modal('show');
    } else {
        var tfb = $(custom ? custom : 'div#tikifeedback');
        if (killall) {
            tfb.find('div.alert.alert-dismissable').remove();
        }
        if (mes.length == 0) {
            meshtml = '';
        }
        tfb.append(
            '<div class="alert alert-dismissable alert-' + map[type]['class'] + '">' +
                '<button type="button" class="close btn-close" aria-label="{tr}Close{/tr}" data-bs-dismiss="modal"></button>' +
                '<h4>' + icon[0].outerHTML + ' ' + title + '</h4>' +
                meshtml +
            '</div>'
        ).on('click' , 'button.close' , function() {
            $(this).parent().remove();
        });
        placeFeedback(tfb);
    }
}

/**
 * Utility for tikifeedback to place the feedback at the top of the page (at div#tikifeedback) or viewport, whichever is
 * lower to allow it to be seen. If shown at the top of the view port, after 5 seconds it will move to its normal
 * position, which is the first element in div#col1
 *
 * @param object
 */
function placeFeedback(object) {
    if ($('.modal.fade.show').length) {
        $('#col1, #col2, #col3').css('z-index', 'auto');
        object.css('z-index', 3000);
        object.find('div').css('z-index', 3000);
    }
    if (object.offset().top < $(window).scrollTop()) {
        object.find('div.alert').append('<div id="move-message" style="font-size:smaller;margin-top:10px"><em>'
            + tr('This message will move to the top of the page after a few seconds.') + '</em>');
        //it's important to not define top until after the tfb object has been manipulated
        object.offset({'top': $(window).scrollTop()});
        object.css('z-index', 3000);
        setTimeout(function() {
            object.fadeOut(1000, function() {
                //move back to usual position and clear style attribute so subsequent feedback appears properly
                $('div#col1').prepend(object);
                object.css({'z-index':'', 'position':'', 'top': ''});
                object.find('div#move-message').css('visibility', 'hidden');
                object.fadeIn();
            });
        }, 5000);
    }

}

// thanks to Rob W on https://stackoverflow.com/a/8962023/2459703
$.fn.closestDescendent = function(filter) {
        var $found = $(),
                $currentSet = this; // Current place
        while ($currentSet.length) {
                $found = $currentSet.filter(filter);
                if ($found.length) break;  // At least one match: break loop
                // Get all children of the current set
                $currentSet = $currentSet.children();
        }
        return $found.first(); // Return first match of the collection
};

window.regCapsLock = function () {};

// Avoid that jquery appends a cachebuster to scripts loaded via a regular script tag when the base content was loaded from an xhr call
// I.e xhr call loads same html boilerplate and that boilerplate contains a script tag that loads some .js script.
// In this case, jquery would add a cachebuster to the js request, and no cache would be work.
$.ajaxPrefilter(function( options, originalOptions, jqXHR ) {
    if ( options.dataType == 'script' || originalOptions.dataType == 'script' ) {
        options.cache = true;
    }
});

//Preview for the upload avatar popup
function readURL(input) {
    if (input.files && input.files[0]) {
        $(".btn-upload-avatar").removeClass('disabled');
        var reader = new FileReader();
        reader.onload = function (e) {
            $('.user-avatar-preview img').attr('src', e.target.result);
        };
        reader.readAsDataURL(input.files[0]);
    }
}
$(document).on('change', '#userfile', function(){
    readURL(this);
});

function objectLockToggle(icon) {

    var $this = $(icon).tikiModal(" "),
        action = $this.data("is_locked") ? "unlock" : "lock";

    $.post($.service(
        "object",
        action,
        {
            type: $this.data("type"),
            object: $this.data("object"),
            value: $this.data("is_locked") ? "" : jqueryTiki.username
        }
        ), function (data) {
            if (data && data.locked) {
                $this.find(".icon").setIcon("lock");
                $this.data("is_locked", "1")
                    .attr("title", tr("Locked by " + jqueryTiki.userRealName))
                    .parent().find("input[name=locked]").val(jqueryTiki.username);
            } else {
                $this.find(".icon").setIcon("unlock");
                $this.data("is_locked", "")
                    .attr("title", "")
                    .parent().find("input[name=locked]").val("");
            }
        },
        "json").done(function () {
        $this.tikiModal();
    });

    return false;
}


/**
 * Remove accents from chars
 * public/generated/js/vendor_dist/tablesorter/dist/js/jquery.tablesorter.combined.js:2373
 *
 * @param str
 */
(function setup_removediacritics_function(){
    var characterEquivalents = {
    'a' : '\u00e1\u00e0\u00e2\u00e3\u00e4\u0105\u00e5', // áàâãäąå
    'A' : '\u00c1\u00c0\u00c2\u00c3\u00c4\u0104\u00c5', // ÁÀÂÃÄĄÅ
    'c' : '\u00e7\u0107\u010d', // çćč
    'C' : '\u00c7\u0106\u010c', // ÇĆČ
    'e' : '\u00e9\u00e8\u00ea\u00eb\u011b\u0119', // éèêëěę
    'E' : '\u00c9\u00c8\u00ca\u00cb\u011a\u0118', // ÉÈÊËĚĘ
    'i' : '\u00ed\u00ec\u0130\u00ee\u00ef\u0131', // íìİîïı
    'I' : '\u00cd\u00cc\u0130\u00ce\u00cf', // ÍÌİÎÏ
    'o' : '\u00f3\u00f2\u00f4\u00f5\u00f6\u014d', // óòôõöō
    'O' : '\u00d3\u00d2\u00d4\u00d5\u00d6\u014c', // ÓÒÔÕÖŌ
    'ss': '\u00df', // ß (s sharp)
    'SS': '\u1e9e', // ẞ (Capital sharp s)
    'u' : '\u00fa\u00f9\u00fb\u00fc\u016f', // úùûüů
    'U' : '\u00da\u00d9\u00db\u00dc\u016e' // ÚÙÛÜŮ
    };

    var characterRegex, characterRegexArray;

    function removeDiacritics(str) {
        var chr,
            acc = '[',
            eq = characterEquivalents;

        if ( !characterRegex ) {
            characterRegexArray = {};
            for ( chr in eq ) {
                if ( typeof chr === 'string' ) {
                    acc += eq[ chr ];
                    characterRegexArray[ chr ] = new RegExp( '[' + eq[ chr ] + ']', 'g' );
                }
            }
            characterRegex = new RegExp( acc + ']' );
        }
        if ( characterRegex.test( str ) ) {
            for ( chr in eq ) {
                if ( typeof chr === 'string' ) {
                    str = str.replace( characterRegexArray[ chr ], chr );
                }
            }
        }
        return str;
    }

    window.removeDiacritics = removeDiacritics;
})();

/**
 * Fetch feedback sent through ajax and place into the tikifeedback div that is on each page
 * If tikifeedback div is outside of the viewport, place it at the top of the viewport and have it move to the
 * normal position (first element in div#col1) after 5 seconds
 */
$(document).on("ajaxComplete", function (e, jqxhr) {
    var feedback = jqxhr.getResponseHeader('X-Tiki-Feedback'),
        tfb = $('#tikifeedback');
    if (feedback) {
        feedback = decodeURIComponent(feedback); // decodeURIComponent() reverses rawurlencode().
        tfb.fadeIn(200, function() {
            //place html from ajax X-Tiki-Feedback into the div#tikifeedback
            tfb.html($($.parseHTML(feedback)).filter('#tikifeedback').html());
            tfb.find('div.alert').each(function() {
                var    title = $(this).find('span.rboxtitle').text().trim(),
                    content = $(this).find('div.rboxcontent').text().trim();
                $(this).find('span.rboxtitle').text(title);
                $(this).find('div.rboxcontent').text(content);
            });
            //place tikifeedback div into window view if necessary
            placeFeedback(tfb);
        });
    }
    tfb.find('.clear').on('click', function () {
        $(tfb).empty();
        //move back to usual position and clear style attribute so subsequent feedback appears properly
        $('div#col1').prepend(tfb);
        tfb.css({'z-index':'', 'position':'', 'top': ''});
        return true;
    });
});

$(document).on('keydown', 'textarea.autoheight', function(evt){
    var el = this;
    var height = Math.max(el.clientHeight, el.offsetHeight, el.scrollHeight);
    el.style.cssText = 'height:' + height + 'px; overflow-y: hidden';
    setTimeout(function(){
        el.scrollTo(0,0);
    }, 0);
});

$(document).on('change', '.preference :checkbox:not(.pref-reset)', function () {
    var childBlock = $(this).data('tiki-admin-child-block')
        , childMode = $(this).data('tiki-admin-child-mode')
        , checked = $(this).is(':checked')
        , disabled = $(this).prop('disabled')
        , $depedencies = $(this).parents(".adminoption").find(".pref_dependency")
        , childrenElements = null
    ;
    var childrenElements = $(this).parents('.adminoptionbox').nextAll('.adminoptionboxchild').eq(0).find(':input[id^="pref-"]');

    if (childBlock) {
        childrenElements = $(childBlock).find(':input[id^="pref-"]');
    }

    if (childMode === 'invert') {
        // FIXME: Should only affect childBlock, not $depedencies. From r54386
        checked = ! checked;
    }

    if (disabled && checked) {
        $(childBlock).show('fast');
        $depedencies.show('fast');
    } else if (disabled || ! checked) {
        /* Only hides child preferences if they are all at default values.
        Purpose questioned in https://sourceforge.net/p/tikiwiki/mailman/tikiwiki-cvs/thread/F2DE8896807BF045932776107E2E783D350674DB%40CT20SEXCHP02.FONCIERQC.INTRA/#msg36171225
         */
        var hideBlock = true;
        childrenElements.each(function( index ) {
            var value = $( this ).val();
            var valueDefault = $( this ).parents('div.col').siblings('div.tikihelp-reset-wrapper').children('span.pref-reset-wrapper').find('.pref-reset').attr('data-preference-default');

            if ($( this ).is(':checkbox')) {
                valueDefault = $( this ).parents('div.flex-shrink-1').siblings('div.tikihelp-reset-wrapper').children('span.pref-reset-wrapper').find('.pref-reset').attr('data-preference-default');
            }
            if (typeof valueDefault != 'undefined' && value != valueDefault) {
                hideBlock = false;
            }
        });

        if (hideBlock) {
            $(childBlock).hide('fast');
            $depedencies.hide('fast');
        }
    } else {
        $(childBlock).show('fast');
        $depedencies.show('fast');
    }
});

$(document).on('click', '.pref-reset-wrapper a', function () {
    var box = $(this).closest('span').find(':checkbox');
    box.trigger("click");
    $(this).closest('span').children( ".pref-reset-undo, .pref-reset-redo" ).toggle();
    return false;
});

$(document).on('click', '.pref-reset', function() {
    var c = $(this).prop('checked');
    var $el = $(this).closest('.adminoptionbox').find('input:not(:hidden),select,textarea')
        .not('.system').attr( 'disabled', c )
        .css("opacity", c ? .6 : 1 );
    var defval = $(this).data('preference-default');

    if ($el.is(':checkbox')) {
        $(this).data('preference-default', $el.prop('checked') ? 'y' : 'n');
        $el.prop('checked', defval === "y");
    } else {
        $(this).data('preference-default', $el.val());
        $el.val(defval);
    }
    $el.trigger("change");
    if (jqueryTiki.select2) {
        $el.trigger("change.select2");
    }
});

$(document).on('change', '.preference select', function () {
    var childBlock = $(this).data('tiki-admin-child-block')
        , selected = $(this).find('option:selected')
        , childMode = $(this).data('tiki-admin-child-mode')
    ;

    $(childBlock).hide();
    $(childBlock + ' .modified').show();
    $(childBlock + ' .modified').parent().show();

    selected.each(function(index, option) {
        var value = option.value;
        if (value && /^[\w-]+$/.test(value)) {
            $(childBlock).filter('.' + value).show();
        }
        if (childMode === 'notempty' && value.length) {
            $(childBlock).show();
        }
    });
});

$(document).on('change', '.preference :radio', function () {
    var childBlock = $(this).data('tiki-admin-child-block');

    if ($(this).prop('checked')) {
        $(childBlock).show('fast');
        $(this).closest('.preference').find(':radio').not(this).trigger("change");
    } else {
        $(childBlock).hide();
    }
});

$(document).on('change', '.checkbox_plugin', function () {
    var checkbox = $(this);
    $.post($.service('edit', 'updateChecklistItem'), {
        objectType: checkbox.data('object-type'),
        objectId: checkbox.data('object-id'),
        fieldName: checkbox.data('field-name'),
        checkboxNum: checkbox.data('order'),
    }, function (data) {
        checkbox.prop('checked', data.state);
        feedback(
            tr('Checklist item updated'),
            'success',
            false,
            tr('Content changed')
        );
    });
});

function hexToRgb(hex) {
    // Remove the leading '#' if present
    hex = hex.replace(/^#/, '');

    // Parse short (3 digits) or long (6 digits) hex format
    if (hex.length === 3) {
        hex = hex.split('').map(char => char + char).join('');
    }

    const num = parseInt(hex, 16);
    return [num >> 16, (num >> 8) & 255, num & 255];
}

function parseRgbString(rgbString) {
    // Extract RGB values from rgb or rgba string
    const rgb = rgbString.match(/\d+/g).map(Number);
    return rgb.length === 4 ? rgb.slice(0, 3) : rgb; // Ignore alpha channel if rgba
}

function isDarkColor(color) {
    let rgb;

    if (color.startsWith('#')) {
        rgb = hexToRgb(color);
    } else if (color.startsWith('rgb')) {
        rgb = parseRgbString(color);
    } else {
        // Handle named colors by using a div as fallback only when necessary
        const tempDiv = document.createElement('div');
        tempDiv.style.color = color;
        // Avoid rendering the div on the page
        tempDiv.style.position = 'absolute';
        tempDiv.style.left = '-9999px';

        document.body.appendChild(tempDiv);
        rgb = parseRgbString(window.getComputedStyle(tempDiv).color);
        document.body.removeChild(tempDiv);
    }

    // Calculate luminance
    const luminance = 0.299 * rgb[0] + 0.587 * rgb[1] + 0.114 * rgb[2];
    return luminance < 128; // If luminance is less than 128, it's a dark color
}

$(function () {
    $('.preference :checkbox, .preference select, .preference :radio').trigger("change");

    const bodyBackgroundColor = window.getComputedStyle(document.body).backgroundColor;
    if (isDarkColor(bodyBackgroundColor)) {
        document.body.setAttribute('data-bg-theme', 'dark');
    } else {
        document.body.setAttribute('data-bg-theme', 'light');
    }
});

$(document).ready(function() {
    $(".range_slider").each(function() {
      var range_slider = $(this);
      var range_selector = range_slider.siblings(".range_selector");
      var range_selectValue = range_selector.find(".range_selectValue");
      var range_progressBar = range_slider.siblings(".range_progressBar");
      var min = parseInt(range_slider.attr("min"));
      var max = parseInt(range_slider.attr("max"));

      range_selectValue.html(range_slider.val());
      range_slider.on("input", function() {
        range_selectValue.html($(this).val());
        var width = ((parseInt($(this).val()) - min) / (max - min)) * 100;
        range_selector.css("left", width + "%");
        range_progressBar.css("width", width + "%");
      });
    });

    const elementsWithTitle = document.querySelectorAll('[title]');

    elementsWithTitle.forEach(element => {
        const titleText = element.getAttribute('title');
        element.setAttribute('data-mobile-tooltip', titleText);
        $(element).on('touchstart', function() {
            element.classList.toggle('show-tooltip');
        });
        $(element).on('touchend', function() {
            element.classList.remove('show-tooltip');
        });
    });
});

$(document).tiki('copy')(null, function(element) {
    setTimeout(function()
    {
        element.trigger.firstChild.innerHTML = tr('Copy to clipboard');
    }, 2000);
    element.clearSelection();
    element.trigger.firstChild.innerHTML = tr('Copied to clipboard');
}, function() {
    alert(tr("Press Ctrl+C to copy"));
}, '.icon_copy_code');

$(document).tiki('copy')(null, function(element) {
    $.notify(tr('Copied to clipboard'), { speed: 500 });
}, function() {
    alert(tr("Press Ctrl+C to copy"));
}, '.copy');

/**
 * Display a toast notification with customizable options.
 *
 * @param {Object} options - The options for customizing the toast notification.
 *                           Available options:
 *                             - body: The content of the toast notification (default: "").
 *                             - title: The title of the toast notification (default: null).
 *                             - position: The position of the toast notification (default: "top-end").
 *                             - delay: The delay before the toast notification disappears (default: "5000").
 *                             - autohide: Whether the toast notification should automatically disappear (default: true).
 *                             - classes: Additional CSS classes for styling the toast notification (default: "").
 *                             - style: Inline CSS styles for styling the toast notification (default: "").
 *                             - onClose: Callback function to be invoked when the toast notification is closed (default: null).
 */

$.fn.toastNotification = function (options) {
    // Default options
    const defaultOptions = {
        body: "",
        title: null,
        position: "top-end",
        delay: "5000",
        autohide: true,
        classes: "",
        style: "",
        onClose: null // Close callback function
    };

    // Merge user-provided options with default options
    options = $.extend({}, defaultOptions, options);

    window.toast_notifications = window.toast_notifications || 0;
    let positions_classes = {
        "top-end": "top-0 end-0",
        "top-start": "top-0 start-0",
        "bottom-end": "bottom-0 end-0",
        "bottom-start": "bottom-0 start-0"
    };
    let current_position = positions_classes.hasOwnProperty(options.position) ? positions_classes[options.position] : "top-0 end-0";
    let classes_array = current_position.split(" ");
    let toastwrapper = this.find(`.toast-wrapper .toast-container.${classes_array[0]}.${classes_array[1]}`); //this help us have different containers per position
    if (!toastwrapper.length) {
        this.append(`<div aria-live="polite" aria-atomic="true" class="position-relative toast-wrapper"><div class="toast-container position-fixed ${current_position} p-3"></div></div>`);
        toastwrapper = this.find(`.toast-wrapper .toast-container.${classes_array[0]}.${classes_array[1]}`);
    }
    const currentid = `liveToast-${window.toast_notifications + 1}`;
    const toastElement = $(`<div id="${currentid}" class="toast" data-bs-delay="${options.delay}" data-bs-autohide="${options.autohide ? 'true' : 'false'}" role="alert" aria-live="assertive" aria-atomic="true"></div>`);
    if (options.title) {
        if (options.classes) toastElement.addClass(options.classes);
        if (options.style) toastElement.attr("style", options.style);
        toastElement.html(`
            <div class="toast-header">
                <i class="bi bi-info"></i>
                <strong class="me-auto">${options.title}</strong>
                <button type="button" onclick="window.delete_toast('${currentid}')" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${options.body}
            </div>`);
    }
    else {
        if (options.classes) toastElement.addClass(options.classes);
        if (options.style) toastElement.attr("style", options.style);
        toastElement.addClass("align-items-center");
        toastElement.html(`
            <div class="d-flex">
                <div class="toast-body">
                    ${options.body}
                </div>
                <button type="button" onclick="window.delete_toast('${currentid}')" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>`);
    }
    toastwrapper.prepend(toastElement);
    const toastBootstrap = bootstrap.Toast.getOrCreateInstance($(`#${currentid}`).get(0));
    toastBootstrap.show();
    window.toast_notifications += 1;

    window.delete_toast = (id) => {
        // Call the close callback function if provided
        if (options.onClose && typeof options.onClose === "function") {
            options.onClose();
        }

        (function () {
            setTimeout(function () { $(`#${id}`).remove(); }, 1000);
        })();
    };

    if (options.autohide) {
        (function () {
            setTimeout(function () { window.delete_toast(currentid); }, options.delay);
        })(options.delay, currentid);
    }
};
