<style>
    /* LTR overrides for English package docs (capabilities CSS is RTL-first) */
    html[dir="ltr"] .progress-bar { transform-origin: left center; }
    html[dir="ltr"] .to-top { left: auto; right: 1.5rem; }
    html[dir="ltr"] .search-icon { left: 0.75rem; right: auto; }
    html[dir="ltr"] .search-kbd { right: 0.6rem; left: auto; }
    html[dir="ltr"] th, html[dir="ltr"] td { text-align: left; }
    html[dir="ltr"] .nav-links a::after { left: 0; right: auto; }
    html[dir="ltr"] .acc-trigger { text-align: left; }
    html[dir="ltr"] .prose blockquote { border-left: 3px solid var(--primary); border-right: none; padding-left: 1rem; padding-right: 0; }

    /* Hide duplicate hero embedded in package index.html — Blade layout provides the hero */
    #hubContent > header.hero { display: none; }

    /* Package hub content classes (from docs/index.html) */
    #hubContent h2.section-title,
    #hubContent .section-title {
        font-size: clamp(1.35rem, 3vw, 1.6rem);
        font-weight: 800;
        letter-spacing: -0.02em;
        margin: 0 0 0.35rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid var(--border);
        color: var(--text);
        display: block;
    }

    #hubContent .section-desc {
        color: var(--muted);
        margin-bottom: 1rem;
        max-width: 65ch;
        font-size: 0.92rem;
        font-weight: 500;
    }

    #hubContent h3 {
        font-size: 1.05rem;
        font-weight: 800;
        margin: 1.35rem 0 0.5rem;
        color: var(--text);
    }

    #hubContent h4 {
        font-size: 0.95rem;
        font-weight: 800;
        margin: 0.75rem 0 0.4rem;
        color: var(--text);
    }

    #hubContent p,
    #hubContent ul,
    #hubContent ol {
        margin-bottom: 0.75rem;
        color: var(--muted);
        font-weight: 500;
    }

    #hubContent ul,
    #hubContent ol { padding-left: 1.25rem; }

    #hubContent li { margin-bottom: 0.35rem; line-height: 1.65; }

    #hubContent .hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.72rem;
        font-weight: 700;
        padding: 0.25rem 0.65rem;
        border-radius: 999px;
        background: rgba(56, 189, 248, 0.12);
        color: var(--primary);
        border: 1px solid rgba(56, 189, 248, 0.25);
        margin-bottom: 0.85rem;
    }

    #hubContent .hero-lead {
        color: var(--muted);
        font-size: 1.02rem;
        max-width: 58ch;
        font-weight: 500;
    }

    #hubContent .hero-actions {
        margin-top: 1.25rem;
        display: flex;
        flex-wrap: wrap;
        gap: 0.625rem;
    }

    #hubContent .btn-secondary {
        background: rgba(30, 41, 59, 0.8);
        color: var(--text);
        border-color: var(--border);
    }

    #hubContent .btn-secondary:hover {
        color: var(--text);
        border-color: var(--border-glow);
        text-decoration: none;
    }

    #hubContent .grid-2 {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 260px), 1fr));
        gap: 0.875rem;
    }

    #hubContent .grid-3 {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 200px), 1fr));
        gap: 0.875rem;
    }

    #hubContent .grid-4 {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 180px), 1fr));
        gap: 0.875rem;
    }

    #hubContent .card h4 { margin-top: 0; }

    #hubContent .pill {
        display: inline-block;
        font-size: 0.68rem;
        font-weight: 700;
        padding: 0.12rem 0.45rem;
        border-radius: 999px;
        margin-bottom: 0.35rem;
        margin-right: 0.25rem;
    }

    #hubContent .pill-blue { background: rgba(56, 189, 248, 0.12); color: var(--primary); }
    #hubContent .pill-green { background: rgba(74, 222, 128, 0.12); color: #86efac; }
    #hubContent .pill-amber { background: rgba(251, 191, 36, 0.12); color: #fde68a; }

    #hubContent .callout {
        border-radius: var(--radius);
        padding: 0.875rem 1rem;
        margin: 1rem 0;
        border: 1px solid var(--border);
        font-size: 0.9rem;
        background: var(--glass);
    }

    #hubContent .callout-info {
        background: rgba(56, 189, 248, 0.08);
        border-color: rgba(56, 189, 248, 0.28);
    }

    #hubContent .callout-warn {
        background: rgba(251, 191, 36, 0.08);
        border-color: rgba(251, 191, 36, 0.28);
    }

    #hubContent .callout strong {
        color: var(--text);
        display: block;
        margin-bottom: 0.25rem;
    }

    #hubContent .callout p { margin: 0; font-size: 0.875rem; color: var(--muted); }

    #hubContent .use-case {
        border-left: 3px solid var(--primary);
        padding-left: 0.85rem;
    }

    #hubContent .flow {
        background: var(--code-bg);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1rem 1.125rem;
        font-family: var(--mono);
        font-size: 0.78rem;
        line-height: 1.55;
        white-space: pre;
        overflow-x: auto;
        color: var(--muted);
        direction: ltr;
        text-align: left;
    }

    /* Package index ships pre-built code-block + code-toolbar — map to guide chrome */
    #hubContent .code-block .code-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.5rem 0.75rem;
        background: var(--code-head);
        border-bottom: 1px solid rgba(148, 163, 184, 0.12);
        direction: ltr;
    }

    #hubContent .code-block .code-toolbar .code-lang {
        font-family: var(--mono);
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #7f8ba6;
    }

    #hubContent .code-block .copy-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-family: var(--font);
        font-size: 0.72rem;
        font-weight: 700;
        padding: 0.26rem 0.6rem;
        border-radius: 7px;
        border: 1px solid rgba(148, 163, 184, 0.2);
        background: rgba(148, 163, 184, 0.08);
        color: #94a3b8;
        cursor: pointer;
    }

    #hubContent .code-block .copy-btn:hover {
        color: #e2e8f0;
        border-color: rgba(56, 189, 248, 0.5);
        background: rgba(56, 189, 248, 0.12);
    }

    #hubContent .code-block .copy-btn.copied {
        color: #86efac;
        border-color: rgba(74, 222, 128, 0.5);
        background: rgba(74, 222, 128, 0.12);
    }

    #hubContent .code-block {
        margin: 0.75rem 0 1rem;
        border: 1px solid rgba(148, 163, 184, 0.16);
        border-radius: 13px;
        overflow: hidden;
        background: var(--code-bg);
        box-shadow: 0 12px 34px rgba(0, 0, 0, 0.35);
    }

    #hubContent .code-block pre {
        margin: 0;
        padding: 0.95rem 1.1rem;
        overflow-x: auto;
        direction: ltr;
        text-align: left;
    }

    #hubContent .code-block pre code {
        background: none;
        border: none;
        padding: 0;
        display: block;
        font-family: var(--mono);
        font-size: 0.8rem;
        line-height: 1.75;
        color: #cdd6f4;
    }

    #hubContent footer {
        margin-top: 2.5rem;
        padding-top: 1.25rem;
        border-top: 1px solid var(--border);
        color: var(--muted);
        font-size: 0.85rem;
        background: transparent;
        border-radius: 0;
    }

    #hubContent footer p { margin-bottom: 0.4rem; color: var(--muted); }

    @media (max-width: 640px) {
        #hubContent .hero-actions { flex-direction: column; }
        #hubContent .hero-actions .btn { width: 100%; }
    }

    /* Markdown pages */
    .prose :is(h1, h2, h3, h4) {
        color: var(--text);
        scroll-margin-top: 5.5rem;
    }

    .prose h1 { font-size: 1.65rem; font-weight: 900; margin: 0 0 1rem; background: none; color: var(--text); }
    .prose h2 {
        font-size: 1.35rem;
        font-weight: 800;
        margin: 2rem 0 0.85rem;
        padding-bottom: 0.45rem;
        border-bottom: 1px solid var(--border);
    }

    .prose h3 { font-size: 1.08rem; font-weight: 800; margin: 1.5rem 0 0.65rem; }
    .prose p, .prose li { color: var(--muted); font-weight: 500; line-height: 1.75; }
    .prose p { margin-bottom: 1rem; }
    .prose ul, .prose ol { margin-bottom: 1rem; padding-left: 1.25rem; }
    .prose blockquote {
        border-left: 3px solid var(--primary);
        padding-left: 1rem;
        margin: 1rem 0;
        color: var(--muted);
    }

    .prose .table-wrap { margin: 1rem 0; }

    /* Docs navbar — no horizontal scroll */
    .docs-topnav { margin-bottom: 1.5rem; }

    .docs-topnav-inner {
        display: grid;
        grid-template-columns: auto 1fr auto;
        align-items: center;
        gap: 0.75rem 1rem;
    }

    .docs-nav {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.25rem;
        min-width: 0;
    }

    .docs-nav-main {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        justify-content: center;
        gap: 0.15rem 0.35rem;
    }

    .docs-nav-link,
    .nav-drop-trigger {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.42rem 0.72rem;
        border-radius: 999px;
        font-size: 0.82rem;
        font-weight: 700;
        color: var(--muted);
        border: 1px solid transparent;
        background: transparent;
        font-family: var(--font);
        cursor: pointer;
        transition: color 0.2s, background 0.2s, border-color 0.2s;
        white-space: nowrap;
    }

    .docs-nav-link:hover,
    .nav-drop-trigger:hover,
    .nav-drop.open .nav-drop-trigger {
        color: var(--text);
        background: rgba(255, 255, 255, 0.04);
        border-color: var(--border);
        text-decoration: none;
    }

    .docs-nav-link.active,
    .nav-drop.open .nav-drop-trigger {
        color: var(--primary);
        background: rgba(56, 189, 248, 0.1);
        border-color: rgba(56, 189, 248, 0.25);
    }

    .docs-nav-link-primary {
        color: #bae6fd;
        border-color: rgba(56, 189, 248, 0.22);
        background: rgba(56, 189, 248, 0.08);
    }

    .docs-start-banner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 1.5rem;
        padding: 0.85rem 1.1rem;
        border-radius: 14px;
        border: 1px solid rgba(56, 189, 248, 0.28);
        background: rgba(56, 189, 248, 0.08);
        color: #bae6fd;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .docs-start-banner strong { color: var(--text); }
    .docs-start-banner a { font-weight: 700; }

    .btn-sm {
        padding: 0.45rem 0.85rem;
        font-size: 0.82rem;
    }

    .docs-nav-host .ico { color: var(--primary); }

    .nav-chevron {
        width: 0.85em;
        height: 0.85em;
        transition: transform 0.2s;
    }

    .nav-drop.open .nav-chevron { transform: rotate(180deg); }

    .nav-drop { position: relative; }

    .nav-drop-panel {
        position: absolute;
        top: calc(100% + 0.45rem);
        left: 50%;
        transform: translateX(-50%);
        z-index: 120;
        min-width: 220px;
        padding: 0.55rem;
        border-radius: 14px;
        border: 1px solid var(--border);
        background: rgba(10, 14, 26, 0.96);
        backdrop-filter: blur(16px);
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.45);
    }

    .nav-drop-panel-wide { min-width: min(720px, calc(100vw - 2rem)); padding: 0.85rem; }

    .nav-drop-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem 1.25rem;
    }

    @media (min-width: 900px) {
        .nav-drop-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    }

    .nav-drop-title {
        font-size: 0.68rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        color: var(--muted);
        padding: 0.2rem 0.55rem 0.35rem;
    }

    .nav-drop-link {
        display: block;
        padding: 0.38rem 0.55rem;
        border-radius: 8px;
        font-size: 0.84rem;
        font-weight: 600;
        color: var(--muted);
    }

    .nav-drop-link:hover {
        color: var(--text);
        background: rgba(56, 189, 248, 0.08);
        text-decoration: none;
    }

    .docs-nav-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 0.5rem;
    }

    .docs-nav-actions .search-wrap {
        flex: 0 1 240px;
        max-width: 240px;
        min-width: 160px;
    }

    .docs-nav-toggle {
        display: none;
        align-items: center;
        gap: 0.35rem;
        padding: 0.45rem 0.8rem;
        border-radius: 10px;
        border: 1px solid var(--border);
        background: rgba(15, 23, 42, 0.85);
        color: var(--text);
        font-family: var(--font);
        font-size: 0.82rem;
        font-weight: 700;
        cursor: pointer;
    }

    .docs-nav-backdrop {
        position: fixed;
        inset: 0;
        z-index: 140;
        background: rgba(1, 4, 9, 0.65);
        backdrop-filter: blur(2px);
    }

    .docs-nav-panel {
        position: fixed;
        top: 0;
        right: 0;
        z-index: 150;
        width: min(320px, 88vw);
        height: 100vh;
        overflow-y: auto;
        padding: 1rem 0.85rem 2rem;
        border-left: 1px solid var(--border);
        background: rgba(8, 12, 24, 0.98);
        backdrop-filter: blur(16px);
        box-shadow: -20px 0 60px rgba(0, 0, 0, 0.45);
    }

    .docs-nav-panel-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.35rem 0.45rem 0.85rem;
        border-bottom: 1px solid var(--border);
        margin-bottom: 0.65rem;
    }

    .docs-nav-panel-head strong { font-size: 0.92rem; }

    .docs-nav-close {
        display: grid;
        place-items: center;
        width: 2rem;
        height: 2rem;
        border-radius: 8px;
        border: 1px solid var(--border);
        background: transparent;
        color: var(--muted);
        cursor: pointer;
    }

    .docs-nav-panel-group { margin-bottom: 0.85rem; }

    .docs-nav-panel-label {
        font-size: 0.68rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        color: var(--muted);
        padding: 0.35rem 0.45rem 0.25rem;
    }

    .docs-nav-panel a {
        display: block;
        padding: 0.42rem 0.45rem;
        border-radius: 8px;
        font-size: 0.86rem;
        font-weight: 600;
        color: var(--muted);
    }

    .docs-nav-panel a:hover {
        color: var(--text);
        background: rgba(56, 189, 248, 0.08);
        text-decoration: none;
    }

    @media (max-width: 960px) {
        .docs-topnav-inner {
            grid-template-columns: 1fr auto;
        }

        .docs-nav {
            grid-column: 1 / -1;
            order: 3;
            justify-content: space-between;
        }

        .docs-nav-main { display: none; }

        .docs-nav-toggle { display: inline-flex; }

        .docs-nav-actions {
            grid-column: 2;
        }

        .docs-nav-actions .search-wrap {
            max-width: 200px;
            min-width: 140px;
        }
    }

    @media (max-width: 640px) {
        .docs-topnav-inner { grid-template-columns: 1fr; }
        .docs-nav-actions { width: 100%; }
        .docs-nav-actions .search-wrap { flex: 1; max-width: none; }
    }

    /* Learning paths */
    .path-grid { align-items: stretch; }
    .path-card { display: flex; flex-direction: column; height: 100%; }
    .path-card h3 { margin: 0.35rem 0 0.5rem; font-size: 1.05rem; font-weight: 800; }
    .path-card > p { margin-bottom: 0.85rem; flex: 1; }
    .path-featured { border-color: rgba(56, 189, 248, 0.35); box-shadow: 0 12px 40px rgba(56, 189, 248, 0.08); }
    .path-steps {
        margin: 0 0 1rem;
        padding-left: 1.15rem;
        color: var(--muted);
        font-size: 0.88rem;
        font-weight: 500;
    }
    .path-steps li { margin-bottom: 0.35rem; }
    .path-go { margin-top: auto; align-self: flex-start; }

    .path-note {
        display: flex;
        align-items: flex-start;
        gap: 0.65rem;
        margin-top: 1rem;
        text-align: left;
    }

    .path-note .ico { flex-shrink: 0; margin-top: 0.15rem; color: var(--primary); }
    .path-note div { color: #bae6fd; font-weight: 500; line-height: 1.6; }
    .path-note strong { color: var(--text); }

    .hub-divider,
    .content-divider {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin: 2.5rem 0 2rem;
        color: var(--muted);
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .hub-divider::before,
    .hub-divider::after,
    .content-divider::before,
    .content-divider::after {
        content: "";
        flex: 1;
        height: 1px;
        background: var(--border);
    }

    .md-guide-card {
        display: block;
        color: inherit;
        text-decoration: none;
    }

    .md-guide-card:hover { color: inherit; text-decoration: none; }
    .md-guide-card h4 { margin: 0 0 0.35rem; font-size: 0.95rem; }
    .md-guide-card p { margin: 0; font-size: 0.78rem; }
    .md-guide-card code { font-size: 0.72rem; }

    html[dir="ltr"] .acc-trigger { text-align: left; }
    html[dir="ltr"] .acc-body { text-align: left; }
    html[dir="ltr"] .acc-body p { color: var(--muted); font-weight: 500; }
</style>
