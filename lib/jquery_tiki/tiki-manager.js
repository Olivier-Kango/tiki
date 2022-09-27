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

    var generate_password = function() {
        var length = 13;
        chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        password = "";
        for (var i = 0, n = chars.length; i < length; ++i) {
            password += chars.charAt(Math.floor(Math.random() * n));
        }
        return password;
    }

    function tikipassword_options() {
        $("#tikipassword").val(generate_password);

        $('#passwordgenerate').click(function() {
            $("#tikipassword").val(generate_password);
        });

        $('#togglepassword').click(function() {
            const tiki_password = document.getElementById('tikipassword');
            const icon_change = document.getElementById('icon-change');
            const type = tiki_password.getAttribute('type') === 'password' ? 'text' : 'password';
            tiki_password.setAttribute('type', type);
            if (type === 'text') {
                icon_change.classList.add("fa-eye-slash");
                icon_change.classList.remove("fa-eye");
            } else {
                icon_change.classList.add("fa-eye");
                icon_change.classList.remove("fa-eye-slash");
            }
        });
    }

    // Calling for Tiki Manager Package
    tikipassword_options();

    // Calling for Plugin TikiManager
    $('#bootstrap-modal').on('shown.bs.modal', function (e) {
        setTimeout(function() {
            tikipassword_options();
        }, 2000);
    })

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

    var $instance_backup_form = $("#tiki-manager-backup-instance");
    if ($instance_backup_form.length > 0) {
        $instance_backup_form.on('submit', function(){
            ajaxLoadingShow('tiki-manager-backup-instance');
        });
    }
})();
