<?php
require_once __DIR__ . '/../config/app.php';

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

if (isLoggedIn()) {
    header('Location: ?page=home');
    exit;
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        $error = '不正なリクエストです。ページを再読み込みしてください。';
    } else {
        $loginId = trim($_POST['login_id'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        // バリデーション
        if (strlen($loginId) < 3 || strlen($loginId) > 30) {
            $error = 'ログインIDは3〜30文字で入力してください';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $loginId)) {
            $error = 'ログインIDは半角英数字とアンダースコアのみ使用できます';
        } elseif (strlen($name) === 0 || strlen($name) > 50) {
            $error = '表示名を入力してください（50文字以内）';
        } elseif (strlen($password) < 6) {
            $error = 'パスワードは6文字以上で設定してください';
        } elseif ($password !== $passwordConfirm) {
            $error = 'パスワードが一致しません';
        } elseif (findUser($loginId) !== null) {
            $error = 'このログインIDは既に使用されています';
        } else {
            $user = registerUser($loginId, $password, $name);
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            header('Location: ?page=home');
            exit;
        }
    }
}

$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新規登録 - Life Review Ledger</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/life-review-ledger/assets/css/style.css">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <h1 class="text-[20px] font-semibold text-slate-800 tracking-wide">Life Review Ledger</h1>
            <p class="text-[13px] text-slate-400 mt-1">新規アカウント登録</p>
        </div>

        <div class="login-card p-8">
            <?php if ($error): ?>
            <div class="mb-5 bg-red-50 border border-red-200 rounded-lg px-4 py-3">
                <p class="text-[13px] text-red-600"><?= htmlspecialchars($error) ?></p>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div>
                    <label class="block text-[12px] font-semibold text-slate-500 uppercase tracking-wider mb-2">表示名</label>
                    <input type="text" name="name" class="field-input" placeholder="例：坂口" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" autofocus>
                </div>
                <div>
                    <label class="block text-[12px] font-semibold text-slate-500 uppercase tracking-wider mb-2">ログインID</label>
                    <input type="text" name="login_id" class="field-input" placeholder="半角英数字 3〜30文字" value="<?= htmlspecialchars($_POST['login_id'] ?? '') ?>">
                </div>
                <div>
                    <label class="block text-[12px] font-semibold text-slate-500 uppercase tracking-wider mb-2">パスワード</label>
                    <input type="password" name="password" class="field-input" placeholder="6文字以上">
                </div>
                <div>
                    <label class="block text-[12px] font-semibold text-slate-500 uppercase tracking-wider mb-2">パスワード（確認）</label>
                    <input type="password" name="password_confirm" class="field-input" placeholder="もう一度入力">
                </div>
                <button type="submit" class="w-full py-3 bg-slate-900 text-white rounded-xl text-[14px] font-medium hover:bg-slate-800 transition-colors">
                    登録する
                </button>
            </form>

            <div class="mt-5 text-center">
                <a href="?page=login" class="text-[13px] text-brand-500 hover:text-brand-400 font-medium transition-colors">← ログイン画面に戻る</a>
            </div>
        </div>
    </div>
</body>
</html>
