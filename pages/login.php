<?php
require_once __DIR__ . '/../config/app.php';

// セキュリティヘッダー
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// 既にログイン済みならホームへ
if (isLoggedIn()) {
    header('Location: ?page=home');
    exit;
}

$error = '';

// ログイン処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRFトークン検証
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        $error = '不正なリクエストです。ページを再読み込みしてください。';
    }
    // レート制限チェック
    elseif (!checkLoginRateLimit()) {
        $error = 'ログイン試行回数が上限に達しました。5分後に再試行してください。';
    }
    else {
        $loginId = trim($_POST['login_id'] ?? '');
        $password = $_POST['password'] ?? '';

        // 入力バリデーション
        if (strlen($loginId) === 0 || strlen($loginId) > 100 || strlen($password) === 0 || strlen($password) > 255) {
            $error = 'ログインIDまたはパスワードが正しくありません';
            recordLoginAttempt();
        }
        // ユーザー認証
        else {
            $user = authenticateUser($loginId, $password);
            if ($user) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                resetLoginAttempts();
                header('Location: ?page=home');
                exit;
            }
        }

        if (empty($error)) {
            $error = 'ログインIDまたはパスワードが正しくありません';
            recordLoginAttempt();
        }
    }
}

// CSRFトークン生成
$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン - Life Review Ledger</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/life-review-ledger/assets/css/style.css">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <!-- ロゴ -->
        <div class="text-center mb-8">
            <h1 class="text-[20px] font-semibold text-slate-800 tracking-wide">Life Review Ledger</h1>
            <p class="text-[13px] text-slate-400 mt-1">人生と関係性のレビュー基盤</p>
        </div>

        <!-- ログインカード -->
        <div class="login-card p-8">
            <?php if ($error): ?>
            <div class="mb-5 bg-red-50 border border-red-200 rounded-lg px-4 py-3">
                <p class="text-[13px] text-red-600"><?= htmlspecialchars($error) ?></p>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div>
                    <label class="block text-[12px] font-semibold text-slate-500 uppercase tracking-wider mb-2">ログインID</label>
                    <input type="text" name="login_id" class="field-input" placeholder="ログインIDを入力" value="<?= htmlspecialchars($_POST['login_id'] ?? '') ?>" autofocus>
                </div>
                <div>
                    <label class="block text-[12px] font-semibold text-slate-500 uppercase tracking-wider mb-2">パスワード</label>
                    <input type="password" name="password" class="field-input" placeholder="パスワードを入力">
                </div>
                <button type="submit" class="w-full py-3 bg-slate-900 text-white rounded-xl text-[14px] font-medium hover:bg-slate-800 transition-colors">
                    ログイン
                </button>
            </form>

            <div class="mt-5 text-center">
                <p class="text-[13px] text-slate-400">アカウントをお持ちでない方</p>
                <a href="?page=register" class="text-[13px] text-brand-500 hover:text-brand-400 font-medium transition-colors">新規登録はこちら →</a>
            </div>
        </div>
    </div>
</body>
</html>
