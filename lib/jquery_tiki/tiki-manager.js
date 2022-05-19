/**
 * (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
 *
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
 * $Id$
 */

(function () {
    var $container = $('.tiki-manager-command');

    $('#check', $container).hide();
    $('#copy', $container).show().on('click', function() {
        var $temp = $("<input>");
        $("body").append($temp);
        $temp.val($('#command', $container).html()).select();
        document.execCommand("copy");
        $temp.remove();

        $('#copy', $container).hide();
        $('#check', $container).show();
    });

    var $form = $('#tiki-manager-create-instance');

    $('#host', $form).on('change', function() {
        $('#command', $container).text($('#command', $container).text().replace(/@.*$/, '@'+$(this).val()));
    });

    $('#user', $form).on('change', function() {
        $('#command', $container).text($('#command', $container).text().replace(/ [^ ]+@/, ' '+$(this).val()+'@'));
    });

    $('#port', $form).on('change', function() {
        $('#command', $container).text($('#command', $container).text().replace(/ -p [^ ]+/, ' -p '+$(this).val()));
    });
})();
