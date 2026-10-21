import test from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';

const source = await readFile(new URL('../../public/js/shared/page-history.js', import.meta.url), 'utf8');
let instance = 0;
async function browser(t) {
    const names = ['location', 'history', 'scrollX', 'scrollY', 'addEventListener', 'crypto'];
    const previous = names.map((name) => Object.getOwnPropertyDescriptor(globalThis, name));
    t.after(() => names.forEach((name, i) => {
        if (previous[i]) Object.defineProperty(globalThis, name, previous[i]); else delete globalThis[name];
    }));
    const entries = [{ url: 'https://shop.test/shop', state: null }];
    let index = 0;
    let writes = 0;
    const events = new Map();
    Object.defineProperty(globalThis, 'crypto', { configurable: true, value: {} });
    globalThis.location = new URL(entries[0].url);
    globalThis.scrollX = globalThis.scrollY = 0;
    globalThis.addEventListener = (name, callback) => events.set(name, callback);
    globalThis.history = {
        get state() { return entries[index].state; },
        replaceState(state, _, url) { writes++; entries[index] = { url: String(url), state: structuredClone(state) }; location.href = url; },
        pushState(state, _, url) { writes++; entries.splice(++index); entries[index] = { url: String(url), state: structuredClone(state) }; location.href = url; },
    };
    const module = await import(`data:text/javascript;base64,${Buffer.from(source).toString('base64')}#${instance++}`);
    return {
        module, entries,
        get writes() { return writes; },
        scroll(y) { globalThis.scrollY = y; events.get('scroll')(); },
        go(offset) { index += offset; location.href = entries[index].url; return history.state; },
    };
}

test('10,000 scrolling events do not write to browser history', async (t) => {
    const b = await browser(t);
    b.module.rememberPosition();
    for (let i = 1; i <= 10000; i++) b.scroll(i);
    assert.equal(b.writes, 1);
    assert.equal(b.module.savedPosition(history.state).y, 10000);
    b.module.rememberPosition();
    b.module.rememberPosition();
    assert.equal(b.writes, 2, 'persist only a changed position when leaving the page');
});

test('Back and Forward restore the latest in-memory position without history writes', async (t) => {
    const b = await browser(t);
    b.module.rememberPosition();
    b.scroll(420);
    b.module.rememberPosition();
    b.module.changePageHistory('https://shop.test/products/headphones', { x: 0, y: 0 });
    b.scroll(900);
    let state = b.go(-1);
    const before = b.writes;
    assert.equal(b.module.savedPosition(state).y, 420);
    b.module.changePageHistory(location.href, b.module.savedPosition(state), 'pop');
    b.scroll(420);
    state = b.go(1);
    assert.equal(b.module.savedPosition(state).y, 900);
    b.module.changePageHistory(location.href, b.module.savedPosition(state), 'pop');
    assert.equal(b.writes, before);
    b.module.changePageHistory(location.href, { x: 0, y: 900 }, 'refresh');
    assert.equal(b.writes, before, 'background catalog updates do not mutate history');
});

test('a browser history refusal keeps the current entry and does not throw', async (t) => {
    const b = await browser(t);
    b.module.rememberPosition();
    const id = history.state.meshPage.id;
    history.pushState = () => { throw new DOMException('History limit', 'SecurityError'); };
    assert.equal(b.module.changePageHistory('https://shop.test/help', { x: 0, y: 0 }), false);
    assert.equal(location.pathname, '/shop');
    assert.equal(history.state.meshPage.id, id);
    b.scroll(150);
    assert.equal(b.module.savedPosition(history.state).y, 150);
});

test('legacy entries without IDs migrate once and keep independent positions', async (t) => {
    const b = await browser(t);
    b.module.rememberPosition();
    b.scroll(420);
    b.module.rememberPosition();
    b.module.changePageHistory('https://shop.test/help', { x: 0, y: 0 });
    b.entries[1].state = { meshPage: { x: 0, y: 900 } };
    b.go(-1);
    b.module.changePageHistory(location.href, b.module.savedPosition(history.state), 'pop');
    b.go(1);
    const before = b.writes;
    b.module.changePageHistory(location.href, b.module.savedPosition(history.state), 'pop');
    assert.equal(b.writes, before + 1);
    b.scroll(1200);
    assert.equal(b.module.savedPosition(b.go(-1)).y, 420);
    b.module.changePageHistory(location.href, b.module.savedPosition(history.state), 'pop');
    assert.equal(b.module.savedPosition(b.go(1)).y, 1200);
    assert.equal(b.writes, before + 1);
});
