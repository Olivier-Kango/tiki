$('.intertrans').find('*').addClass('intertrans');
$('#intertrans-modal form :reset').on("click", function(e) {
    e.preventDefault();
    $('#intertrans-modal').modal('hide');
} );

if (localStorage.getItem("isCheckActiveTrans") === "true") {
    $("#intertrans-active").prop("checked", true);
}

var interTransDone = false;
$('#intertrans-modal form').on("submit", function( e ) {
    e.preventDefault();
    $('body, input[type="submit"]').css('cursor', 'wait');

    $.ajax({
        url: $(this).attr('action'),
        data: $(this).serialize(),
        method: 'POST',
        success: function() {
            $('body').css('cursor', 'default');
            $('input[type="submit"]').css('cursor', 'pointer');
            $('#intertrans-modal').hide();
            interTransDone = true;
            document.location.href = document.location.href.replace(/#.*$/, "");
        }
    });

 } );

var canTranslateIt = function(e) {
    var $target = $(e.currentTarget);

    return $('#intertrans-active:checked').length > 0
        && !$target.closest('.intertrans, #intertrans-form').length;
};

var interTransDeepestElement = -1;

$('#intertrans-active').on("click", function( e ) {

    var isActive = $("#intertrans-active").is(":checked");
    if (isActive) {
        localStorage.setItem("isCheckActiveTrans", "true");
    } else {
        localStorage.setItem("isCheckActiveTrans", "false");
    }

    if (interTransDone && !$(this).prop("checked")) {
        history.go(0);
    }

    if ($.lang != 'en') {
        if (!$('#intertrans-active').is(":checked")) {
            $('.to-translate').removeClass('to-translate');
            localStorage.setItem("isCheckActive", "false");
        } else {
            for (let i in data) {
                let original = data[i][0].trim();
                let translated = data[i][1].trim();
                let isTranslated = data[i][2];

                if (original != '' && !isTranslated) {
                    let needTranslate = $('.container *:contains("' + original + '")');
                    needTranslate.each(function() {
                        if ($(this).text().trim() == original) {
                            $(this).addClass('to-translate');
                        }
                    });
                }
            }
        }
    }
});

$("#disableTranslation").on("click", function () {
    localStorage.setItem("isCheckActiveTrans", "false");
});

// Delegate these events so content added by modals, dropdowns, and popovers is
// available to interactive translation without binding duplicate handlers.
var interactiveTranslationTargets = '.container *, .modal-content *, .dropdown-menu *, .popover *';

function isInteractiveFormControl($target) {
    return $target.is('select, option, textarea, input:not([type="button"]):not([type="submit"]):not([type="reset"])');
}

function getTextToTranslate($target) {
    if (isInteractiveFormControl($target)) {
        return null;
    }
    if ($target.is('.modal-content, .modal-body, .modal-header, .modal-footer, .accordion, .card-accordion')) {
        return null;
    }
    if ($target.children().length && $target.is('div, section, main, nav, aside, ul, ol, table, thead, tbody, tfoot, tr, form, fieldset')) {
        return null;
    }

    // Do not use all descendant text from generic wrappers: a click on an icon
    // could otherwise select the text of an entire module, modal, or page.
    var text = $target.is('a, button, label, legend')
        ? $target.text().trim()
        : $target.contents().filter(function() {
            return this.nodeType === 3;
        }).text().trim();
    var val = $target.is('input[type="button"], input[type="submit"], input[type="reset"]') ? $target.val() : null;
    var alt = $target.attr('alt');
    var title = $target.attr('title');

    if (!text && !val && !alt && !title) {
        return null;
    }

    return { text: text, val: val, alt: alt, title: title };
}

$(document).on("click.interactiveTranslation", interactiveTranslationTargets, function (e) {
    if (!canTranslateIt(e)) { return; }
    if (isInteractiveFormControl($(e.target))) { return; }

    var $target = $(this);
    var content = getTextToTranslate($target);
    if (!content) { return; }

    e.preventDefault();
    e.stopImmediatePropagation();
    findTextToTranslate(content.text, content.val, content.alt, content.title);

    var popoverId = $target.closest('.popover').attr('id');
    if (popoverId) {
        $('[aria-describedby]').filter(function() {
            return $(this).attr('aria-describedby') === popoverId;
        }).popover('hide');
    }
}).on("mouseenter.interactiveTranslation", interactiveTranslationTargets, function (e) {
    if (!canTranslateIt(e)) { return; }
    var $this = $(this);

    if ($this.hasClass('dropdown-toggle')) {
        $this.trigger('click.bs.dropdown');
    }
    if (!getTextToTranslate($this)) { return; }

    var myparents = $this.parents();
    if (myparents.length > interTransDeepestElement) {    // trying to only highlight one element at a time
        var shad = "black 0 0 5px";
        $this.css({ "box-shadow": shad, "-moz-box-shadow": shad, "-webkit-box-shadow": shad });
        $(myparents[interTransDeepestElement]).css({ "box-shadow": "", "-moz-box-shadow": "", "-webkit-box-shadow": "" });
        interTransDeepestElement = myparents.length;
    }
}).on("mouseleave.interactiveTranslation", interactiveTranslationTargets, function (e) {
    if (!canTranslateIt(e)) { return; }
    $(this).css({ "box-shadow": "", "-moz-box-shadow": "", "-webkit-box-shadow": "" });
    interTransDeepestElement = -1;
});

// Global function to be called from Vue component when an item is selected
window.vueselectIntertransHandler = function (selectedOption) {
    if (!$("#intertrans-active").is(":checked")) {
        return;
    }

    if (!selectedOption || !selectedOption.label) {
        return;
    }

    findTextToTranslate(selectedOption.label, null, null, null);
};

// Function to find occurrences of text, val, alt, or title in the data array
// and populate the modal with the results
function findTextToTranslate(text, val, alt, title) {
    // data is defined on lib/smarty_tiki/function.interactivetranslation.php
    var applicable = $(data).filter(function (k) {
        var textToSearchFor = $('<span>' + this[1] + '</span>').text(); // The spans just make sure this calls jQuery( html ) instead of another jQuery constructor. text() will strip them.
        return textToSearchFor.length && ((text && text.length && text.indexOf(textToSearchFor) != -1)
            || (val && val.length && val.indexOf(textToSearchFor) != -1)
            || (alt && alt.length && alt.indexOf(textToSearchFor) != -1)
            || (title && title.length && title.indexOf(textToSearchFor) != -1));
    });

    $('#intertrans-table table tbody').empty();

    if (applicable.length > 0) {
        $('#intertrans-empty').hide();
        $('#intertrans-close').hide();
        $('#intertrans-submit').show();
        $('#intertrans-cancel').show();
        $('#intertrans-help').show();

        $('#intertrans-table table tbody')
            .append(applicable.map(function () {
                var r = $('<tr><td class="original"></td><td><textarea name="trans[]" class="form-control"></textarea><input type="hidden" name="source[]"/></td></tr>');
                r.find('td.original').text(this[0]);
                if (this[2]) {    // new ones in italic
                    r.find('td.original').css("font-style", 'italic');
                }
                r.find(':hidden').val(this[0]);
                r.find(':text').val(this[1]);
                r.find('textarea').val(reverseArgReplace(this[1], this[3]));
                return r[0];
            }));
    } else {
        // set text in span with this id intertrans-string-to-translate
        $("#intertrans-string-to-translate")
            .empty()
            .append('"', $('<b>').text(text || val || alt || title), '"');
        $('#intertrans-empty').show();
        $('#intertrans-close').show();
        $('#intertrans-submit').hide();
        $('#intertrans-cancel').hide();
        $('#intertrans-help').hide();
    }

    $('#intertrans-modal').modal('show');
    $('#intertrans-modal').find('textarea:visible, input:visible').first().trigger('focus');
}

/**
 * Replace argument values in the content with their placeholders.
 *
 * @param {string} content Text with argument values
 * @param {string[]} args Array of argument values
 * @returns {string} Text with arguments replaced by their placeholders
 */
function reverseArgReplace(content, args) {
    if (!args || args.length === 0 || !content) return content;

    const matches = [];

    args.forEach((val, i) => {
        if (!val) return;
        let offset = 0;
        while (true) {
            const pos = content.indexOf(val, offset);
            if (pos === -1) break;
            matches.push({
                start: pos,
                length: val.length,
                arg: i
            });
            offset = pos + 1;
        }
    });

    if (matches.length === 0) return content;

    matches.sort((a, b) => b.length - a.length || a.start - b.start);

    const selected = [];
    matches.forEach(m => {
        const s = m.start;
        const e = s + m.length;
        let overlap = selected.some(sel => !(e <= sel.start || s >= sel.start + sel.length));
        if (!overlap) selected.push(m);
    });

    if (selected.length === 0) return content;

    selected.sort((a, b) => a.start - b.start);
    let out = '';
    let cursor = 0;
    selected.forEach(m => {
        const start = m.start;
        const len = m.length;
        const argIndex = m.arg;

        if (cursor < start) {
            out += content.slice(cursor, start);
        }

        out += `%${argIndex}`;
        cursor = start + len;
    });

    if (cursor < content.length) {
        out += content.slice(cursor);
    }

    return out;
}
