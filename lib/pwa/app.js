(function () {

    const cacheName = 'pages-cache-v1';

    const offlineFeedback = {message: "Tiki will try to handle your requests to send them later.", type: "warning", title: "Tiki is offline"};
    const onlineFeedback = {message: "You may need to sync your data now.", type: "success", title: "Tiki is back online"};
    const syncButton = {enable: false, disable: true}; // disable by default
    let requestCount = 0; // count cached requests

    $.loadCache = function (pages) {
        console.warn("Lets fetch", pages);
        let urls = pages["wiki"].map(function (page) {
            return 'tiki-index.php?page=' + encodeURI(page);
        });
        urls = urls.concat(pages["urls"]);
        urls = urls.concat(
            pages["trackers"].map(function (page) {
                return 'tiki-view_tracker_item.php?itemId=' + page.itemId;
            })
        );
        urls = urls.concat(
            pages["trackers"].map(function (tracker) {
                return 'tiki-ajax_services.php?controller=tracker&action=update_item&trackerId=' + tracker.id + '&itemId=' + tracker.itemId;
            })
        );
        caches.open(cacheName)
            .then(cache => urls.map(url => cache.match(url).then(z => (!z) ? cache.add(url) : false).catch(x => console.error(x))));

    };
    /**
     * Send custom feedback in pwa mode
     *
     * @param {string} message - Feedback message
     * @param {string} type - Type of alert: error, warning, success or info (default)
     * @param {string} title - Feedback title
     *
     */
    $.pwaFeedback = function (message, type, title) {
        feedback(tr(message), type, false, tr(title), "", true);
    };
    /**
     * Enabling or disabling sync button
     * @param {boolean} status - true or false
     */
    $.syncButtonControl = function (status) {
        $("#sync-pwa").prop('disabled', status);
    };

    if (!navigator.serviceWorker) {
        console.warn("Service Worker Unavailable");
        return;
    }
    navigator.serviceWorker.register('./sw.js').then(() => {
        //init database
        console.warn("init app");


        const db = new Dexie("post_cache");
        db.version(1).stores({
            messages: 'name,value', //table work like a flag. SW change the message to flag the ui that a warning need to be shown
            post_cache: 'key,request,timestamp',
        });

        $.updatePWACount = function () {//update pwa requests count and check that need to show the warning message

            $.syncButtonControl(syncButton.enable);

            db.post_cache.count().then(function (n) {
                $("#pwa-n-requests").text(n);
                if (n == 0) {//disable Sync button when no request in cache
                    $.syncButtonControl(syncButton.disable);
                }
            });
            db.messages.get({name: "show-warning"}, function (row) {
                if (row && row.value == true) {
                    $.pwaFeedback(offlineFeedback.message, offlineFeedback.type, offlineFeedback.title);
                    console.warn(offlineFeedback.title);
                    $.syncButtonControl(syncButton.disable);
                    db.messages.where("name").aboveOrEqual("show-warning").modify({value: false}).then(function () {
                    });
                }
            });
        };

        $.updatePWACount();

        $("#sync-pwa").on("click touchstart", function (event) {
            console.log("#sync-pwa clicked");
            const callsArray = [];
            db.post_cache.each(function ({key, request}) {
                callsArray.push(new Promise(function (deferrer, reject) {
                        console.warn(request);
                        if (request) {
                            $.ajax({
                                async: false,
                                type: request.method,
                                url: request.url,
                                headers: {...request.headers, pwa: true},
                                data: request.body,
                                success: function (ret) {
                                },
                                error: function (ret) {
                                }
                            });
                            deferrer(key);
                        }
                    })
                );
            }).then(function () {

                Promise.all(callsArray).then(function (keys) {
                    console.warn(keys);
                    const att = [];
                    keys.forEach(function (k) {
                        att.push(db.post_cache.where('key').equals(k).delete().then($.updatePWACount));
                    });
                    return Promise.all(att);

                }).then(function () {
                    location.reload();
                });
            });
            event.preventDefault();
            $.syncButtonControl(syncButton.disable);
            this.textContent = "Syncing...";
        });
        self.addEventListener('load', () => {
            function handleOnlineStatusChange() {
                requestCount = $("#pwa-n-requests").text();
                if (navigator.onLine) {
                    console.warn(onlineFeedback.title);
                    $.pwaFeedback(onlineFeedback.message, onlineFeedback.type, onlineFeedback.title);
                    if (requestCount > 0) {// even online, enable it only if there are data to sync
                        $.syncButtonControl(syncButton.enable);
                    }
                } else {
                    console.warn(offlineFeedback.title);
                    $.pwaFeedback(offlineFeedback.message, offlineFeedback.type, offlineFeedback.title);
                    $.syncButtonControl(syncButton.disable);
                }
            }
            self.addEventListener('online', handleOnlineStatusChange);
            self.addEventListener('offline', handleOnlineStatusChange);
        });

    }).catch((err) => {
        console.log('registration failed', err);
    });
})();
