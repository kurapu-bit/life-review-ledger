<?php
/**
 * Life Review Ledger - メインルーター
 * 人生と関係性のレビュー基盤
 */

require_once __DIR__ . '/config/app.php';

// セキュリティヘッダー
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// ページパラメータの取得とバリデーション
$page = $_GET['page'] ?? 'home';
if (!preg_match('/^[a-z\-]+$/', $page)) {
    http_response_code(400);
    exit;
}

// ログアウト処理
if ($page === 'logout') {
    $_SESSION = [];
    session_destroy();
    header('Location: ?page=login');
    exit;
}

// ログイン画面はそのまま表示
if ($page === 'login') {
    require __DIR__ . '/pages/login.php';
    exit;
}

// 未ログインならログイン画面へリダイレクト
if (!isLoggedIn()) {
    header('Location: ?page=login');
    exit;
}

$validPages = [
    'home' => 'pages/home.php',
    'issues' => 'pages/issues.php',
    'weekly' => 'pages/weekly.php',
    'conversation' => 'pages/conversation.php',
    'patterns' => 'pages/patterns.php',
    'daily-input' => 'pages/daily-input.php',
];

if (isset($validPages[$page])) {
    require __DIR__ . '/' . $validPages[$page];
} else {
    http_response_code(404);
    echo '<h1>ページが見つかりません</h1>';
}
