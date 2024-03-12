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
    }
    let initialize_vue_components = async function() {
        let trackerData = await db.table('trackers').toArray();
        window.registerApplication({
            name: "@vue-mf/tiki-offline",
            app: () => importShim("@vue-mf/tiki-offline"),
            activeWhen: (location) => {
                let condition = true;
                return condition;
            },
            customProps: {
                trackerData: trackerData,
            },
        })
        onDOMElementRemoved("single-spa-application:@vue-mf/tiki-offline", function () {
            window.unregisterApplication("single-spa-application:@vue-mf/tiki-offline");
        });
    }
    db.open().then(function() {
        if (navigator.onLine) {
            cache_tracker_data();
        } else {
            initialize_vue_components();
        }
    });
})();
