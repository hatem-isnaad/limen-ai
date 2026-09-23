@verbatim
<script>
(function () {
    'use strict';

    const icon = (id, cls) => '<svg class="ico ' + cls + '"><use href="#' + id + '"/></svg>';

    const COPY_MARKUP =
        '<span class="ico-swap" aria-hidden="true">' +
            icon('i-copy', 'ico-idle') +
            icon('i-check', 'ico-done') +
            icon('i-x', 'ico-fail') +
        '</span>' +
        '<span class="copy-label">Copy</span>';

    const initCopyButton = (btn) => {
        btn.type = 'button';
        btn.setAttribute('aria-label', 'Copy');
        btn.className = 'code-copy';
        btn.innerHTML = COPY_MARKUP;
        return btn;
    };

    const copy = async (text, btn) => {
        const label = btn.querySelector('.copy-label');
        let state = 'copied';

        try {
            await navigator.clipboard.writeText(text);
        } catch (_) {
            state = 'failed';
        }

        btn.classList.remove('copied', 'failed');
        btn.classList.add(state);
        if (label) {
            label.textContent = state === 'copied' ? 'Copied' : 'Failed';
        }

        clearTimeout(btn._resetTimer);
        btn._resetTimer = setTimeout(() => {
            btn.classList.remove('copied', 'failed');
            if (label) label.textContent = 'Copy';
        }, 1700);
    };

    const PHP_KEYWORDS = 'return|function|public|private|protected|class|interface|use|new|true|false|null|require|array|const|static|namespace|extends|implements|if|else|elseif|foreach|while|as|try|catch|throw|void|bool|string|int|float|fn|match|enum|readonly|declare|abstract|final';
    const PHP_FUNCS = 'env|config|app_path|base_path|storage_path|route|url|data_get|auth|__|trans|collect|now';

    const sniffLanguage = (text) => {
        if (/^\s*(#|[A-Z][A-Z0-9_]*=)/m.test(text) && !/[{};]/.test(text)) return 'env';
        if (/^\s*(composer|php artisan|npm|ollama|curl|open|git)\b/m.test(text)) return 'bash';
        if (/&lt;x-|&lt;!--/.test(text) || /^\s*</.test(text)) return 'blade';
        if (/[$'"]|=>|::/.test(text)) return 'php';
        return 'text';
    };

    const escapeHtml = (s) => s
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');

    const wrap = (cls, text) => '<span class="tok-' + cls + '">' + text + '</span>';

    const HIGHLIGHTERS = {
        php: (src) => src.replace(
            new RegExp(
                '(\\/\\/[^\\n]*|\\/\\*[\\s\\S]*?\\*\\/|#[^\\n]*)' +
                "|('(?:\\\\.|[^'\\\\])*'|\"(?:\\\\.|[^\"\\\\])*\")" +
                '|(\\$[A-Za-z_]\\w*)' +
                '|\\b(' + PHP_FUNCS + ')(?=\\s*\\()' +
                '|\\b(' + PHP_KEYWORDS + ')\\b' +
                '|\\b([A-Z]\\w*(?:\\\\[A-Z]\\w*)*)\\b' +
                '|([A-Za-z_]\\w*)(?=\\s*\\()' +
                '|(=&gt;|-&gt;|\\?-&gt;|::)' +
                '|\\b(\\d+(?:\\.\\d+)?)\\b',
                'g'
            ),
            (m, comment, str, variable, helper, keyword, cls, call, op, num) => {
                if (comment) return wrap('comment', comment);
                if (str) return wrap('string', str);
                if (variable) return wrap('var', variable);
                if (helper) return wrap('func', helper);
                if (keyword) return wrap('keyword', keyword);
                if (cls) return wrap('class', cls);
                if (call) return wrap('func', call);
                if (op) return wrap('op', op);
                if (num) return wrap('number', num);
                return m;
            }
        ),
        bash: (src) => src.replace(
            /(#[^\n]*)|('[^']*'|"[^"]*")|(^|\s)(--?[A-Za-z][\w-]*)|\b([\w-]+:[\w:-]+)|\b(composer|php|artisan|npm|ollama|curl|git|open|cd)\b/gm,
            (m, comment, str, lead, flag, sub, cmd) => {
                if (comment) return wrap('comment', comment);
                if (str) return wrap('string', str);
                if (flag) return lead + wrap('flag', flag);
                if (sub) return wrap('func', sub);
                if (cmd) return wrap('keyword', cmd);
                return m;
            }
        ),
        env: (src) => src.replace(
            /(#[^\n]*)|^([A-Z][A-Z0-9_]*)(=)(.*)$/gm,
            (m, comment, key, eq, value) => {
                if (comment) return wrap('comment', comment);
                return wrap('key', key) + wrap('op', eq) + wrap('string', value);
            }
        ),
        blade: (src) => src.replace(
            /(&lt;!--[\s\S]*?--&gt;)|(&lt;\/?[\w:.-]+)|([\w-]+)(=)("[^"]*")|(\/?&gt;)/g,
            (m, comment, tag, attr, eq, val, close) => {
                if (comment) return wrap('comment', comment);
                if (tag) return wrap('tag', tag);
                if (attr) return wrap('attr', attr) + wrap('op', eq) + wrap('string', val);
                if (close) return wrap('tag', close);
                return m;
            }
        ),
        text: (src) => src,
    };

    const LANG_LABEL = { php: 'PHP', bash: 'Terminal', env: '.env', blade: 'Blade', text: 'Text' };

    document.querySelectorAll('pre > code').forEach((code) => {
        if (code.closest('.code-block')) return;

        const pre = code.parentElement;
        const raw = code.textContent.replace(/\s+$/, '');
        const lang = pre.dataset.lang || sniffLanguage(escapeHtml(raw));
        const lineCount = raw.split('\n').length;

        code.innerHTML = (HIGHLIGHTERS[lang] || HIGHLIGHTERS.text)(escapeHtml(raw));

        const block = document.createElement('div');
        block.className = 'code-block';
        pre.parentNode.insertBefore(block, pre);

        block.innerHTML =
            '<div class="code-head">' +
                '<span class="code-dots" aria-hidden="true"><i></i><i></i><i></i></span>' +
                '<span class="code-lang">' + (LANG_LABEL[lang] || lang) + '</span>' +
            '</div>' +
            '<div class="code-body">' +
                (lineCount > 1
                    ? '<div class="code-gutter" aria-hidden="true">' +
                        Array.from({ length: lineCount }, (_, i) => i + 1).join('\n') +
                      '</div>'
                    : '') +
            '</div>';

        block.querySelector('.code-body').appendChild(pre);

        const btn = initCopyButton(document.createElement('button'));
        block.querySelector('.code-head').prepend(btn);
        btn.addEventListener('click', () => copy(raw, btn));
    });

    const mapHubLang = (code) => {
        const cls = [...code.classList].find((c) => c.startsWith('language-'));
        if (!cls) return 'text';
        const lang = cls.replace('language-', '');
        if (lang === 'xml') return 'blade';
        if (lang === 'ini' || lang === 'env') return 'env';
        if (lang === 'php') return 'php';
        if (lang === 'bash') return 'bash';
        return 'text';
    };

    document.querySelectorAll('#hubContent .code-block pre > code').forEach((code) => {
        const raw = code.textContent.replace(/\s+$/, '');
        const lang = mapHubLang(code);
        code.innerHTML = (HIGHLIGHTERS[lang] || HIGHLIGHTERS.text)(escapeHtml(raw));
    });

    document.querySelectorAll('#hubContent .copy-btn').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const code = btn.closest('.code-block')?.querySelector('code');
            if (!code) return;
            try {
                await navigator.clipboard.writeText(code.textContent.trim());
                btn.textContent = 'Copied';
                btn.classList.add('copied');
                setTimeout(() => {
                    btn.textContent = 'Copy';
                    btn.classList.remove('copied');
                }, 2000);
            } catch (_) {
                btn.textContent = 'Failed';
            }
        });
    });

    document.querySelectorAll('.tab').forEach((tab) => {
        tab.addEventListener('click', () => {
            const scope = tab.closest('section') || document;
            scope.querySelectorAll('.tab').forEach((t) => t.classList.remove('active'));
            scope.querySelectorAll('.tab-panel').forEach((p) => p.classList.remove('active'));
            tab.classList.add('active');
            document.getElementById('panel-' + tab.dataset.tab)?.classList.add('active');
        });
    });

    document.querySelectorAll('.acc-trigger').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            trigger.closest('.acc-item')?.classList.toggle('open');
        });
    });

    const closeAllDrops = () => {
        document.querySelectorAll('[data-nav-drop].open').forEach((drop) => {
            drop.classList.remove('open');
            drop.querySelector('.nav-drop-trigger')?.setAttribute('aria-expanded', 'false');
            drop.querySelector('.nav-drop-panel')?.setAttribute('hidden', '');
        });
    };

    document.querySelectorAll('[data-nav-drop]').forEach((drop) => {
        const trigger = drop.querySelector('.nav-drop-trigger');
        const panel = drop.querySelector('.nav-drop-panel');
        trigger?.addEventListener('click', (event) => {
            event.stopPropagation();
            const isOpen = drop.classList.contains('open');
            closeAllDrops();
            if (!isOpen) {
                drop.classList.add('open');
                trigger.setAttribute('aria-expanded', 'true');
                panel?.removeAttribute('hidden');
            }
        });
    });

    document.addEventListener('click', closeAllDrops);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeAllDrops();
        }
    });

    const navPanel = document.getElementById('docsNavPanel');
    const navBackdrop = document.getElementById('docsNavBackdrop');
    const navToggle = document.getElementById('docsNavToggle');
    const navClose = document.getElementById('docsNavClose');

    const openNavPanel = () => {
        navPanel?.removeAttribute('hidden');
        navBackdrop?.removeAttribute('hidden');
        navToggle?.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
    };

    const closeNavPanel = () => {
        navPanel?.setAttribute('hidden', '');
        navBackdrop?.setAttribute('hidden', '');
        navToggle?.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    };

    navToggle?.addEventListener('click', openNavPanel);
    navClose?.addEventListener('click', closeNavPanel);
    navBackdrop?.addEventListener('click', closeNavPanel);
    navPanel?.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', closeNavPanel);
    });

    const progress = document.getElementById('progressBar');
    const toTop = document.getElementById('toTop');

    const onScroll = () => {
        const max = document.documentElement.scrollHeight - window.innerHeight;
        const ratio = max > 0 ? window.scrollY / max : 0;
        if (progress) progress.style.transform = 'scaleX(' + ratio + ')';
        if (toTop) toTop.classList.toggle('visible', window.scrollY > 600);
    };

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    toTop?.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

    const navLinks = Array.from(document.querySelectorAll('.docs-nav-link[href*="#"]'));
    const sections = navLinks
        .map((link) => {
            const href = link.getAttribute('href') || '';
            const hash = href.includes('#') ? href.split('#')[1] : '';
            return hash ? document.getElementById(hash) : null;
        })
        .filter(Boolean);

    if (sections.length && 'IntersectionObserver' in window) {
        const spy = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                navLinks.forEach((l) => l.classList.remove('active'));
                const match = navLinks.find((l) => (l.getAttribute('href') || '').endsWith('#' + entry.target.id));
                match?.classList.add('active');
            });
        }, { rootMargin: '-20% 0px -70% 0px' });

        sections.forEach((s) => spy.observe(s));
    }

    const search = document.getElementById('docsSearch');
    const hub = document.getElementById('hubContent');
    const searchables = hub
        ? [...document.querySelectorAll('section[id]')]
        : [];

    if (search && searchables.length) {
        let timer;
        const runSearch = (raw) => {
            const q = raw.trim().toLowerCase();
            searchables.forEach((block) => {
                if (!q) {
                    block.classList.remove('search-hidden');
                    return;
                }
                block.classList.toggle('search-hidden', !block.textContent.toLowerCase().includes(q));
            });
        };

        search.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(() => runSearch(search.value), 140);
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === '/' && document.activeElement !== search && !['INPUT', 'TEXTAREA'].includes(document.activeElement?.tagName)) {
                e.preventDefault();
                search.focus();
            }
            if (e.key === 'Escape' && document.activeElement === search) {
                search.value = '';
                runSearch('');
                search.blur();
            }
        });
    }
})();
</script>
@endverbatim
