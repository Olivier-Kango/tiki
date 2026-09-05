// Run with: node --test lib/xmpp/tests/converse-integration.test.cjs
// No Tiki database, browser credentials, XMPP connection or generated files needed.
const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { resolve } = require('node:path');
const { execFileSync } = require('node:child_process');
const { webcrypto } = require('node:crypto');
const vm = require('node:vm');

const root = resolve(__dirname, '../../..');
const read = name => readFileSync(resolve(root, 'lib/xmpp/js', name), 'utf8');
const plain = value => JSON.parse(JSON.stringify(value));

function memoryStorage() {
    const values = new Map();
    return {
        getItem: key => values.get(String(key)) ?? null,
        setItem: (key, value) => values.set(String(key), String(value)),
        removeItem: key => values.delete(String(key)),
        get length() { return values.size; },
        key: index => [...values.keys()][index],
    };
}

function environment(blocked = false) {
    const logs = [];
    const listeners = {};
    const document = {
        documentElement: { dataset: {}, style: { setProperty() {} } }, body: {},
        querySelector: () => null, querySelectorAll: () => [], getElementById: () => null,
        addEventListener() {},
    };
    const window = { document, crypto: webcrypto, requestAnimationFrame() {}, addEventListener() {} };
    for (const name of ['localStorage', 'sessionStorage', 'indexedDB']) {
        if (blocked) Object.defineProperty(window, name, { get() { throw new Error('storage denied'); } });
        else window[name] = name === 'indexedDB' ? undefined : memoryStorage();
    }
    const ctx = vm.createContext({
        window, document, console: { error: (...args) => logs.push(args.join(' ')), warn: (...args) => logs.push(args.join(' ')) },
        setInterval() {}, setTimeout() {}, requestAnimationFrame() {},
        MutationObserver: class { observe() {} disconnect() {} },
        fetch: async () => ({ ok: true, json: async () => ({}) }),
        customElements: { get: () => undefined },
    });
    vm.runInContext(read('conversejs-tiki-storage.js'), ctx);
    vm.runInContext(read('conversejs-tiki-presentation.js'), ctx);
    return { ctx, window, logs, listeners };
}

function plugin(env, options = {}) {
    const values = { ...options };
    let registered;
    env.window.converse = {
        plugins: { add: (name, value) => { registered = value; } },
        env: { Strophe: { Connection: function () {} } },
    };
    // Expose private helpers only in the in-memory test copy, never in production.
    const source = read('conversejs-tiki.js').replace('    converse.plugins.add("tiki", {',
        '    window.testHooks = { contract: TIKI_SETTINGS_CONTRACT, noteChatActivity, getReadCursors, whenConverseInitialized, getConverseCollection, canPatchConverseMethod };\n    converse.plugins.add("tiki", {');
    vm.runInContext(source, env.ctx);
    const internal = {
        state: {}, exports: {}, session: { get: () => 'me@example' },
        api: {
            settings: {
                get: key => values[key], set: (key, value) => { values[key] = value; },
                extend: defaults => Object.entries(defaults).forEach(([key, value]) => {
                    if (values[key] === undefined) values[key] = value;
                }),
            },
            waitUntil: () => new Promise(() => {}),
            listen: { on: (name, callback) => { (env.listeners[name] ||= []).push(callback); } },
        },
    };
    return { registered, internal, values, initialize: () => registered.initialize.call({ _converse: internal }) };
}

function php(options = {}, alwaysLoad = 'y') {
    const code = `
        define('CONVERSEJS_DIST_PATH', 'public/generated/vendor_dist/converse.js/dist');
        define('JS_ASSETS_PATH', 'public/generated');
        require $argv[1] . '/lib/xmpp/ConverseJS.php';
        class TikiLib {
            public static function lib($name) { return new class {
                public function add_jq_onready($script, $priority) { return $script; }
                public function add_js_module($script) {}
            }; }
        }
        function tra($text) { return $text; }
        $user = 'test-user';
        $prefs = ['xmpp_conversejs_debug' => 'n', 'xmpp_conversejs_always_load' => $argv[2]];
        $_SESSION = ['chat-session-init' => 123];
        $options = json_decode(stream_get_contents(STDIN), true);
        $converse = new ConverseJS($options);
        $property = (new ReflectionClass(ConverseJS::class))->getProperty('TIKI_JS_SETTINGS_CONTRACT');
        echo json_encode(['script' => $converse->render(), 'options' => $converse->get_options(),
            'schema' => $property->getValue(), 'dependencies' => $converse->get_js_dependencies()]);
    `;
    return JSON.parse(execFileSync('php', ['-r', code, root, alwaysLoad], {
        input: JSON.stringify(options), encoding: 'utf8', stdio: ['pipe', 'pipe', 'pipe'],
    }));
}

test('blocked storage property getters do not prevent plugin registration or initialization', () => {
    const env = environment(true);
    const app = plugin(env);
    assert.ok(app.registered);
    assert.doesNotThrow(app.initialize);
});

test('storage failures return documented fallbacks without acquiring storage at the call site', () => {
    const { window } = environment(true);
    const storage = window.TikiConverseStorage;
    for (const name of ['localStorage', 'sessionStorage']) {
        assert.equal(storage.get(name, 'x'), null);
        assert.equal(storage.set(name, 'x', 'v'), false);
        assert.equal(storage.remove(name, 'x'), false);
        assert.equal(storage.clearMatching(name, /x/), false);
        assert.deepEqual(storage.getObject(name, 'x', {}), {});
    }
    assert.doesNotThrow(() => storage.clearIndexedDbMatching(/converse/));
});

test('quota errors are contained, and later storage recovery is usable', () => {
    const { window } = environment();
    window.localStorage.setItem = () => { throw new Error('quota'); };
    assert.equal(window.TikiConverseStorage.set('localStorage', 'x', 'v'), false);
    window.localStorage = memoryStorage();
    assert.equal(window.TikiConverseStorage.set('localStorage', 'x', 'v'), true);
    assert.equal(window.TikiConverseStorage.get('localStorage', 'x'), 'v');
});

for (const raw of ['null', '[]', '42', '"text"', '{broken']) {
    test('activity and cursor maps recover from stored ' + raw, () => {
        const env = environment();
        plugin(env);
        env.window.localStorage.setItem('tiki_xmpp_chat_activity', raw);
        env.window.localStorage.setItem('tiki_xmpp_read_cursors', raw);
        assert.doesNotThrow(() => env.window.testHooks.noteChatActivity('peer@example', 123));
        assert.deepEqual(plain(env.window.testHooks.getReadCursors()), {});
        assert.equal(JSON.parse(env.window.localStorage.getItem('tiki_xmpp_chat_activity'))['peer@example'], 123);
    });
}

test('storage cleanup removes adjacent matching keys and preserves unrelated keys', () => {
    const { window } = environment();
    for (const key of ['converse-a', 'converse-b', 'other']) window.localStorage.setItem(key, '1');
    assert.equal(window.TikiConverseStorage.clearMatching('localStorage', /^converse/g), true);
    assert.equal(window.localStorage.length, 1);
    assert.equal(window.localStorage.getItem('other'), '1');
});

test('PHP and JS declare identical keys, defaults, types and allowed values', () => {
    const env = environment();
    plugin(env);
    const phpSchema = php().schema;
    assert.deepEqual(Object.keys(phpSchema).sort(), plain(env.window.testHooks.contract.map(entry => entry.key)).sort());
    for (const entry of env.window.testHooks.contract) {
        assert.equal(entry.default, phpSchema[entry.key].default, entry.key);
        assert.equal(entry.type, phpSchema[entry.key].type, entry.key);
        assert.deepEqual(plain(entry.values || []), phpSchema[entry.key].values || [], entry.key);
    }
});

for (const options of [
    { anonymous: 'y', auto_open: 'n', using_external: 'y', tiki_anonymous_nick: 'custom', dm_target: 'support@example' },
    { anonymous: true, auto_open: 'bad', dm_target: [], current_room: 42, tiki_site_name: {}, tiki_anonymous_nick: 'invalid' },
    { anonymous: null, tiki_site_name: null, tiki_support_label: '', tiki_site_logo: '', current_room: '' },
]) {
    test('PHP and JS normalize the same configuration: ' + JSON.stringify(options), () => {
        const env = environment();
        const app = plugin(env, options);
        app.initialize();
        const phpOptions = php(options).options;
        for (const key of Object.keys(options)) assert.deepEqual(plain(app.values[key]), phpOptions[key], key);
        if (options.tiki_site_name === null) assert.equal(app.internal.tikiSettings.site_name, 'Tiki');
    });
}

test('required missing internals disable Tiki setup with a named diagnostic', () => {
    const env = environment();
    const app = plugin(env);
    delete app.internal.session;
    assert.doesNotThrow(app.initialize);
    assert.equal(env.logs.length, 1);
    assert.match(env.logs[0], /session.get/);
    assert.equal(app.internal.tikiSettings, undefined);
});

test('missing lifecycle promises skip callbacks and diagnose each feature once', async () => {
    const env = environment();
    const app = plugin(env);
    app.initialize();
    app.internal.api.waitUntil = () => null;
    let called = false;
    for (let i = 0; i < 2; i++) await env.window.testHooks.whenConverseInitialized('test feature', () => { called = true; });
    assert.equal(called, false);
    assert.equal(env.logs.length, 1);
    assert.match(env.logs[0], /initialized lifecycle promise/);
});

test('one failed initialization callback does not prevent another', async () => {
    const env = environment();
    const app = plugin(env);
    app.initialize();
    app.internal.api.waitUntil = () => Promise.resolve();
    let healthy = false;
    await env.window.testHooks.whenConverseInitialized('broken', () => { throw new Error('private data'); });
    await env.window.testHooks.whenConverseInitialized('healthy', () => { healthy = true; });
    assert.equal(healthy, true);
    assert.match(env.logs[0], /broken/);
    assert.ok(!env.logs[0].includes('private data'));
});

test('collection and prototype checks reject incompatible shapes', () => {
    const env = environment();
    const app = plugin(env);
    app.initialize();
    app.internal.state.chatboxes = { on() {} };
    assert.equal(env.window.testHooks.getConverseCollection('chatboxes', ['on'], 'badges'), null);
    assert.equal(env.window.testHooks.canPatchConverseMethod(undefined, 'render', 'preview'), false);
    assert.equal(env.logs.length, 2);
});

for (const authentication of ['login', 'anonymous']) {
    test('PHP-generated ' + authentication + ' startup survives disabled browser storage', async () => {
        const env = environment(true);
        const calls = [];
        env.window.converse = { initialize: options => calls.push(options) };
        env.window.tikiConverseReady = Promise.resolve(env.window.converse);
        const rendered = php({ authentication, jid: 'guest.example', anonymous: authentication === 'anonymous' ? 'y' : 'n' });
        await vm.runInContext(rendered.script, env.ctx);
        await Promise.resolve();
        assert.equal(calls.length, 1);
        assert.equal(calls[0].authentication, 'login');
        assert.equal(calls[0].auto_open, 'n');
        if (authentication === 'anonymous') {
            assert.match(calls[0].jid, /^guest-[a-f0-9]{20}@guest.example$/);
            assert.match(calls[0].password, /^[a-f0-9]{48}$/);
        }
        assert.ok(!env.logs.some(line => line.includes('FATAL')));
    });
}

test('generated startup awaits helpers and reuses the same guest identity on a subsequent render', async () => {
    const env = environment();
    const calls = [];
    env.window.converse = { initialize: options => calls.push(plain(options)) };
    let ready;
    env.window.tikiConverseReady = new Promise(resolve => { ready = resolve; });
    const rendered = php({ authentication: 'anonymous', jid: 'guest.example', anonymous: 'y' });
    const pending = vm.runInContext(rendered.script, env.ctx);
    assert.equal(calls.length, 0);
    ready(env.window.converse);
    await pending;
    await Promise.resolve();
    await vm.runInContext(rendered.script, env.ctx);
    await Promise.resolve();
    assert.equal(calls.length, 2);
    assert.equal(calls[0].jid, calls[1].jid);
    assert.equal(calls[0].password, calls[1].password);
});

test('both PHP loading modes keep storage and presentation before the main plugin', () => {
    for (const always of ['y', 'n']) {
        const deps = php({}, always).dependencies.map(path => path.split('?')[0]);
        assert.deepEqual(deps.slice(0, 3), [
            'lib/xmpp/js/conversejs-tiki-storage.js', 'lib/xmpp/js/conversejs-tiki-presentation.js', 'lib/xmpp/js/conversejs-tiki.js',
        ]);
    }
});

test('shared dropdown and flyout selectors retain their previous values', () => {
    const { window } = environment();
    const selectors = window.TikiConversePresentation.SELECTORS;
    assert.equal(selectors.CHAT_FLYOUTS, '#conversejs .chatbox .box-flyout, #conversejs #controlbox .box-flyout');
    assert.equal(selectors.DROPDOWN, 'converse-dropdown');
    assert.equal(selectors.CHATBOX, '.chatbox:not(#controlbox)');
});

test('initialization callbacks wait for the public lifecycle promise', async () => {
    const env = environment();
    const app = plugin(env);
    app.initialize();
    let ready;
    app.internal.api.waitUntil = () => new Promise(resolve => { ready = resolve; });
    let called = false;
    const pending = env.window.testHooks.whenConverseInitialized('delayed', () => { called = true; });
    assert.equal(called, false);
    ready();
    await pending;
    assert.equal(called, true);
});

test('a throwing lifecycle lookup is diagnosed without running its patch', async () => {
    const env = environment();
    const app = plugin(env);
    app.initialize();
    app.internal.api.waitUntil = () => { throw new Error('incompatible API'); };
    let called = false;
    await env.window.testHooks.whenConverseInitialized('broken API', () => { called = true; });
    assert.equal(called, false);
    assert.match(env.logs[0], /api.waitUntil/);
});

test('generated startup reports a rejected Converse initialization promise', async () => {
    const env = environment();
    env.window.converse = { initialize: () => Promise.reject(new Error('test failure')) };
    const rendered = php({ authentication: 'login', jid: 'user@example' });
    await vm.runInContext(rendered.script, env.ctx);
    await new Promise(resolve => setImmediate(resolve));
    assert.ok(env.logs.some(line => line.includes('initialization failed')));
});
