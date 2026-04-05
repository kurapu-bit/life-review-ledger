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

// === ユーザー管理（JSONファイルベース） ===
define('USERS_FILE', __DIR__ . '/../data/users.json');

function ensureDataDir(): void {
    $dir = dirname(USERS_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

function loadUsers(): array {
    if (!file_exists(USERS_FILE)) {
        // 初回: テストユーザーを含むファイルを作成
        ensureDataDir();
        $initial = [
            'test1' => [
                'id' => 1,
                'login_id' => 'test1',
                'name' => 'テストユーザー',
                'password_hash' => TEST_USERS['test1']['password_hash'],
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ];
        file_put_contents(USERS_FILE, json_encode($initial, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return $initial;
    }
    return json_decode(file_get_contents(USERS_FILE), true) ?: [];
}

function saveUsers(array $users): void {
    ensureDataDir();
    file_put_contents(USERS_FILE, json_encode($users, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
}

function findUser(string $loginId): ?array {
    $users = loadUsers();
    return $users[$loginId] ?? null;
}

function registerUser(string $loginId, string $password, string $name): array {
    $users = loadUsers();

    // 次のIDを採番
    $maxId = 0;
    foreach ($users as $u) {
        if (($u['id'] ?? 0) > $maxId) $maxId = $u['id'];
    }

    $user = [
        'id' => $maxId + 1,
        'login_id' => $loginId,
        'name' => $name,
        'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        'created_at' => date('Y-m-d H:i:s'),
    ];

    $users[$loginId] = $user;
    saveUsers($users);

    return $user;
}

function authenticateUser(string $loginId, string $password): ?array {
    $user = findUser($loginId);
    if ($user && password_verify($password, $user['password_hash'])) {
        return $user;
    }
    return null;
}

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
