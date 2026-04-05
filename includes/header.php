<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle ?? 'Life Review Ledger') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f8f6f3',
                            100: '#efe9e0',
                            200: '#ddd2c1',
                            300: '#c7b49a',
                            400: '#b09474',
                            500: '#9a7d5b',
                            600: '#86694c',
                            700: '#6d5540',
                            800: '#5b4737',
                            900: '#4d3d31',
                        },
                    },
                    fontFamily: {
                        sans: ['"Noto Sans JP"', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/life-review-ledger/assets/css/style.css">
    <script>var LRL_USER_ID = <?= currentUserId() ?>;</script>
    <script src="/life-review-ledger/assets/js/store.js"></script>
</head>
<body class="bg-gray-50 text-slate-800 min-h-screen">

<!-- モバイルメニューオーバーレイ -->
<div id="mobileOverlay" class="fixed inset-0 bg-black/40 z-40 hidden lg:hidden" onclick="toggleMobileMenu()"></div>

<div class="flex min-h-screen">
    <!-- サイドバー -->
    <aside id="sidebar" class="fixed lg:static inset-y-0 left-0 z-50 w-60 bg-slate-900 text-white flex-shrink-0 flex flex-col transform -translate-x-full lg:translate-x-0 transition-transform duration-200">
        <div class="px-5 py-6">
            <h1 class="text-base font-semibold tracking-wide text-white">Life Review Ledger</h1>
            <p class="text-[11px] text-slate-500 mt-1">人生と関係性のレビュー基盤</p>
        </div>
        <nav class="px-3 space-y-0.5">
            <a href="?page=home" class="nav-link flex items-center gap-3 px-4 py-2.5 rounded-lg text-[13px] <?= ($currentPage ?? '') === 'home' ? 'active text-white font-medium' : 'text-slate-400' ?>">
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                ホーム
            </a>
            <a href="?page=issues" class="nav-link flex items-center gap-3 px-4 py-2.5 rounded-lg text-[13px] <?= ($currentPage ?? '') === 'issues' ? 'active text-white font-medium' : 'text-slate-400' ?>">
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                未処理論点
            </a>
            <a href="?page=weekly" class="nav-link flex items-center gap-3 px-4 py-2.5 rounded-lg text-[13px] <?= ($currentPage ?? '') === 'weekly' ? 'active text-white font-medium' : 'text-slate-400' ?>">
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                今週のレビュー
            </a>
            <a href="?page=conversation" class="nav-link flex items-center gap-3 px-4 py-2.5 rounded-lg text-[13px] <?= ($currentPage ?? '') === 'conversation' ? 'active text-white font-medium' : 'text-slate-400' ?>">
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                夫婦会話準備
            </a>
            <a href="?page=patterns" class="nav-link flex items-center gap-3 px-4 py-2.5 rounded-lg text-[13px] <?= ($currentPage ?? '') === 'patterns' ? 'active text-white font-medium' : 'text-slate-400' ?>">
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                崩れ方・パターン
            </a>
        </nav>

        <!-- 日次入力ボタン -->
        <div class="mt-5 px-3">
            <a href="?page=daily-input" class="flex items-center justify-center gap-2 w-full py-2.5 px-4 bg-brand-500 hover:bg-brand-400 text-white rounded-lg text-[13px] font-medium transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                今日の記録をつける
            </a>
        </div>

        <!-- ユーザー & ログアウト -->
        <div class="mt-auto px-4 py-4 border-t border-slate-800">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-full bg-slate-700 flex items-center justify-center text-[11px] text-slate-300 font-medium"><?= mb_substr(currentUserName(), 0, 1) ?></div>
                    <span class="text-[12px] text-slate-400"><?= htmlspecialchars(currentUserName()) ?></span>
                </div>
                <a href="?page=logout" class="text-[11px] text-slate-500 hover:text-slate-300 transition-colors">ログアウト</a>
            </div>
        </div>
    </aside>

    <!-- メインコンテンツ -->
    <main class="flex-1 min-w-0">
        <!-- トップバー -->
        <header class="bg-white/80 backdrop-blur-sm border-b border-gray-200/60 px-4 sm:px-6 lg:px-8 py-3 sm:py-4 flex items-center justify-between sticky top-0 z-30">
            <div class="flex items-center gap-3">
                <button onclick="toggleMobileMenu()" class="lg:hidden p-2 -ml-2 text-slate-600 hover:bg-gray-100 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h2 class="text-[15px] font-semibold text-slate-800"><?= h($pageHeading ?? '') ?></h2>
            </div>
            <div class="text-[13px] text-slate-400">
                <?= date('Y年n月j日') ?>
            </div>
        </header>

        <div class="p-4 sm:p-5 lg:p-7 max-w-5xl">
