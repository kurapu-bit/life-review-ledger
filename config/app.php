<?php
// タイムゾーン（日本時間）
date_default_timezone_set('Asia/Tokyo');

// アプリケーション設定
define('APP_NAME', 'Life Review Ledger');

// セッションセキュリティ設定
ini_set('session.cookie_httponly', '1');       // JSからcookieアクセス不可
ini_set('session.cookie_samesite', 'Strict');  // CSRF対策
ini_set('session.use_strict_mode', '1');       // 不正セッションID拒否
ini_set('session.gc_maxlifetime', '7200');     // 2時間でセッション期限切れ

// HTTPS環境ではcookieをSecureに
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', '1');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// テスト用ユーザー（パスワードはハッシュ化して保存）
define('TEST_USERS', [
    'test1' => [
        'password_hash' => '$2y$10$ngI0PFd0NlzZ5fbXFgChSOyoqxK/o5mCPOeo.d.w1BSp.6Q1ksebW',
        'id' => 1,
        'name' => 'テストユーザー',
    ],
]);

// 初回のみ: ハッシュ値を生成するためのヘルパー（本番では削除）
// echo password_hash('test1', PASSWORD_BCRYPT); exit;

// ログイン済みか確認
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

// ログイン中のユーザーID
function currentUserId(): int {
    return $_SESSION['user_id'] ?? 0;
}

// ログイン中のユーザー名
function currentUserName(): string {
    return $_SESSION['user_name'] ?? '';
}

// CSRFトークン生成
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRFトークン検証
function verifyCsrfToken(string $token): bool {
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ログイン試行回数制限
function checkLoginRateLimit(): bool {
    $key = 'login_attempts';
    $max = 5;
    $window = 300; // 5分間

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
    }

    $data = $_SESSION[$key];

    // ウィンドウ期間を過ぎていたらリセット
    if (time() - $data['first_attempt'] > $window) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
        return true;
    }

    return $data['count'] < $max;
}

function recordLoginAttempt(): void {
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = ['count' => 0, 'first_attempt' => time()];
    }
    $_SESSION['login_attempts']['count']++;
}

function resetLoginAttempts(): void {
    unset($_SESSION['login_attempts']);
}
