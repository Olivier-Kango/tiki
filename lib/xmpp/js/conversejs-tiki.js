(function(window, converse) {
    var _converse;

    converse.plugins.add("tiki", {
        "dependencies": [
            'converse-muc-views',
            'converse-controlbox',
            'converse-chatview',
            // 'converse-bookmarks',
        ],

        "initialize": function () {
            _converse = this._converse;

            // Tiki custom settings
            _converse.api.settings.extend({
                dm_target: null,
                anon_room: null,
                anonymous: null,
                anonymous_auto_open: null,
                auto_open: null,
                on_xmpp_page: null,
            });

            _converse.tikiSettings = {
                dm_target: _converse.api.settings.get('dm_target'),
                anon_room: _converse.api.settings.get('anon_room'),
                anonymous: _converse.api.settings.get('anonymous'),
                anonymous_nick: _converse.api.settings.get('tiki_anonymous_nick') || 'visitor',
            };

            var error = console && console.error
                ? console.error.bind(console)
                : function (msg) { return feedback(msg, "error", false); };
            var tr = window.tr
                ? tr
                : function(str) { return str; };

            // Boost ANONYMOUS mechanism priority
            if (_converse.api.settings.get('authentication') === "anonymous") {
                _converse.promises.initialized.then(function() {
                    const connection = _converse.api.connection.get();
                    if (connection && connection.mechanisms && connection.mechanisms.ANONYMOUS) {
                        delete connection.mechanisms.ANONYMOUS.priority;
                        connection.mechanisms.ANONYMOUS.priority = 50;
                    }
                });
            }

            // Override MUC nickname for anonymous users
            _converse.promises.initialized.then(function () {
                const originalGetDefaultMUCNickname = _converse.exports.getDefaultMUCNickname;

                if (!originalGetDefaultMUCNickname) {
                    console.error('[tiki] getDefaultMUCNickname is not initialized.');
                    return;
                }

                Object.assign(_converse.exports, {
                    getDefaultMUCNickname: function (...args) {
                        if (_converse.api.settings.get('authentication') !== 'anonymous') {
                            return originalGetDefaultMUCNickname.apply(this, args);
                        }

                        if (_converse.tikiSettings.anonymous_nick === 'custom') {
                            return originalGetDefaultMUCNickname.apply(this, args);
                        }

                        const previousNick = (typeof getPreviousAnonymousNick === 'function')
                            ? getPreviousAnonymousNick()
                            : null;

                        const randomVisitorNick = (typeof randomNick === 'function')
                            ? randomNick('visitor')
                            : 'visitor-' + Math.floor(Math.random() * 100000);

                        return (
                            originalGetDefaultMUCNickname.apply(this, args) ||
                            previousNick ||
                            randomVisitorNick
                        );
                    }
                });
            });

            _converse.promises.initialized.then(function () {
                _converse.api.settings.set('bookmarks', false);
                _converse.api.settings.set('auto_join_bookmarks', false);
                const observer = new MutationObserver(function() {
                    const toggleBtn = document.querySelector('#minimized-chats > button.btn-primary');
                    if (toggleBtn) {
                        toggleBtn.classList.remove('btn-primary');
                        toggleBtn.classList.add('btn-secondary');
                        observer.disconnect();
                    }
                });
                observer.observe(document.body, { childList: true, subtree: true });

                function prettifyJidName(name) {
                    if (typeof name !== 'string') {
                        return name;
                    }
                    var dmTarget = _converse.tikiSettings && _converse.tikiSettings.dm_target;
                    if (dmTarget && name.toLowerCase() === dmTarget.toLowerCase()) {
                        return 'Kingdom Support';
                    }
                    var atIndex = name.indexOf('@');
                    if (atIndex <= 0) {
                        return name;
                    }
                    var local = name.slice(0, atIndex);
                    return local.charAt(0).toUpperCase() + local.slice(1);
                }

                function patchGetDisplayName(Ctor) {
                    if (!Ctor || !Ctor.prototype || typeof Ctor.prototype.getDisplayName !== 'function') {
                        return;
                    }
                    var original = Ctor.prototype.getDisplayName;
                    Ctor.prototype.getDisplayName = function () {
                        return prettifyJidName(original.apply(this, arguments));
                    };
                }

                [
                    _converse.exports.VCard,
                    _converse.exports.ChatBox,
                    _converse.exports.Message,
                    _converse.exports.RosterContact,
                    _converse.exports.MUC,
                    _converse.exports.MUCOccupant,
                ].forEach(patchGetDisplayName);
            });

            document.addEventListener('click', function (ev) {
                var toggle = ev.target.closest && ev.target.closest('#conversejs [data-bs-toggle="dropdown"]');
                if (!toggle) {
                    return;
                }
                var host = toggle.closest('converse-dropdown, converse-emoji-dropdown');
                if (!host || !host.dropdown) {
                    return;
                }
                ev.stopPropagation();
                ev.stopImmediatePropagation();
                host.dropdown.toggle();
            }, true);

            document.addEventListener('click', function (ev) {
                var toggle = ev.target.closest && ev.target.closest('.toggle-controlbox');
                if (!toggle) {
                    return;
                }
                ev.preventDefault();
                ev.stopPropagation();

                var settings = _converse.tikiSettings;
                console.log('[tiki-chat] click', { anonymous: settings && settings.anonymous, dm_target: settings && settings.dm_target, anon_room: settings && settings.anon_room });

                if (settings && settings.anonymous === 'y' && settings.dm_target) {
                    // Support mode: take the visitor straight to a 1-1 with the admin/support.
                    _converse.api.chats.open(settings.dm_target, {}, true).then(function (chat) {
                        console.log('[tiki-chat] chat opened', chat && chat.attributes);
                    }).catch(function (e) {
                        console.error('[tiki-chat] chat open error', e);
                    });
                    return;
                }

                if (settings && settings.anonymous === 'y' && settings.anon_room) {
                    // Community mode: reveal the shared room directly instead of an empty
                    // controlbox the visitor would still have to click through.
                    _converse.api.rooms.create(settings.anon_room).then(function (room) {
                        room.set({ hidden: false, minimized: false });
                        console.log('[tiki-chat] anon room opened', room && room.attributes);
                    }).catch(function (e) {
                        console.error('[tiki-chat] anon room open error', e);
                    });
                    return;
                }

                _converse.api.controlbox.open().then(function (controlbox) {
                    if (controlbox) {
                        controlbox.save({ hidden: false, closed: false });
                    }
                    console.log('[tiki-chat] controlbox opened', controlbox && controlbox.attributes);
                }).catch(function (e) {
                    console.error('[tiki-chat] controlbox open error', e);
                });
            }, true);

            // Patch Strophe
            converse.env.Strophe.Connection.prototype.setProtocol = function() {
                const proto = this.options.protocol || "";
                if (this.options.worker) {
                    this._proto = new Strophe.WorkerWebsocket(this);
                } else if (
                    this.service.indexOf("ws:") === 0 ||
                    this.service.indexOf("wss:") === 0 ||
                    proto.indexOf("ws") === 0
                ) {
                    this._proto = new Strophe.Websocket(this);
                } else {
                    this._proto = new Strophe.Bosh(this);
                }
            };

            _converse.api.listen.on("noResumeableSession", function (xhr) {
                error(tr("XMPP Module error") + ": " + xhr.statusText);
                $("#conversejs").fadeOut("fast");
            });

            // Sort occupants by presence status
            _converse.api.listen.on('chatRoomViewInitialized', function (view) {
                if (view.model && view.model.occupants) {
                    view.model.occupants.comparator = function (a, b) {
                        const order = { 'chat': 1, 'available': 1, 'away': 2, 'xa': 3, 'dnd': 4, 'offline': 5 };
                        const presA = a.get('show') || 'offline';
                        const presB = b.get('show') || 'offline';
                        return order[presA] - order[presB];
                    };
                }
            });

            // Connected: reconcile rooms/DM without disturbing chatboxes the user already
            // has open. Tiki is server-rendered (not a SPA), so 'connected' fires on every
            // page load; previously this handler force-closed every open chatbox on each
            // connect, which both destroyed state across page navigation and raced against
            // converse.js's own session-restore of previously-open rooms (surfacing as stray
            // MUC ping/disco errors in the console). We now only ever create rooms the user
            // is newly authorized for, and only apply the initial minimized/hidden state to
            // chatboxes that didn't already exist - anything the user (or a prior page's
            // auto_open) already opened or minimized is left untouched.
            _converse.api.listen.on('connected', async function () {
                const authMode = _converse.api.settings.get('authentication');
                const dmTarget = _converse.tikiSettings.dm_target;
                const anonRoom = _converse.tikiSettings.anon_room;
                const tikiAnon = _converse.tikiSettings.anonymous;
                const autoOpen = _converse.api.settings.get('auto_open') === 'y';
                const anonymousAutoOpen = _converse.api.settings.get('anonymous_auto_open') === 'y';

                if (authMode === 'anonymous' && tikiAnon === 'y') {
                    if (anonRoom) {
                        try {
                            await _converse.api.waitUntil('chatBoxesInitialized');
                            const existed = !!_converse.api.chatboxes.get(anonRoom);
                            const room = await _converse.api.rooms.create(anonRoom);
                            if (!existed && !anonymousAutoOpen) {
                                room.set({ minimized: true, hidden: true });
                            }
                        } catch (e) {
                            console.error('[XMPP] Anonymous room join error:', e);
                        }
                    }
                    return;
                }

                // Authenticated: sync and rooms
                try {
                    await fetch((window.location.origin || '') + '/tiki-xmpp-sync.php', {
                        credentials: 'include',
                        keepalive: true,
                    });
                } catch (e) {
                    console.error('[XMPP] Sync trigger error:', e);
                }

                let data = [];
                try {
                    const controller = new AbortController();
                    const timeout = setTimeout(() => controller.abort(), 3000);
                    const res = await fetch((window.location.origin || '') + '/tiki-xmpp-rooms.php', {
                        credentials: 'include',
                        signal: controller.signal,
                    });
                    clearTimeout(timeout);
                    if (res.ok) {
                        data = await res.json();
                    }
                } catch (e) {
                    console.warn('[XMPP] Rooms fetch skipped:', e);
                    data = [];
                }

                const rooms = Array.isArray(data) ? data : (data.rooms || []);
                const providedNick = (data && typeof data.nickname === 'string') ? data.nickname.trim() : '';
                const onXmppPage = _converse.api.settings.get('on_xmpp_page') === 'y';

                // Targeted diff: close only rooms the user is no longer authorized for
                // (e.g. removed from a Tiki group) - never a blanket close of everything.
                try {
                    const authorizedSet = new Set(rooms);
                    const existingChatboxes = await _converse.api.chatboxes.get();
                    const staleRooms = (existingChatboxes || []).filter((c) =>
                        c.get('type') === 'chatroom' && c.get('jid') && !authorizedSet.has(c.get('jid'))
                    );
                    await Promise.all(staleRooms.map((c) => c.close()));
                } catch (e) {
                    console.error('[XMPP] Stale room cleanup error:', e);
                }

                if (onXmppPage) {
                    for (const room of rooms) {
                        const attrs = {};
                        if (providedNick) {
                            attrs.nick = providedNick;
                        }
                        const existed = !!_converse.api.chatboxes.get(room);
                        const chatRoom = await _converse.api.rooms.create(room, attrs);
                        if (!existed && !autoOpen) {
                            chatRoom.set({ minimized: true, hidden: true });
                        }
                    }
                } else {
                    console.log('[tiki-chat] Rooms available but not auto-opened (not on an XMPP page)');
                }

                if (dmTarget && autoOpen) {
                    try {
                        const existed = !!_converse.api.chatboxes.get(dmTarget);
                        const chat = await _converse.api.chats.open(dmTarget);
                        if (chat && !existed) {
                            chat.set('hidden', false);
                            chat.set('minimized', false);
                            chat.trigger('show');
                            if (chat.view && typeof chat.view.show === 'function') {
                                chat.view.show();
                            }
                        }
                    } catch (e) {
                        console.error('[XMPP] DM open error:', e);
                    }
                }
            });
        },

        "overrides": {
            "Bookmarks": {
                "openBookmarkedRoom": async function (bookmark) {
                    if (bookmark.get('autojoin')) {
                        const room = await _converse.api.rooms.create(bookmark.get('jid'), bookmark.get('nick'));
                        //room.set('minimized', true);
                        //room.set('hidden', true);
                    }
                    return bookmark;
                }
            },
            "ChatRoom": {
                // Auto‑increment nick on conflict (anonymous mode)
                "onNicknameClash": function (presence) {
                    if (_converse.api.settings.get('authentication') !== 'anonymous') {
                        return this.__super__.onNicknameClash.apply(this, arguments);
                    }

                    const attemptedNick = (presence.getAttribute('from') || '').split('/')[1];
                    if (!attemptedNick) {
                        return this.__super__.onNicknameClash.apply(this, arguments);
                    }
                    const lastDash = attemptedNick.lastIndexOf('-');
                    const suffix = lastDash !== -1 ? attemptedNick.substring(lastDash + 1) : '';
                    const num = parseInt(suffix, 10);
                    const newNick = !isNaN(num) && suffix !== ''
                        ? attemptedNick.substring(0, lastDash + 1) + (num + 1)
                        : attemptedNick + '-2';
                    this.join(newNick);
                },
            },

            "ChatBoxView": {
                "renderMessageForm": function() {
                    this.__super__.renderMessageForm();

                    var form_container = this.el.querySelector('.message-form-container');
                    var nick_reset_box = this.el.querySelector('.tiki-reset-box');
                    var object = window.sessionStorage.getItem(_converse.jid);

                    if (object) {
                        try {
                            object = JSON.parse(object);
                        } catch (e) {
                            object = null;
                        }
                    }
                    var nickSet = object ? object.nickSet : null;

                    const isDmSupportMode =
                        _converse.tikiSettings &&
                        _converse.tikiSettings.anonymous === 'y' &&
                        _converse.tikiSettings.dm_target;

                    if (
                        form_container &&
                        _converse.authentication === "anonymous" &&
                        !nickSet &&
                        !isDmSupportMode &&
                        !nick_reset_box
                    ) {
                        form_container.classList.add('hidden');
                        this.renderNickResetBox(form_container.parentElement);
                        return this;
                    }

                    return this;
                },

                "onNickReset": function(evt, nick) {
                    if (!(typeof nick === "string" || (nick instanceof String))) {
                        return;
                    }

                    nick = nick.trim().replace(/\s+/g, ' ');
                    if (!nick.length) {
                        return;
                    }

                    var object = window.sessionStorage.getItem(_converse.jid);
                    object = object ? JSON.parse(object) : {};
                    object.nickSet = nick;

                    try {
                        this.parseMessageForCommands('/nick ' + nick);
                        window.sessionStorage.setItem(_converse.jid, JSON.stringify(object));
                    } catch(e) {
                        console.error("Can't change nickname:", e);
                    }

                    var message_box = this.el.querySelector('.message-form-container.hidden');
                    var nick_reset_box = this.el.querySelector('.tiki-reset-box');

                    if (nick_reset_box) nick_reset_box.classList.add('hidden');
                    if (message_box) message_box.classList.remove('hidden');
                },

                "renderNickResetBox": function (container) {
                    var resetBox = document.createElement('div');
                    resetBox.classList.add('tiki-reset-box', 'form-row', 'px-1', 'py-1', 'bg-secondary');

                    var col = document.createElement('div');
                    col.classList.add('col');
                    resetBox.appendChild(col);

                    var input = document.createElement('input');
                    input.type="text";
                    input.placeholder="Type a nick";
                    input.classList.add('form-control');
                    col.appendChild(input);

                    col = document.createElement('div');
                    col.classList.add('col', 'btn-group');
                    resetBox.appendChild(col);

                    var button = document.createElement('button');
                    button.innerText = 'Join';
                    button.classList.add('btn', 'btn-info');
                    col.appendChild(button);

                    var onNickReset = this.onNickReset.bind(this);
                    button.addEventListener("click", function (evt) {
                        return onNickReset(evt, input.value);
                    });
                    input.addEventListener("keydown", function(evt){
                        return evt.keyCode === 13 && onNickReset(evt, input.value);
                    });

                    var regBtn = document.createElement('a');
                    regBtn.href = 'tiki-register.php';
                    regBtn.innerText = 'Register';
                    regBtn.classList.add('btn', 'btn-success', 'text-white');
                    col.appendChild(regBtn);

                    container.appendChild(resetBox);
                    return this;
                },
            }
        }

    });
})(window, window.converse);
