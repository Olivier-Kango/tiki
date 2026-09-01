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

    var FULLSCREEN_CHAT_KEY = 'tiki_xmpp_fullscreen_chat';
    function takeFullscreenChatSelection() {
        try {
            var stored = window.sessionStorage.getItem(FULLSCREEN_CHAT_KEY);
            window.sessionStorage.removeItem(FULLSCREEN_CHAT_KEY);
            if (!stored) return null;
            var selection = JSON.parse(stored);
            return selection && selection.jid ? selection : null;
        } catch (e) {
            return null;
        }
    }

    var SESSION_HEARTBEAT_KEY = 'tiki_xmpp_session_heartbeat';

    var lastKnownSessionActiveAt = (function () {
        try {
            var stored = window.localStorage.getItem(SESSION_HEARTBEAT_KEY);
            return stored ? parseInt(stored, 10) : 0;
        } catch (e) {
            return 0;
        }
    })();

    function touchSessionHeartbeat() {
        try {
            window.localStorage.setItem(SESSION_HEARTBEAT_KEY, String(Date.now()));
        } catch (e) {}
    }

    touchSessionHeartbeat();
    setInterval(touchSessionHeartbeat, 10000);
    window.addEventListener('pagehide', touchSessionHeartbeat);
    window.addEventListener('beforeunload', touchSessionHeartbeat);

    var READ_CURSORS_KEY = 'tiki_xmpp_read_cursors';
    var CHAT_ACTIVITY_KEY = 'tiki_xmpp_chat_activity';
    var chatSortScheduled = false;

    function getChatActivity() {
        try {
            return JSON.parse(window.localStorage.getItem(CHAT_ACTIVITY_KEY) || '{}');
        } catch (e) {
            return {};
        }
    }

    function noteChatActivity(jid, time) {
        var bare = String(jid || '').split('/')[0].toLowerCase();
        if (!bare) return;
        var timestamp = new Date(time || Date.now()).getTime();
        if (!isFinite(timestamp)) timestamp = Date.now();
        try {
            var activity = getChatActivity();
            if (!activity[bare] || timestamp > activity[bare]) {
                activity[bare] = timestamp;
                window.localStorage.setItem(CHAT_ACTIVITY_KEY, JSON.stringify(activity));
            }
        } catch (e) {}
        scheduleChatListSort();
    }

    function getListItemJid(row) {
        var contactEl = row.matches && row.matches('converse-roster-contact') ? row : row.querySelector('converse-roster-contact');
        var contact = contactEl && contactEl.model;
        var jid = contact && (contact.get('jid') || contact.get('id'));
        if (!jid) {
            var jidEl = row.matches && row.matches('[data-room-jid], [data-jid], [jid]') ? row : row.querySelector('[data-room-jid], [data-jid], [jid]');
            jid = jidEl && (jidEl.getAttribute('data-room-jid') || jidEl.getAttribute('data-jid') || jidEl.getAttribute('jid'));
        }
        if (!jid && row.model && typeof row.model.get === 'function') {
            jid = row.model.get('jid') || row.model.get('id');
        }
        return String(jid || '').split('/')[0].toLowerCase();
    }

    function sortChatList(list, activity) {
        if (!list) return;
        var rows = Array.prototype.slice.call(list.children);
        rows.forEach(function (row, index) {
            row.__tikiChatJid = getListItemJid(row);
            row.__tikiOriginalIndex = index;
        });
        if (!rows.some(function (row) { return row.__tikiChatJid; })) return;
        var sorted = rows.slice().sort(function (a, b) {
            var byActivity = (activity[b.__tikiChatJid] || 0) - (activity[a.__tikiChatJid] || 0);
            return byActivity || a.__tikiOriginalIndex - b.__tikiOriginalIndex;
        });
        if (sorted.some(function (row, index) { return row !== rows[index]; })) {
            sorted.forEach(function (row) { list.appendChild(row); });
        }
    }

    function scheduleChatListSort() {
        if (chatSortScheduled) return;
        chatSortScheduled = true;
        window.requestAnimationFrame(function () {
            chatSortScheduled = false;
            var activity = getChatActivity();
            document.querySelectorAll('#conversejs .roster-group').forEach(function (group) {
                sortChatList(group.querySelector('.roster-group-contacts'), activity);
            });
            document.querySelectorAll('#conversejs .open-rooms-list').forEach(function (list) {
                var domainLists = list.querySelectorAll('.muc-domain-group-rooms');
                if (domainLists.length) {
                    domainLists.forEach(function (domainList) { sortChatList(domainList, activity); });
                } else {
                    sortChatList(list, activity);
                }
            });
        });
    }

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
        if (_converse && _converse.tikiSettings && _converse.tikiSettings.anonymous === 'y') {
            return;
        }
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
        var TIKI_VIEW_MODES = {
            overlayed: { label: 'Overlayed', icon: 'fa-window-restore' },
            embedded: { label: 'Embedded', icon: 'fa-table-cells' },
            fullscreen: { label: 'Fullscreen', icon: 'fa-expand' }
        };
        function rememberChatForFullscreen(dropdown) {
            if (_converse.api.settings.get('on_xmpp_page') === 'y') return;
            var chatboxElement = dropdown.closest('.chatbox:not(#controlbox)');
            var jid = getChatboxElementJid(chatboxElement);
            if (!jid) return;
            var type = '';
            try {
                var model = _converse.state.chatboxes && _converse.state.chatboxes.get(jid);
                type = model && model.get('type');
            } catch (e) {}
            try {
                window.sessionStorage.setItem(FULLSCREEN_CHAT_KEY, JSON.stringify({
                    jid: String(jid).split('/')[0],
                    type: type
                }));
            } catch (e) {}
        }
        function addViewModeMenuItems(dropdown) {
            var menu = dropdown.querySelector('.dropdown-menu');
            if (!menu || menu.querySelector('.tiki-chat-viewmode-menu-item')) return;
            var current = _converse.api.settings.get('view_mode');
            var pluginDefault = (document.getElementById('conversejs') || {}).dataset;
            pluginDefault = pluginDefault ? (pluginDefault.pluginDefaultViewMode || '') : '';
            var modes = Object.keys(TIKI_VIEW_MODES);
            if (current === 'fullscreen') {
                modes = modes.filter(function (mode) { return mode !== 'overlayed'; });
                modes.push('overlayed');
            }
            modes.forEach(function (mode) {
                if (mode === current) return;
                if (mode === 'embedded' && _converse.api.settings.get('on_xmpp_page') !== 'y') return;
                var item = document.createElement('button');
                item.type = 'button';
                item.className = 'dropdown-item tiki-chat-viewmode-menu-item';
                var label = current === 'fullscreen' && mode === 'overlayed'
                    ? 'Quit fullscreen'
                    : TIKI_VIEW_MODES[mode].label;
                item.innerHTML = '<i class="fa ' + TIKI_VIEW_MODES[mode].icon + ' me-2" aria-hidden="true"></i>' + label;
                item.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    if (mode === 'fullscreen') rememberChatForFullscreen(dropdown);
                    document.cookie = 'tiki_xmpp_view_mode=' + pluginDefault + ':' + mode + '; path=/; max-age=31536000; SameSite=Lax';
                    location.reload();
                });
                menu.appendChild(item);
            });
        }
        function showExplicitlySelectedChat(event) {
            var target = event.target.closest('[data-room-jid], [data-jid], [jid]');
            var jid = target && (target.getAttribute('data-room-jid') || target.getAttribute('data-jid') || target.getAttribute('jid'));
            if (!jid) {
                var rosterElement = event.target.closest('converse-roster-contact');
                var contact = rosterElement && rosterElement.model;
                jid = contact && (contact.get('jid') || contact.get('id'));
            }
            if (!jid) return;
            forceShowChatboxElement(jid);
            // Converse's click handler creates unopened conversations. Reveal
            // only an existing model here: repeated async lookups used to
            // trigger concurrent renders and history loads on large chats.
            try {
                var box = _converse.state.chatboxes && _converse.state.chatboxes.get(jid);
                if (box) {
                    box.set({ hidden: false, minimized: false });
                    box.trigger('show');
                }
            } catch (e) {}
        }
        function isAnonymousProfileSession() {
            if (!_converse) return false;
            var settings = _converse.api && _converse.api.settings;
            var bareJid = _converse.session && _converse.session.get("bare_jid");
            return (settings && settings.get("anonymous") === "y") ||
                (settings && settings.get("authentication") === "anonymous") ||
                isGeneratedGuestJid(bareJid);
        }
        function restrictAnonymousProfile(root) {
            if (!root || !isAnonymousProfileSession()) return;

            var modals = [];
            var modalSelector = "converse-profile-modal, .profile-modal";
            var closestModal = root.closest && root.closest(modalSelector);
            if (closestModal) modals.push(closestModal);
            if (root.querySelectorAll) {
                root.querySelectorAll(modalSelector).forEach(function (modal) {
                    if (modals.indexOf(modal) === -1) modals.push(modal);
                });
            }

            modals.forEach(function (modal) {
                var controls = modal.querySelectorAll(
                    "#profile-tab, #profile-tabpanel, #password-tab, #reset-password-tab, " +
                    "#passwordreset-tab, #passwordreset-tabpanel, " +
                    "[href=\"#profile\"], [href=\"#password\"], [href=\"#reset-password\"], [href=\"#passwordreset-tabpanel\"], " +
                    "[aria-controls=\"profile\"], [aria-controls=\"password\"], [aria-controls=\"reset-password\"], " +
                    "[aria-controls=\"passwordreset-tabpanel\"], .logout, [data-action=\"logout\"], " +
                    ".modal-footer .btn-danger"
                );
                controls.forEach(function (control) {
                    var item = control.closest(".nav-item") || control;
                    item.hidden = true;
                    control.setAttribute("aria-hidden", "true");
                    if ("disabled" in control) control.disabled = true;
                });

                modal.querySelectorAll("button, a").forEach(function (control) {
                    var label = (control.textContent || "").trim().toLowerCase();
                    if (label === "log out" || label === "logout" || label === "reset password") {
                        var item = control.closest(".nav-item") || control;
                        item.hidden = true;
                        control.setAttribute("aria-hidden", "true");
                        if ("disabled" in control) control.disabled = true;
                    }
                });
            });
        }
        function enhance(root) {
            (root.querySelectorAll ? root.querySelectorAll('#conversejs .chat-msg__body') : []).forEach(classifyMessage);
            (root.querySelectorAll ? root.querySelectorAll('#conversejs converse-contact-approval-alert') : []).forEach(suppressGuestAlertElement);
            restrictAnonymousProfile(root);
            scheduleChatListSort();
        }
        var observer = new MutationObserver(function (records) {
            var rosterChanged = false;
            records.forEach(function (record) { record.addedNodes.forEach(function (node) {
                if (node.nodeType !== 1 || !node.matches) return;
                if (node.matches('#conversejs .chat-msg__body')) classifyMessage(node);
                if (node.querySelectorAll) node.querySelectorAll('#conversejs .chat-msg__body, .chat-msg__body').forEach(classifyMessage);
                if (node.matches('converse-contact-approval-alert')) suppressGuestAlertElement(node);
                if (node.querySelectorAll) node.querySelectorAll('converse-contact-approval-alert').forEach(suppressGuestAlertElement);
                restrictAnonymousProfile(node);
                var parentBox = node.closest && node.closest('#conversejs .chatbox:not(#controlbox)');
                if (parentBox) applyAutomaticChatboxSuppression(parentBox);
                if (node.querySelectorAll) node.querySelectorAll('#conversejs .chatbox:not(#controlbox), .chatbox:not(#controlbox)').forEach(applyAutomaticChatboxSuppression);
                if (
                    node.matches('#conversejs .roster-group, #conversejs .roster-group *, #conversejs .open-rooms-list, #conversejs .open-rooms-list *, #conversejs .rooms-list, #conversejs .rooms-list *') ||
                    (node.querySelector && node.querySelector('#conversejs .roster-group, .roster-group, #conversejs .open-rooms-list, .open-rooms-list, #conversejs .rooms-list, .rooms-list'))
                ) {
                    rosterChanged = true;
                }
            }); });
            if (rosterChanged) scheduleChatListSort();
        });
        observer.observe(document.body, { childList: true, subtree: true });
        enhance(document);
        document.addEventListener('click', function (event) {
            if (!event.target.closest) return;
            if (event.target.closest('#controlbox')) showExplicitlySelectedChat(event);
            var dropdownToggle = event.target.closest('.chatbox:not(#controlbox) converse-dropdown [data-bs-toggle="dropdown"], .chatbox:not(#controlbox) converse-dropdown.chatbox-btn');
            if (dropdownToggle) {
                var dropdown = dropdownToggle.closest('converse-dropdown');
                setTimeout(function () { addViewModeMenuItems(dropdown); }, 0);
                setTimeout(function () { addViewModeMenuItems(dropdown); }, 100);
            }
        }, true);
        window.addEventListener('pageshow', function () {
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
            'converse-mam',
        ],

        "initialize": function () {
            _converse = this._converse;
            installTikiChatPresentation();

            function primeNotificationsAndAudio() {
                if (window.Notification && Notification.permission === 'default') {
                    try {
                        var permissionRequest = Notification.requestPermission();
                        if (permissionRequest && typeof permissionRequest.catch === 'function') permissionRequest.catch(function () {});
                    } catch (e) {}
                }
                try {
                    if (window.Audio) {
                        var soundsPath = (_converse.api.settings.get('sounds_path')) || '/';
                        var audio = new Audio(soundsPath + 'msg_received.ogg');
                        audio.volume = 0;
                        var playback = audio.play();
                        if (playback && typeof playback.then === 'function') {
                            playback.then(function () { audio.pause(); }).catch(function () {});
                        } else {
                            audio.pause();
                        }
                    }
                } catch (e) {}
            }
            ['click', 'keydown', 'touchstart'].forEach(function (evt) {
                document.addEventListener(evt, primeNotificationsAndAudio, { once: true, capture: true });
            });

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
                tiki_destination_close_label: 'Close',
                tiki_destination_title: 'How would you like to chat?',
                tiki_destination_description: 'Join the community conversation, or chat privately with a member of our support team.',
                tiki_destination_community_label: 'Join the community group',
                tiki_destination_private_label: 'Chat privately with a team member',
                tiki_anonymous_nick: 'visitor',
            });
            // Load private archives like a modern messenger: a small initial
            // page, with older MAM pages requested when the user scrolls up.
            _converse.api.settings.set('archived_messages_page_size', '20');
            _converse.api.settings.set('mam_request_all_pages', false);
            _converse.api.settings.set('muc_nickname_from_jid', true);

            // Prosody can omit `to` from outgoing messages returned by a
            // private MAM query. Converse cannot route those archived stanzas,
            // even though the query's `with` value identifies the recipient.
            // Restore only that missing value before Converse parses them.
            var archiveApi = _converse.api.archive;
            if (archiveApi && typeof archiveApi.query === 'function' && !archiveApi.query.__tikiMissingToPatched) {
                var originalArchiveQuery = archiveApi.query;
                var patchedArchiveQuery = async function (query) {
                    var result = await originalArchiveQuery.apply(this, arguments);
                    var peerJid = query && !query.is_groupchat && query.mam && query.mam.with;
                    var ownJid = _converse.session && _converse.session.get('bare_jid');
                    if (!peerJid || !ownJid || !result || !Array.isArray(result.messages)) {
                        return result;
                    }
                    result.messages.forEach(function (stanza) {
                        if (!stanza || typeof stanza.getAttribute !== 'function') return;

                        var message = stanza;
                        var forwarded = stanza.getElementsByTagName && stanza.getElementsByTagName('forwarded');
                        if (forwarded && forwarded.length) {
                            for (var i = 0; i < forwarded.length; i++) {
                                if (forwarded[i].getAttribute('xmlns') !== converse.env.Strophe.NS.FORWARD) continue;
                                var messages = forwarded[i].getElementsByTagName('message');
                                if (messages.length) {
                                    message = messages[0];
                                    break;
                                }
                            }
                        }
                        if (message.getAttribute('to')) return;
                        var from = message.getAttribute('from');
                        var fromBare = from && converse.env.Strophe.getBareJidFromJid(from);
                        if (fromBare && fromBare.toLowerCase() === ownJid.toLowerCase()) {
                            message.setAttribute('to', peerJid);
                        }
                    });
                    return result;
                };
                patchedArchiveQuery.__tikiMissingToPatched = true;
                archiveApi.query = patchedArchiveQuery;
            }

            if (_converse.api.settings.get('anonymous') !== 'y') {
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
            }

            _converse.tikiSettings = {
                dm_target: _converse.api.settings.get('dm_target'),
                anon_room: _converse.api.settings.get('anon_room'),
                anonymous: _converse.api.settings.get('anonymous'),
                anonymous_nick: _converse.api.settings.get('tiki_anonymous_nick') || 'visitor',
                site_name: _converse.api.settings.get('tiki_site_name') || 'Tiki',
                site_logo: _converse.api.settings.get('tiki_site_logo') || '',
                support_label: _converse.api.settings.get('tiki_support_label') || 'Support',
                destination_close_label: _converse.api.settings.get('tiki_destination_close_label') || 'Close',
                destination_title: _converse.api.settings.get('tiki_destination_title') || 'How would you like to chat?',
                destination_description: _converse.api.settings.get('tiki_destination_description') || 'Join the community conversation, or chat privately with a member of our support team.',
                destination_community_label: _converse.api.settings.get('tiki_destination_community_label') || 'Join the community group',
                destination_private_label: _converse.api.settings.get('tiki_destination_private_label') || 'Chat privately with a team member',
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

                function patchMissingJidVCardLookup(Ctor) {
                    if (!Ctor || !Ctor.prototype || typeof Ctor.prototype.getVCard !== 'function' || Ctor.prototype.__tikiMissingJidVCardPatched) {
                        return;
                    }
                    Ctor.prototype.__tikiMissingJidVCardPatched = true;
                    var original = Ctor.prototype.getVCard;
                    Ctor.prototype.getVCard = function () {
                        var from = this.get && (this.get('jid') || this.get('from'));
                        var occupantJid = this.occupant && this.occupant.get && this.occupant.get('jid');
                        if (!from && !occupantJid) {
                            // Converse cannot perform a VCard request without a
                            // JID. System and fully-anonymous MUC messages are
                            // valid, so skip only this impossible lookup.
                            return Promise.resolve(null);
                        }
                        return original.apply(this, arguments);
                    };
                }

                function patchNonBlockingInitialMessageFetch(Ctor) {
                    if (!Ctor || !Ctor.prototype || typeof Ctor.prototype.fetchMessages !== 'function' || Ctor.prototype.__tikiNonBlockingFetchPatched) {
                        return;
                    }
                    Ctor.prototype.__tikiNonBlockingFetchPatched = true;
                    var originalFetchMessages = Ctor.prototype.fetchMessages;
                    Ctor.prototype.fetchMessages = function () {
                        // Keep groupchat restoration unchanged. This shortcut
                        // is only for potentially very long one-to-one chats.
                        if (this.get('type') !== 'chatbox' || !this.messages || this.messages.fetched_flag) {
                            return originalFetchMessages.apply(this, arguments);
                        }
                        var storage = this.messages.storage;
                        if (storage && storage.store && typeof storage.findAll === 'function' && !storage.__tikiRecentMessagesOnly) {
                            storage.__tikiRecentMessagesOnly = true;
                            var originalFindAll = storage.findAll;
                            storage.findAll = async function () {
                                // Read only the newest local page initially.
                                // Nothing is deleted; older history remains in
                                // IndexedDB and can also be retrieved via MAM.
                                storage.findAll = originalFindAll;
                                try {
                                    await storage.storeInitialized;
                                    var keys = await storage.store.getItem(storage.name);
                                    if (!Array.isArray(keys) || !keys.length) return [];
                                    var recentKeys = keys.slice(-20);
                                    var items = await storage.store.getItems(recentKeys);
                                    return recentKeys.map(function (key) { return items[key]; }).filter(Boolean);
                                } catch (e) {
                                    return originalFindAll.call(storage);
                                }
                            };
                        }
                        try {
                            var fetchResult = originalFetchMessages.apply(this, arguments);
                            if (fetchResult && typeof fetchResult.catch === 'function') {
                                fetchResult.catch(function () {});
                            }
                        } catch (e) {
                            return Promise.reject(e);
                        }
                        // Let the chatbox mount immediately while IndexedDB is
                        // read in the background.
                        return Promise.resolve();
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
                patchNonBlockingInitialMessageFetch(_converse.exports.ChatBox);
                patchMissingJidVCardLookup(_converse.exports.Message);
                patchMissingJidVCardLookup(_converse.exports.MUCMessage);
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
                    roster.on('add change:jid', function (contact) {
                        suppressGuestContactSuggestion(contact);
                        scheduleChatListSort();
                    });
                }).catch(function () {});

                Promise.resolve(_converse.api.waitUntil('chatBoxesInitialized')).then(function () {
                    function trackChatboxActivity(chatbox) {
                        if (!chatbox || chatbox.__tikiActivityTracked) return;
                        chatbox.__tikiActivityTracked = true;
                        var jid = chatbox.get('jid');
                        if (!jid || !chatbox.messages) return;
                        if (chatbox.messages.models.length) {
                            var latest = chatbox.messages.models.reduce(function (time, message) {
                                var candidate = new Date(message.get('time') || 0).getTime();
                                return isFinite(candidate) && candidate > time ? candidate : time;
                            }, 0);
                            if (latest) noteChatActivity(jid, latest);
                        }
                        chatbox.messages.on('add', function (message) {
                            if (!message || message.get('is_error')) return;
                            noteChatActivity(jid, message.get('time') || Date.now());
                        });
                    }
                    (_converse.state.chatboxes.models || []).forEach(trackChatboxActivity);
                    _converse.state.chatboxes.on('add', trackChatboxActivity);
                    scheduleChatListSort();
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

            function isOwnAttrsMessage(attrs, chatbox) {
                if (chatbox.get('type') === 'chatroom') {
                    var resource = attrs.from && converse.env.Strophe.getResourceFromJid(attrs.from);
                    return !!(resource && chatbox.get('nick') && resource === chatbox.get('nick'));
                }
                var bareJid = _converse.session && _converse.session.get('bare_jid');
                var fromBare = attrs.from && converse.env.Strophe.getBareJidFromJid(attrs.from);
                return !!(bareJid && fromBare && fromBare.toLowerCase() === bareJid.toLowerCase());
            }

            var countedArchivedMessageKeys = new Set();

            function countArchivedMessageAsUnreadIfNeeded(attrs, chatbox) {
                if (!attrs || !chatbox || !attrs.time || !attrs.body) {
                    return;
                }
                var jid = chatbox.get('jid');
                var cursor = jid && getReadCursors()[jid];
                var isReadPerCursor = cursor && new Date(attrs.time) <= new Date(cursor);
                if (isReadPerCursor) {
                    Promise.resolve().then(function () {
                        if (typeof chatbox.clearUnreadMsgCounter === 'function') {
                            chatbox.clearUnreadMsgCounter();
                        }
                    });
                }
                var msgTime = new Date(attrs.time).getTime();
                if (!(msgTime > lastKnownSessionActiveAt)) {
                    return;
                }
                if (isOwnAttrsMessage(attrs, chatbox)) {
                    return;
                }
                if (chatbox.get('hidden') === false) {
                    return;
                }
                var dedupKey = jid + '|' + attrs.from + '|' + attrs.time + '|' + attrs.body.slice(0, 80);
                if (countedArchivedMessageKeys.has(dedupKey)) {
                    return;
                }
                countedArchivedMessageKeys.add(dedupKey);
                Promise.resolve().then(function () {
                    try {
                        if (chatbox.get('type') === 'chatroom') {
                            chatbox.save('num_unread_general', (chatbox.get('num_unread_general') || 0) + 1);
                        } else {
                            chatbox.save('num_unread', (chatbox.get('num_unread') || 0) + 1);
                        }
                    } catch (e) {}
                });
            }

            _converse.api.listen.on('message', function (data) {
                try {
                    var attrs = data && data.attrs;
                    var chatbox = data && data.chatbox;
                    if (!attrs || !chatbox || (!attrs.is_archived && !attrs.is_forwarded && !attrs.is_delayed)) {
                        return;
                    }
                    countArchivedMessageAsUnreadIfNeeded(attrs, chatbox);
                } catch (e) {}
            });

            _converse.api.listen.on('MAMResult', function (data) {
                try {
                    var chatbox = data && data.chatbox;
                    var messages = data && data.messages;
                    if (!chatbox || !messages || !messages.length) {
                        return;
                    }
                    messages.forEach(function (attrs) {
                        if (attrs && !attrs.is_error) {
                            countArchivedMessageAsUnreadIfNeeded(attrs, chatbox);
                        }
                    });
                } catch (e) {}
            });

            _converse.api.listen.on('message', function (data) {
                var attrs = data && data.attrs;
                var chatbox = data && data.chatbox;
                if (!attrs || !chatbox || attrs.is_archived || attrs.is_forwarded || attrs.is_error) {
                    return;
                }
                if (!attrs.body && !attrs.message) {
                    return;
                }
                // Incoming and outgoing messages make their contact or room
                // the most recent item in its current sidebar section.
                noteChatActivity(chatbox.get('jid'), attrs.time || Date.now());
                function openChatbox() {
                    try {
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
                }
                if (attrs.type === 'groupchat') {
                    Promise.resolve().then(openChatbox);
                } else {
                    openChatbox();
                }
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

                function showAnonymousDestinationChoice() {
                    var oldChoice = document.querySelector('.tiki-chat-destination-choice');
                    if (oldChoice) oldChoice.remove();
                    var backdrop = document.createElement('div');
                    backdrop.className = 'tiki-chat-destination-choice';
                    backdrop.innerHTML = '<div class="tiki-chat-destination-card" role="dialog" aria-modal="true" aria-labelledby="tiki-chat-destination-title">' +
                        '<button type="button" class="tiki-chat-destination-close">&times;</button>' +
                        '<h2 id="tiki-chat-destination-title"></h2>' +
                        '<p></p>' +
                        '<button type="button" class="btn btn-primary tiki-chat-destination-community"></button>' +
                        '<button type="button" class="btn btn-outline-primary tiki-chat-destination-private"></button>' +
                        '</div>';
                    backdrop.querySelector('.tiki-chat-destination-close').setAttribute('aria-label', settings.destination_close_label);
                    backdrop.querySelector('#tiki-chat-destination-title').textContent = settings.destination_title;
                    backdrop.querySelector('.tiki-chat-destination-card > p').textContent = settings.destination_description;
                    backdrop.querySelector('.tiki-chat-destination-community').textContent = settings.destination_community_label;
                    backdrop.querySelector('.tiki-chat-destination-private').textContent = settings.destination_private_label;
                    function closeChoice() { backdrop.remove(); }
                    backdrop.addEventListener('click', function (event) {
                        if (event.target === backdrop || event.target.closest('.tiki-chat-destination-close')) closeChoice();
                    });
                    backdrop.querySelector('.tiki-chat-destination-community').addEventListener('click', function () {
                        closeChoice();
                        Promise.resolve(_converse.api.rooms.create(settings.anon_room)).then(showChatbox).catch(function () {});
                    });
                    backdrop.querySelector('.tiki-chat-destination-private').addEventListener('click', function () {
                        closeChoice();
                        Promise.resolve(_converse.api.chats.open(settings.dm_target, {}, true)).then(showChatbox).catch(function () {});
                    });
                    document.body.appendChild(backdrop);
                    backdrop.querySelector('.tiki-chat-destination-community').focus();
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

                if (settings.dm_target && settings.anon_room) {
                    showAnonymousDestinationChoice();
                    return;
                }

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
                const fullscreenSelection = viewMode === 'fullscreen' && !onXmppPage
                    ? takeFullscreenChatSelection()
                    : null;
                await tikiStrictHideEverythingExceptThisPage();

                if (fullscreenSelection) {
                    try {
                        await _converse.api.waitUntil('chatBoxesInitialized');
                        let selectedChat = await _converse.api.chatboxes.get(fullscreenSelection.jid);
                        if (!selectedChat && fullscreenSelection.type === 'chatroom') {
                            selectedChat = await _converse.api.rooms.create(fullscreenSelection.jid);
                        } else if (!selectedChat) {
                            selectedChat = await _converse.api.chats.get(fullscreenSelection.jid, {}, true);
                        }
                        if (selectedChat) {
                            forceShowChatboxElement(fullscreenSelection.jid);
                            selectedChat.set({ hidden: false, minimized: false, closed: false });
                            if (typeof selectedChat.maybeShow === 'function') {
                                selectedChat.maybeShow(true);
                            } else {
                                selectedChat.trigger('show');
                            }
                        }
                    } catch (e) {}
                }

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

            var supportPresenceListenersBound = false;
            var SUPPORT_ONLINE_WINDOW_MS = 45000;

            function setupSupportPresenceTracking() {
                var tikiSettings = _converse.tikiSettings;
                if (!tikiSettings || tikiSettings.anonymous !== 'y' || !tikiSettings.dm_target) {
                    return;
                }
                var supportJid = tikiSettings.dm_target;

                if (supportPresenceListenersBound) {
                    return;
                }
                supportPresenceListenersBound = true;

                function formatLastSeen(msAgo) {
                    var minutes = Math.round(msAgo / 60000);
                    if (minutes < 1) return 'Last seen just now';
                    if (minutes < 60) return 'Last seen ' + minutes + ' min ago';
                    var hours = Math.round(minutes / 60);
                    if (hours < 24) return 'Last seen ' + hours + ' h ago';
                    var days = Math.round(hours / 24);
                    return 'Last seen ' + days + ' d ago';
                }

                function renderSupportStatus(activityModel) {
                    var el = findChatboxElement(supportJid);
                    if (!el) return;
                    var titleEl = el.querySelector('.chatbox-title__text');
                    if (!titleEl) return;
                    var lastActivityAt = activityModel.get('tiki_last_activity_at');
                    var serverLastSeenAt = activityModel.get('tiki_server_last_seen_at');
                    var referenceAt = lastActivityAt || serverLastSeenAt;
                    var msSinceActivity = referenceAt ? (Date.now() - referenceAt) : Infinity;
                    var isOnline = activityModel.get('tiki_xmpp_online') === true ||
                        (activityModel.get('tiki_activity_online_until') || 0) > Date.now();
                    var badge = titleEl.querySelector('.tiki-support-status');
                    if (!badge) {
                        badge = document.createElement('span');
                        badge.className = 'tiki-support-status';
                        badge.style.fontWeight = 'normal';
                        badge.style.fontSize = '0.8em';
                        badge.style.marginTop = '2px';
                        badge.style.lineHeight = '1.2';
                        badge.style.whiteSpace = 'nowrap';
                        badge.style.overflow = 'hidden';
                        badge.style.textOverflow = 'ellipsis';
                        badge.style.maxWidth = '100%';
                        titleEl.appendChild(badge);
                    }
                    badge.style.color = isOnline ? '#2ecc71' : '#999';
                    badge.textContent = isOnline
                        ? 'Online'
                        : (referenceAt ? formatLastSeen(msSinceActivity) : 'Offline');
                }

                function queryServerLastSeen(activityModel) {
                    if (activityModel.get('tiki_last_seen_query_pending')) return;
                    activityModel.set('tiki_last_seen_query_pending', true);
                    try {
                        var Strophe = converse.env.Strophe;
                        var iq = new Strophe.Builder('iq', { to: supportJid, type: 'get' }).c('query', { xmlns: 'jabber:iq:last' });
                        _converse.api.sendIQ(iq, 10000, false).then(function (stanza) {
                            if (!stanza || stanza.getAttribute('type') === 'error') {
                                return;
                            }
                            var queryEl = stanza.querySelector('query');
                            var secondsAttr = queryEl && queryEl.getAttribute('seconds');
                            var seconds = secondsAttr !== null ? parseInt(secondsAttr, 10) : NaN;
                            if (isNaN(seconds)) {
                                return;
                            }
                            activityModel.set('tiki_server_last_seen_at', Date.now() - (seconds * 1000));
                            renderSupportStatus(activityModel);
                        }).catch(function () {}).finally(function () {
                            activityModel.set('tiki_last_seen_query_pending', false);
                        });
                    } catch (e) {
                        activityModel.set('tiki_last_seen_query_pending', false);
                    }
                }

                function lastActivityStorageKey() {
                    return 'tiki_xmpp_last_activity:' + supportJid;
                }

                function loadStoredLastActivity() {
                    try {
                        var stored = window.localStorage.getItem(lastActivityStorageKey());
                        return stored ? parseInt(stored, 10) : null;
                    } catch (e) {
                        return null;
                    }
                }

                function storeLastActivity(timestamp) {
                    try {
                        window.localStorage.setItem(lastActivityStorageKey(), String(timestamp));
                    } catch (e) {}
                }

                Promise.resolve(_converse.api.chats.get(supportJid, {}, true)).then(function (chatbox) {
                    if (!chatbox) return;

                    var presences = _converse.state.presences;
                    var activityModel = presences.get(supportJid) || presences.create({ jid: supportJid });

                    // Only concrete presence resources or fresh chat activity
                    // may promote the support account to online.
                    activityModel.set('tiki_xmpp_online', false);
                    activityModel.set('tiki_activity_online_until', 0);

                    var storedActivity = loadStoredLastActivity();
                    if (storedActivity && !activityModel.get('tiki_last_activity_at')) {
                        activityModel.set('tiki_last_activity_at', storedActivity);
                    }

                    function markActive() {
                        var now = Date.now();
                        activityModel.set('tiki_last_activity_at', now);
                        activityModel.set('tiki_activity_online_until', now + SUPPORT_ONLINE_WINDOW_MS);
                        storeLastActivity(now);
                        renderSupportStatus(activityModel);
                    }

                    // Presence is aggregated across every XMPP resource, so an
                    // administrator connected through Tiki, Gajim, Monal, or
                    // another client is treated the same way. Keep a resource
                    // set because one phone going offline must not hide a still
                    // connected desktop client.
                    var onlineResources = new Set();
                    var connection = _converse.api.connection.get();
                    if (connection && typeof connection.addHandler === 'function') {
                        connection.addHandler(function (stanza) {
                            try {
                                var from = stanza.getAttribute('from') || '';
                                var fromBare = converse.env.Strophe.getBareJidFromJid(from);
                                if (!fromBare || fromBare.toLowerCase() !== supportJid.toLowerCase()) return true;
                                var resource = converse.env.Strophe.getResourceFromJid(from) || '__bare__';
                                var type = stanza.getAttribute('type') || 'available';
                                if (type === 'unavailable' || type === 'error') {
                                    if (resource === '__bare__') onlineResources.clear();
                                    else onlineResources.delete(resource);
                                } else if (type === 'available') {
                                    onlineResources.add(resource);
                                } else {
                                    // Subscription acknowledgements and probes
                                    // are presence protocol traffic, not proof
                                    // that this user has an online resource.
                                    return true;
                                }
                                var online = onlineResources.size > 0;
                                activityModel.set('tiki_xmpp_online', online);
                                if (online) markActive();
                                else {
                                    activityModel.set('tiki_activity_online_until', 0);
                                    renderSupportStatus(activityModel);
                                }
                            } catch (e) {}
                            return true;
                        }, null, 'presence');
                    }

                    function syncAggregatedPresence() {
                        var show = activityModel.get('show');
                        if (!show) return;
                        var online = show !== 'offline' && show !== 'unavailable';
                        activityModel.set('tiki_xmpp_online', online);
                        if (online) markActive();
                        else renderSupportStatus(activityModel);
                    }
                    activityModel.on('change:show', syncAggregatedPresence);

                    // Do not read `show` immediately from a model that may
                    // have just been created locally: its default is not proof
                    // that a remote client is connected. Existing resources,
                    // however, are concrete sessions already seen by Converse.
                    if (activityModel.resources) {
                        var syncPresenceResources = function () {
                            var count = activityModel.resources.length || 0;
                            activityModel.set('tiki_xmpp_online', count > 0);
                            if (count > 0) markActive();
                            else {
                                activityModel.set('tiki_activity_online_until', 0);
                                renderSupportStatus(activityModel);
                            }
                        };
                        activityModel.resources.on('add remove reset', syncPresenceResources);
                        if (activityModel.resources.length) syncPresenceResources();
                    }

                    if (chatbox.notifications) {
                        chatbox.notifications.on('change', function () {
                            var composing = chatbox.notifications.get('composing');
                            var chatState = chatbox.notifications.get('chat_state');
                            if ((composing && composing.length) || chatState === 'composing' || chatState === 'active') {
                                markActive();
                            }
                        });
                    }

                    if (chatbox.messages) {
                        chatbox.messages.on('add', function (message) {
                            try {
                                if (!message || typeof message.get !== 'function') return;
                                var sender = message.get('sender');
                                var isArchived = message.get('is_archived') || message.get('is_forwarded');
                                var time = message.get('time');
                                var msgAgeMs = time ? (Date.now() - new Date(time).getTime()) : Infinity;
                                var isFresh = msgAgeMs >= 0 && msgAgeMs < 10000;
                                if (sender !== 'me' && !isArchived && isFresh) {
                                    markActive();
                                }
                            } catch (e) {}
                        });
                    }

                    _converse.api.listen.on('message', function (data) {
                        try {
                            var attrs = data && data.attrs;
                            if (!attrs || attrs.is_archived || attrs.is_forwarded) return;
                            var fromBare = attrs.from && converse.env.Strophe.getBareJidFromJid(attrs.from);
                            if (fromBare && fromBare.toLowerCase() === supportJid.toLowerCase()) {
                                markActive();
                            }
                        } catch (e) {}
                    });

                    renderSupportStatus(activityModel);
                    queryServerLastSeen(activityModel);
                    [0, 50, 150, 350, 700, 1500, 3000, 5000].forEach(function (delay) {
                        setTimeout(function () { renderSupportStatus(activityModel); }, delay);
                    });
                    setInterval(function () {
                        renderSupportStatus(activityModel);
                        queryServerLastSeen(activityModel);
                    }, 60000);
                }).catch(function () {});
            }

            _converse.api.listen.on('connected', setupSupportPresenceTracking);
            _converse.api.listen.on('reconnected', setupSupportPresenceTracking);
        },

        "overrides": {
            "ChatBoxes": {
                "createModel": function (attrs, options) {
                    if (!attrs || typeof attrs !== 'object' || !attrs.type) {
                        return { validationError: 'tiki: ignoring an invalid cached chatbox' };
                    }
                    if (!_converse.api.chatboxes.registry.get(attrs.type)) {
                        return { validationError: 'tiki: chatbox type "' + attrs.type + '" is not available in this view mode' };
                    }
                    return this.__super__.createModel.apply(this, arguments);
                },
            },
            "RosterContacts": {
                "createModel": function (attrs, options) {
                    if (!attrs || typeof attrs !== 'object' || !attrs.jid) {
                        return { validationError: 'tiki: ignoring an invalid roster contact without a JID' };
                    }
                    return this.__super__.createModel.apply(this, arguments);
                },
                "handleIncomingSubscription": function (presence) {
                    var jid = presence.getAttribute('from');
                    var isAnonymousSession = _converse.tikiSettings && _converse.tikiSettings.anonymous === 'y';
                    var isGuestJid = isGeneratedGuestJid(jid);
                    if (isAnonymousSession || isGuestJid) {
                        try {
                            var Strophe = converse.env.Strophe;
                            var stanza = new Strophe.Builder('presence', { to: jid, type: 'unsubscribed' });
                            _converse.api.send(stanza);
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
                "isJoined": async function () {
                    var sessionResumed = _converse.session && _converse.session.get('smacks_resumed');
                    var skipResumedPing = sessionResumed && !this.__tikiSkippedResumedPing;
                    if (skipResumedPing) {
                        this.__tikiSkippedResumedPing = true;
                    }
                    if (!this.get('nick') || skipResumedPing) {
                        return false;
                    }
                    return this.__super__.isJoined.apply(this, arguments);
                },
                "getDiscoInfo": async function () {
                    var jid = this.get('jid');
                    var identity = await _converse.api.disco.getIdentity('conference', 'text', jid);
                    var name = identity && identity.get('name');

                    // During session resumption a restored MUC can refresh its
                    // disco data before the chatbox persistence adapter is
                    // attached. `save` then falls through to HTTP sync and
                    // throws because chatboxes intentionally have no URL.
                    this.set({ name: name || converse.env.Strophe.getNodeFromJid(jid) });
                    await this.getDiscoInfoFields();
                    await this.getDiscoInfoFeatures();
                },
                "sendMarkerForMessage": function (message, type, force) {
                    var stanzaIdField = 'stanza_id ' + this.get('jid');
                    if (message && !message.get(stanzaIdField)) {
                        // A marker cannot be addressed without the server's
                        // stanza ID. Skipping it is the XMPP-compliant no-op.
                        return Promise.resolve();
                    }
                    return this.__super__.sendMarkerForMessage.apply(this, arguments);
                },
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
