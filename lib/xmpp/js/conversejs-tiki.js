(function(window, converse) {
    var _converse;

    // Remember the MUC nickname a signed-in user ends up with after a
    // clash gets auto-resolved (see the ChatRoom.onNicknameClash override
    // below), so repeat visits reuse it directly instead of colliding with
    // their own previous session's nick and re-prompting every time.
    function tikiMucNickStorageKey() {
        var barejid = _converse && _converse.session && _converse.session.get('bare_jid');
        return barejid ? ('tiki_xmpp_muc_nick:' + barejid) : null;
    }

    function getStoredMucNick() {
        try {
            var key = tikiMucNickStorageKey();
            return key ? window.localStorage.getItem(key) : null;
        } catch (e) {
            return null;
        }
    }

    function setStoredMucNick(nick) {
        try {
            var key = tikiMucNickStorageKey();
            if (key) {
                window.localStorage.setItem(key, nick);
            }
        } catch (e) {
            // localStorage unavailable (private browsing, quota, etc.) - not fatal.
        }
    }

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
                current_room: null,
                external_jid: null,
                using_external: null,
                tiki_anonymous_nick: 'visitor',
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
                            // Reuse whatever nick a previous clash resolved to for this
                            // signed-in user, instead of always starting back at the bare
                            // display name and immediately clashing with our own old session.
                            const stored = getStoredMucNick();
                            return stored || originalGetDefaultMUCNickname.apply(this, args);
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
                        return 'Kingdom';
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

            // The "chat-content__notifications" bar is shared by ConverseJS for
            // *every* transient notification (is typing, has entered/left the
            // groupchat, role changes...) with no distinguishing class - our CSS
            // animates 3 dots on it for a "typing" look, which without this patch
            // fires for "X has entered the groupchat" too. Toggle a class after
            // each render so the dots only animate for a genuine composing state.
            _converse.promises.initialized.then(function () {
                function patchTypingIndicator(Ctor) {
                    if (!Ctor || Ctor.prototype.__tikiTypingPatched) {
                        return;
                    }
                    Ctor.prototype.__tikiTypingPatched = true;
                    var originalUpdated = Ctor.prototype.updated;
                    Ctor.prototype.updated = function (changed) {
                        if (originalUpdated) {
                            originalUpdated.call(this, changed);
                        }
                        var el = this.querySelector('.chat-content__notifications');
                        if (el && this.model && this.model.notifications) {
                            var composing = this.model.notifications.get('composing');
                            el.classList.toggle('tiki-is-typing', !!(composing && composing.length));
                        }
                    };
                }
                patchTypingIndicator(customElements.get('converse-chat-content'));
            });

            // Stage pasted/dropped/picked files in the composer instead of
            // uploading+sending them immediately - only send once the user
            // clicks Send (or presses Enter), same as typed text. Stock
            // converse.js calls model.sendFiles() straight from onPaste/onDrop/
            // onFileSelection, so we intercept those three entry points and
            // hold the File objects on the chat model until onFormSubmitted runs.
            _converse.promises.initialized.then(function () {
                var html = converse.env.html;

                function isImageFile(file) {
                    return !!file && typeof file.type === 'string' && file.type.indexOf('image/') === 0;
                }

                function getPendingFiles(model) {
                    return model.get('_tiki_pending_files') || [];
                }

                function stagePendingFiles(model, fileList) {
                    var existing = getPendingFiles(model).slice();
                    Array.from(fileList).forEach(function (file) {
                        existing.push({
                            file: file,
                            name: file.name,
                            url: isImageFile(file) ? URL.createObjectURL(file) : null,
                        });
                    });
                    model.set('_tiki_pending_files', existing);
                }

                function clearPendingFiles(model) {
                    getPendingFiles(model).forEach(function (entry) {
                        if (entry.url) {
                            URL.revokeObjectURL(entry.url);
                        }
                    });
                    model.set('_tiki_pending_files', []);
                }

                function removePendingFile(model, index) {
                    var existing = getPendingFiles(model).slice();
                    var removed = existing.splice(index, 1)[0];
                    if (removed && removed.url) {
                        URL.revokeObjectURL(removed.url);
                    }
                    model.set('_tiki_pending_files', existing);
                }

                function renderPendingFilesPreview(view) {
                    var pending = getPendingFiles(view.model);
                    if (!pending.length) {
                        return '';
                    }
                    return html`
                        <div class="tiki-pending-files">
                            ${pending.map(function (entry, idx) {
                                return html`
                                    <div class="tiki-pending-file">
                                        ${entry.url
                                            ? html`<img class="tiki-pending-file-thumb" src="${entry.url}">`
                                            : html`<span class="tiki-pending-file-icon fa fa-file-o"></span>`}
                                        <span class="tiki-pending-file-name">${entry.name}</span>
                                        <button type="button" class="tiki-pending-file-remove" title="Remove"
                                            @click=${function (ev) {
                                                ev.preventDefault();
                                                ev.stopPropagation();
                                                removePendingFile(view.model, idx);
                                                view.requestUpdate();
                                            }}>&times;</button>
                                    </div>`;
                            })}
                        </div>`;
                }

                function patchRenderPreview(Ctor) {
                    if (!Ctor || Ctor.prototype.__tikiRenderPatched) {
                        return;
                    }
                    Ctor.prototype.__tikiRenderPatched = true;
                    var originalRender = Ctor.prototype.render;
                    Ctor.prototype.render = function () {
                        var preview = renderPendingFilesPreview(this);
                        var original = originalRender.call(this);
                        return preview ? html`${preview}${original}` : original;
                    };
                }

                function patchMessageFormBehavior(Ctor) {
                    if (!Ctor || Ctor.prototype.__tikiBehaviorPatched) {
                        return;
                    }
                    Ctor.prototype.__tikiBehaviorPatched = true;

                    Ctor.prototype.onPaste = function (ev) {
                        if (ev.clipboardData.files.length !== 0) {
                            ev.stopPropagation();
                            ev.preventDefault();
                            stagePendingFiles(this.model, ev.clipboardData.files);
                            this.requestUpdate();
                            return;
                        }
                        var textarea = this.querySelector('.chat-textarea');
                        if (!textarea) {
                            console.error('[tiki-chat] onPaste: could not find textarea to paste in to!');
                            return;
                        }
                        ev.preventDefault();
                        ev.stopPropagation();
                        var draft = textarea.value || '';
                        var pasted_text = ev.clipboardData.getData('text/plain');
                        var cursor_pos = textarea.selectionStart;
                        var before = draft.substring(0, cursor_pos);
                        var after = draft.substring(textarea.selectionEnd);
                        var separator = (before.endsWith(' ') || before.length === 0) ? '' : ' ';
                        var end_separator = (after.startsWith(' ') || after.length === 0) ? '' : ' ';
                        this.model.save({ draft: before + separator + pasted_text + end_separator + after });
                        var new_pos = before.length + separator.length + pasted_text.length + end_separator.length;
                        setTimeout(function () { textarea.setSelectionRange(new_pos, new_pos); }, 0);
                    };

                    Ctor.prototype.onDrop = function (ev) {
                        if (ev.dataTransfer.files.length === 0) {
                            return;
                        }
                        ev.preventDefault();
                        stagePendingFiles(this.model, ev.dataTransfer.files);
                        this.requestUpdate();
                    };

                    var originalOnFormSubmitted = Ctor.prototype.onFormSubmitted;
                    Ctor.prototype.onFormSubmitted = async function (ev) {
                        var pending = getPendingFiles(this.model);
                        if (!pending.length) {
                            return originalOnFormSubmitted.call(this, ev);
                        }
                        var files = pending.map(function (entry) { return entry.file; });
                        clearPendingFiles(this.model);
                        this.requestUpdate();
                        // Reuses the stock flow for any typed text (validation, spoiler
                        // hint, textarea reset, etc.) - it no-ops harmlessly if the
                        // textarea is empty, which is the common image-only case.
                        await originalOnFormSubmitted.call(this, ev);
                        await this.model.sendFiles(files);
                    };
                }

                function patchToolbar(Ctor) {
                    if (!Ctor || Ctor.prototype.__tikiBehaviorPatched) {
                        return;
                    }
                    Ctor.prototype.__tikiBehaviorPatched = true;
                    Ctor.prototype.onFileSelection = function (ev) {
                        var input = ev.target;
                        if (input.files.length) {
                            stagePendingFiles(this.model, input.files);
                            var form = this.closest('converse-message-form, converse-muc-message-form');
                            if (form) {
                                form.requestUpdate();
                            }
                        }
                        input.value = '';
                    };
                }

                var MessageForm = customElements.get('converse-message-form');
                var MUCMessageForm = customElements.get('converse-muc-message-form');
                var ChatToolbar = customElements.get('converse-chat-toolbar');

                patchMessageFormBehavior(MessageForm);
                patchRenderPreview(MessageForm);
                patchRenderPreview(MUCMessageForm);
                patchToolbar(ChatToolbar);
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

                if (!(settings && settings.anonymous === 'y')) {
                    var externalJid = _converse.api.settings.get('external_jid');
                    var usingExternal = _converse.api.settings.get('using_external') === 'y';

                    // Shift+click while already connected as the external account
                    // switches back to the regular Tiki identity.
                    if (usingExternal && ev.shiftKey) {
                        document.cookie = 'tiki_xmpp_external=; path=/; max-age=0';
                        window.location.reload();
                        return;
                    }

                    // A preferred external XMPP address is configured but not in use
                    // yet - connect as that account instead. Tiki can't supply its
                    // password, so this reloads the page and converse.js shows its
                    // own login form for that JID (shift+click switches back).
                    if (externalJid && !usingExternal) {
                        document.cookie = 'tiki_xmpp_external=1; path=/; max-age=' + (60 * 60 * 24 * 30);
                        window.location.reload();
                        return;
                    }
                }

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
                const viewMode = _converse.api.settings.get('view_mode');
                // Embedded/fullscreen rooms ARE the page - there's no floating toggle
                // button to reveal them again in these modes (see the CSS that hides it),
                // so they must always be shown regardless of auto_open.
                const alwaysShow = viewMode === 'embedded' || viewMode === 'fullscreen';

                if (authMode === 'anonymous' && tikiAnon === 'y') {
                    if (anonRoom) {
                        try {
                            await _converse.api.waitUntil('chatBoxesInitialized');
                            const existed = !!_converse.api.chatboxes.get(anonRoom);
                            const room = await _converse.api.rooms.create(anonRoom);
                            if (!existed && !alwaysShow && !anonymousAutoOpen) {
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
                const currentRoom = _converse.api.settings.get('current_room');

                // Targeted diff: close only rooms the user is no longer authorized for
                // (e.g. removed from a Tiki group) - never a blanket close of everything.
                try {
                    const authorizedSet = new Set(rooms);
                    if (currentRoom) {
                        authorizedSet.add(currentRoom);
                    }
                    const existingChatboxes = await _converse.api.chatboxes.get();
                    const staleRooms = (existingChatboxes || []).filter((c) =>
                        c.get('type') === 'chatroom' && c.get('jid') && !authorizedSet.has(c.get('jid'))
                    );
                    await Promise.all(staleRooms.map((c) => c.close()));
                } catch (e) {
                    console.error('[XMPP] Stale room cleanup error:', e);
                }

                if (onXmppPage) {
                    const attrs = {};
                    if (providedNick) {
                        attrs.nick = providedNick;
                    }

                    // This page's own room (declared by the PluginXMPP instance rendering
                    // it) is always joined and shown/hidden per its own auto_open, whether
                    // or not it's also in the user's group-mapped room list below - so a
                    // page's own options work the same regardless of how that room happens
                    // to be provisioned elsewhere.
                    if (currentRoom) {
                        try {
                            const existed = !!_converse.api.chatboxes.get(currentRoom);
                            const room = await _converse.api.rooms.create(currentRoom, attrs);
                            if (!existed && !alwaysShow && !autoOpen) {
                                room.set({ minimized: true, hidden: true });
                            }
                        } catch (e) {
                            console.error('[XMPP] Current room join error:', e);
                        }
                    }

                    for (const room of rooms) {
                        if (room === currentRoom) {
                            continue;
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
                // Auto-increment nick on conflict instead of blocking the user with
                // ConverseJS's native "please choose a different nickname" prompt.
                // Applies to anonymous visitors AND signed-in users alike - a clash
                // usually just means an earlier tab/session of the same person is
                // still lingering in the room, so silently resolving it is friendlier
                // than making them retype their own name.
                "onNicknameClash": function (presence) {
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

                    if (_converse.api.settings.get('authentication') !== 'anonymous') {
                        // Remember it so getDefaultMUCNickname() starts from here next
                        // time, instead of the bare display name clashing all over again.
                        setStoredMucNick(newNick);
                    }
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
