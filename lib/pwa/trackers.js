(function () {
    const db = new Dexie("post_cache");
    let cache_tracker_data = function() {
        $.ajax({
            url: $.serviceUrl({controller: 'tracker_offline', action: 'cache'}),
            success: function (ret) {
                let results = [];
                for (let info of ret) {
                    let fields = info.fields;
                    delete info.fields;
                    let entry = {
                        trackerId: info.trackerId,
                        name: info.name,
                        options: info,
                        fields: fields
                    };
                    results.push(db.table('trackers').put(entry));
                }
                Promise.all(results).then(function() {
                    initialize_vue_components();
                });
            },
            error: function (ret) {
            }
        });
    };

    let initialize_vue_components = async function() {
        let trackerData = await db.table('trackers').toArray();
        let trackerItems = [];
        await getTrackerOfflineItems().then(function (data) {
            trackerItems = data;
        });
        window.registerApplication({
            name: "@vue-mf/tiki-offline",
            app: () => importShim("@vue-mf/tiki-offline"),
            activeWhen: (location) => {
                let condition = true;
                return condition;
            },
            customProps: {
                trackerData: trackerData,
                trackerItems: trackerItems
            },
        });
        onDOMElementRemoved("single-spa-application:@vue-mf/tiki-offline", function () {
            window.unregisterApplication("single-spa-application:@vue-mf/tiki-offline");
        });
    };

    let getTrackerOfflineItems = async function () {
        // Filter requests from dexie?
        let requests = [];
        await db.table("post_cache").toArray(function (data){
            requests.push(data);
        });
        let trackerItems = [];
        requests.forEach((items) => {
            // Only load the requests for tracker items, skipping those for wikikipages and other
            items.forEach((item) => {
                let url = item.request.url, body = item.request.body;
                if (url.includes('tiki-ajax_services') && body && !body.includes('editor_id=editwiki'))
                {
                    let trackerItem = parseItemObject(item.request.body);
                    trackerItems.push(trackerItem);
                }
            }); 
        });
        return trackerItems;
    };

    let parseItemObject = function (itemObject) {
        const params = {};
        itemObject.split('&').forEach(function(pair) {
            let keyValue = pair.split('=');
            params[keyValue[0]] = decodeURIComponent(keyValue[1] || '');
        });
        return params;
    };

    db.open().then(function() {
        if (navigator.onLine) {
            cache_tracker_data();
        } else {
            initialize_vue_components();
        }
    });
})();
