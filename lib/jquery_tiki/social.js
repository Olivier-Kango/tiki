(function ($) {
    var
        sortFriends = function () {
            $('.friend-list').each(function () {
                $(this).sortList();
            });
        },
        reload = function (control) {
            $('.friend-container').each(function () {
                var $container = $(this);
                var action = $container.data('action');
                var controller = $container.data('controller');
                var params = $container.data('params') || {};
                $container.parent().tikiModal(tr("Loading..."));
                $container.parent().load($.service(controller, action, params), function () {
                    $(this).tikiModal();
                    sortFriends();
                });
            });
        };

    $(document).on('click', '.add-friend', $.clickModal({
        open: function () {
            if (window.elementPlus?.autocomplete) {
                autocomplete($("input[name=username]", this)[0], 'userrealname');
            } else if (jqueryTiki.autocomplete) {
                $("input[name=username]").tiki("autocomplete", "userrealname", {
                    select: function (event, data) {
                        let usernamePattern = jqueryTiki.usernamePattern ?? "/^['\-_a-zA-Z0-9\.]*$/";
                        usernamePattern = usernamePattern.substring(2, usernamePattern.length - 2);    // trim /^ and $/ (TODO refactor with $.userMentions)

                        let regex = new RegExp("\\((" + usernamePattern + ")\\)$"),
                            userName = data.item.value.match(regex);

                        if (userName) {
                            userName = userName[1];
                        } else {
                            userName = data.item.value;
                        }

                        $(this).val(userName);

                        return false;
                    },
                });
            }
        },
        success: function () {
            $.closeModal();
        	reload(this);
        },
    }));

    $(document).on('click', '.request-list .add-friend, .request-list .approve-friend,' +
                ' .user-info .add-friend, .user-info .approve-friend', function (e) {
        var control = this;
        e.preventDefault();
        $.post($(control).attr('href'), function () {
            reload(control);
        });

        return false;
    });
    $(document).on('click', ' .remove-friend', function (e) {
        var control = this;
        e.preventDefault();

        $(this).doConfirm({
            success: function () {
                reload(control);
            }
        });

        return false;
    });

    $(sortFriends);
})(jQuery);

(function ($) {
    var createReload = function (link) {
        var activity = $(link).closest('.activity'), container = activity.parent(), id = activity.data('id');

        return function () {
            container.load($.service('object', 'infobox', {
                type: 'activity',
                object: id,
                plain: 1,
                format: 'default'
            }));
        };
    };
    $(document).on('click', '.activity a.comment', function () {
        var myReload = createReload(this);
        var url = $(this).attr('href');
        $.openModal({
            remote: url,
            open: function () {
                var container = this;

                $(container).addClass('comment-container');
                container.reload = function () {
                    myReload();
                    $('.modal-content', container).load(url, function () {
                        $(container).trigger('tiki.modal.redraw');
                    });
                };
            },
            close: function () {
                var container = this;
                $(container).removeClass('comment-container');
                container.reload = null;
            }
        });

        return false;
    });

    $(document).on('click', '.activity a.like', function () {
        var myReload = createReload(this);
        $.post($(this).attr('href'), function () {
            myReload();
        });
        return false;
    });

    $(document).on('click', '.stream-container .show-more', function () {
        var link = this
          , page = $(link).data('page')
          , listContainer = $(link).closest('.stream-container').find('ol').first()
          ;

        $(link).hide();

        $.post($.service('activitystream', 'render'), {
            stream: $(link).data('stream'),
            page: page + 1
        }, function (data) {
            $(link).data('page', page + 1);
            var list = $('<div>' + data + '</div>').find('.stream-container > ol > li:not(.invalid)');

            list.appendTo(listContainer);

            if (list.length > 0) {
                $(link).show();
            }
        });
    });

    $(document).on('scroll', function () {
        function elementInViewport(el) {
            var top = el.offsetTop;
            var height = el.offsetHeight;

            while(el.offsetParent) {
                el = el.offsetParent;
                top += el.offsetTop;
            }

            return top >= window.pageYOffset && (top + height) <= (window.pageYOffset + window.innerHeight);
        }

        $('.stream-container.auto-scroll .show-more:visible').each(function () {
            if (elementInViewport(this)) {
                $(this).trigger("click");
            }
        });
    });
})(jQuery);
