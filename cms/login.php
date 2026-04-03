<?php
// ===================================================
//  管理者ログイン画面
// ===================================================
require_once 'config.php';

// セッション開始
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// すでにログイン済みなら管理画面へ
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . '/cms/admin/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'ユーザー名とパスワードを入力してください。';
    } else {
        $pdo = db();

        // ① ユーザー名でDBを検索
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(); // 見つからなければ false

        // ② password_verify() でハッシュと照合
        if ($user && password_verify($password, $user['password'])) {
            // ログイン成功

            // セッションIDを再生成してセッションハイジャック対策
            session_regenerate_id(true);

            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];

            header('Location: ' . SITE_URL . '/cms/admin/index.php');
            exit;
        } else {
            // ユーザー名が間違っている場合も同じメッセージにする（情報漏洩防止）
            $error = 'ユーザー名またはパスワードが正しくありません。';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ログイン</title>
    <style>
        body { font-family: sans-serif; max-width: 360px; margin: 80px auto; padding: 0 20px; }
        h1 { font-size: 1.3rem; margin-bottom: 24px; }
        label { display: block; margin-top: 16px; font-size: .9rem; }
        input { width: 100%; padding: 8px; box-sizing: border-box; margin-top: 4px; border: 1px solid #ccc; }
        button { margin-top: 20px; width: 100%; padding: 10px; background: #222; color: #fff; border: none; cursor: pointer; font-size: 1rem; }
        button:hover { background: #444; }
        .error { margin-top: 16px; padding: 10px; background: #fdecea; border-left: 4px solid #c0392b; font-size: .9rem; }
    </style>
</head>
<body>
    <h1>管理者ログイン</h1>

    <?php if ($error !== ''): ?>
        <div class="error"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <label>ユーザー名<br>
            <input type="text" name="username" autocomplete="username" required>
        </label>
        <label>パスワード<br>
            <input type="password" name="password" autocomplete="current-password" required>
        </label>
        <button type="submit">ログイン</button>
    </form>
</body>
</html>
