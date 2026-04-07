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

// セクションを sort_order 順に取得
$s_stmt = $pdo->prepare(
    'SELECT * FROM post_sections WHERE post_id = :post_id ORDER BY sort_order ASC'
);
$s_stmt->execute([':post_id' => $id]);
$sections = $s_stmt->fetchAll(); // [PDO組み込み] 全行を配列で取得

// tags をカンマ区切りから配列に変換
$tags = !empty($post['tags']) ? explode(',', $post['tags']) : [];
// [組み込み] explode('区切り文字', 文字列) = 文字列を分割して配列にする
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title><?= h($post['title']) ?> — Suzuki Portfolio</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div id="canvas-container">
    <canvas id="bg-canvas"></canvas>
</div>

<header class="header--work">
    <a href="./index.php" class="back-link">
        <svg viewBox="0 0 24 24"><path d="M19 12H5M5 12l7 7M5 12l7-7"/></svg>
        Back to Portfolio
    </a>
    <div class="header-logo">Suzuki Portfolio</div>
</header>

<main>

    <!-- Hero -->
    <div class="work-hero">
        <div class="work-hero-eyebrow">Works</div>
        <h1 class="work-hero-title"><?= h($post['title']) ?></h1>
    </div>

    <div class="hero-divider"></div>

    <!-- Meta bar -->
    <div class="work-meta-bar">
        <div class="work-meta-item">
            <div class="work-meta-label">制作期間</div>
            <div class="work-meta-value"><?= h($post['period']) ?></div>
        </div>
        <div class="work-meta-item">
            <div class="work-meta-label">使用技術</div>
            <div class="work-meta-value">
                <div class="tag-list">
                    <?php foreach ($tags as $tag): ?>
                        <span class="tag"><?= h(trim($tag)) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="work-meta-item">
            <div class="work-meta-label">種別</div>
            <div class="work-meta-value"><?= nl2br(h($post['type'])) ?></div>
            <?php // [組み込み] nl2br() = 文字列中の改行(\n)をHTMLの<br>タグに変換する ?>
        </div>
    </div>

    <!-- Content -->
    <div class="work-content">
        <article>

            <?php if ($post['thumbnail']): ?>
                <div class="mock-img">
                    <img src="<?= UPLOAD_URL . h($post['thumbnail']) ?>" alt="<?= h($post['title']) ?>">
                </div>
            <?php endif; ?>

            <?php if (!empty($post['external_url'])): ?>
                <div class="work-cta">
                    <a href="<?= h($post['external_url']) ?>" target="_blank" rel="noopener" class="btn-primary">
                        作品へ
                        <svg viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    </a>
                </div>
            <?php endif; ?>

            <?php foreach ($sections as $section): ?>
                <div class="article-block">
                    <h2 class="block-title"><?= h($section['title']) ?></h2>
                    <div class="block-body">
                        <?= nl2br(h($section['body'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>

        </article>
    </div>

</main>

<footer>2026 Suzuki Yutaro — All Rights Reserved</footer>

<script src="script.js"></script>
</body>
</html>
