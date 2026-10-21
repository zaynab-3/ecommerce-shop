"""Smoke-test the temporary storefront using real Chromium and WebKit."""
import json
import os
import re
import sys
import traceback
from pathlib import Path

from playwright.sync_api import expect, sync_playwright

base = sys.argv[1].rstrip("/")
output = Path(os.environ.get("RUNNER_TEMP", "/tmp")) / "mesh-preview-browser-results"
output.mkdir(parents=True, exist_ok=True)
results = []
errors = []
admin_results = []
credentials_file = Path(os.environ.get("RUNNER_TEMP", "/tmp")) / "mesh-preview-admin.json"
credentials = json.loads(credentials_file.read_text()) if credentials_file.exists() else None
stage = "initialization"


def report_failure(kind, error, trace):
    message = f"{stage}: {kind.__name__}: {error}"
    if credentials:
        message = message.replace(credentials["password"], "***")
    frames = [frame for frame in traceback.extract_tb(trace) if frame.filename.endswith("test-owner-preview.py")]
    line = frames[-1].lineno if frames else 1
    message = message[:2500].replace("%", "%25").replace("\r", "%0D").replace("\n", "%0A")
    print(f"::error file=scripts/test-owner-preview.py,line={line}::{message}", flush=True)
    sys.__excepthook__(kind, error, trace)


sys.excepthook = report_failure
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
                    stage = f"{engine.name}/{width}/{path}"
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
                            scrollbarWidth: getComputedStyle(root).scrollbarWidth,
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
                        assert style["scrollbarWidth"] == "none", (engine.name, width, path, style)
                        assert style["bar"] == "none", (engine.name, width, path, style)
                        assert style["thumb"] == "rgba(0, 0, 0, 0)", (engine.name, width, path, style)
                    assert page.locator('[data-carousel-track], .category-nav-track').evaluate_all(
                        '(elements) => elements.every(e => getComputedStyle(e).scrollbarWidth === "none")'
                    ), (engine.name, "horizontal scrollbar", path)
                    page.screenshot(path=str(output / f"{engine.name}-{width}-{'touch' if touch else 'mouse'}-{path.strip('/').replace('/', '-') or 'discover'}.png"))
                    results.append({"engine": engine.name, "width": width, "touch": touch, "path": path, **style})
                context.close()
            if credentials:
                for width in (390, 1440):
                    stage = f"{engine.name}/{width}/admin login"
                    context = browser.new_context(viewport={"width": width, "height": 915}, has_touch=width == 390)
                    page = context.new_page()
                    page.on("pageerror", lambda error: errors.append(str(error)))
                    page.goto(base + "/manage-store", wait_until="networkidle")
                    assert page.url == base + "/manage-store/login", "Unauthenticated admin access must require login"
                    page.locator('input[name="email"]').fill(credentials["email"])
                    page.locator('input[name="password"]').fill(credentials["password"])
                    page.get_by_role("button", name="Sign in", exact=True).click()
                    page.wait_for_url(re.compile(re.escape(base) + r"/manage-store/?$"))
                    expect(page.locator(".admin-profile strong")).to_have_text("Owner preview")
                    session = next(cookie for cookie in context.cookies() if cookie["name"] == "mesh_owner_preview")
                    assert session["secure"] and session["httpOnly"], "Admin session must use a secure, HttpOnly cookie"
                    edit_path = None
                    for path in ("", "/products", "/products/create", "@edit", "/categories", "/delivery", "/settings", "/team", "/orders"):
                        if path == "@edit":
                            assert edit_path, "The demo product must have an edit link"
                            path = edit_path
                        stage = f"{engine.name}/{width}/manage-store{path}"
                        response = page.goto(base + "/manage-store" + path, wait_until="networkidle")
                        assert response.status == 200 and page.locator(".admin-main").is_visible(), (engine.name, width, path)
                        if path == "/products":
                            edit_path = page.locator(".table-product").first.get_attribute("href").removeprefix(base + "/manage-store")
                        assert page.evaluate("document.documentElement.scrollWidth <= document.documentElement.clientWidth + 1"), (engine.name, width, path)
                        if width == 390:
                            assert page.locator("html").evaluate("e => getComputedStyle(e).scrollbarWidth") == "none"
                        admin_results.append({"engine": engine.name, "width": width, "path": "/manage-store" + path})
                    page.screenshot(path=str(output / f"{engine.name}-{width}-admin-orders.png"))
                    stage = f"{engine.name}/{width}/admin logout"
                    if page.locator("[data-admin-menu]").is_visible():
                        page.locator("[data-admin-menu]").click()
                    page.get_by_role("button", name="Sign out", exact=True).click()
                    page.wait_for_url(base + "/manage-store/login")
                    page.goto(base + "/manage-store/settings", wait_until="networkidle")
                    assert page.url == base + "/manage-store/login", "Logout must revoke access"
                    context.close()
assert not errors, errors
(output / "results.json").write_text(json.dumps(results, indent=2))
(output / "admin-results.json").write_text(json.dumps(admin_results, indent=2))
print(f"{len(results)} Chromium/WebKit scroll, scrollbar, history and layout checks passed.")
if credentials:
    print(f"{len(admin_results)} authenticated admin page checks plus login/logout and secure cookies passed.")
