/**
 * Support JavaScript for wikiplugin_convene
 */

$.fn.setupConvene = function (pluginParams) {
    this.each(function () {
        let $this = $(this);

        let convene = $.extend({
            updateUsersVotes: function () {
                let data = {}, dateFromData, dataComments = {}, comment;

                $('.conveneUserVotes', $this).each(function () {
                    $('.conveneUserVote', this).each(function () {
                        dateFromData = $(this).data("date");
                        comment = $(this).data("comment");
                        if (dateFromData) {
                            if (data[dateFromData] === undefined) {
                                data[dateFromData] = {};
                                dataComments[dateFromData] ={};
                            }
                            data[dateFromData][$(this).data("voter")] = $(this).val();
                            dataComments[dateFromData][$(this).data("voter")] = comment;
                        } else {
                            data.push($(this).attr('name') + ' : ' + $(this).val());
                            dataComments.push($(this).attr('name') + ' : ' + comment);
                        }
                    });
                });

                this.voteData = [data,dataComments];

            },
            addUser: function (user) {
                lockPage(function (user) {
                    if (! user) {
                        return;
                    }

                    let users = $(".conveneUserVotes", $this).map(function() { return $(this).data("voter"); }).get();

                    if ($.inArray(user, users) > -1) {
                        return;
                    }

                    this.updateUsersVotes();

                    for (const date in this.voteData[0]) {
                        this.voteData[0][date][user] = 0;
                        this.voteData[1][date][user] = '';
                    }

                    this.save();
                }, this, [user]);
            },
            deleteUser: function (user) {
                lockPage(function (user) {
                    if (!user) {
                        return;
                    }

                    this.updateUsersVotes();

                    for (const date in this.voteData[0]) {
                        delete this.voteData[0][date][user];
                        delete this.voteData[1][date][user];
                    }

                    this.save();
                }, this, [user]);
            },
            addDate: function (date) {
                // should already be locked by the click event
                if (!date) {
                    return;
                }
                date = Date.parseUnix(date);

                if (getCurrentDateTime()>date) {
                    $("#tikifeedback").showError(
                        tr("You cannot select a date and time that has already passed."));
                    $("#page-data").tikiModal();
                } else {

                    this.updateUsersVotes();

                    if (typeof this.voteData[0][date] !== "undefined") {    // don't reset an existing date?
                        return;
                    }

                    this.voteData[0][date] = {};
                    this.voteData[1][date] = {};

                    $('.conveneUserVotes', $this).each(function () {
                        convene.voteData[0][date][$(this).data("voter")] = 0;
                        convene.voteData[1][date][$(this).data("voter")] = '';
                    });

                    this.save();
                }
            },
            deleteDate: function (date) {
                lockPage(function (date) {
                    if (! date) {
                        return;
                    }
                    this.updateUsersVotes();

                    delete this.voteData[0][date];
                    delete this.voteData[1][date];

                    this.save();
                }, this, [date]);
            },
            save: function (reload) {
                $("#page-data").tikiModal(tr("Loading..."));
                let content = $.toJSON(this.voteData);

                if (content === undefined || content === "undefined" || typeof content === "undefined") {
                    alert(tr("Sorry, no content to save, try reloading the page"));
                    unlockPage();
                    return;
                }

                let needReload = reload !== undefined, page;

                if (jqueryTiki.current_object.type === "wiki page") {
                    page = jqueryTiki.current_object.object;
                } else {
                    alert(tr("Sorry, only wiki pages supported currently"));
                    return;
                }

                let params = {
                    page: page,
                    content: content,
                    index: pluginParams.index,
                    type: "convene",
                    ticket: $("input[name=ticket]", $this).val(),
                    params: {
                        title: pluginParams.title,
                        calendarid: pluginParams.calendarid,
                        minvotes: pluginParams.minvotes,
                        locked: pluginParams.locked,
                        autolock: pluginParams.autolock,
                        voteoptions: pluginParams.voteoptions.join(', '),
                    }
                };

                $.post($.service("plugin", "replace"), params, function () {
                    $.get($.service("wiki", "get_page", {page: page}), function (data) {
                        unlockPage();

                        if (needReload) {
                            history.go(0);
                        } else {
                            if (data) {
                                let formId = "#" + $this.attr("id");
                                let $newForm = $(formId, data);
                                $(formId, "#page-data").empty().append($newForm.children());
                            }
                        }
                    }).always(function () {
                        initConvene();
                    });

                })
                    .fail(function (jqXHR) {
                        $("#tikifeedback").showError(jqXHR);
                    })
                    .always(function () {
                        unlockPage();
                        $("#page-data").tikiModal();
                    });
            }
        }, pluginParams);

        $(window).on('beforeunload', function () {
            unlockPage();
        });

        window.pageLocked = false;

        // set semaphore
        let lockPage = function (callback, context, args) {
            let theArgs = args || [];
            if (!window.pageLocked) {
                $.getJSON($.service("semaphore", "is_set"), {
                        object_type: jqueryTiki.current_object.type,
                        object_id: jqueryTiki.current_object.object
                    },
                    function (data) {
                        if (data) {
                            $("#tikifeedback").showError(
                                tr("This page is being edited by another user. Please reload the page and try again later."));
                            $("#page-data").tikiModal();
                        } else {
                            // no one else using it, so carry on...
                            $.getJSON($.service("semaphore", "set"), {
                                object_type: jqueryTiki.current_object.type,
                                object_id: jqueryTiki.current_object.object
                            }, function () {
                                window.pageLocked = true;
                                callback.apply(context, theArgs);
                            });

                        }
                    }
                );
            } else {
                return callback.apply(context, theArgs);
            }
        };

        // unset semaphore
        let unlockPage = function () {
            if (window.pageLocked) {
                // needs to be synchronous to prevent page unload while executing
                $.ajax($.service("semaphore", "unset"), {
                    async: false,
                    dataType: "json",
                    data: {
                        object_type: jqueryTiki.current_object.type,
                        object_id: jqueryTiki.current_object.object
                    },
                    success: function () {
                        window.pageLocked = false;
                    }
                });
            }
        };

        let initConvene = function () {
            $('.conveneAddDate', $this).click(function () {
                lockPage(function () {
                    let dialogOptions = {
                        modal: true,
                        title: tr("Add Date"),
                        buttons: {}
                    };

                    dialogOptions.buttons[tr("Add")] = function () {
                        convene.addDate(o.find('input:first').val());
                        o.dialog('close');
                    };

                    let o = $('<div><input type="text" style="width: 100%;" /></div>')
                        .dialog(dialogOptions);

                    o.find('input:first')
                        .datetimepicker({
                            changeMonth: jqueryTiki.changeMonth,
                            changeYear: jqueryTiki.changeYear,
                        })
                        .focus();
                    return false;
                }, this);
            });

            $('.conveneDeleteDate', $this)
                .click(function () {
                    if (confirm(tr("Delete this date?"))) {
                        convene.deleteDate($(this).data("date"));
                    }
                    return false;
                });

            $('.conveneDeleteUser', $this)
                .click(function () {
                    if (confirm(tr("Are you sure you want to remove this user's votes?") + "\n" +
                        tr("There is no undo"))) {
                        convene.deleteUser($(this).data("user"));
                    }
                    return false;
                });

            $('.conveneUpdateUser', $this).click(function () {
                let $thisButton = $(this),
                    $row = $thisButton.parents("tr:first");

                if ($('.conveneDeleteUser.btn-danger', $row).length) {
                    lockPage(function () {

                        $thisButton.find(".icon").popover("hide");
                        $('.conveneUpdateUser', $row).not($thisButton).hide();
                        // change the delete button into cancel
                        $('.conveneDeleteUser', $row)
                            .removeClass("btn-danger").addClass("btn-link")
                            .attr("title", tr("Cancel"))
                            .off("click").click(function () {
                                history.go(0);
                            })
                            .find('.icon').setIcon("ban");

                        $('.conveneDeleteDate', $row).hide();
                        $('.conveneMain', $row).hide();
                        $row.addClass('convene-highlight')
                            .find('td').not(':first')
                            .addClass('conveneTd');

                        $thisButton.find('.icon').setIcon("save");
                        $row.find('.vote').hide();
                        $row.find('input').each(function (){

                            let select_disable = "" , lock_icon = "";

                            let inputDataVote = $(this);
                            let convene_date_time = $(this).data("date");
                            let current_time = getCurrentDateTime();

                            let select_option = "";
                            pluginParams.voteoptions.forEach(row => {
                                let option = row.split('=');
                                select_option += '<option value="'+ option[0] +'">' + tr(option[1]) + '</option>';
                            });
                            
                            if ( convene_date_time < current_time ){
                                lock_icon = '<div> <i class="fa fa-lock"></i> </div>';
                                select_disable = "disabled";
                                select_option = "";
                            }

                            $(`<select class="form-control" ${select_disable}>` +
                                select_option +
                                '</select>'+
                                 lock_icon)
                                .val($(this).val())
                                .insertAfter($(this))
                                .change(function () {
                                    let cl = '', icon = '';

                                    if ( convene_date_time < current_time ){
                                        $("#tikifeedback").showError(
                                            tr("You cannot vote on a date and time that has already passed."));
                                        $("#page-data").tikiModal();

                                        convene.updateUsers = false;

                                    } else {
                                        const optionValue = $(this).val() * 1;
                                        switch (true) {
                                            case (optionValue > 0):
                                                cl = 'convene-ok alert-success';
                                                icon = 'ok';
                                                break;
                                            case (optionValue < 0):
                                                cl = 'convene-no alert-danger';
                                                icon = 'remove';
                                                break;
                                            default:
                                                cl = 'convene-unconfirmed alert-light';
                                                icon = 'help';
                                        }

                                        $(this)
                                            .parent()
                                            .removeClass('convene-no convene-ok convene-unconfirmed alert-success alert-danger alert-light')
                                            .addClass(cl)
                                            .find(".icon")
                                            .setIcon(icon);
                                        $(this)
                                            .parent()
                                            .find(".icon")
                                            .addClass("fa-2x");


                                        //Add Comment dialogue
                                        let dialogCommentOptions = {
                                            modal: true,
                                            title: tr("Add Comment"),
                                            buttons: {}
                                        };

                                        dialogCommentOptions.buttons[tr("Add")] = function () {
                                            let comment = o.find('input:first').val();
                                            inputDataVote.data("comment", comment);
                                            o.dialog('close');
                                        };

                                        let o = $('<div><input type="text" style="width: 100%;" value="'+inputDataVote.data("comment")+'" /></div>')
                                            .dialog(dialogCommentOptions);
                    
                                        o.find('input:first').focus();                    
                                     
                                        convene.updateUsers = true;
                                    }
                                })
                                .parent().css({position: "relative"});

                        });

                    }, this);
                } else {
                    $('.conveneUpdateUser', $row).show();
                    $('.conveneDeleteUser', $row).show();
                    $('.conveneDeleteDate', $row).show();
                    $row.removeClass('convene-highlight')
                        .find('.conveneTd')
                        .removeClass('convene-highlight');

                    $('.conveneMain').show();
                    $(this).find('span.icon-pencil');
                    parent = $(this).parent().parent().parent().parent();
                    parent.find('select').each(function (i) {
                        parent.find('input.conveneUserVote').eq(i).val($(this).val());

                        $(this).remove();
                    });

                    if (convene.updateUsers) {
                        convene.updateUsersVotes();
                        convene.save();
                    }
                }
                return false;
            });

            let addUsers = $('.conveneAddUser')
                .click(function () {
                    if (!$(this).data('clicked')) {
                        $(this)
                            .data('initval', $(this).val())
                            .val('')
                            .data('clicked', true);
                    }
                })
                .blur(function () {
                    if (!$(this).val()) {
                        $(this)
                            .val($(this).data('initval'))
                            .data('clicked', '');

                    }
                })
                .keydown(function (e) {
                    let user = $(this).val();

                    if (e.which == 13) {//enter
                        convene.addUser(user);
                        return false;
                    }
                });

            //ensure autocomplete works, it may not be available in mobile mode
            if (addUsers.autocomplete) {
                addUsers.tiki("autocomplete", "username");
            }

            $('.conveneAddUserButton', $this).click(function () {
                if ($('.conveneAddUser', $this).val()) {
                    convene.addUser($('.conveneAddUser', $this).val());
                } else {
                    $('.conveneAddUser', $this).val(jqueryTiki.username).focus()
                }
                return false;
            });

            if (jQuery.timeago) {
                $("time.timeago").timeago();
            }
            if (jqueryTiki.tooltips) {
                $this.tiki_popover();
            }
            convene.updateUsersVotes();
        };

        initConvene();

        let getCurrentDateTime = function () {
            let date_time = new Date();
            let local_date = date_time.toDateString();
            let local_time = date_time.toTimeString().split(":");
            return Date.parseUnix(`${local_date} ${local_time[0]}:${local_time[1]}`);
        };

    });
}
