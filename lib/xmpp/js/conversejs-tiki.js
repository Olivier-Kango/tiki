(function(window, converse) {
    var _converse;

    if (!converse || !converse.plugins || typeof converse.plugins.add !== 'function') {
        return;
    }

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

    function dmAutoOpenKey(jid) {
        return jid ? ('tiki_xmpp_dm_autoopened:' + jid) : null;
    }

    function hasAutoOpenedDm(jid) {
        try {
            var key = dmAutoOpenKey(jid);
            return key ? window.localStorage.getItem(key) === '1' : false;
        } catch (e) {
            return false;
        }
    }

    function markDmAutoOpened(jid) {
        try {
            var key = dmAutoOpenKey(jid);
            if (key) {
                window.localStorage.setItem(key, '1');
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

    var visuallySuppressedChatJids = new Set();
    var explicitlyOpenedChatJids = new Set();

    function isGeneratedGuestJid(jid) {
        var bare = String(jid || '').split('/')[0];
        var localpart = bare.split('@')[0];
        return /^guest-[0-9a-f]{8,}$/i.test(localpart);
    }

    function suppressGuestContactSuggestion(contact) {
        if (!contact || typeof contact.get !== 'function' || typeof contact.set !== 'function') return;
        var jid = contact.get('jid') || contact.get('id');
        if (isGeneratedGuestJid(jid) && contact.get('hide_contact_add_alert') !== true) {
            contact.set('hide_contact_add_alert', true);
        }
    }

    function findChatboxElement(jid) {
        var direct = document.getElementById('box-' + jid);
        if (direct) return direct;
        var boxes = document.querySelectorAll('#conversejs .chatbox:not(#controlbox)');
        for (var i = 0; i < boxes.length; i++) {
            var heading = boxes[i].querySelector('.chatbox-title__text');
            var jidHolder = boxes[i].querySelector('[data-room-jid], [data-jid]');
            var nestedJid = jidHolder && (jidHolder.getAttribute('data-room-jid') || jidHolder.getAttribute('data-jid'));
            if (boxes[i].getAttribute('data-jid') === jid || nestedJid === jid || (heading && heading.getAttribute('title') === jid)) return boxes[i];
        }
        return null;
    }

    function getChatboxElementJid(el) {
        if (!el) return null;
        var heading = el.querySelector('.chatbox-title__text');
        var jidHolder = el.querySelector('[data-room-jid], [data-jid]');
        return el.getAttribute('data-jid') ||
            (jidHolder && (jidHolder.getAttribute('data-room-jid') || jidHolder.getAttribute('data-jid'))) ||
            (heading && heading.getAttribute('title')) || null;
    }

    function applyAutomaticChatboxSuppression(el) {
        var jid = getChatboxElementJid(el);
        if (!jid) return;
        el.classList.toggle('tiki-chat-auto-suppressed', visuallySuppressedChatJids.has(jid) && !explicitlyOpenedChatJids.has(jid));
        el.classList.toggle('tiki-chat-user-selected', explicitlyOpenedChatJids.has(jid));
    }

    function forceHideChatboxElement(jid) {
        if (!jid || explicitlyOpenedChatJids.has(jid)) return;
        visuallySuppressedChatJids.add(jid);
        var el = findChatboxElement(jid);
        if (el) el.classList.add('tiki-chat-auto-suppressed');
    }

    function forceShowChatboxElement(jid) {
        if (!jid) return;
        explicitlyOpenedChatJids.add(jid);
        visuallySuppressedChatJids.delete(jid);
        var el = findChatboxElement(jid);
        if (el) {
            el.classList.add('tiki-chat-user-selected');
            el.classList.remove('tiki-chat-auto-suppressed', 'hidden');
        }
    }

    function openExistingControlbox() {
        try {
            var model = _converse && _converse.state && _converse.state.chatboxes && _converse.state.chatboxes.get('controlbox');
            var view = _converse && _converse.state && _converse.state.chatboxviews && _converse.state.chatboxviews.get('controlbox');
            if (!model) return false;
            model.set({ hidden: false, minimized: false, closed: false });
            model.trigger('show');
            if (view && typeof view.show === 'function') view.show();
            var launcher = document.querySelector('.toggle-controlbox');
            if (launcher) launcher.classList.add('tiki-chat-launcher-hidden');
            return true;
        } catch (e) {
            return false;
        }
    }

    function installTikiChatPresentation() {
        if (document.documentElement.dataset.tikiChatPresentation === '1') return;
        document.documentElement.dataset.tikiChatPresentation = '1';

        function isEmojiOnly(text) {
            var compact = (text || '').replace(/[\s\u200d\ufe0f]/g, '');
            if (!compact || compact.length > 32) return false;
            try {
                return /^(?:\p{Extended_Pictographic}|\p{Emoji_Presentation}|\p{Emoji_Modifier}|[#*0-9]\ufe0f?\u20e3)+$/u.test(compact);
            } catch (e) { return false; }
        }
        function classifyMessage(body) {
            var rich = body.querySelector('img, audio, video, figure, .chat-msg__media, .chat-image, .file, .attachment, a[href$=".pdf"], a[href$=".doc"], a[href$=".docx"], a[href$=".xls"], a[href$=".xlsx"], a[href$=".zip"], a[href$=".mp3"], a[href$=".m4a"], a[href$=".wav"], a[href$=".ogg"]');
            var text = body.querySelector('.chat-msg__text');
            body.classList.toggle('tiki-chat-msg--bare', !!rich || isEmojiOnly(text && text.textContent));
        }
        function suppressGuestAlertElement(element) {
            if (element && element.contact) suppressGuestContactSuggestion(element.contact);
        }
        function closeFullscreen() {
            document.querySelectorAll('#conversejs .tiki-chatbox--fullscreen').forEach(function (box) {
                box.classList.remove('tiki-chatbox--fullscreen');
            });
            document.documentElement.classList.remove('tiki-chat-fullscreen-open');
        }
        function toggleFullscreen(box) {
            var entering = !box.classList.contains('tiki-chatbox--fullscreen');
            closeFullscreen();
            if (entering) {
                box.classList.add('tiki-chatbox--fullscreen');
                document.documentElement.classList.add('tiki-chat-fullscreen-open');
            }
        }
        function addFullscreenMenuItem(dropdown) {
            if (!window.matchMedia('(min-width: 768px)').matches) return;
            var box = dropdown.closest('.chatbox:not(#controlbox)');
            var menu = dropdown.querySelector('.dropdown-menu');
            if (!box || !menu || menu.querySelector('.tiki-chat-fullscreen-menu-item')) return;
            var item = document.createElement('button');
            item.type = 'button';
            item.className = 'dropdown-item tiki-chat-fullscreen-menu-item';
            item.innerHTML = '<span aria-hidden="true" class="me-2">&#x26F6;</span><span>Plein écran</span>';
            item.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                toggleFullscreen(box);
                if (dropdown.dropdown && typeof dropdown.dropdown.hide === 'function') dropdown.dropdown.hide();
            });
            menu.appendChild(item);
        }
        function showExplicitlySelectedChat(event) {
            var target = event.target.closest('[data-room-jid], [data-jid], [jid]');
            var jid = target && (target.getAttribute('data-room-jid') || target.getAttribute('data-jid') || target.getAttribute('jid'));
            if (!jid) return;
            forceShowChatboxElement(jid);
            function reveal() {
                try {
                    Promise.resolve(_converse.api.chatboxes.get(jid)).then(function (box) {
                        if (!box) return;
                        box.set({ hidden: false, minimized: false });
                        box.trigger('show');
                    }).catch(function () {});
                } catch (e) {}
            }
            [0, 50, 150, 350, 700].forEach(function (delay) { setTimeout(reveal, delay); });
        }
        function enhance(root) {
            (root.querySelectorAll ? root.querySelectorAll('#conversejs .chat-msg__body') : []).forEach(classifyMessage);
            (root.querySelectorAll ? root.querySelectorAll('#conversejs converse-contact-approval-alert') : []).forEach(suppressGuestAlertElement);
        }
        var observer = new MutationObserver(function (records) {
            records.forEach(function (record) { record.addedNodes.forEach(function (node) {
                if (node.nodeType !== 1 || !node.matches) return;
                if (node.matches('#conversejs .chat-msg__body')) classifyMessage(node);
                if (node.querySelectorAll) node.querySelectorAll('#conversejs .chat-msg__body, .chat-msg__body').forEach(classifyMessage);
                if (node.matches('converse-contact-approval-alert')) suppressGuestAlertElement(node);
                if (node.querySelectorAll) node.querySelectorAll('converse-contact-approval-alert').forEach(suppressGuestAlertElement);
                var parentBox = node.closest && node.closest('#conversejs .chatbox:not(#controlbox)');
                if (parentBox) applyAutomaticChatboxSuppression(parentBox);
                if (node.querySelectorAll) node.querySelectorAll('#conversejs .chatbox:not(#controlbox), .chatbox:not(#controlbox)').forEach(applyAutomaticChatboxSuppression);
            }); });
        });
        observer.observe(document.body, { childList: true, subtree: true });
        enhance(document);
        document.addEventListener('click', function (event) {
            if (!event.target.closest) return;
            if (event.target.closest('#controlbox')) showExplicitlySelectedChat(event);
            var dropdownToggle = event.target.closest('.chatbox:not(#controlbox) converse-dropdown [data-bs-toggle="dropdown"], .chatbox:not(#controlbox) converse-dropdown.chatbox-btn');
            if (dropdownToggle) {
                var dropdown = dropdownToggle.closest('converse-dropdown');
                setTimeout(function () { addFullscreenMenuItem(dropdown); }, 0);
                setTimeout(function () { addFullscreenMenuItem(dropdown); }, 100);
            }
        }, true);
        document.addEventListener('keydown', function (event) { if (event.key === 'Escape') closeFullscreen(); });
        window.addEventListener('pageshow', function () {
            closeFullscreen();
            explicitlyOpenedChatJids.clear();
            document.querySelectorAll('#conversejs .tiki-chat-user-selected').forEach(function (box) {
                box.classList.remove('tiki-chat-user-selected');
            });
        });
    }

    converse.plugins.add("tiki", {
        "dependencies": [
            'converse-muc-views',
            'converse-controlbox',
            'converse-chatview',
        ],

        "initialize": function () {
            _converse = this._converse;
            installTikiChatPresentation();

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
                local_jid: null,
                tiki_site_name: 'Tiki',
                tiki_site_logo: '',
                tiki_support_label: 'Support',
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
                site_name: _converse.api.settings.get('tiki_site_name') || 'Tiki',
                site_logo: _converse.api.settings.get('tiki_site_logo') || '',
                support_label: _converse.api.settings.get('tiki_support_label') || 'Support',
            };

            function detectTikiSiteLogo() {
                var selectors = [
                    '#sitelogo img',
                    '.sitelogo img',
                    '.navbar-brand img',
                    '#top_modules img',
                    'header .site-logo img',
                    'header img[alt*="logo" i]',
                ];
                for (var i = 0; i < selectors.length; i++) {
                    var image = document.querySelector(selectors[i]);
                    if (image && !image.closest('#conversejs')) {
                        return image.currentSrc || image.getAttribute('src') || '';
                    }
                }
                return '';
            }

            var logo = String(_converse.tikiSettings.site_logo || detectTikiSiteLogo());
            if (logo) {
                try {
                    logo = new URL(logo, document.baseURI).href;
                } catch (e) {}
                logo = logo.replace(/["\\\n\r]/g, '\\$&');
                document.documentElement.style.setProperty('--tiki-chat-logo-image', 'url("' + logo + '")');
            }

            var error = function (msg) { return feedback(msg, "error", false); };
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
                    return;
                }

                Object.assign(_converse.exports, {
                    getDefaultMUCNickname: function (...args) {
                        if (_converse.tikiSettings.anonymous !== 'y') {
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
                        return _converse.tikiSettings.support_label;
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
            _converse.promises.initialized.then(function () {
                Promise.resolve(_converse.api.waitUntil('rosterInitialized')).then(function () {
                    var roster = _converse.state.roster;
                    if (!roster) return;
                    roster.models.forEach(suppressGuestContactSuggestion);
                    roster.on('add change:jid', suppressGuestContactSuggestion);
                }).catch(function () {});

                _converse.state.chatboxes.on('change:hidden', function (model) {
                    var modelJid = model.get('jid');
                    if (
                        model.get('hidden') === false &&
                        visuallySuppressedChatJids.has(modelJid) &&
                        !explicitlyOpenedChatJids.has(modelJid)
                    ) {
                        model.set({ hidden: true, minimized: true });
                        forceHideChatboxElement(modelJid);
                        return;
                    }
                    if (model.get('hidden') || typeof model.clearUnreadMsgCounter !== 'function') {
                        return;
                    }
                    if (typeof model.isScrolledUp === 'function' && model.isScrolledUp()) {
                        return;
                    }
                    model.clearUnreadMsgCounter();
                    if (model.get('type') === 'chatroom') {
                        return;
                    }
                    var jid = modelJid;
                    var bareJid = _converse.session && _converse.session.get('bare_jid');
                    if (jid && bareJid && jid.toLowerCase() === bareJid.toLowerCase()) {
                        return;
                    }
                    markJidRead(jid);
                });
            });

            _converse.api.listen.on('message', function (data) {
                try {
                    var attrs = data && data.attrs;
                    var chatbox = data && data.chatbox;
                    if (!attrs || !chatbox || !attrs.time || (!attrs.is_archived && !attrs.is_forwarded)) {
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

            _converse.api.listen.on('message', function (data) {
                try {
                    var attrs = data && data.attrs;
                    var chatbox = data && data.chatbox;
                    if (!attrs || !chatbox || attrs.is_archived || attrs.is_forwarded || attrs.is_error) {
                        return;
                    }
                    if (!attrs.body && !attrs.message) {
                        return;
                    }
                    if (!document.hasFocus()) {
                        return;
                    }
                    var bareJid = _converse.session && _converse.session.get('bare_jid');
                    var fromBare = attrs.from && converse.env.Strophe.getBareJidFromJid(attrs.from);
                    if (bareJid && fromBare && fromBare.toLowerCase() === bareJid.toLowerCase()) {
                        return;
                    }
                    var jid = chatbox.get('jid');
                    if (!jid) {
                        return;
                    }
                    forceShowChatboxElement(jid);
                    if (typeof chatbox.save === 'function') {
                        chatbox.save({ hidden: false, minimized: false, closed: false });
                    } else if (typeof chatbox.set === 'function') {
                        chatbox.set({ hidden: false, minimized: false, closed: false });
                    }
                    if (typeof chatbox.maybeShow === 'function') {
                        chatbox.maybeShow(true);
                    } else if (typeof chatbox.trigger === 'function') {
                        chatbox.trigger('show');
                    }
                } catch (e) {}
            });

            _converse.promises.initialized.then(function () {
                var tikiFavico = null;

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

                function updateBrowserBadge(numUnread) {
                    try {
                        if (!tikiFavico && converse.env && typeof converse.env.Favico === 'function') {
                            tikiFavico = new converse.env.Favico({ type: 'circle', animation: 'pop' });
                        }
                        if (tikiFavico) tikiFavico.badge(numUnread);
                    } catch (e) {}
                    try {
                        if (navigator.setAppBadge) {
                            if (numUnread > 0) navigator.setAppBadge(numUnread).catch(function () {});
                            else if (navigator.clearAppBadge) navigator.clearAppBadge().catch(function () {});
                        }
                    } catch (e) {}
                }

                function updateChatButtonBadge() {
                    var numUnread = getTotalUnread();
                    updateBrowserBadge(numUnread);
                    var btn = document.querySelector('.toggle-controlbox');
                    if (!btn) {
                        return;
                    }
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

                _converse.state.chatboxes.on('change:num_unread', function () { requestAnimationFrame(updateChatButtonBadge); });
                _converse.state.chatboxes.on('change:num_unread_general', function () { requestAnimationFrame(updateChatButtonBadge); });

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
                function isAnyChatboxOpen() {
                    var flyouts = document.querySelectorAll('#conversejs .chatbox .box-flyout, #conversejs #controlbox .box-flyout');
                    for (var i = 0; i < flyouts.length; i++) {
                        if (flyouts[i].offsetParent !== null) {
                            return true;
                        }
                    }
                    return false;
                }

                function updateToggleButtonVisibility() {
                    var btn = document.querySelector('.toggle-controlbox');
                    if (!btn) {
                        return;
                    }
                    btn.classList.toggle('tiki-chat-launcher-hidden', isAnyChatboxOpen());
                }

                function scheduleToggleButtonUpdate() {
                    requestAnimationFrame(updateToggleButtonVisibility);
                }

                function observeConverseRoot() {
                    var root = document.getElementById('conversejs');
                    if (!root) {
                        return false;
                    }
                    var rootObserver = new MutationObserver(scheduleToggleButtonUpdate);
                    rootObserver.observe(root, {
                        childList: true,
                        subtree: true,
                        attributes: true,
                        attributeFilter: ['class', 'style', 'hidden'],
                    });
                    updateToggleButtonVisibility();
                    return true;
                }

                if (!observeConverseRoot()) {
                    var waitForRootObserver = new MutationObserver(function () {
                        if (observeConverseRoot()) {
                            waitForRootObserver.disconnect();
                        }
                    });
                    waitForRootObserver.observe(document.body, { childList: true, subtree: true });
                }
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

                function patchDmReturnToRoomBanner(Ctor) {
                    if (!Ctor || Ctor.prototype.__tikiReturnBannerPatched) {
                        return;
                    }
                    Ctor.prototype.__tikiReturnBannerPatched = true;
                    var originalRender = Ctor.prototype.render;
                    Ctor.prototype.render = function () {
                        var original = originalRender.call(this);
                        var settings = _converse.tikiSettings;
                        var isSupportChat = settings && settings.anonymous === 'y' &&
                            settings.dm_target && settings.anon_room &&
                            this.model && this.model.get('jid') === settings.dm_target;
                        if (!isSupportChat) {
                            return original;
                        }
                        var anonRoomJid = settings.anon_room;
                        var banner = html`
                            <div class="tiki-return-to-room-banner text-center">
                                <a href="#" class="tiki-return-to-room-banner-link"
                                    @click=${function (ev) {
                                        ev.preventDefault();
                                        Promise.resolve(_converse.api.rooms.create(anonRoomJid))
                                            .then(function (room) {
                                                if (!room) {
                                                    return;
                                                }
                                                if (typeof room.save === 'function') {
                                                    room.save({ hidden: false, minimized: false, closed: false });
                                                } else if (typeof room.set === 'function') {
                                                    room.set({ hidden: false, minimized: false, closed: false });
                                                }
                                                if (typeof room.maybeShow === 'function') {
                                                    room.maybeShow(true);
                                                } else if (typeof room.trigger === 'function') {
                                                    room.trigger('show');
                                                }
                                            })
                                            .catch(function () {});
                                    }}>${'Return to group chat'}</a>
                            </div>`;
                        return html`${banner}${original}`;
                    };
                }

                var MessageForm = customElements.get('converse-message-form');
                var MUCMessageForm = customElements.get('converse-muc-message-form');
                var ChatToolbar = customElements.get('converse-chat-toolbar');

                patchMessageFormBehavior(MessageForm);
                patchRenderPreview(MessageForm);
                patchRenderPreview(MUCMessageForm);
                patchDmReturnToRoomBanner(MessageForm);
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
                var identityButton = ev.target.closest && ev.target.closest('[data-tiki-xmpp-identity]');
                if (!identityButton) {
                    return;
                }
                ev.preventDefault();
                var identity = identityButton.getAttribute('data-tiki-xmpp-identity');
                var cookie = 'tiki_xmpp_external=' + (identity === 'external' ? '1' : '');
                cookie += '; path=/; max-age=' + (identity === 'external' ? (60 * 60 * 24 * 30) : '0');
                cookie += '; SameSite=Lax';
                if (window.location.protocol === 'https:') {
                    cookie += '; Secure';
                }
                document.cookie = cookie;
                window.location.reload();
            }, true);

            document.addEventListener('click', function (ev) {
                var toggle = ev.target.closest && ev.target.closest('.toggle-controlbox');
                if (!toggle) {
                    return;
                }
                var settings = _converse.tikiSettings;
                var isAnonymous = settings && settings.anonymous === 'y';

                function showChatbox(chatbox) {
                    if (!chatbox) {
                        return;
                    }
                    forceShowChatboxElement(chatbox.get('jid'));
                    if (typeof chatbox.save === 'function') {
                        chatbox.save({ hidden: false, minimized: false, closed: false });
                    } else if (typeof chatbox.set === 'function') {
                        chatbox.set({ hidden: false, minimized: false, closed: false });
                    }
                    if (typeof chatbox.maybeShow === 'function') {
                        chatbox.maybeShow(true);
                    } else if (typeof chatbox.trigger === 'function') {
                        chatbox.trigger('show');
                    }
                }

                // Permission prompts and audio playback must be initiated from a
                // real user gesture. This must run for authenticated users too.
                if (window.Notification && Notification.permission === 'default') {
                    try {
                        var permissionRequest = Notification.requestPermission();
                        if (permissionRequest && typeof permissionRequest.catch === 'function') permissionRequest.catch(function () {});
                    } catch (e) {}
                }

                if (!isAnonymous) {
                    if (openExistingControlbox()) {
                        ev.preventDefault();
                        ev.stopPropagation();
                    }
                    return;
                }

                if (!settings.dm_target && !settings.anon_room) {
                    return;
                }

                ev.preventDefault();
                ev.stopPropagation();

                if (settings.dm_target) {
                    Promise.resolve(_converse.api.chats.open(settings.dm_target, {}, true))
                        .then(showChatbox)
                        .catch(function () {});
                    return;
                }

                Promise.resolve(_converse.api.rooms.create(settings.anon_room))
                    .then(showChatbox)
                    .catch(function () {});
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

            function suppressAutomaticallyOpenedChatbox(box, jid) {
                const chatJid = jid || box.get('jid');
                if (!chatJid || explicitlyOpenedChatJids.has(chatJid)) return;
                // Hidden/minimized does not clear unread counters or messages. It only
                // prevents restored rooms from covering the page before the user picks one.
                box.set({ hidden: true, minimized: true });
                forceHideChatboxElement(chatJid);
            }

            async function tikiStrictHideEverythingExceptThisPage() {
                const onXmppPage = _converse.api.settings.get('on_xmpp_page') === 'y';
                const viewMode = _converse.api.settings.get('view_mode');
                const pageShowsRoomDirectly = onXmppPage && (viewMode === 'embedded' || viewMode === 'fullscreen');
                const keepJids = new Set();
                if (pageShowsRoomDirectly) {
                    const currentRoom = _converse.api.settings.get('current_room');
                    const dmTarget = _converse.tikiSettings && _converse.tikiSettings.dm_target;
                    if (currentRoom) {
                        keepJids.add(currentRoom);
                    }
                    if (dmTarget) {
                        keepJids.add(dmTarget);
                    }
                }
                try {
                    const allBoxes = await _converse.api.chatboxes.get();
                    (allBoxes || []).forEach((box) => {
                        const id = box.get('id');
                        const boxId = box.get('box_id');
                        const type = box.get('type');
                        if (
                            id === 'controlbox' ||
                            boxId === 'controlbox' ||
                            type === 'controlbox' ||
                            typeof box.getDisplayName !== 'function'
                        ) {
                            return;
                        }
                        if (keepJids.has(box.get('jid'))) {
                            return;
                        }
                        suppressAutomaticallyOpenedChatbox(box, box.get('jid'));
                    });
                } catch (e) {}
            }

            window.addEventListener('pageshow', function (ev) {
                if (ev.persisted && _converse && _converse.api) {
                    tikiStrictHideEverythingExceptThisPage();
                }
            });

            _converse.api.listen.on('connected', async function () {
                const dmTarget = _converse.tikiSettings.dm_target;
                const anonRoom = _converse.tikiSettings.anon_room;
                const tikiAnon = _converse.tikiSettings.anonymous;
                const autoOpen = _converse.api.settings.get('auto_open') === 'y';
                const anonymousAutoOpen = _converse.api.settings.get('anonymous_auto_open') === 'y';
                const viewMode = _converse.api.settings.get('view_mode');
                const alwaysShow = viewMode === 'embedded' || viewMode === 'fullscreen';
                const onXmppPage = _converse.api.settings.get('on_xmpp_page') === 'y';
                const configuredPageRoom = _converse.api.settings.get('current_room');
                await tikiStrictHideEverythingExceptThisPage();

                if (onXmppPage && !alwaysShow) {
                    try {
                        const launcher = document.querySelector('.toggle-controlbox');
                        if (launcher) launcher.classList.add('tiki-chat-launcher-hidden');
                        openExistingControlbox();
                    } catch (e) {}
                }

                if (tikiAnon === 'y') {
                    const anonymousRoom = (onXmppPage && configuredPageRoom) ? configuredPageRoom : anonRoom;
                    if (anonymousRoom) {
                        try {
                            await _converse.api.waitUntil('chatBoxesInitialized');
                            const room = await _converse.api.rooms.create(anonymousRoom);
                            if (alwaysShow) {
                                forceShowChatboxElement(anonymousRoom);
                                room.set({ hidden: false, minimized: false, closed: false });
                                room.trigger('show');
                            } else suppressAutomaticallyOpenedChatbox(room, anonymousRoom);
                        } catch (e) {}
                    }
                    if (dmTarget && !anonymousAutoOpen) {
                        try {
                            const existedDm = !!(await _converse.api.chatboxes.get(dmTarget));
                            const dmChat = await _converse.api.chats.get(dmTarget, {}, true);
                            if (dmChat && !existedDm && !alwaysShow) {
                                suppressAutomaticallyOpenedChatbox(dmChat, dmTarget);
                            }
                        } catch (e) {}
                    }
                    return;
                }

                if (_converse.api.settings.get('using_external') === 'y') {
                    const currentRoom = _converse.api.settings.get('current_room');

                    try {
                        await fetch((window.location.origin || '') + '/tiki-xmpp-sync.php', {
                            credentials: 'include',
                            keepalive: true,
                        });
                    } catch (e) {}

                    if (currentRoom) {
                        try {
                            const room = await _converse.api.rooms.create(currentRoom);
                            if (alwaysShow) {
                                forceShowChatboxElement(currentRoom);
                                room.set({ hidden: false, minimized: false, closed: false });
                                room.trigger('show');
                            } else suppressAutomaticallyOpenedChatbox(room, currentRoom);
                        } catch (e) {}
                    }

                    try {
                        const controller = new AbortController();
                        const timeout = setTimeout(() => controller.abort(), 3000);
                        const response = await fetch((window.location.origin || '') + '/tiki-xmpp-rooms.php', {
                            credentials: 'include',
                            signal: controller.signal,
                        });
                        clearTimeout(timeout);
                        const roomData = response.ok ? await response.json() : [];
                        const mappedRooms = Array.isArray(roomData) ? roomData : (roomData.rooms || []);
                        for (const mappedRoom of mappedRooms) {
                            if (!mappedRoom || mappedRoom === currentRoom) {
                                continue;
                            }
                            const room = await _converse.api.rooms.create(mappedRoom);
                            if (!alwaysShow) suppressAutomaticallyOpenedChatbox(room, mappedRoom);
                        }
                    } catch (e) {}

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
                        } catch (e) {}
                    }

                    return;
                }

                try {
                    await fetch((window.location.origin || '') + '/tiki-xmpp-sync.php', {
                        credentials: 'include',
                        keepalive: true,
                    });
                } catch (e) {}

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
                    data = [];
                }

                const rooms = Array.isArray(data) ? data : (data.rooms || []);
                const providedNick = (data && typeof data.nickname === 'string') ? data.nickname.trim() : '';
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
                } catch (e) {}

                const dmPartners = Array.isArray(data && data.dm_partners) ? data.dm_partners : [];
                for (const partnerJid of dmPartners) {
                    if (partnerJid === dmTarget) {
                        continue;
                    }
                    try {
                        const existed = !!(await _converse.api.chatboxes.get(partnerJid));
                        if (existed) {
                            continue;
                        }
                        const chat = await _converse.api.chats.get(partnerJid, {}, true);
                        if (chat) {
                            suppressAutomaticallyOpenedChatbox(chat, partnerJid);
                        }
                    } catch (e) {}
                }

                const attrs = {};
                if (providedNick) {
                    attrs.nick = providedNick;
                }

                if (onXmppPage && currentRoom) {
                    try {
                        const room = await _converse.api.rooms.create(currentRoom, attrs);
                        if (alwaysShow) {
                            forceShowChatboxElement(currentRoom);
                            room.set({ hidden: false, minimized: false, closed: false });
                            room.trigger('show');
                        } else suppressAutomaticallyOpenedChatbox(room, currentRoom);
                    } catch (e) {}
                }

                for (const room of rooms) {
                    if (room === currentRoom) {
                        continue;
                    }
                    try {
                        const chatRoom = await _converse.api.rooms.create(room, attrs);
                        if (!alwaysShow) {
                            suppressAutomaticallyOpenedChatbox(chatRoom, room);
                        }
                    } catch (e) {}
                }

                if (dmTarget && autoOpen && onXmppPage && alwaysShow) {
                    try {
                        const chat = await _converse.api.chats.get(dmTarget, {}, true);
                        if (chat && !hasAutoOpenedDm(dmTarget)) {
                            forceShowChatboxElement(dmTarget);
                            chat.set('hidden', false);
                            chat.set('minimized', false);
                            chat.trigger('show');
                            if (chat.view && typeof chat.view.show === 'function') {
                                chat.view.show();
                            }
                            markDmAutoOpened(dmTarget);
                        }
                    } catch (e) {}
                }
            });
        },

        "overrides": {
            "RosterContacts": {
                "handleIncomingSubscription": function (presence) {
                    var jid = presence.getAttribute('from');
                    var isAnonymousSession = _converse.tikiSettings && _converse.tikiSettings.anonymous === 'y';
                    var isGuestJid = isGeneratedGuestJid(jid);
                    if (isAnonymousSession || isGuestJid) {
                        try {
                            var stanza = converse.env.Strophe.$pres({ to: jid, type: 'unsubscribed' });
                            _converse.api.connection.get().send(stanza);
                        } catch (e) {}
                        return;
                    }
                    return this.__super__.handleIncomingSubscription.apply(this, arguments);
                },
            },
            "Bookmarks": {
                "openBookmarkedRoom": async function (bookmark) {
                    if (bookmark.get('autojoin')) {
                        const room = await _converse.api.rooms.create(bookmark.get('jid'), bookmark.get('nick'));
                    }
                    return bookmark;
                }
            },
            "ChatRoom": {
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

                    if (_converse.tikiSettings.anonymous !== 'y') {
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
                        _converse.tikiSettings.anonymous === 'y' &&
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
                    } catch(e) {}

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
