@php
    $docsHub = route('demo.limen-ai.docs.hub');

    $learnLinks = [
        ['label' => 'المفاهيم', 'href' => '#concepts'],
        ['label' => 'التثبيت', 'href' => '#install'],
        ['label' => 'مثال كامل', 'href' => '#example'],
        ['label' => 'Integration Guide', 'href' => '#integration'],
    ];

    $referenceGroups = [
        'أساسيات' => [
            ['label' => 'أفضل الممارسات', 'href' => '#best-practices'],
            ['label' => 'المبدأ', 'href' => '#principle'],
            ['label' => 'المكونات', 'href' => '#components'],
            ['label' => 'كيف تضيف', 'href' => '#how-to-add'],
            ['label' => 'المسجّل في هذا التطبيق', 'href' => '#registered'],
        ],
        'تشغيل' => [
            ['label' => 'الوكلاء', 'href' => '#agents'],
            ['label' => 'الأدوات', 'href' => '#tools'],
            ['label' => 'مرجع الملفات', 'href' => '#file-reference'],
            ['label' => 'خريطة القرار', 'href' => '#decision'],
        ],
        'مرجع' => [
            ['label' => 'CLI', 'href' => '#cli'],
            ['label' => 'متغيرات .env', 'href' => '#env-reference'],
            ['label' => 'ملخص التثبيت', 'href' => '#current-install'],
            ['label' => 'القدرات', 'href' => '#capabilities'],
        ],
        'English docs' => [
            ['label' => 'Learning paths (EN)', 'href' => $docsHub.'#learning-paths'],
            ['label' => 'Package docs hub', 'href' => $docsHub],
            ['label' => 'Black-box guide', 'href' => $docsHub.'/'.rawurlencode('black-box-host-guide.md')],
        ],
    ];
@endphp

<div class="host-nav" id="hostNav">
    <div class="host-nav-main">
        <a class="host-nav-link host-nav-link-primary" href="#concepts">المفاهيم</a>
        <a class="host-nav-link" href="#install">التثبيت</a>
        <a class="host-nav-link" href="#example">مثال</a>

        <div class="nav-drop" data-nav-drop>
            <button class="nav-drop-trigger" type="button" aria-expanded="false" aria-haspopup="true">
                دليل
                <svg class="ico nav-chevron"><use href="#i-chevron"/></svg>
            </button>
            <div class="nav-drop-panel nav-drop-panel-sm" hidden>
                @foreach($learnLinks as $link)
                    <a class="nav-drop-link" href="{{ $link['href'] }}">{{ $link['label'] }}</a>
                @endforeach
            </div>
        </div>

        <div class="nav-drop" data-nav-drop>
            <button class="nav-drop-trigger" type="button" aria-expanded="false" aria-haspopup="true">
                مرجع
                <svg class="ico nav-chevron"><use href="#i-chevron"/></svg>
            </button>
            <div class="nav-drop-panel nav-drop-panel-wide" hidden>
                <div class="nav-drop-grid">
                    @foreach($referenceGroups as $title => $links)
                        <div class="nav-drop-col">
                            <div class="nav-drop-title">{{ $title }}</div>
                            @foreach($links as $link)
                                <a class="nav-drop-link" href="{{ $link['href'] }}">{{ $link['label'] }}</a>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <a class="host-nav-link host-nav-link-en" href="{{ $docsHub }}#learning-paths">
            <svg class="ico"><use href="#i-globe"/></svg>
            Learning paths
        </a>
    </div>

    <button class="host-nav-toggle" type="button" id="hostNavToggle" aria-expanded="false" aria-controls="hostNavPanel">
        <svg class="ico"><use href="#i-menu"/></svg>
        <span>الأقسام</span>
    </button>
</div>

<div class="host-nav-backdrop" id="hostNavBackdrop" hidden></div>

<aside class="host-nav-panel" id="hostNavPanel" aria-label="أقسام الدليل" hidden>
    <div class="host-nav-panel-head">
        <strong>انتقل إلى قسم</strong>
        <button class="host-nav-close" type="button" id="hostNavClose" aria-label="إغلاق">
            <svg class="ico"><use href="#i-x"/></svg>
        </button>
    </div>

    <div class="host-nav-panel-group">
        <div class="host-nav-panel-label">ابدأ هنا</div>
        @foreach($learnLinks as $link)
            <a href="{{ $link['href'] }}">{{ $link['label'] }}</a>
        @endforeach
        <a href="{{ $docsHub }}#learning-paths">Learning paths (EN)</a>
    </div>

    @foreach($referenceGroups as $title => $links)
        <div class="host-nav-panel-group">
            <div class="host-nav-panel-label">{{ $title }}</div>
            @foreach($links as $link)
                <a href="{{ $link['href'] }}">{{ $link['label'] }}</a>
            @endforeach
        </div>
    @endforeach
</aside>
