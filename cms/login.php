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
  <style>
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      background: #0f0f0f;
      color: #ede8df;
      font-family: 'Space Mono', monospace;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .login-box {
      width: 100%;
      max-width: 360px;
      padding: 48px;
      background: #1a1a1a;
      border: 1px solid #2e2e2e;
    }

    h1 {
      font-family: 'Cormorant Garamond', serif;
      font-size: 32px;
      font-weight: 300;
      margin-bottom: 6px;
    }

    .sub {
      font-size: 8px;
      letter-spacing: 0.3em;
      text-transform: uppercase;
      color: #b8a88a;
      margin-bottom: 40px;
    }

    label {
      display: block;
      font-size: 9px;
      letter-spacing: 0.25em;
      text-transform: uppercase;
      color: #7a7a72;
      margin-bottom: 8px;
    }

    input[type=password] {
      width: 100%;
      background: #0f0f0f;
      border: 1px solid #2e2e2e;
      color: #ede8df;
      padding: 12px 14px;
      font-size: 13px;
      font-family: inherit;
      outline: none;
      transition: border-color 0.2s;
      margin-bottom: 20px;
    }
    input[type=password]:focus { border-color: #b8a88a; }

    button {
      width: 100%;
      background: #b8a88a;
      color: #0f0f0f;
      border: none;
      padding: 12px;
      font-family: inherit;
      font-size: 10px;
      letter-spacing: 0.3em;
      text-transform: uppercase;
      cursor: pointer;
      transition: opacity 0.2s;
    }
    button:hover { opacity: 0.85; }

    .error {
      font-size: 10px;
      letter-spacing: 0.15em;
      color: #c0392b;
      margin-top: 14px;
      text-align: center;
    }
  </style>
</head>
<body>
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
