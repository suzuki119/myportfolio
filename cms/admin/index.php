<?php
// ===================================================
//  管理画面 トップ（記事一覧）
//  Step 3 で実装予定
// ===================================================
require_once '../config.php';
require_login();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>管理画面</title>
</head>
<body>
    <h1>ようこそ、<?= h($_SESSION['username']) ?> さん</h1>
    <p>記事一覧は Step 3 で実装します。</p>
    <a href="<?= SITE_URL ?>/cms/logout.php">ログアウト</a>
</body>
</html>
