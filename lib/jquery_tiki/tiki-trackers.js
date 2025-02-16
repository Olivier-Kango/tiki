// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
(function ($) {

    $.fn = $.extend($.fn, {
        /**
         * options:
         *     trackerId: int
         *     success: function (data) {}
         */
        tracker_add_field: function (options) {
            var dialog = this;
            $('select', dialog).on("change", function () {
                var descriptions = $(this).closest('.tracker-field-group').find('.form-text')
                    .hide();

                if ($(this).val()) {
                    descriptions
                        .filter('.' + $(this).val())
                        .show();
                }
            }).trigger("change");

            $('form', dialog).each(function () {
                var form = this;
                $(form.name).on("keyup", function () {
                    var val = $("#fieldPrefix").val() + " " + $(this).val();
                    val = removeDiacritics(val);
                    val = val.replace(/[^\w]+/g, '_');
                    val = val.replace(/_+([a-zA-Z])/g, function (parts) {
                        return parts[1].toUpperCase();
                    });
                    val = val.replace(/^[A-Z]/, function (parts) {
                        return parts[0].toLowerCase();
                    });
                    val = val.replace(/_+$/, '');

                    $(form.permName).val(val);
                });

                $(form.submit_and_edit).on("click", function () {
                    $(form.next).val('edit');
                });
            });
        },

        tracker_load_fields: function (trackerId) {
            var element = this;
            this.each(function () {
                var $container = $(this).empty();
                element.tikiModal(tr('Loading...'));
                $.getJSON($.service('tracker', 'list_fields'), {
                    trackerId: trackerId
                }, function (data) {
                    element.tikiModal();
                    $.each(data.fields, function (k, field) {
                        var $row = $('<tr/>').addClass("tracker-field-" + field.type);
                        $row.append($('<td class="checkbox-cell"/>').append($('<input type="checkbox" name="fields[]"/>').val(field.fieldId)));
                        $row.append($('<td class="id"/>')
                            .text(field.fieldId)
                            .append($('<input type="hidden" name="field~' + field.fieldId + '~position"/>').val(field.position))
                        );
                        $row.append(
                            $('<td/>')
                            .append(
                                $('<div class="small">')
                                    .text(field.permName)
                                    .append($(`<a href="#" id="copy${field.fieldId}" class="text-info pl-1 tips small" title="${tr("Copy permanent name")}
                                                "><span class="icon fas fa-copy"/></a>`)
                                        .tiki('copy')(() => field.permName, function () {
                                            $(this).animate({ opacity: .5 }, 200, function () {
                                                $(this).animate({ opacity: 1 }, 500);
                                            });
                                        })
                                    )
                            )
                            .prepend($('<a/>')
                                .text(field.name == null?" ":field.name)
                                .attr('href', $.service('tracker', 'edit_field', {trackerId: trackerId, fieldId: field.fieldId, modal: 1}))
                                .clickModal({
                                    success: function () {
                                        $.fn.resetFieldsCache();
                                        $container.tracker_load_fields(trackerId);
                                        $.closeModal();
                                    }
                                })
                        ));
                        if (data.types[field.type]) {
                            $row.append($('<td/>').text(data.types[field.type].name));
                            if (field.options_map.mirrorField) {
                                $row.find('td').last().append($('<div class="small">').text(tr('Mirror field')+': '+field.options_map.mirrorField));
                            }

                            var addCheckbox = function (name, title, disabled = false) {
                                $row.append($('<td class="checkbox-cell"/>').append(
                                    $('<input type="checkbox" name="field~' + field.fieldId + '~' + name + '" value="1" title="' + title + '"/>')
                                        .prop('checked', field[name] === 'y').prop('disabled', disabled === true)
                                ));
                            };

                            if ($("#rulesColumn").length) {
                                if (field.rules) {
                                    let rulesString = "";

                                    // reformat the ruels for a basic preview, slightly prettier than raw JSON
                                    $.each(JSON.parse(field.rules), function (part, rule) {
                                        rulesString += tr(part);
                                        if (rule && rule.hasOwnProperty("predicates")) {
                                            rulesString += " (" + tr(rule.logicalType_id) + "):\n";
                                            rule.predicates.forEach(function (predicate) {
                                                rulesString += "    " + predicate.target_id + " " +
                                                    predicate.operator_id + " " +
                                                    (predicate.argument !== null ? "\"" + predicate.argument + "\"" : "") +
                                                    "\n";
                                            });
                                        } else {
                                            rulesString += ":\n";
                                        }
                                    });

                                    $row.append($('<td class="text-info" />').append(
                                            $().getIcon("ok")
                                                .attr("title", tr("Rules") + "|<pre>" + rulesString + "</pre>")
                                                .addClass("tips")
                                    ).tiki_popover());
                                } else {
                                    $row.append($('<td />'));
                                }
                            }

                            addCheckbox('isTblVisible', tr('List'));
                            addCheckbox('isMain', tr('Main'));
                            addCheckbox('isSearchable', tr('Searchable'), $.inArray(field.type, ['h']) !== -1);
                            addCheckbox('isPublic', tr('Public'));
                            addCheckbox('isMandatory', tr('Mandatory'));

                            $row.append($('<td class="action"/>').append($('<a href="#" class="text-danger"><span class="icon fas fa-times"/></a>')
                                .attr('href', $.service('tracker', 'remove_fields', {trackerId: trackerId, 'fields~0': field.fieldId}))
                                .requireConfirm({
                                    message: tr('Removing the field will result in data loss. Are you sure?'),
                                    success: function (data) {
                                        $(this).closest('tr').remove();
                                        $.fn.resetFieldsCache();
                                    }
                                })
                            ));
                        } else if (data.typesDisabled) {
                            if (data.typesDisabled[field.type]) {
                                $row.find('td').last()
                                    .append(' - <a class="ui-state-error" href="tiki-admin.php?lm_criteria=' + data.typesDisabled[field.type].prefs.join('+') + '&exact">' + tr('(Disabled, Click to Enable)') + '</a>');
                            }
                        }

                        $container.append($row);

                        if (data.duplicates && data.duplicates[field.fieldId]) {
                            $.each(data.duplicates[field.fieldId], function (k, v) {
                                $row = $('<tr class="bg-warning"/>');
                                $row.append($('<td colspan="2"/>'));
                                $row.append($('<td colspan="8"/>')
                                    .append(v.message)
                                );
                                $container.append($row);
                            });
                        }
                    });
                });
            });

            return this;
        },
        tracker_get_inputs_from_form: function() {
            var fields = {};

            $.each($(this).serializeArray(), function() {
                if (this.name.substr(-2) == '[]') {
                    if (typeof fields[this.name] === 'undefined') {
                        fields[this.name] = [];
                    }
                    fields[this.name].push(this.value);
                } else {
                    fields[this.name] = this.value;
                }
            });

            if ($(this).data('ajax')) {
                fields['ajax'] = true;
            }

            return fields;
        },
        tracker_insert_item: function(options, fn) {
            $.extend( options, $(this).tracker_get_inputs_from_form() );

            $.tracker_insert_item(options, fn);

            return this;
        },
        tracker_remove_item: function(options, fn) {
            $.tracker_remove_item(options, fn);

            return this;
        },
        tracker_update_item: function(options, fn) {
            $.extend( options, $(this).tracker_get_inputs_from_form() );

            $.tracker_update_item(options, fn);

            return this;
        },
        tracker_get_item_inputs: function(options, fn) {
            $.tracker_get_item_inputs(options, fn);

            return this;
        }
    });

    $ = $.extend($, {
        tracker_insert_item: function(options, fn) {
            options = $.extend({
                controller: 'tracker',
                action: 'insert_item',
                trackerId: 0,
                trackerName: '',
                itemId: 0,
                byName: false,
                fields: {}
            }, options);

            $.ajax({
                url: 'tiki-ajax_services.php',
                dataType: 'json',
                data: options,
                type: 'post',
                success: (fn ? fn : null),
            });
        },
        tracker_remove_item: function(options, fn) {
            options = $.extend({
                controller: 'tracker',
                action: 'remove_item',
                trackerId: 0,
                trackerName: '',
                itemId: 0,
                byName: false
            }, options);

            $.ajax({
                url: 'tiki-ajax_services.php',
                dataType: 'json',
                data: options,
                type: 'post',
                success: (fn ? fn : null),
            });
        },
        tracker_update_item: function(options, fn) {
            options = $.extend({
                controller: 'tracker',
                action: 'update_item',
                trackerId: 0,
                trackerName: '',
                itemId: 0,
                byName: false,
                fields: {}
            }, options);

            $.ajax({
                url: 'tiki-ajax_services.php',
                dataType: 'json',
                data: options,
                type: 'post',
                success: (fn ? fn : null),
            });
        },
        tracker_get_item_inputs: function(options, fn) {
            options = $.extend({
                controller: 'tracker',
                action: 'get_item_inputs',
                trackerId: 0,
                trackerName: '',
                itemId: 0,
                byName: false,
                defaults: {}
            }, options);

            $.ajax({
                url: 'tiki-ajax_services.php',
                dataType: 'json',
                data: options,
                type: 'post',
                success: (fn ? fn : null)
            });
        }
    });

    // Tracker import-export

    $(document).on('click', '.use-odbc', function() {
        if (this.checked) {
            $('.odbc-container').show();
        } else {
            $('.odbc-container').hide();
        }
    });

    $(document).on('click', '.use-api', function() {
        if (this.checked) {
            $('.api-container').show();
        } else {
            $('.api-container').hide();
        }
    });

    $(document).on('click', '.use-api-comments', function() {
        if (this.checked) {
            $('.api-comments-container').show();
        } else {
            $('.api-comments-container').hide();
        }
    });

    $(document).on('mouseenter', '.edit-tabular tbody:not(.ui-sortable) .icon-sort', function () {
        $(this)
            .closest("tbody")
            .filter(":not(.ui-sortable)")
            .each(function () {
                Sortable.create(this, {
                    handle: ".icon-sort",
                    onEnd: function () {
                        $(this.el).closest("table").trigger("tabular-update");
                    },
                });
            });
    });

    $(document).on('change', '.edit-tabular .selection', function () {
        var value = $(this).val(), $add = $(this).closest('table').find('.add-field.from-select, .add-filter');

        if (! $add.data('original-href')) {
            $add.data('original-href', $add.attr('href'));
        }

        $add
            .attr('href', $add.data('original-href') + '&permName=' + value);
    });

    $(document).on('click', '.edit-tabular .add-field', $.clickModal({
        success: function (data) {
            var $row;
            if (typeof data.columnIndex !== "undefined") {
                $row = $(this).closest('table').find("tbody tr:not(.d-none)").eq(data.columnIndex);
            } else {
                $row = $(this).closest('table').find('tbody tr.d-none').clone().removeClass('d-none').appendTo($(this).closest('table').find('tbody'));
            }

            $('.field-label', $row[0]).val(data.label);
            $('.field', $row[0]).text(data.field);
            $('.mode', $row[0]).text(data.mode);
            $('.unique-key', $row[0]).prop('checked', data.isUniqueKey);
            $('.read-only', $row[0]).prop('checked', data.isReadOnly);
            $('.export-only', $row[0]).prop('checked', data.isExportOnly);

            $(this).closest('table').trigger('tabular-update');

            $.closeModal();
        }
    }));

    $(document).on('click', '.edit-tabular .add-filter', $.clickModal({
        success: function (data) {
            var $row = $(this).closest('table').find('tbody tr.d-none').clone().removeClass('d-none').appendTo($(this).closest('table').find('tbody'));

            $('.filter-label', $row[0]).val(data.label);
            $('.field', $row[0]).text(data.field);
            $('.mode', $row[0]).text(data.mode);

            $(this).closest('table').trigger('tabular-update');

            $.closeModal();
        }
    }));

    $(document).on('click', '.edit-tabular .choose-applied-value', $.clickModal({
        success: function (data) {
            $(this).closest('tr').data('applied_value', data.applied_value);
            $(this).closest('table').trigger('tabular-update');
            $.closeModal();
        }
    }));

    $(document).on('click', '.edit-tabular .align-option', function (e) {
        e.preventDefault();
        var hash = this.href.substring(this.href.lastIndexOf('#') + 1);
        $(this).closest('tr')
            .find('.align').text($(this).text()).end()
            .find('.display-align').val(hash).end()
            ;

        $(this).closest('table').trigger('tabular-update');
    });

    $(document).on('click', '.edit-tabular .position-option', function (e) {
        e.preventDefault();
        var hash = this.href.substring(this.href.lastIndexOf('#') + 1);
        $(this).closest('tr')
            .find('.position-label').text($(this).text()).end()
            .find('.position').val(hash).end()
            ;

        $(this).closest('table').trigger('tabular-update');
    });

    $(document).on('click', '.edit-tabular .remove', function (e) {
        var $table = $(this).closest('table');
        e.preventDefault();
        $(this).closest('tr').remove();
        $table.trigger('tabular-update');
    });

    $(document).on('change', '.edit-tabular table :input', function (e) {
        $(this).closest('table').trigger('tabular-update');
    });

    $(document).on('tabular-update', '.edit-tabular table.fields', function () {
        var data = [], count = 0;

        $('tbody tr:not(.d-none)', this).each(function () {
            var $link = $(this).find("a.add-field");

            if ($link.length) {
                $link.attr("href", $link.attr("href").replace(/columnIndex=\d+/, "columnIndex=" + count));
                count++;
            }

            data.push({
                label: $('.field-label', this).val(),
                field: $('.field', this).text(),
                mode: $('.mode', this).text(),
                remoteField: $('.remote-field', this).val(),
                type: $('.type', this).val(),
                displayAlign: $('.display-align', this).val(),
                isUniqueKey: $('.unique-key', this).is(':checked'),
                isReadOnly: $('.read-only', this).is(':checked'),
                isExportOnly: $('.export-only', this).is(':checked'),
                isPrimary: $('.primary', this).is(':checked')
            });
        });

        $('textarea', this).val(JSON.stringify(data));

        $(".edit-tabular").data("dirty", true);
    });

    $(document).on('tabular-update', '.edit-tabular table.filters', function () {
        var data = [];

        $('tbody tr:not(.d-none)', this).each(function () {
            data.push({
                label: $('.field-label', this).val(),
                field: $('.field', this).text(),
                mode: $('.mode', this).text(),
                position: $('.position', this).val(),
                applied_value: $(this).data('applied_value'),
            });
        });

        $('textarea', this).val(JSON.stringify(data));

        $(".edit-tabular").data("dirty", true);
    });

    if ($(".edit-tabular").length) {
        $(window).on("beforeunload", function () {
            return $(".edit-tabular").data("dirty");
        });
    }

    $(document).on('click', '.previewItemBtn', function (e) {
        var trackerForm = $(this).parents('form');
        if ($('#bootstrap-modal').hasClass('show')) {
            trackerForm = $(this).parents('div.modal-content').find('form');
        }
        var trackerFormId = '#' + trackerForm[0].id;
        var fields = {};

        $.each($('input, select, textarea, radio, img', trackerFormId), function(k) {
            var field = $(this);
            var fieldType = field.attr('type');
            var fieldName = field.attr('name');
            var fieldValue = field.val();

            if (fieldType == 'checkbox') {
                var multicheck = $('input[name="' + fieldName + '"]:checked', trackerFormId);
                fieldValue = [];
                multicheck.each(function () {
                    fieldValue.push($(this).val());
                });
            }
            if (fieldType == 'radio') {
                fieldValue = $('input[name="' + fieldName + '"]:checked', trackerFormId).val();
            }

            if (fieldName) {
                fieldName = fieldName.replace('[]', '');
            }
            fields[fieldName] = fieldValue;
        });

        var options = [];
        options = $.extend({
            controller: 'tracker',
            action: 'preview_item',
            fields : fields
        }, options);

        $.ajax({
            url: 'tiki-ajax_services.php',
            data: options,
            type: 'post',
        })
        .done(function( html ) {
            $('.previewTrackerItem').empty();
            $('.previewTrackerItem').append(html);
            $('.previewTrackerItem').append('<hr/>');
            $('.previewTrackerItem').trigger("scroll");
            document.getElementsByClassName('previewTrackerItem')[0].scrollIntoView();
        });
    });

    // Global tracker field functions
    $(document).on('mouseenter', '.currency_output', function(){
      $('.'+$(this).attr('id')).removeClass('d-none');
    });
    $(document).on('mouseleave', '.currency_output', function(){
      $('.'+$(this).attr('id')).addClass('d-none');
    });

    $(document).on('hidden.bs.modal', function (event) {
        if (m = window.location.href.match(/item(\d+)|itemId=(\d+)/)) {
            var itemId = m[1] || m[2];
            $.getJSON($.service("semaphore", "unset"), {
                object_id: itemId,
                object_type: 'trackeritem'
            });
        }
    });

}(jQuery));
