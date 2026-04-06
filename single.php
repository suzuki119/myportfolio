<?php
require_once 'cms/config.php';
$pdo = db();

// URLの ?id=1 から記事IDを取得
$id = (int)($_GET['id'] ?? 0);

if ($id === 0) {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

// 公開済みの記事のみ取得（下書きは表示しない）
$stmt = $pdo->prepare(
    'SELECT * FROM posts WHERE id = :id AND status = :status LIMIT 1'
);
$stmt->execute([':id' => $id, ':status' => 'published']);
$post = $stmt->fetch();

// 存在しない・非公開の記事はTOPへ
if (!$post) {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

// 日付を読みやすい形式に変換（例：2026年4月6日）
$date = date('Y年n月j日', strtotime($post['created_at']));
// [組み込み] strtotime()='2026-04-06 12:00:00' のような日付文字列をUNIXタイムスタンプ（数値）に変換する
// [組み込み] date('フォーマット', タイムスタンプ)=タイムスタンプを指定した形式の文字列に変換する
// 'Y'=4桁の年 'n'=月（ゼロ埋めなし） 'j'=日（ゼロ埋めなし）
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title><?= h($post['title']) ?> — Portfolio</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <header class="header">
        <div class="header__logo"><a href="index.php" style="color:inherit;text-decoration:none;">SuzukiPortfolio</a></div>
    </header>

    <main style="max-width:800px;margin:100px auto;padding:0 24px;">

        <?php if ($post['thumbnail']): ?>
            <img src="<?= UPLOAD_URL . h($post['thumbnail']) ?>"
                 alt="<?= h($post['title']) ?>"
                 style="width:100%;max-height:400px;object-fit:cover;margin-bottom:32px;">
        <?php endif; ?>

        <p style="font-size:.85rem;color:#999;margin-bottom:8px;"><?= h($date) ?></p>
        <h1 style="font-size:2rem;margin-bottom:32px;"><?= h($post['title']) ?></h1>

        <div class="single__content">
            <?= $post['content'] ?>
            <?php // h() を使わずHTMLとしてそのまま出力する ?>
            <?php // 理由：管理者（自分）しか入力しないため。h()を使うとHTMLタグが文字列として表示されてしまう ?>
        </div>

        <p style="margin-top:60px;">
            <a href="index.php" style="color:#999;font-size:.9rem;">← 一覧へ戻る</a>
        </p>

    </main>

</body>
</html>
