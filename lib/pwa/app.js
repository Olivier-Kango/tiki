(function () {

    const cacheName = 'pages-cache-v1';

    const offlineFeedback = {message: "Tiki will try to handle your requests to send them later.", type: "warning", title: "Tiki is offline"};
    const onlineFeedback = {message: "You may need to sync your data now.", type: "success", title: "Tiki is back online"};
    const syncButton = {enable: false, disable: true}; // disable by default
    let requestCount = 0; // count cached requests

    function injectTicket(body, ticket) {
        const params = new URLSearchParams(body || '');
        params.set('ticket', ticket);
        return params.toString();
    }

    function fetchCsrfTickets(amount) {
        return $.post($.serviceUrl({controller: 'tracker_offline', action: 'tickets'}), { ticketsAmount: amount })
            .then(function (ret) {
                return (ret && ret.new_tickets && ret.new_tickets.length) ? ret.new_tickets : $.Deferred().reject().promise();
            });
    }

    $.loadCache = function (pages) {
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
            .then(cache => urls.map(url => cache.match(url).then(z => (!z) ? cache.add(url) : false).catch(x => console.error(x)))); // eslint-disable-line no-console

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
        console.warn("Service Worker Unavailable"); // eslint-disable-line no-console
        return;
    }
    navigator.serviceWorker.register('./sw.js').then(() => {
        const db = new Dexie("post_cache");

        db.open().then(function() {
            $.updatePWACount(db);
        });

        $("#sync-pwa").on("click touchstart", function (event) {
            event.preventDefault();
            const syncBtn = this;
            if (! navigator.onLine) {
                $.pwaFeedback('You don\'t seem to be online yet.', 'error', 'No connection');
                return;
            }
            $.syncButtonControl(syncButton.disable);
            syncBtn.textContent = "Syncing...";

            Promise.all([
                db.table('post_cache').toArray(),
                db.table('trackers').toArray()
            ]).then(function ([cachedPosts, trackers]) {
                const trackerPayload = trackers
                    .filter((tracker) => tracker.items && tracker.items.length)
                    .map((tracker) => ({ trackerId: tracker.trackerId, items: tracker.items }));
                const ticketCount = cachedPosts.length + (trackerPayload.length ? 1 : 0);
                if (! ticketCount) {
                    location.reload();
                    return;
                }
                return fetchCsrfTickets(ticketCount).then(function (tickets) {
                    return cachedPosts.reduce(function (chain, {key, request}) {
                        return chain.then(function () {
                            if (! request) {
                                return;
                            }
                            return $.ajax({
                                type: request.method,
                                url: request.url,
                                headers: {...request.headers, pwa: true},
                                data: injectTicket(request.body, tickets.shift())
                            }).then(function () {
                                return db.table('post_cache').where('key').equals(key).delete();
                            });
                        });
                    }, Promise.resolve()).then(function () {
                        $.updatePWACount(db);
                        if (! trackerPayload.length) {
                            location.reload();
                            return;
                        }
                        return $.ajax({
                            url: $.serviceUrl({controller: 'tracker_offline', action: 'sync'}),
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                data: JSON.stringify(trackerPayload),
                                ticket: tickets.shift()
                            }
                        }).then(function (ret) {
                            if (ret.errors.length > 0) {
                                let msg = 'There was an error storing one or more tracker items.';
                                ret.errors.forEach(function (e) {
                                    msg += "<br>\n" + e;
                                });
                                msg += "<br>\nPlease reload the page to see remaining items that still need to be synchronized.";
                                $.pwaFeedback(msg, 'error', 'Error');
                            }
                            const all = [];
                            if (ret.success) {
                                ret.success.forEach(function (row) {
                                    trackers.forEach(function (tracker) {
                                        if (tracker.trackerId == row.trackerId) {
                                            tracker.items = tracker.items.filter(function (item, index) {
                                                return row.items.indexOf(index) == -1;
                                            });
                                            all.push(db.table('trackers').put(tracker));
                                        }
                                    });
                                });
                            }
                            return Promise.all(all).then(function () {
                                if (ret.errors.length == 0) {
                                    $.pwaFeedback('Offline tracker data successfully saved.', 'success', 'Success');
                                    location.reload();
                                } else {
                                    $.syncButtonControl(syncButton.enable);
                                    syncBtn.textContent = "Sync";
                                }
                            });
                        });
                    });
                });
            }).catch(function () {
                $.pwaFeedback('Could not obtain a security ticket to synchronize offline data. Please reload the page and try again.', 'error', 'Error');
                $.syncButtonControl(syncButton.enable);
                syncBtn.textContent = "Sync";
            });
        });
        self.addEventListener('load', () => {
            function handleOnlineStatusChange() {
                requestCount = $("#pwa-n-requests").text();
                if (navigator.onLine) {
                    console.warn(onlineFeedback.title); // eslint-disable-line no-console
                    $.pwaFeedback(onlineFeedback.message, onlineFeedback.type, onlineFeedback.title);
                    if (requestCount > 0) {// even online, enable it only if there are data to sync
                        $.syncButtonControl(syncButton.enable);
                    }
                } else {
                    console.warn(offlineFeedback.title); // eslint-disable-line no-console
                    $.pwaFeedback(offlineFeedback.message, offlineFeedback.type, offlineFeedback.title);
                    $.syncButtonControl(syncButton.disable);
                }
            }
            self.addEventListener('online', handleOnlineStatusChange);
            self.addEventListener('offline', handleOnlineStatusChange);
        });

    }).catch((err) => {
        // eslint-disable-next-line no-console
        console.log('registration failed', err);
    });

    $.updatePWACount = async function (db) {//update pwa requests count and check that need to show the warning message
        $.syncButtonControl(syncButton.enable);

        let n = await db.table('post_cache').count();
        let trackers = await db.table('trackers').toArray();
        trackers.forEach(function(tracker) {
            n += tracker.items.length;
        });
        $("#pwa-n-requests").text(n);
        if (n == 0) {//disable Sync button when no request in cache
            $.syncButtonControl(syncButton.disable);
        }

        db.table('messages').get({name: "show-warning"}, function (row) {
            if (row && row.value == true) {
                $.pwaFeedback(offlineFeedback.message, offlineFeedback.type, offlineFeedback.title);
                console.warn(offlineFeedback.title); // eslint-disable-line no-console
                $.syncButtonControl(syncButton.disable);
                db.table('messages').where("name").aboveOrEqual("show-warning").modify({value: false}).then(function () {
                });
            }
        });
    };
})();
