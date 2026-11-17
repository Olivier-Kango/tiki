/* Manual article playback with an auto-hiding control and sequential media. */
(() => {
    'use strict';
    function initialize() {
        if (document.documentElement.dataset.articleMediaReady) return;
        document.documentElement.dataset.articleMediaReady = "1";
        const body = document.querySelector('.tiki-article-page > .article .articlebody');
        if (!body) return;
        const article = body.closest('.article');
        const elements = [...body.querySelectorAll('audio, video, iframe')].filter(element => {
            if (element.closest('.article') !== article) return false;
            if (element.tagName !== 'IFRAME') return Boolean(element.getAttribute('src') || element.querySelector('source[src]'));
            try {const url = new URL(element.src); return /^(www\.)?(youtube\.com|youtube-nocookie\.com)$/.test(url.hostname) && url.pathname.startsWith('/embed/');} catch (_) {return false;}
        });
        if (!elements.length) return;
        const labels = document.querySelector('.tiki-article-page').dataset;
        const text = (a, b) => ({
            'Article playback': labels.mediaRegion, 'Pause': labels.mediaPause, 'Play': labels.mediaPlay,
            'Listen to the article': labels.mediaListen, 'Press Play to listen to this article.': labels.mediaBlocked,
            'Playback finished.': labels.mediaFinished, 'Audio unavailable.': labels.mediaError,
            'Video unavailable.': labels.mediaError
        })[b] || b;
        let current = 0, playing = false, requested = false, attempt = 0, scrollTimer;
        const dock = document.createElement('div');
        dock.className = 'article-media';
        dock.setAttribute('role','region');
        dock.setAttribute('aria-label',text('Lecture de l’article','Article playback'));
        const button = document.createElement('button');
        button.type = 'button';
        const label = document.createElement('span');
        label.className = 'article-media-label';
        const status = document.createElement('span');
        status.className = 'visually-hidden';
        status.setAttribute('role','status');
        const icon = document.createElement('span');
        icon.className = 'article-media-icon';
        icon.setAttribute('aria-hidden','true');
        button.append(icon,label);
        dock.append(button,status);
        const position = document.createElement('div');
        position.className = 'article-media-position';
        const heading = article.querySelector('.articletitle');
        if (heading) heading.after(position);
        else body.before(position);
        position.append(dock);
        function startDock() {
            if (!dock.classList.contains('is-floating')) {
                const focused = document.activeElement === button;
                document.body.append(dock);
                if (focused) button.focus({preventScroll: true});
                dock.classList.add('is-floating');
            }
        }
        function finishDock() {
            clearTimeout(scrollTimer);
            dock.classList.remove('is-floating','is-compact');
            const focused = document.activeElement === button;
            position.append(dock);
            if (focused) button.focus({preventScroll: true});
        }
        function onScroll() {
            if (!dock.classList.contains('is-floating')) return;
            clearTimeout(scrollTimer);
            dock.classList.add('is-compact');
            scrollTimer = setTimeout(() => dock.classList.remove('is-compact'), 600);
        }
        function render(on) {
            playing = on;
            const action = on ? text('Mettre en pause','Pause') : text('Lancer la lecture','Play');
            icon.innerHTML = on
                ? '<svg viewBox="0 0 24 24" focusable="false"><rect x="6" y="5" width="4" height="14" rx="1.5"/><rect x="14" y="5" width="4" height="14" rx="1.5"/></svg>'
                : '<svg viewBox="0 0 24 24" focusable="false"><path d="M8 5.5a1 1 0 0 1 1.5-.86l10 6.5a1 1 0 0 1 0 1.72l-10 6.5A1 1 0 0 1 8 18.5z"/></svg>';
            button.setAttribute('aria-pressed', String(on));
            button.setAttribute('aria-label',action);
            button.title = action;
            label.textContent = text('Écouter l’article','Listen to the article') + (elements.length > 1 ? ` · ${current+1}/${elements.length}` : '');
            if (on) startDock();

        }
        const tracks = elements.map((element,index) => ({element,index,player:null,ready:null}));
        function pause(track) {
            if (track.element.tagName !== 'IFRAME') track.element.pause();
            else track.player?.pauseVideo?.();
        }
        function activate(index) {
            if (current !== index) {attempt++; pause(tracks[current]); current = index;}
            tracks.forEach(track => {if (track.index !== index) pause(track);});
        }
        async function play(index) {
            activate(index);
            requested = true;
            const ticket = ++attempt, track = tracks[index];
            try {
                if (track.element.tagName !== 'IFRAME') await track.element.play();
                else {const ready = await new Promise(resolve => {
                    const timeout = setTimeout(() => resolve(false), 12000);
                    track.ready.then(() => {clearTimeout(timeout); resolve(true);});
                }); if (ticket !== attempt) return; if (!ready) throw new Error('YouTube unavailable'); track.player.playVideo();}
            } catch (_) {
                if (ticket !== attempt) return;
                requested = false;
                render(false);
                status.textContent = text('Cliquez sur Lecture pour écouter cet article.','Press Play to listen to this article.');
            }
        }
        function ended(index) {
            if (current !== index) return;
            requested = false;
            render(false);
            if (index + 1 < tracks.length) play(index + 1);
            else {finishDock();current=0;render(false);status.textContent=text('Lecture terminée.','Playback finished.');}
        }
        tracks.filter(track => track.element.tagName !== 'IFRAME').forEach(track => {
            track.element.autoplay = false;
            track.element.removeAttribute('autoplay');
            track.element.addEventListener('play', () => {requested=false;activate(track.index);render(true);});
            track.element.addEventListener('pause', () => {if (current === track.index) render(false);});
            track.element.addEventListener('ended', () => ended(track.index));
            track.element.addEventListener('error', () => {if (current === track.index) {render(false);status.textContent=text('Audio indisponible.','Audio unavailable.');}});
        });
        const videos = tracks.filter(track => track.element.tagName === 'IFRAME');
        if (videos.length) {
            videos.forEach(track => {
                const frame = track.element, url = new URL(frame.src);
                // Tiki may render HTTP embeds on a local HTTP instance; YouTube requires HTTPS.
                url.protocol = 'https:';
                url.searchParams.set('enablejsapi','1');
                url.searchParams.set('origin',location.origin);
                url.searchParams.set('autoplay','0');
                frame.allow += '; autoplay';
                frame.src = url.href;
                track.ready = new Promise(resolve => {track.resolve = resolve;});
            });
            const setup = () => {
                if (!window.YT?.Player) return;
                videos.forEach(track => {
                    if (track.player) return;
                    track.player = new window.YT.Player(track.element, {events:{
                        onReady: () => track.resolve(),
                        onStateChange: event => {
                            if (event.data === 1) {requested=false;activate(track.index);render(true);}
                            else if (event.data === 2 && current === track.index) render(false);
                            else if (event.data === 0) ended(track.index);
                        },
                        onAutoplayBlocked: () => {if(current === track.index) {requested=false;render(false);status.textContent=text('Cliquez sur Lecture pour écouter cet article.','Press Play to listen to this article.');}},
                        onError: () => {if(current === track.index) {requested=false;render(false);status.textContent=text('Vidéo indisponible.','Video unavailable.');}}
                    }});
                });
            };
            const previous = window.onYouTubeIframeAPIReady;
            window.onYouTubeIframeAPIReady = () => {if (typeof previous === 'function') previous(); setup();};
            if (window.YT?.Player) setup();
            else if (!document.querySelector('script[src="https://www.youtube.com/iframe_api"]')) {
                const script = document.createElement('script');script.src='https://www.youtube.com/iframe_api';document.head.append(script);
            }
        }
        button.addEventListener('click', () => {
            const track = tracks[current];
            const active = track.element.tagName !== 'IFRAME'
                ? !track.element.paused && !track.element.ended
                : track.player?.getPlayerState ? track.player.getPlayerState() === 1 : playing;
            if (active || requested) {
                attempt++;
                requested = false;
                tracks.forEach(pause);
                render(false);
            } else play(current);
        });
        window.addEventListener('scroll',onScroll,{passive:true});
        window.addEventListener('pagehide', () => {attempt++; requested=false; tracks.forEach(pause); finishDock();});
        render(false);
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded',initialize);
    else initialize();
})();
