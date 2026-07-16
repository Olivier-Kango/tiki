(function(window, converse) {
    var _converse;

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
        } catch (e) {}
    }

    var READ_CURSORS_KEY = 'tiki_xmpp_read_cursors';

    function getReadCursors() {
        try {
            return JSON.parse(window.localStorage.getItem(READ_CURSORS_KEY) || '{}');
        } catch (e) {
            return {};
        }
    }

    function setReadCursor(jid, isoTime) {
        if (!jid) {
            return;
        }
        try {
            var cursors = getReadCursors();
            if (!cursors[jid] || new Date(isoTime) > new Date(cursors[jid])) {
                cursors[jid] = isoTime;
                window.localStorage.setItem(READ_CURSORS_KEY, JSON.stringify(cursors));
            }
        } catch (e) {}
    }

    function markJidRead(jid) {
        if (!jid) {
            return;
        }
        var now = new Date().toISOString();
        setReadCursor(jid, now);
        try {
            var body = {};
            body[jid] = now;
            fetch('/tiki-xmpp-read-cursors.php', {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body),
                keepalive: true,
            }).catch(function () {});
        } catch (e) {}
    }

    // XMPP has no standard "last seen" - it's tracked client-side, purely from
    // the moment presence for a contact is last observed going offline in
    // *this* browser (same approach as WhatsApp Web's own "last seen" when the
    // server doesn't share it), keyed by bare JID, similar to the read-cursors
    // cache above.
    var LAST_SEEN_KEY = 'tiki_xmpp_last_seen';

    function getLastSeenMap() {
        try {
            return JSON.parse(window.localStorage.getItem(LAST_SEEN_KEY) || '{}');
        } catch (e) {
            return {};
        }
    }

    function setLastSeen(jid) {
        if (!jid) {
            return;
        }
        try {
            var map = getLastSeenMap();
            map[jid] = Date.now();
            window.localStorage.setItem(LAST_SEEN_KEY, JSON.stringify(map));
        } catch (e) {}
    }

    function getLastSeen(jid) {
        if (!jid) {
            return null;
        }
        var map = getLastSeenMap();
        return map[jid] || null;
    }

    function formatLastSeen(tr, ts) {
        var minutes = Math.round((Date.now() - ts) / 60000);
        if (minutes < 1) {
            return tr('Last seen just now');
        }
        if (minutes < 60) {
            return tr('Last seen %1$s min ago').replace('%1$s', minutes);
        }
        var hours = Math.round(minutes / 60);
        if (hours < 24) {
            return tr('Last seen %1$s h ago').replace('%1$s', hours);
        }
        var days = Math.round(hours / 24);
        return tr('Last seen %1$s d ago').replace('%1$s', days);
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

            fetch('/tiki-xmpp-read-cursors.php', { credentials: 'include' })
                .then(function (res) { return res.ok ? res.json() : {}; })
                .then(function (serverCursors) {
                    var local = getReadCursors();
                    var merged = Object.assign({}, local);
                    Object.keys(serverCursors || {}).forEach(function (jid) {
                        if (!merged[jid] || new Date(serverCursors[jid]) > new Date(merged[jid])) {
                            merged[jid] = serverCursors[jid];
                        }
                    });
                    window.localStorage.setItem(READ_CURSORS_KEY, JSON.stringify(merged));
                })
                .catch(function () {});

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
                ? window.tr
                : function(str) { return str; };

            if (_converse.api.settings.get('authentication') === "anonymous") {
                _converse.promises.initialized.then(function() {
                    const connection = _converse.api.connection.get();
                    if (connection && connection.mechanisms && connection.mechanisms.ANONYMOUS) {
                        delete connection.mechanisms.ANONYMOUS.priority;
                        connection.mechanisms.ANONYMOUS.priority = 50;
                    }
                });
            }

            _converse.promises.initialized.then(function () {
                const originalGetDefaultMUCNickname = _converse.exports.getDefaultMUCNickname;

                if (!originalGetDefaultMUCNickname) {
                    console.error('[tiki] getDefaultMUCNickname is not initialized.');
                    return;
                }

                Object.assign(_converse.exports, {
                    getDefaultMUCNickname: function (...args) {
                        if (_converse.api.settings.get('authentication') !== 'anonymous') {
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
                if (_converse.api.settings.get('using_external') !== 'y') {
                    _converse.api.settings.set('bookmarks', false);
                    _converse.api.settings.set('auto_join_bookmarks', false);
                }
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
                            var isComposing = (composing && composing.length) ||
                                this.model.notifications.get('chat_state') === 'composing';
                            el.classList.toggle('tiki-is-typing', !!isComposing);
                        }
                    };
                }
                patchTypingIndicator(customElements.get('converse-chat-content'));
            });

            _converse.promises.initialized.then(function () {
                function patchReceiptTicks(Ctor) {
                    if (!Ctor || Ctor.prototype.__tikiReceiptPatched) {
                        return;
                    }
                    Ctor.prototype.__tikiReceiptPatched = true;
                    var originalUpdated = Ctor.prototype.updated;
                    Ctor.prototype.updated = function (changed) {
                        if (originalUpdated) {
                            originalUpdated.call(this, changed);
                        }
                        if (!this.model) {
                            return;
                        }
                        var receiptIcon = this.querySelector('.chat-msg__receipt');
                        var wrapper = this.querySelector('.chat-msg__body--wrapper');
                        if (!wrapper) {
                            return;
                        }
                        var existingTicks = wrapper.querySelector('.tiki-receipt-ticks');
                        if (existingTicks) {
                            existingTicks.remove();
                        }
                        if (!receiptIcon || !this.model.get('received')) {
                            return;
                        }
                        var isRead = !!this.model.get('marker_displayed');
                        var ticks = document.createElement('span');
                        ticks.className = 'tiki-receipt-ticks' + (isRead ? ' tiki-receipt-ticks--read' : '');
                        var tick1 = document.createElement('span');
                        tick1.className = 'tiki-tick';
                        ticks.appendChild(tick1);
                        if (isRead) {
                            var tick2 = document.createElement('span');
                            tick2.className = 'tiki-tick';
                            ticks.appendChild(tick2);
                        }
                        receiptIcon.insertAdjacentElement('afterend', ticks);
                    };
                }
                patchReceiptTicks(customElements.get('converse-chat-message'));
            });

            // Bug fix: converse.js 13.0.1's own ChatBox.handleChatMarker() has a
            // typo that makes read receipts never work - it does
            // `message.save({ field_name: ... })` using the *literal* string
            // "field_name" as the attribute key, instead of the variable
            // `field_name` (which holds "marker_displayed"/"marker_received"/
            // "marker_acknowledged"). So a message is never actually flagged as
            // read - patchReceiptTicks() above already reads `marker_displayed`
            // to decide between one tick (delivered) and two (read), but that
            // attribute was never being set, hence "only ever one checkmark".
            // Fixed by wrapping the method: let the original run for its other
            // side effects (e.g. sending back our own "received" marker), then
            // independently re-derive the same marker_id/marker attrs from the
            // stanza and save them under the correct key.
            _converse.promises.initialized.then(function () {
                function patchHandleChatMarker(Ctor) {
                    if (!Ctor || Ctor.prototype.__tikiChatMarkerPatched) {
                        return;
                    }
                    Ctor.prototype.__tikiChatMarkerPatched = true;
                    var originalHandleChatMarker = Ctor.prototype.handleChatMarker;
                    Ctor.prototype.handleChatMarker = function (attrs) {
                        var result = originalHandleChatMarker.apply(this, arguments);
                        if (attrs && attrs.marker_id && attrs.marker) {
                            var message = this.messages.findWhere({ msgid: attrs.marker_id });
                            var fieldName = 'marker_' + attrs.marker;
                            if (message && !message.get(fieldName)) {
                                message.save(fieldName, new Date().toISOString());
                            }
                        }
                        return result;
                    };
                }
                patchHandleChatMarker(_converse.exports.ChatBox);
            });

            // Feature: show whether the other person is online/away/busy, or
            // when they were last seen, under their name in the 1-on-1 chat
            // header - converse.js only ever surfaces this in the roster list,
            // never in the chat window itself, which makes it hard to tell at a
            // glance whether a reply is likely to come soon.
            _converse.promises.initialized.then(function () {
                var PRESENCE_LABELS = {
                    online: tr('Online'),
                    chat: tr('Online'),
                    away: tr('Away'),
                    xa: tr('Away'),
                    dnd: tr('Busy'),
                };

                function patchChatHeading(Ctor) {
                    if (!Ctor || Ctor.prototype.__tikiPresencePatched) {
                        return;
                    }
                    Ctor.prototype.__tikiPresencePatched = true;

                    var originalInitialize = Ctor.prototype.initialize;
                    Ctor.prototype.initialize = function () {
                        originalInitialize.call(this);
                        var self = this;
                        function attachPresenceListener() {
                            if (self.__tikiPresenceListenerAttached) {
                                return;
                            }
                            var contact = self.model && self.model.contact;
                            if (!contact || !contact.presence) {
                                return;
                            }
                            self.__tikiPresenceListenerAttached = true;
                            self.listenTo(contact.presence, 'change', function () {
                                var status = contact.getStatus();
                                if (!status || status === 'offline' || status === 'unavailable') {
                                    setLastSeen(self.model.get('jid'));
                                }
                                self.requestUpdate();
                            });
                        }
                        attachPresenceListener();
                        if (this.model && this.model.rosterContactAdded) {
                            this.model.rosterContactAdded.then(attachPresenceListener);
                        }
                    };

                    var originalUpdated = Ctor.prototype.updated;
                    Ctor.prototype.updated = function (changed) {
                        if (originalUpdated) {
                            originalUpdated.call(this, changed);
                        }
                        var existing = this.querySelector('.tiki-presence-status');
                        if (existing) {
                            existing.remove();
                        }
                        var contact = this.model && this.model.contact;
                        if (!contact || typeof contact.getStatus !== 'function') {
                            return;
                        }
                        // Inserted as a child of .chatbox-title__text (the name
                        // itself), not as a sibling further down the header, so
                        // it lines up under the name regardless of avatar size -
                        // no hand-tuned left-margin guess needed.
                        var nameEl = this.querySelector('.chatbox-title__text');
                        if (!nameEl) {
                            return;
                        }
                        var show = contact.getStatus() || 'offline';
                        var label = PRESENCE_LABELS[show];
                        if (!label) {
                            var lastSeen = getLastSeen(this.model.get('jid'));
                            label = lastSeen ? formatLastSeen(tr, lastSeen) : tr('Offline');
                        }
                        var statusEl = document.createElement('p');
                        statusEl.className = 'tiki-presence-status tiki-presence-status--' + show;
                        statusEl.textContent = label;
                        nameEl.appendChild(statusEl);
                    };
                }
                patchChatHeading(customElements.get('converse-chat-heading'));
            });

            _converse.promises.initialized.then(function () {
                _converse.state.chatboxes.on('change:hidden', function (model) {
                    if (model.get('hidden') || typeof model.clearUnreadMsgCounter !== 'function') {
                        return;
                    }
                    if (typeof model.isScrolledUp === 'function' && model.isScrolledUp()) {
                        return;
                    }
                    model.clearUnreadMsgCounter();
                    markJidRead(model.get('jid'));
                });
            });

            _converse.api.listen.on('message', function (data) {
                try {
                    var attrs = data && data.attrs;
                    var chatbox = data && data.chatbox;
                    if (!attrs || !chatbox || !attrs.time) {
                        return;
                    }
                    var jid = chatbox.get('jid');
                    var cursor = jid && getReadCursors()[jid];
                    if (cursor && new Date(attrs.time) <= new Date(cursor)) {
                        Promise.resolve().then(function () {
                            if (typeof chatbox.clearUnreadMsgCounter === 'function') {
                                chatbox.clearUnreadMsgCounter();
                            }
                        });
                    }
                } catch (e) {}
            });

            _converse.promises.initialized.then(function () {
                function getTotalUnread() {
                    var chats = _converse.state.chatboxes.models;
                    return chats.reduce(function (acc, chat) {
                        var isGroupchat = chat.get('type') === 'chatroom';
                        var count = isGroupchat
                            ? (chat.get('num_unread_general') || 0)
                            : (chat.get('num_unread') || 0);
                        return acc + count;
                    }, 0);
                }

                function updateChatButtonBadge() {
                    var btn = document.querySelector('.toggle-controlbox');
                    if (!btn) {
                        return;
                    }
                    var numUnread = getTotalUnread();
                    var badge = btn.querySelector('.tiki-chat-unread-badge');
                    if (numUnread > 0) {
                        if (!badge) {
                            badge = document.createElement('span');
                            badge.className = 'tiki-chat-unread-badge';
                            btn.appendChild(badge);
                        }
                        badge.textContent = numUnread > 99 ? '99+' : String(numUnread);
                    } else if (badge) {
                        badge.remove();
                    }
                }

                _converse.state.chatboxes.on('change:num_unread', updateChatButtonBadge);
                _converse.state.chatboxes.on('change:num_unread_general', updateChatButtonBadge);

                var observer = new MutationObserver(function () {
                    if (document.querySelector('.toggle-controlbox')) {
                        updateChatButtonBadge();
                        observer.disconnect();
                    }
                });
                observer.observe(document.body, { childList: true, subtree: true });

                updateChatButtonBadge();
            });

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

                if (window.Notification && Notification.permission !== 'granted' && Notification.permission !== 'denied') {
                    Notification.requestPermission();
                }

                var settings = _converse.tikiSettings;
                console.log('[tiki-chat] click', { anonymous: settings && settings.anonymous, dm_target: settings && settings.dm_target, anon_room: settings && settings.anon_room });

                if (!(settings && settings.anonymous === 'y')) {
                    var externalJid = _converse.api.settings.get('external_jid');
                    var usingExternal = _converse.api.settings.get('using_external') === 'y';

                    if (usingExternal && ev.shiftKey) {
                        document.cookie = 'tiki_xmpp_external=; path=/; max-age=0';
                        window.location.reload();
                        return;
                    }

                    if (externalJid && !usingExternal) {
                        document.cookie = 'tiki_xmpp_external=1; path=/; max-age=' + (60 * 60 * 24 * 30);
                        window.location.reload();
                        return;
                    }
                }

                if (settings && settings.anonymous === 'y' && settings.dm_target) {
                    _converse.api.chats.open(settings.dm_target, {}, true).then(function (chat) {
                        console.log('[tiki-chat] chat opened', chat && chat.attributes);
                    }).catch(function (e) {
                        console.error('[tiki-chat] chat open error', e);
                    });
                    return;
                }

                if (settings && settings.anonymous === 'y' && settings.anon_room) {
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

            _converse.api.listen.on('connected', async function () {
                const authMode = _converse.api.settings.get('authentication');
                const dmTarget = _converse.tikiSettings.dm_target;
                const anonRoom = _converse.tikiSettings.anon_room;
                const tikiAnon = _converse.tikiSettings.anonymous;
                const autoOpen = _converse.api.settings.get('auto_open') === 'y';
                const anonymousAutoOpen = _converse.api.settings.get('anonymous_auto_open') === 'y';
                const viewMode = _converse.api.settings.get('view_mode');
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

                if (_converse.api.settings.get('using_external') === 'y') {
                    const currentRoom = _converse.api.settings.get('current_room');
                    if (currentRoom) {
                        try {
                            const existed = !!_converse.api.chatboxes.get(currentRoom);
                            const room = await _converse.api.rooms.create(currentRoom);
                            if (!existed && !alwaysShow && !autoOpen) {
                                room.set({ minimized: true, hidden: true });
                            }
                        } catch (e) {
                            console.error('[XMPP] External identity current room join error:', e);
                        }
                    }

                    if (_converse.api.settings.get('on_xmpp_page') === 'y' && _converse.api.settings.get('allow_bookmarks') !== false) {
                        try {
                            await _converse.api.waitUntil('bookmarksInitialized');
                            var bookmarks = (_converse.state.bookmarks && _converse.state.bookmarks.models) || [];
                            var bookmarkedJids = bookmarks.map(function (b) { return b.get('jid'); })
                                .filter(function (jid) { return jid && jid !== currentRoom; });
                            var allRooms = currentRoom ? [currentRoom].concat(bookmarkedJids) : bookmarkedJids;
                            if (allRooms.length <= 1 && _converse.api.controlbox && typeof _converse.api.controlbox.close === 'function') {
                                await _converse.api.controlbox.close();
                            }
                        } catch (e) {
                            console.error('[XMPP] External room-count check error:', e);
                        }
                    }

                    return;
                }

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
                        if (!existed) {
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
