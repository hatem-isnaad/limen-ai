    <style>
        :root {
            --bg: #050816;
            --bg-2: #0c1229;
            --surface: rgba(15, 23, 42, 0.72);
            --surface-solid: #0f172a;
            --glass: rgba(255, 255, 255, 0.04);
            --border: rgba(148, 163, 184, 0.14);
            --border-glow: rgba(56, 189, 248, 0.35);
            --text: #f8fafc;
            --muted: #94a3b8;
            --primary: #38bdf8;
            --primary-2: #818cf8;
            --accent: #22d3ee;
            --success: #4ade80;
            --warning: #fbbf24;
            --danger: #fb7185;
            --radius: 18px;
            --shadow: 0 24px 80px rgba(0, 0, 0, 0.45);
            --font: "Cairo", system-ui, sans-serif;
            --mono: "JetBrains Mono", ui-monospace, "SF Mono", Menlo, monospace;
            --code-bg: #0a0e1a;
            --code-head: #10162b;
            --code-line: rgba(148, 163, 184, 0.08);
        }

        *, *::before, *::after { box-sizing: border-box; }

        .sprite { display: none; }

        .ico {
            width: 1em;
            height: 1em;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.9;
            stroke-linecap: round;
            stroke-linejoin: round;
            flex-shrink: 0;
            vertical-align: -0.125em;
            overflow: visible;
        }

        html { scroll-behavior: smooth; }

        body {
            margin: 0;
            font-family: var(--font);
            background: var(--bg);
            color: var(--text);
            line-height: 1.75;
            overflow-x: hidden;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background:
                radial-gradient(900px 500px at 85% -5%, rgba(56, 189, 248, 0.18), transparent 55%),
                radial-gradient(700px 420px at 5% 15%, rgba(129, 140, 248, 0.16), transparent 50%),
                radial-gradient(600px 400px at 50% 100%, rgba(34, 211, 238, 0.08), transparent 60%);
            pointer-events: none;
            z-index: 0;
        }

        .grid-bg {
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(148, 163, 184, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(148, 163, 184, 0.04) 1px, transparent 1px);
            background-size: 48px 48px;
            mask-image: radial-gradient(ellipse at center, black 20%, transparent 75%);
            pointer-events: none;
            z-index: 0;
        }

        a { color: var(--primary); text-decoration: none; transition: color 0.2s; }
        a:hover { color: #7dd3fc; }

        .shell {
            position: relative;
            z-index: 1;
            max-width: 1180px;
            margin: 0 auto;
            padding: 0 1.25rem 5rem;
        }

        /* Nav */
        .topnav {
            position: sticky;
            top: 0;
            z-index: 50;
            backdrop-filter: blur(16px);
            background: rgba(5, 8, 22, 0.78);
            border-bottom: 1px solid var(--border);
            margin: 0 -1.25rem 2rem;
            padding: 0.85rem 1.25rem;
        }

        .topnav-inner {
            max-width: 1180px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            font-weight: 800;
            font-size: 1.05rem;
        }

        .brand-icon {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary), var(--primary-2));
            display: grid;
            place-items: center;
            font-size: 1rem;
            box-shadow: 0 8px 24px rgba(56, 189, 248, 0.35);
        }

        .nav-links {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem 1rem;
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--muted);
        }

        .nav-links a:hover { color: var(--text); text-decoration: none; }

        /* Hero */
        .hero {
            position: relative;
            padding: 3rem 2rem 2.5rem;
            border-radius: calc(var(--radius) + 6px);
            border: 1px solid var(--border);
            background: linear-gradient(145deg, rgba(15, 23, 42, 0.9), rgba(15, 23, 42, 0.55));
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 2.5rem;
        }

        .hero::after {
            content: "";
            position: absolute;
            inset: auto auto -40% -10%;
            width: 320px;
            height: 320px;
            background: radial-gradient(circle, rgba(56, 189, 248, 0.25), transparent 70%);
            filter: blur(8px);
            animation: pulse 6s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.7; }
            50% { transform: scale(1.08); opacity: 1; }
        }

        .badge-row { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1.25rem; }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.78rem;
            font-weight: 700;
            padding: 0.3rem 0.75rem;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: var(--glass);
        }

        .badge-live {
            border-color: rgba(74, 222, 128, 0.4);
            color: #86efac;
            background: rgba(74, 222, 128, 0.08);
        }

        .live-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #4ade80;
            box-shadow: 0 0 0 0 rgba(74, 222, 128, 0.6);
            animation: livePulse 2s ease-out infinite;
        }

        @keyframes livePulse {
            0% { box-shadow: 0 0 0 0 rgba(74, 222, 128, 0.55); }
            70% { box-shadow: 0 0 0 6px rgba(74, 222, 128, 0); }
            100% { box-shadow: 0 0 0 0 rgba(74, 222, 128, 0); }
        }

        .badge-ai {
            border-color: rgba(56, 189, 248, 0.35);
            color: #bae6fd;
            background: rgba(56, 189, 248, 0.1);
        }

        h1 {
            margin: 0 0 1rem;
            font-size: clamp(2rem, 5vw, 3rem);
            font-weight: 900;
            line-height: 1.2;
            letter-spacing: -0.02em;
            background: linear-gradient(135deg, #fff 0%, #cbd5e1 45%, #38bdf8 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .lead {
            margin: 0;
            max-width: 52ch;
            font-size: 1.08rem;
            color: var(--muted);
            font-weight: 500;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 0.85rem;
            margin: 2rem 0 1.75rem;
        }

        .stat {
            padding: 1rem 1.1rem;
            border-radius: 14px;
            border: 1px solid var(--border);
            background: var(--glass);
            backdrop-filter: blur(8px);
        }

        .stat-num {
            font-size: 1.75rem;
            font-weight: 900;
            line-height: 1;
            color: var(--primary);
        }

        .stat-label { font-size: 0.82rem; color: var(--muted); font-weight: 600; margin-top: 0.25rem; }

        .cta-row { display: flex; flex-wrap: wrap; gap: 0.65rem; }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            padding: 0.7rem 1.15rem;
            border-radius: 12px;
            font-family: var(--font);
            font-weight: 700;
            font-size: 0.92rem;
            border: 1px solid transparent;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s, background 0.15s;
        }

        .btn:hover { transform: translateY(-2px); text-decoration: none; }

        .btn-primary {
            background: linear-gradient(135deg, #0ea5e9, #6366f1);
            color: white;
            box-shadow: 0 12px 32px rgba(14, 165, 233, 0.35);
        }

        .btn-ghost {
            background: rgba(30, 41, 59, 0.8);
            color: var(--text);
            border-color: var(--border);
        }

        /* Sections */
        section { margin-bottom: 3rem; scroll-margin-top: 5rem; }

        .section-head {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1.25rem;
        }

        h2 {
            margin: 0;
            font-size: 1.55rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 0.65rem;
        }

        .h2-icon {
            flex-shrink: 0;
            width: 2.1rem;
            height: 2.1rem;
            border-radius: 10px;
            display: grid;
            place-items: center;
            font-size: 1.05rem;
            color: var(--primary);
            background: linear-gradient(145deg, rgba(56, 189, 248, 0.16), rgba(129, 140, 248, 0.1));
            border: 1px solid rgba(56, 189, 248, 0.2);
        }

        .section-sub { margin: 0.25rem 0 0; color: var(--muted); font-size: 0.92rem; font-weight: 500; }

        .grid { display: grid; gap: 1rem; }
        @media (min-width: 640px) { .grid-2 { grid-template-columns: repeat(2, 1fr); } }
        @media (min-width: 900px) { .grid-3 { grid-template-columns: repeat(3, 1fr); } }
        @media (min-width: 900px) { .grid-4 { grid-template-columns: repeat(4, 1fr); } }

        /* Bento cards */
        .card {
            position: relative;
            background: linear-gradient(160deg, rgba(30, 41, 59, 0.55), rgba(15, 23, 42, 0.85));
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.35rem 1.4rem;
            transition: border-color 0.25s, transform 0.25s, box-shadow 0.25s;
        }

        .card:hover {
            border-color: rgba(56, 189, 248, 0.28);
            transform: translateY(-3px);
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.35);
        }

        .card-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-size: 1.2rem;
            margin-bottom: 0.85rem;
            color: var(--primary);
            background: linear-gradient(145deg, rgba(56, 189, 248, 0.16), rgba(129, 140, 248, 0.1));
            border: 1px solid rgba(56, 189, 248, 0.22);
            transition: transform 0.25s cubic-bezier(0.34, 1.5, 0.64, 1), color 0.25s;
        }

        .card:hover .card-icon { transform: translateY(-2px) scale(1.06); color: #7dd3fc; }

        .card h3 {
            margin: 0 0 0.4rem;
            font-size: 1.05rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card h3 > .ico { font-size: 1.05rem; color: var(--primary); }

        .step-dot {
            flex-shrink: 0;
            width: 1.5rem;
            height: 1.5rem;
            border-radius: 7px;
            display: inline-grid;
            place-items: center;
            font-size: 0.72rem;
            font-weight: 900;
            font-family: var(--mono);
            color: #04121f;
            background: linear-gradient(135deg, var(--primary), var(--primary-2));
        }
        .card p { margin: 0; color: var(--muted); font-size: 0.9rem; font-weight: 500; line-height: 1.65; }

        .card-wide { grid-column: 1 / -1; }

        @media (min-width: 900px) {
            .bento .span-2 { grid-column: span 2; }
        }

        /* Timeline */
        .timeline {
            display: flex;
            flex-direction: column;
            gap: 0;
            position: relative;
            padding-right: 1rem;
        }

        .timeline::before {
            content: "";
            position: absolute;
            top: 0;
            bottom: 0;
            right: 0.45rem;
            width: 2px;
            background: linear-gradient(to bottom, var(--primary), var(--primary-2), transparent);
        }

        .step {
            position: relative;
            padding: 0.85rem 1.5rem 0.85rem 0;
        }

        .step::before {
            content: "";
            position: absolute;
            right: -0.15rem;
            top: 1.15rem;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary);
            box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.2);
        }

        .step-title {
            font-weight: 800;
            font-size: 0.98rem;
            margin-bottom: 0.15rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .step-desc { color: var(--muted); font-size: 0.88rem; margin: 0; }

        /* Table */
        .table-wrap {
            overflow: hidden;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            background: var(--surface-solid);
        }

        table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }

        th, td {
            padding: 0.9rem 1rem;
            border-bottom: 1px solid var(--border);
            text-align: right;
            vertical-align: top;
        }

        th {
            background: rgba(15, 23, 42, 0.95);
            color: var(--muted);
            font-size: 0.78rem;
            font-weight: 700;
        }

        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(56, 189, 248, 0.04); }

        code {
            font-family: var(--mono);
            font-size: 0.8em;
            font-weight: 500;
            direction: ltr;
            display: inline-block;
            background: rgba(129, 140, 248, 0.1);
            border: 1px solid rgba(129, 140, 248, 0.2);
            padding: 0.08rem 0.4rem;
            border-radius: 6px;
            color: #c7d2fe;
            vertical-align: baseline;
        }

        .pill {
            display: inline-block;
            font-size: 0.76rem;
            font-weight: 600;
            padding: 0.2rem 0.55rem;
            border-radius: 999px;
            background: rgba(30, 41, 59, 0.9);
            border: 1px solid var(--border);
            color: #cbd5e1;
            margin: 0.15rem 0 0.15rem 0.35rem;
            direction: ltr;
        }

        .pill-live { background: rgba(74, 222, 128, 0.1); border-color: rgba(74, 222, 128, 0.35); color: #86efac; }
        .pill-warn { background: rgba(251, 191, 36, 0.1); border-color: rgba(251, 191, 36, 0.35); color: #fde68a; }

        /* Prompts */
        .prompt-box { display: flex; flex-direction: column; gap: 0.55rem; }

        .prompt {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.85rem 1rem;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: rgba(15, 23, 42, 0.7);
            font-size: 0.92rem;
            font-weight: 600;
        }

        .prompt-text { flex: 1; }

        .copy-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            flex-shrink: 0;
            order: -1;
            font-family: var(--font);
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.35rem 0.65rem;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--glass);
            color: var(--muted);
            cursor: pointer;
            transition: all 0.15s;
        }

        .copy-btn:hover { color: var(--text); border-color: var(--primary); }
        .copy-btn:active { transform: scale(0.95); }
        .copy-btn.copied { color: var(--success); border-color: rgba(74, 222, 128, 0.5); }
        .copy-btn.failed { color: var(--danger); border-color: rgba(251, 113, 133, 0.5); }
        .code-copy.failed { color: #fda4af; border-color: rgba(251, 113, 133, 0.5); background: rgba(251, 113, 133, 0.12); }

        /* CLI command rows */
        .cli-block {
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.6rem 0.8rem;
            border-radius: 10px;
            border: 1px solid rgba(148, 163, 184, 0.14);
            background: var(--code-bg);
            margin-bottom: 0.45rem;
            direction: ltr;
            text-align: left;
            transition: border-color 0.18s, background 0.18s;
        }

        .cli-block:hover { border-color: rgba(56, 189, 248, 0.32); }

        .cli-block::before {
            content: "$";
            flex-shrink: 0;
            font-family: var(--mono);
            font-weight: 700;
            font-size: 0.82rem;
            color: #4ade80;
        }

        .cli-block > code {
            flex: 1;
            min-width: 0;
            overflow-x: auto;
            white-space: pre;
            background: none;
            border: none;
            padding: 0;
            border-radius: 0;
            display: block;
            font-family: var(--mono);
            font-size: 0.79rem;
            color: #cdd6f4;
        }

        .cli-block > code::-webkit-scrollbar { height: 6px; }
        .cli-block > code::-webkit-scrollbar-thumb { background: #1f2a44; border-radius: 6px; }

        .cli-block > span {
            flex-shrink: 0;
            max-width: 42%;
            color: var(--muted);
            direction: rtl;
            text-align: right;
            font-family: var(--font);
            font-size: 0.78rem;
            font-weight: 500;
            padding-right: 0.7rem;
            border-right: 1px solid rgba(148, 163, 184, 0.14);
        }

        @media (max-width: 720px) {
            .cli-block { flex-wrap: wrap; }
            .cli-block > span { max-width: 100%; border-right: none; padding-right: 0; padding-top: 0.35rem; }
        }

        /* Tabs */
        .tabs { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem; }

        .tab {
            font-family: var(--font);
            font-weight: 700;
            font-size: 0.88rem;
            padding: 0.5rem 1rem;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--muted);
            cursor: pointer;
            transition: all 0.2s;
        }

        .tab.active, .tab:hover {
            color: var(--text);
            border-color: rgba(56, 189, 248, 0.45);
            background: rgba(56, 189, 248, 0.1);
        }

        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        footer {
            margin-top: 3rem;
            padding: 2rem 1.5rem;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            background: var(--glass);
            color: var(--muted);
            font-size: 0.9rem;
            font-weight: 500;
        }

        footer strong { color: var(--text); }

        /* Flow diagram */
        .flow {
            display: flex;
            flex-direction: column;
            gap: 0.55rem;
            direction: ltr;
            text-align: center;
        }

        .flow-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
        }

        .flow-box {
            padding: 0.55rem 0.85rem;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: rgba(15, 23, 42, 0.85);
            font-size: 0.78rem;
            font-weight: 700;
            color: #e2e8f0;
            direction: rtl;
        }

        .flow-box.highlight {
            border-color: rgba(56, 189, 248, 0.45);
            background: rgba(56, 189, 248, 0.12);
            color: #bae6fd;
        }

        .flow-arrow {
            color: var(--primary);
            font-size: 1rem;
            font-weight: 400;
            opacity: 0.55;
            line-height: 1;
        }

        /* ---------- Code blocks ---------- */
        pre {
            margin: 0;
            padding: 0.95rem 1.1rem;
            overflow-x: auto;
            direction: ltr;
            text-align: left;
            background: transparent;
            border: none;
            font-family: var(--mono);
            font-size: 0.8rem;
            line-height: 1.75;
            color: #cdd6f4;
            tab-size: 4;
        }

        pre code {
            background: none;
            border: none;
            padding: 0;
            font-size: inherit;
            font-family: inherit;
            display: block;
            color: inherit;
        }

        .code-block {
            position: relative;
            margin: 0 0 0.85rem;
            border-radius: 13px;
            border: 1px solid rgba(148, 163, 184, 0.16);
            background: var(--code-bg);
            overflow: hidden;
            box-shadow: 0 12px 34px rgba(0, 0, 0, 0.35);
        }

        .code-head {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.5rem 0.75rem;
            background: var(--code-head);
            border-bottom: 1px solid rgba(148, 163, 184, 0.12);
            direction: ltr;
        }

        .code-dots { display: inline-flex; gap: 0.3rem; margin-left: auto; }

        .code-dots i {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            display: block;
        }

        .code-dots i:nth-child(1) { background: #ff5f57; }
        .code-dots i:nth-child(2) { background: #febc2e; }
        .code-dots i:nth-child(3) { background: #28c840; }

        .code-lang {
            font-family: var(--mono);
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #7f8ba6;
        }

        .code-copy {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            flex-shrink: 0;
            font-family: var(--font);
            font-size: 0.72rem;
            font-weight: 700;
            padding: 0.26rem 0.6rem;
            border-radius: 7px;
            border: 1px solid rgba(148, 163, 184, 0.2);
            background: rgba(148, 163, 184, 0.08);
            color: #94a3b8;
            cursor: pointer;
            transition: all 0.18s;
        }

        .code-copy:hover {
            color: #e2e8f0;
            border-color: rgba(56, 189, 248, 0.5);
            background: rgba(56, 189, 248, 0.12);
        }

        .code-copy:active { transform: scale(0.95); }

        /* Smooth icon swap */
        .ico-swap {
            position: relative;
            display: inline-block;
            width: 0.95rem;
            height: 0.95rem;
            flex-shrink: 0;
        }

        .ico-swap .ico {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            transition: opacity 0.22s ease, transform 0.28s cubic-bezier(0.34, 1.6, 0.64, 1);
        }

        .ico-swap .ico-done,
        .ico-swap .ico-fail {
            opacity: 0;
            transform: scale(0.4) rotate(-30deg);
        }

        .copied .ico-swap .ico-idle,
        .failed .ico-swap .ico-idle {
            opacity: 0;
            transform: scale(0.4) rotate(30deg);
        }

        .copied .ico-swap .ico-done,
        .failed .ico-swap .ico-fail {
            opacity: 1;
            transform: none;
        }

        .copy-label {
            min-width: 2rem;
            text-align: center;
            transition: color 0.2s;
        }

        .code-copy.copied {
            color: #86efac;
            border-color: rgba(74, 222, 128, 0.5);
            background: rgba(74, 222, 128, 0.12);
        }

        .code-body { display: flex; align-items: stretch; }

        .code-gutter {
            flex-shrink: 0;
            padding: 0.95rem 0.6rem 0.95rem 0.85rem;
            font-family: var(--mono);
            font-size: 0.8rem;
            line-height: 1.75;
            text-align: right;
            color: #3e4a63;
            background: rgba(255, 255, 255, 0.015);
            border-right: 1px solid var(--code-line);
            user-select: none;
            direction: ltr;
            white-space: pre;
        }

        .code-body > pre { flex: 1; min-width: 0; }

        pre::-webkit-scrollbar { height: 8px; }
        pre::-webkit-scrollbar-track { background: transparent; }
        pre::-webkit-scrollbar-thumb { background: #1f2a44; border-radius: 8px; }

        /* Compact command rows */
        .cmd {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.6rem 0.8rem;
            border-radius: 10px;
            border: 1px solid rgba(148, 163, 184, 0.14);
            background: var(--code-bg);
            direction: ltr;
            transition: border-color 0.18s;
        }

        .cmd:hover { border-color: rgba(56, 189, 248, 0.3); }

        .cmd-prompt {
            flex-shrink: 0;
            font-family: var(--mono);
            font-weight: 700;
            font-size: 0.82rem;
            color: #4ade80;
        }

        .cmd-text {
            flex: 1;
            min-width: 0;
            overflow-x: auto;
            white-space: pre;
            font-family: var(--mono);
            font-size: 0.79rem;
            color: #cdd6f4;
        }

        .cmd-text::-webkit-scrollbar { height: 6px; }
        .cmd-text::-webkit-scrollbar-thumb { background: #1f2a44; border-radius: 6px; }

        .cmd-note {
            margin: 0.3rem 0 0.7rem;
            padding-right: 0.15rem;
            font-size: 0.82rem;
            color: var(--muted);
            font-weight: 500;
        }

        .code-caption {
            margin: 0.35rem 0 0.65rem;
            font-size: 0.82rem;
            color: var(--muted);
            font-weight: 600;
        }

        /* Accordion */
        .accordion { display: flex; flex-direction: column; gap: 0.55rem; }

        .acc-item {
            border: 1px solid var(--border);
            border-radius: 14px;
            background: rgba(15, 23, 42, 0.55);
            overflow: hidden;
        }

        .acc-trigger > span:first-child {
            display: flex;
            align-items: center;
            gap: 0.55rem;
        }

        .acc-trigger {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 1.15rem;
            border: none;
            background: transparent;
            color: var(--text);
            font-family: var(--font);
            font-size: 0.98rem;
            font-weight: 800;
            cursor: pointer;
            text-align: right;
        }

        .acc-trigger:hover { background: rgba(56, 189, 248, 0.05); }

        .acc-icon {
            flex-shrink: 0;
            width: 1.75rem;
            height: 1.75rem;
            border-radius: 8px;
            display: grid;
            place-items: center;
            background: rgba(56, 189, 248, 0.12);
            color: var(--primary);
            font-size: 0.85rem;
            transition: transform 0.28s cubic-bezier(0.34, 1.4, 0.64, 1), background 0.2s;
        }

        .acc-trigger:hover .acc-icon { background: rgba(56, 189, 248, 0.22); }

        .acc-item.open .acc-icon { transform: rotate(180deg); }

        .acc-body {
            display: none;
            padding: 0 1.15rem 1.15rem;
            border-top: 1px solid var(--border);
        }

        .acc-item.open .acc-body { display: block; }

        /* Decision map */
        .decision-grid {
            display: grid;
            gap: 0.75rem;
        }

        @media (min-width: 640px) {
            .decision-grid { grid-template-columns: repeat(2, 1fr); }
        }

        .decision {
            display: flex;
            gap: 0.75rem;
            padding: 1rem 1.1rem;
            border-radius: 14px;
            border: 1px solid var(--border);
            background: var(--glass);
        }

        .decision-q {
            flex-shrink: 0;
            width: 2.1rem;
            height: 2.1rem;
            border-radius: 10px;
            display: grid;
            place-items: center;
            font-size: 1.05rem;
            color: var(--primary);
            background: rgba(56, 189, 248, 0.1);
            border: 1px solid rgba(56, 189, 248, 0.18);
        }

        .decision h4 {
            margin: 0 0 0.25rem;
            font-size: 0.92rem;
            font-weight: 800;
        }

        .decision p {
            margin: 0;
            font-size: 0.85rem;
            color: var(--muted);
            font-weight: 500;
        }

        .alert {
            padding: 1rem 1.15rem;
            border-radius: 14px;
            border: 1px solid rgba(251, 191, 36, 0.35);
            background: rgba(251, 191, 36, 0.08);
            color: #fde68a;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .alert-info {
            border-color: rgba(56, 189, 248, 0.35);
            background: rgba(56, 189, 248, 0.08);
            color: #bae6fd;
        }

        h3.section-mini {
            margin: 1.25rem 0 0.65rem;
            font-size: 1rem;
            font-weight: 800;
        }

        /* Concept cards */
        .concepts { display: flex; flex-direction: column; gap: 0.85rem; }

        .concept {
            border: 1px solid var(--border);
            border-radius: var(--radius);
            background: linear-gradient(160deg, rgba(30, 41, 59, 0.5), rgba(15, 23, 42, 0.82));
            padding: 1.15rem 1.3rem;
            transition: border-color 0.25s, transform 0.25s;
        }

        .concept:hover { border-color: rgba(56, 189, 248, 0.26); transform: translateY(-2px); }

        .concept-head {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.9rem;
            flex-wrap: wrap;
        }

        .concept-icon {
            flex-shrink: 0;
            width: 2.4rem;
            height: 2.4rem;
            border-radius: 11px;
            display: grid;
            place-items: center;
            font-size: 1.15rem;
            color: var(--primary);
            background: linear-gradient(145deg, rgba(56, 189, 248, 0.16), rgba(129, 140, 248, 0.1));
            border: 1px solid rgba(56, 189, 248, 0.22);
        }

        .concept-title { font-size: 1.08rem; font-weight: 900; margin: 0; }

        .concept-en {
            font-family: var(--mono);
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--primary);
            margin-right: 0.45rem;
            direction: ltr;
            display: inline-block;
        }

        .concept-tag {
            margin: 0;
            font-size: 0.84rem;
            color: var(--muted);
            font-weight: 600;
        }

        .concept-row {
            display: grid;
            grid-template-columns: 5.5rem 1fr;
            gap: 0.4rem 0.75rem;
            padding: 0.55rem 0;
            border-top: 1px dashed rgba(148, 163, 184, 0.12);
        }

        .concept-row:first-of-type { border-top: none; padding-top: 0; }

        .concept-label {
            font-size: 0.78rem;
            font-weight: 800;
            color: var(--primary);
            padding-top: 0.12rem;
        }

        .concept-row p { margin: 0; font-size: 0.9rem; color: #cbd5e1; font-weight: 500; line-height: 1.75; }
        .concept-row .warn { color: #fde68a; }
        .concept-row .good { color: #86efac; }

        .concept-row .code-block { margin: 0.15rem 0 0; }

        @media (max-width: 560px) {
            .concept-row { grid-template-columns: 1fr; gap: 0.15rem; }
            .concept-label { padding-top: 0; }
        }

        .analogy {
            border: 1px solid rgba(129, 140, 248, 0.28);
            background: linear-gradient(145deg, rgba(129, 140, 248, 0.12), rgba(56, 189, 248, 0.06));
            border-radius: var(--radius);
            padding: 1.35rem 1.5rem;
            margin-bottom: 1.25rem;
        }

        .analogy h3 { margin: 0 0 0.6rem; font-size: 1.1rem; font-weight: 900; }
        .analogy p { margin: 0 0 0.75rem; font-size: 0.95rem; color: #dbeafe; font-weight: 500; }
        .analogy p:last-child { margin-bottom: 0; }

        .env-live {
            display: inline-block;
            font-family: var(--mono);
            font-size: 0.72rem;
            font-weight: 600;
            direction: ltr;
            padding: 0.12rem 0.45rem;
            border-radius: 6px;
            background: rgba(74, 222, 128, 0.1);
            border: 1px solid rgba(74, 222, 128, 0.28);
            color: #86efac;
        }

        .env-table { font-size: 0.82rem; }
        .env-table td:first-child { font-family: var(--mono); direction: ltr; text-align: left; white-space: nowrap; color: #bae6fd; font-size: 0.78rem; }
        .env-table .val { font-family: var(--mono); font-size: 0.76rem; color: #fde68a; direction: ltr; text-align: left; }
        .env-table .def { color: var(--muted); font-size: 0.78rem; }

        .live-grid {
            display: grid;
            gap: 0.55rem;
            margin-bottom: 1rem;
        }

        @media (min-width: 720px) { .live-grid { grid-template-columns: repeat(3, 1fr); } }

        .live-item {
            padding: 0.7rem 0.85rem;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: var(--glass);
        }

        .live-item kbd {
            display: block;
            font-family: var(--mono);
            font-size: 0.72rem;
            color: var(--muted);
            direction: ltr;
            text-align: left;
            margin-bottom: 0.25rem;
        }

        .live-item span { font-size: 0.88rem; font-weight: 700; direction: ltr; display: block; text-align: left; }

        /* Walkthrough steps */
        .walk { display: flex; flex-direction: column; gap: 0.9rem; }

        .walk-step {
            position: relative;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            background: linear-gradient(160deg, rgba(30, 41, 59, 0.5), rgba(15, 23, 42, 0.82));
            padding: 1.2rem 1.35rem;
            transition: border-color 0.25s;
        }

        .walk-step:hover { border-color: rgba(56, 189, 248, 0.26); }

        .walk-head {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            margin-bottom: 0.5rem;
            flex-wrap: wrap;
        }

        .walk-title { font-size: 1.02rem; font-weight: 800; }

        .walk-file {
            font-family: var(--mono);
            font-size: 0.72rem;
            font-weight: 600;
            color: #7dd3fc;
            direction: ltr;
            background: rgba(56, 189, 248, 0.08);
            border: 1px solid rgba(56, 189, 248, 0.18);
            border-radius: 6px;
            padding: 0.1rem 0.45rem;
            margin-right: auto;
        }

        .walk-desc {
            margin: 0 0 0.85rem;
            color: var(--muted);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .walk-step .code-block:last-child { margin-bottom: 0; }

        .scenario {
            display: grid;
            gap: 0.65rem;
            margin-bottom: 1rem;
        }

        @media (min-width: 720px) { .scenario { grid-template-columns: repeat(3, 1fr); } }

        .scenario-item {
            padding: 0.85rem 1rem;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: var(--glass);
        }

        .scenario-item h4 {
            margin: 0 0 0.3rem;
            font-size: 0.85rem;
            font-weight: 800;
            color: var(--primary);
        }

        .scenario-item p { margin: 0; font-size: 0.85rem; color: var(--muted); font-weight: 500; }

        /* English blocks */
        .en {
            direction: ltr;
            text-align: left;
            font-family: "Inter", system-ui, -apple-system, sans-serif;
        }

        .section-sub .en,
        .en-note {
            display: block;
            margin-top: 0.2rem;
            direction: ltr;
            text-align: left;
            font-size: 0.85rem;
            color: rgba(148, 163, 184, 0.78);
            font-weight: 500;
            font-style: italic;
        }

        .en-guide {
            direction: ltr;
            text-align: left;
        }

        .en-guide h3 {
            margin: 0 0 0.35rem;
            font-size: 1.05rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .en-guide p { margin: 0 0 0.85rem; color: var(--muted); font-size: 0.9rem; font-weight: 500; }
        .en-guide ul { margin: 0 0 0.85rem; padding-left: 1.2rem; color: var(--muted); font-size: 0.88rem; }
        .en-guide li { margin-bottom: 0.3rem; }
        .en-guide strong { color: var(--text); }

        .step-num {
            flex-shrink: 0;
            width: 1.85rem;
            height: 1.85rem;
            border-radius: 9px;
            display: grid;
            place-items: center;
            font-size: 0.82rem;
            font-weight: 900;
            background: linear-gradient(135deg, var(--primary), var(--primary-2));
            color: #04121f;
        }

        .lang-toggle {
            display: inline-flex;
            border-radius: 999px;
            border: 1px solid var(--border);
            overflow: hidden;
            background: rgba(15, 23, 42, 0.85);
        }

        .lang-toggle button {
            font-family: var(--font);
            font-size: 0.76rem;
            font-weight: 800;
            padding: 0.35rem 0.7rem;
            border: none;
            background: transparent;
            color: var(--muted);
            cursor: pointer;
            transition: all 0.18s;
        }

        .lang-toggle button.active {
            background: rgba(56, 189, 248, 0.16);
            color: #bae6fd;
        }

        body.hide-en .en-only { display: none !important; }
        body.hide-ar .ar-only { display: none !important; }

        /* Syntax highlighting — Palenight-inspired */
        .tok-comment { color: #5f7186; font-style: italic; }
        .tok-string  { color: #c3e88d; }
        .tok-var     { color: #f07178; }
        .tok-keyword { color: #c792ea; }
        .tok-func    { color: #82aaff; }
        .tok-number  { color: #f78c6c; }
        .tok-op      { color: #89ddff; }
        .tok-key     { color: #ffcb6b; }
        .tok-tag     { color: #f07178; }
        .tok-attr    { color: #ffcb6b; }
        .tok-flag    { color: #f78c6c; }
        .tok-class   { color: #ffcb6b; }

        /* Scroll progress */
        .progress-bar {
            position: fixed;
            top: 0;
            right: 0;
            left: 0;
            height: 3px;
            z-index: 100;
            background: linear-gradient(90deg, var(--primary), var(--primary-2), var(--accent));
            transform: scaleX(0);
            transform-origin: right center;
            transition: transform 0.08s linear;
        }

        /* Search */
        .search-wrap {
            position: relative;
            flex: 1 1 200px;
            max-width: 280px;
        }

        .search-input {
            width: 100%;
            font-family: var(--font);
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text);
            padding: 0.5rem 2.2rem 0.5rem 2.2rem;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: rgba(15, 23, 42, 0.85);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .search-input::placeholder { color: var(--muted); font-weight: 500; }

        .search-input:focus {
            border-color: rgba(56, 189, 248, 0.5);
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.12);
        }

        .search-icon {
            position: absolute;
            top: 50%;
            right: 0.75rem;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: 0.85rem;
            pointer-events: none;
        }

        .search-kbd {
            position: absolute;
            top: 50%;
            left: 0.6rem;
            transform: translateY(-50%);
            font-size: 0.68rem;
            font-weight: 700;
            color: var(--muted);
            border: 1px solid var(--border);
            border-radius: 5px;
            padding: 0.05rem 0.3rem;
            pointer-events: none;
        }

        .search-count {
            font-size: 0.78rem;
            color: var(--muted);
            font-weight: 600;
            margin-top: 0.4rem;
        }

        [hidden] { display: none !important; }

        mark {
            background: rgba(251, 191, 36, 0.28);
            color: #fde68a;
            border-radius: 4px;
            padding: 0 0.15rem;
        }

        .empty-state {
            padding: 2.5rem 1.5rem;
            text-align: center;
            border-radius: var(--radius);
            border: 1px dashed var(--border);
            color: var(--muted);
            font-weight: 600;
        }

        /* Scrollspy */
        .nav-links a {
            position: relative;
            padding: 0.2rem 0;
        }

        .nav-links a::after {
            content: "";
            position: absolute;
            right: 0;
            bottom: -2px;
            width: 0;
            height: 2px;
            border-radius: 2px;
            background: linear-gradient(90deg, var(--primary), var(--primary-2));
            transition: width 0.25s ease;
        }

        .nav-links a.active {
            color: var(--text);
        }

        .nav-links a.active::after { width: 100%; }

        /* Reveal on scroll */
        .reveal {
            opacity: 0;
            transform: translateY(18px);
            transition: opacity 0.55s cubic-bezier(0.22, 1, 0.36, 1), transform 0.55s cubic-bezier(0.22, 1, 0.36, 1);
        }

        .reveal.shown { opacity: 1; transform: none; }

        /* Back to top */
        .to-top {
            position: fixed;
            bottom: 1.5rem;
            left: 1.5rem;
            z-index: 60;
            width: 2.9rem;
            height: 2.9rem;
            border-radius: 50%;
            display: grid;
            place-items: center;
            border: 1px solid var(--border);
            background: rgba(15, 23, 42, 0.92);
            backdrop-filter: blur(10px);
            color: var(--text);
            font-size: 1rem;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transform: translateY(12px);
            transition: all 0.25s;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        }

        .to-top.visible { opacity: 1; visibility: visible; transform: none; }
        .to-top:hover { border-color: var(--primary); color: var(--primary); }

        .cli-block > .code-copy { order: -1; }

        /* Tool filter */
        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            margin-bottom: 1rem;
        }

        .filter-chip {
            font-family: var(--font);
            font-size: 0.82rem;
            font-weight: 700;
            padding: 0.4rem 0.9rem;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--muted);
            cursor: pointer;
            transition: all 0.2s;
        }

        .filter-chip.active, .filter-chip:hover {
            color: var(--text);
            border-color: rgba(56, 189, 248, 0.45);
            background: rgba(56, 189, 248, 0.1);
        }

        .tool-card {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            padding: 0.85rem 1rem;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: rgba(15, 23, 42, 0.62);
            transition: border-color 0.2s, transform 0.2s;
        }

        .tool-card:hover { border-color: rgba(56, 189, 248, 0.3); transform: translateY(-2px); }

        .tool-key {
            font-family: var(--mono);
            font-size: 0.82rem;
            font-weight: 700;
            color: #bae6fd;
            direction: ltr;
            text-align: left;
        }

        .tool-desc { font-size: 0.82rem; color: var(--muted); font-weight: 500; }

        .tool-flags { display: flex; flex-wrap: wrap; gap: 0.3rem; }

        .flag {
            font-size: 0.68rem;
            font-weight: 700;
            padding: 0.1rem 0.45rem;
            border-radius: 999px;
            border: 1px solid var(--border);
            color: var(--muted);
        }

        .flag-guest { border-color: rgba(74, 222, 128, 0.35); color: #86efac; background: rgba(74, 222, 128, 0.08); }
        .flag-approval { border-color: rgba(251, 191, 36, 0.35); color: #fde68a; background: rgba(251, 191, 36, 0.08); }

        .tool-grid { display: grid; gap: 0.6rem; }
        @media (min-width: 640px) { .tool-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (min-width: 1000px) { .tool-grid { grid-template-columns: repeat(3, 1fr); } }

        /* Accordion controls */
        .acc-controls {
            display: flex;
            gap: 0.4rem;
            margin-bottom: 0.75rem;
        }

        .mini-btn {
            font-family: var(--font);
            font-size: 0.78rem;
            font-weight: 700;
            padding: 0.35rem 0.75rem;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--glass);
            color: var(--muted);
            cursor: pointer;
            transition: all 0.18s;
        }

        .mini-btn:hover { color: var(--text); border-color: var(--primary); }

        /* Anchor links on headings */
        .anchor {
            margin-right: 0.45rem;
            font-size: 0.85em;
            color: var(--muted);
            opacity: 0;
            transition: opacity 0.2s;
        }

        h2:hover .anchor { opacity: 1; }

        ::selection { background: rgba(56, 189, 248, 0.3); color: #fff; }

        ::-webkit-scrollbar { width: 10px; height: 10px; }
        ::-webkit-scrollbar-track { background: #050816; }
        ::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 8px; border: 2px solid #050816; }
        ::-webkit-scrollbar-thumb:hover { background: #334155; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
            .reveal { opacity: 1; transform: none; }
        }

        @media print {
            body { background: #fff; color: #000; }
            .grid-bg, .topnav, .to-top, .progress-bar, .copy-btn, .code-copy, .search-wrap { display: none !important; }
            .card, .table-wrap, pre { break-inside: avoid; border-color: #ccc; }
            .acc-body { display: block !important; }
            a { color: #000; }
        }

        @media (max-width: 640px) {
            .hero { padding: 2rem 1.25rem; }
            h1 { font-size: 1.75rem; }
            .nav-links { font-size: 0.8rem; gap: 0.3rem 0.75rem; }
            .search-wrap { max-width: 100%; }
            .to-top { bottom: 1rem; left: 1rem; }
        }
