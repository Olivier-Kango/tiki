(function (window) {
    'use strict';

    window.TikiConverseNotifications = {
        install: function (options) {
            var converse = options.converse;
            // Converse's native audio path also handles events outside this policy.
            // Keep it disabled even after audio has been unlocked by a gesture.
            converse.api.settings.set('play_sounds', false);
            var audio;
            var priming = false;
            var ready = false;
            var lastSound = 0;
            var seen = new Set();

            function getAudio() {
                if (!audio && window.Audio) {
                    var path = converse.api.settings.get('sounds_path') ||
                        (converse.api.settings.get('assets_path') || '').replace(/\/$/, '') + '/sounds/';
                    audio = new window.Audio(path + 'msg_received.ogg');
                    if (audio.canPlayType && !audio.canPlayType('audio/ogg')) {
                        audio = new window.Audio(path + 'msg_received.mp3');
                    }
                }
                return audio;
            }

            function prime() {
                if (ready || priming || !window.Audio) return;
                priming = true;
                try {
                    getAudio();
                    audio.volume = 0;
                    Promise.resolve(audio.play()).then(function () {
                        audio.pause();
                        audio.currentTime = 0;
                        audio.volume = 1;
                        ready = true;
                        priming = false;
                    }).catch(function () { priming = false; });
                } catch (e) { priming = false; }
            }
            ['click', 'keydown', 'touchstart'].forEach(function (event) {
                window.document.addEventListener(event, prime, { capture: true });
            });

            converse.api.listen.on('message', function (data) {
                var attrs = data && data.attrs;
                var box = data && data.chatbox;
                if (!attrs || !box || !attrs.body || attrs.is_archived || attrs.is_delayed ||
                    attrs.is_forwarded || attrs.is_error || attrs.is_only_chat_state_notification) return;
                var room = box.get('type') === 'chatroom';
                var from = String(attrs.from || '');
                var ownJid = String(converse.session.get('bare_jid') || '').toLowerCase();
                var nick = box.get('nick');
                if (!from || attrs.sender === 'me' ||
                    (room ? (nick && from.slice(from.indexOf('/') + 1) === nick) :
                        (ownJid && from.split('/')[0].toLowerCase() === ownJid))) return;
                if (room) {
                    // Nickname boundaries avoid matching "ann" inside "joanna".
                    var escaped = String(nick || '').replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                    if (!escaped || !new RegExp('(^|\\W)' + escaped + '(?=$|\\W)', 'i').test(attrs.body)) return;
                }
                var jid = box.get('jid');
                var element = options.findChatboxElement(jid);
                if (!window.document.hidden && window.document.hasFocus() &&
                    box.get('hidden') === false && !box.get('minimized') &&
                    element && element.getClientRects().length &&
                    !(typeof box.isScrolledUp === 'function' && box.isScrolledUp())) return;

                var id = attrs.origin_id || attrs.stanza_id || attrs.msgid || attrs.id;
                var key = jid + '|' + from + '|' + (id || (attrs.time + '|' + attrs.body));
                if (seen.has(key)) return;
                seen.add(key);
                if (seen.size > 256) seen.delete(seen.values().next().value);
                var now = Date.now();
                // A restored tab may already have autoplay permission without a
                // new gesture. Let the browser decide instead of dropping every
                // notification until the user clicks this page again.
                if (priming || !getAudio() || now - lastSound < 2000) return;
                lastSound = now;
                try {
                    audio.volume = 1;
                    audio.currentTime = 0;
                    var playback = audio.play();
                    if (playback && playback.catch) playback.catch(function () {
                        if (lastSound === now) lastSound = 0;
                    });
                } catch (e) {}
            });
        },
    };
})(window);
