(function (window) {
    /*
     * Tiki / Converse browser storage helper.
     *
     * Single place where localStorage, sessionStorage and IndexedDB access
     * is wrapped in try/catch. Browsers can throw on any of these calls
     * (private browsing modes, storage quota, disabled storage via policy),
     * and the chat UI must keep working even when persistence is
     * unavailable, so every method here fails soft: reads return the given
     * fallback, writes/removes are best-effort and return whether they
     * succeeded.
     *
     * This exists so call sites in conversejs-tiki.js and
     * conversejs-tiki-presentation.js never write a raw try/catch around
     * storage access themselves - if one is ever missed, it throws instead
     * of silently degrading, which this module is meant to prevent.
     *
     * Attached to window (not an ES module export) because this file must
     * load identically whether Tiki includes it as a classic <script> tag
     * (xmpp_conversejs_always_load, see ConverseJS.php) or as a dynamically
     * imported module (registerJsDependencies() in the same file); it must
     * not contain import/export statements.
     */
    var TikiConverseStorage = {
        get: function (storage, key) {
            try {
                return storage ? storage.getItem(key) : null;
            } catch (e) {
                return null;
            }
        },

        set: function (storage, key, value) {
            try {
                if (!storage) {
                    return false;
                }
                storage.setItem(key, value);
                return true;
            } catch (e) {
                return false;
            }
        },

        remove: function (storage, key) {
            try {
                if (storage) {
                    storage.removeItem(key);
                }
            } catch (e) {}
        },

        parseJSON: function (raw, fallback) {
            if (!raw) {
                return fallback;
            }
            try {
                return JSON.parse(raw);
            } catch (e) {
                return fallback;
            }
        },

        getJSON: function (storage, key, fallback) {
            return this.parseJSON(this.get(storage, key), fallback);
        },

        setJSON: function (storage, key, value) {
            try {
                return this.set(storage, key, JSON.stringify(value));
            } catch (e) {
                return false;
            }
        },

        // Removes every key in `storage` matching `pattern` (a RegExp).
        clearMatching: function (storage, pattern) {
            if (!storage) {
                return;
            }
            try {
                var keys = [];
                for (var i = 0; i < storage.length; i++) {
                    var key = storage.key(i);
                    if (key && pattern.test(key)) {
                        keys.push(key);
                    }
                }
                keys.forEach(function (key) {
                    storage.removeItem(key);
                });
            } catch (e) {}
        },

        // Deletes every IndexedDB database whose name matches `pattern`.
        // Requires the (not universally supported) indexedDB.databases().
        clearIndexedDbMatching: function (pattern) {
            try {
                if (!window.indexedDB || !window.indexedDB.databases) {
                    return;
                }
                window.indexedDB.databases().then(function (dbs) {
                    dbs.forEach(function (db) {
                        if (db.name && pattern.test(db.name)) {
                            window.indexedDB.deleteDatabase(db.name);
                        }
                    });
                }).catch(function () {});
            } catch (e) {}
        },
    };

    window.TikiConverseStorage = TikiConverseStorage;
})(window);
