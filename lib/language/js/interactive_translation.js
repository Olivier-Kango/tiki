$('.intertrans').find('*').addClass('intertrans');
$('#intertrans-modal form :reset').on("click", function() {
    $('#intertrans-modal').modal('hide');
    return false;
} );

if (localStorage.getItem("isCheckActiveTrans") === "true") {
    $("#intertrans-active").attr("checked", true);
}

$('body').css('padding-top', 64);

var interTransDone = false;
$('#intertrans-modal form').on("submit", function( e ) {
    e.preventDefault();
    $('body, input[type="submit"]').css('cursor', 'wait');

    $.ajax({
        url: $(this).attr('action'),
        data: $(this).serialize(),
        success: function() {
            $('body').css('cursor', 'default');
            $('input[type="submit"]').css('cursor', 'pointer');
            $('#intertrans-modal').hide();
            interTransDone = true;
            document.location.href = document.location.href.replace(/#.*$/, "");
        }
    });

    return false;
} );


var canTranslateIt = function( e ) {
    if( $('#intertrans-active:checked').length == 0 ||
            e.currentTarget.id.indexOf('intertrans-') === 0 ||
            $(e.currentTarget).parents("form.intertrans, #intertrans-form").length > 0 ) {
        return false;
    } else {
        return true;
    }
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

$(document).find('.container *').on("click", function( e ) {
    if( !canTranslateIt( e ) ) { return; }

    e.preventDefault();
    var text = $(this).text();
    var val = $(this).val();
    var alt = $(this).attr('alt');
    var title = $(this).attr('title');
    if ($(this).parent().hasClass('tikihelp')
        || $(this).parent().hasClass('titletips')
        || $(this).parent().parent().hasClass('tips'))
    {
    }

    // data is defined on lib/smarty_tiki/function.interactivetranslation.php
    var applicable = $(data).filter( function( k ) {
        var textToSearchFor = $('<span>' + this[1] + '</span>').text(); // The spans just make sure this calls jQuery( html ) instead of another jQuery constructor. text() will strip them.
        return textToSearchFor.length && (( text && text.length && text.indexOf( textToSearchFor ) != -1 )
            || ( val && val.length && val.indexOf( textToSearchFor ) != -1 )
            || ( alt && alt.length && alt.indexOf( textToSearchFor ) != -1 )
            || ( title && title.length && title.indexOf( textToSearchFor ) != -1 ));
    } );

    $('#intertrans-table table tbody').empty();

    if (applicable.length > 0) {
        $('#intertrans-empty').hide();
        $('#intertrans-close').hide();
        $('#intertrans-submit').show();
        $('#intertrans-cancel').show();
        $('#intertrans-help').show();

        $('#intertrans-table table tbody')
            .append( applicable.map( function() {
                var r = $('<tr><td class="original"></td><td><textarea name="trans[]" class="form-control"></textarea><input type="hidden" name="source[]"/></td></tr>');
                r.find('td.original').text( this[0] );
                if (this[2]) {    // new ones in italic
                    r.find('td.original').css("font-style", 'italic');
                }
                r.find(':hidden').val( this[0] );
                r.find(':text').val( this[1] );
                r.find('textarea').val(this[1]);
                return r[0];
            } ) );
    } else {
        $('#intertrans-empty').show();
        $('#intertrans-close').show();
        $('#intertrans-submit').hide();
        $('#intertrans-cancel').hide();
        $('#intertrans-help').hide();
    }

    $('#intertrans-modal').modal('show').on("keydown", function (e) {
        }).find("input").first().trigger("focus");
    return false;
} ).on("mouseover", function( e ) {
    if( !canTranslateIt( e ) ) { return; }
    var $this = $(this);

    if($this.hasClass('dropdown-toggle')) {
        $this.trigger('click.bs.dropdown');
    }

    var myparents = $this.parents();
    if ( myparents.length > interTransDeepestElement ) {    // trying to only highlight one element at a time
        var shad = "black 0 0 5px";
        $this.css({"box-shadow":shad, "-moz-box-shadow":shad, "-webkit-box-shadow":shad});
        $(myparents[interTransDeepestElement]).css({"box-shadow":"", "-moz-box-shadow":"", "-webkit-box-shadow":""});
        interTransDeepestElement = myparents.length;
    }
}).on("mouseout", function( e ) {
    if( !canTranslateIt( e ) ) { return; }
    $(this).css({"box-shadow":"", "-moz-box-shadow":"", "-webkit-box-shadow":""});
    interTransDeepestElement = -1;
});
