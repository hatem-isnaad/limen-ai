# Documentation sources

Blade templates synced from the [Limen 3PL reference host](https://github.com/hatem-elsheref/limen-ai-host).

| Path | Renders to |
|------|------------|
| `capabilities.blade.php` | `docs/host-guide-ar.html` (Arabic RTL host guide) |
| `package-docs/` | `docs/index.html` (English package hub) |

Rebuild static HTML after editing the host demo:

```bash
# In reference host (http://127.0.0.1:8000)
curl -sS http://127.0.0.1:8000/demo/limen-ai/docs -o /tmp/demo-docs-hub.html
curl -sS http://127.0.0.1:8000/demo/limen-ai -o /tmp/demo-capabilities.html

# In package repo
python3 docs/build-static-docs.py
```
