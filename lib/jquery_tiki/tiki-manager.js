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

    var $vmform = $('#virtualmin-create-form');
    var limit_available_versions = function() {
        var $source = $vmform.find('select[name=source]');
        var $version = $vmform.find('select[name=php_version]');
        var $branch = $vmform.find('select[name=branch]');
        if (! $source.val()) {
            return;
        }
        ajaxLoadingShow($version[0]);
        $.ajax({
            url: $.service('manager', 'available_versions'),
            data: {
                source: $source.val(),
                php_version: $version.val(),
            },
            dataType: 'json',
            success: function(data) {
                ajaxLoadingHide();
                var old_val = $version.val();
                $version.empty().append("<option value=''></option>");
                if (data.php_versions) {
                    for(var i = 0, l = data.php_versions.length; i < l; i++) {
                        $version.append("<option value='"+data.php_versions[i]+"'>"+data.php_versions[i]+"</option>");
                    }
                }
                $version.val(old_val);
                old_val = $branch.val();
                $branch.empty().append("<option value=''></option>");
                if (data.available_branches) {
                    for(var i = 0, l = data.available_branches.length; i < l; i++) {
                        $branch.append("<option value='"+data.available_branches[i]+"'>"+data.available_branches[i]+"</option>");
                    }
                }
                $branch.val(old_val);
            },
            error: function() {
                ajaxLoadingHide();
            }
        });
    }
    if ($vmform.length > 0) {
        $vmform.find('select[name=source], select[name=php_version]').on('change', limit_available_versions);
    }
})();
