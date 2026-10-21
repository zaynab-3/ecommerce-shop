"""Smoke-test the temporary storefront using real Chromium and WebKit."""
import json
import os
import sys
from pathlib import Path

from playwright.sync_api import sync_playwright

base = sys.argv[1].rstrip("/")
output = Path(os.environ.get("RUNNER_TEMP", "/tmp")) / "mesh-preview-browser-results"
output.mkdir(parents=True, exist_ok=True)
results = []
errors = []
with sync_playwright() as playwright:
    for engine in (playwright.chromium, playwright.webkit):
        with engine.launch() as browser:
            for width, touch in ((390, True), (850, False), (1440, True), (1440, False)):
                context = browser.new_context(viewport={"width": width, "height": 915}, has_touch=touch)
                page = context.new_page()
                page.on("pageerror", lambda error: errors.append(str(error)))
                page.add_init_script("""(() => {
                    window.historyWrites = 0;
                    for (const method of ['pushState', 'replaceState']) {
                        const original = history[method].bind(history);
                        history[method] = (...args) => { window.historyWrites++; return original(...args); };
                    }
                })()""")
                for path in ("/", "/shop", "/products/the-everyday-backpack"):
                    page.goto(base + path, wait_until="networkidle")
                    page.wait_for_function("() => !!window.lenisVersion")
                    if page.get_by_role("button", name="Got it", exact=True).is_visible():
                        page.get_by_role("button", name="Got it", exact=True).click()
                    before = page.evaluate("window.historyWrites")
                    page.mouse.wheel(0, 750)
                    page.wait_for_timeout(650)
                    style = page.evaluate("""() => {
                        const root = document.documentElement;
                        return {
                            width: getComputedStyle(root).scrollbarWidth,
                            bar: getComputedStyle(root, '::-webkit-scrollbar').display,
                            thumb: getComputedStyle(root, '::-webkit-scrollbar-thumb').backgroundColor,
                            overflow: root.scrollWidth - root.clientWidth,
                            scroll: scrollY,
                            writes: window.historyWrites
                        };
                    }""")
                    assert style["scroll"] > 100, (engine.name, width, path, style)
                    assert style["writes"] == before, (engine.name, "scroll changed history", style)
                    assert style["overflow"] <= 1, (engine.name, width, path, style)
                    if width <= 850 or touch:
                        assert style["width"] == "none", (engine.name, width, path, style)
                        assert style["bar"] == "none", (engine.name, width, path, style)
                        assert style["thumb"] == "rgba(0, 0, 0, 0)", (engine.name, width, path, style)
                    page.screenshot(path=str(output / f"{engine.name}-{width}-{'touch' if touch else 'mouse'}-{path.strip('/').replace('/', '-') or 'discover'}.png"))
                    results.append({"engine": engine.name, "width": width, "touch": touch, "path": path, **style})
                context.close()
assert not errors, errors
(output / "results.json").write_text(json.dumps(results, indent=2))
print(f"{len(results)} Chromium/WebKit scroll, scrollbar, history and layout checks passed.")
