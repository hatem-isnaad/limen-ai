#!/usr/bin/env python3
"""Rebuild docs/index.html and docs/host-guide-ar.html from a running host demo.

Usage (host app on http://127.0.0.1:8000):
    curl -sS http://127.0.0.1:8000/demo/limen-ai/docs -o /tmp/demo-docs-hub.html
    curl -sS http://127.0.0.1:8000/demo/limen-ai -o /tmp/demo-capabilities.html
    python3 docs/build-static-docs.py
"""

from __future__ import annotations

import re
import sys
from pathlib import Path
from urllib.parse import unquote

ROOT = Path(__file__).resolve().parent
HUB_SRC = Path('/tmp/demo-docs-hub.html')
AR_SRC = Path('/tmp/demo-capabilities.html')


def strip_logger(html: str) -> str:
    return re.sub(r'<script[^>]*id="browser-logger-active"[^>]*>.*?</script>\s*', '', html, flags=re.S)


def fix_fonts(html: str) -> str:
    return (
        html.replace('&family=JetBrains', '&amp;family=JetBrains')
        .replace('&display=swap', '&amp;display=swap')
    )


def fix_docs_urls(html: str) -> str:
    base = 'http://127.0.0.1:8000/demo/limen-ai/docs'
    html = html.replace(base + '#', 'index.html#')
    html = re.sub(
        r'href="' + re.escape(base) + r'/([^"]+)"',
        lambda m: f'href="{unquote(m.group(1))}"',
        html,
    )
    html = html.replace(base, 'index.html')
    return html


def fix_host_urls(html: str) -> str:
    html = html.replace('http://127.0.0.1:8000/demo/limen-ai', 'host-guide-ar.html')
    for path in ('/login/demo', '/staff/chat', '/admin/chat', '/demo/3pl'):
        html = html.replace(f'http://127.0.0.1:8000{path}', 'limen-integration.md')
    html = html.replace('http://127.0.0.1:8000', 'limen-integration.md')
    return html


def wrap_hub_content(html: str) -> str:
    if 'limen-docs:content-start' in html:
        return html

    html = html.replace('<div id="hubContent">', '<div class="content" id="hubContent">', 1)
    html = html.replace(
        '    <div class="content-divider hub-divider"><span>Official package reference</span></div>\n\n    <div class="content" id="hubContent">',
        '    <div class="content-divider hub-divider"><span>Official package reference</span></div>\n\n    <main class="main">\n    <!-- limen-docs:content-start -->\n    <div class="content" id="hubContent">',
        1,
    )

    html = re.sub(
        r'(\s*</section>\s*\n)(\s*</div>\s*\n)(\s*<footer>)',
        r'\1    </div>\n    <!-- limen-docs:content-end -->\n    </main>\n\n    <footer>',
        html,
        count=1,
        flags=re.S,
    )

    return html


def main() -> int:
    if not HUB_SRC.is_file() or not AR_SRC.is_file():
        print('Missing /tmp/demo-docs-hub.html or /tmp/demo-capabilities.html', file=sys.stderr)
        print('Fetch from host: curl demo URLs first.', file=sys.stderr)
        return 1

    hub = wrap_hub_content(fix_host_urls(fix_docs_urls(fix_fonts(strip_logger(HUB_SRC.read_text(encoding='utf-8'))))))
    ar = fix_host_urls(fix_docs_urls(fix_fonts(strip_logger(AR_SRC.read_text(encoding='utf-8')))))

    (ROOT / 'index.html').write_text(hub, encoding='utf-8')
    (ROOT / 'host-guide-ar.html').write_text(ar, encoding='utf-8')

    print('Wrote', ROOT / 'index.html')
    print('Wrote', ROOT / 'host-guide-ar.html')
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
