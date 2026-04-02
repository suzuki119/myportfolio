<?php
require_once __DIR__ . '/config.php';
session_name(CMS_SESSION);
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['password']) && $_POST['password'] === CMS_PASSWORD) {
    $_SESSION['cms_admin_logged_in'] = true;
    header('Location: admin.php');
    exit;
  }
  $error = 'パスワードが違います';
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CMS Login</title>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300&family=Space+Mono:wght@400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/admin.css">
</head>
<body class="login-page">
<div class="login-box">
  <h1>Works CMS</h1>
  <p class="sub">管理画面</p>
  <form method="post">
    <label>Password</label>
    <input type="password" name="password" autofocus placeholder="••••••••••••">
    <button type="submit">ログイン</button>
    <?php if ($error): ?>
      <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
  </form>
</div>
</body>
</html>
