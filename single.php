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

// セクションを sort_order 順に取得（post_sections テーブル：見出し＋本文の繰り返し）
$s_stmt = $pdo->prepare(
    'SELECT * FROM post_sections WHERE post_id = :post_id ORDER BY sort_order ASC'
);
$s_stmt->execute([':post_id' => $id]);
$sections = $s_stmt->fetchAll(); // [PDO組み込み] 全行を配列で取得

// tags をカンマ区切りから配列に変換（例：'WordPress,SCSS' → ['WordPress', 'SCSS']）
$tags = !empty($post['tags']) ? explode(',', $post['tags']) : [];
// [組み込み] explode('区切り文字', 文字列) = 文字列を分割して配列にする。JSのsplit()に相当

// OGP用：説明文（最初のセクション本文の先頭100文字。なければ種別を使う）
$ogp_description = '';
if (!empty($sections)) {
    $ogp_description = mb_substr(strip_tags($sections[0]['body']), 0, 100);
    // [組み込み] mb_substr()=マルチバイト対応の文字列切り出し / strip_tags()=HTMLタグを除去
}
if ($ogp_description === '' && !empty($post['type'])) {
    $ogp_description = $post['type'];
}

// OGP用：画像（サムネイルがあればそれを、なければデフォルト画像）
$ogp_image = !empty($post['thumbnail'])
    ? UPLOAD_URL . $post['thumbnail']
    : SITE_URL . '/images/ogp.png';
$page_title       = $post['title'] . ' — Suzuki Portfolio';
$page_description = $ogp_description;
$og_url           = SITE_URL . '/single.php?id=' . $post['id'];
$og_image         = $ogp_image;
require 'header.php';
?>

<div id="canvas-container">
    <canvas id="bg-canvas"></canvas>
</div>

<header class="header--work">
    <a href="./index.php" class="back-link">
        <svg viewBox="0 0 24 24"><path d="M19 12H5M5 12l7 7M5 12l7-7"/></svg>
        Back to Portfolio
    </a>
</header>

<main class="single">

    <!-- Hero -->
    <div class="single__hero">
        <h1 class="single__hero-title"><?= h($post['title']) ?></h1>
    </div>

    <div class="hero-divider"></div>

    <!-- Meta bar -->
    <div class="single__meta-bar">
        <div class="single__meta-item">
            <div class="single__meta-label">制作期間</div>
            <div class="single__meta-value"><?= h($post['period']) ?></div>
        </div>
        <div class="single__meta-item">
            <div class="single__meta-label">使用技術</div>
            <div class="single__meta-value">
                <div class="tag-list">
                    <?php foreach ($tags as $tag): ?>
                        <span class="tag"><?= h(trim($tag)) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="single__meta-item">
            <div class="single__meta-label">種別</div>
            <div class="single__meta-value"><?= nl2br(h($post['type'])) ?></div>
        </div>
        <div class="single__meta-item">
            <div class="single__git-value">
                <?php if (!empty($post['github_url'])): ?>
                    <a href="<?= h($post['github_url']) ?>" target="_blank" rel="noopener" class="single__github-link">
                        <img src="<?= SITE_URL ?>/img/github_logo_icon.webp" alt="GitHub">
                        <p>Github</p>
                    </a>
                <?php else: ?>
                    —
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="single__content">
        <div class="content-grid">

            <!-- 目次サイドバー -->
            <?php if (!empty($sections)): ?>
            <aside class="sidebar">
                <p class="block-label">目次</p>
                <ul class="sidebar-nav" id="toc-nav">
                    <?php foreach ($sections as $i => $section): ?>
                        <li>
                            <a href="#section-<?= $i ?>">
                                <?= h($section['title']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </aside>
            <?php else: ?>
            <aside class="sidebar"></aside>
            <?php endif; ?>

            <article>

                <?php if ($post['thumbnail']): ?>
                    <div class="mock-img">
                        <img src="<?= UPLOAD_URL . h($post['thumbnail']) ?>" alt="<?= h($post['title']) ?>">
                    </div>
                <?php endif; ?>

                <?php if (!empty($post['external_url'])): ?>
                    <div class="single__cta">
                        <a href="<?= h($post['external_url']) ?>" target="_blank" rel="noopener" class="btn-primary">
                            作品へ
                            <svg viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        </a>
                    </div>
                <?php endif; ?>

                <?php foreach ($sections as $i => $section): ?>
                    <div class="article-block" id="section-<?= $i ?>">
                        <h2 class="block-title"><?= h($section['title']) ?></h2>
                        <div class="block-body">
                            <?= $section['body'] ?>
                        </div>
                    </div>
                <?php endforeach; ?>

            </article>
        </div>
    </div>

</main>

<?php require 'footer.php'; ?>
