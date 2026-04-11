<?php
require_once 'cms/config.php';
$pdo = db();

// 公開済み記事をカテゴリ情報と合わせて取得
$stmt = $pdo->prepare(
    'SELECT p.*, c.name AS category_name
     FROM posts p
     LEFT JOIN post_categories pc ON p.id = pc.post_id
     LEFT JOIN categories c ON pc.category_id = c.id
     WHERE p.status = :status
     ORDER BY p.created_at DESC'
);
$stmt->execute([':status' => 'published']);
$posts = $stmt->fetchAll();

// フィルター用：公開記事に紐付くカテゴリ一覧を取得
$c_stmt = $pdo->prepare(
    'SELECT DISTINCT c.id, c.name
     FROM categories c
     INNER JOIN post_categories pc ON c.id = pc.category_id
     INNER JOIN posts p ON pc.post_id = p.id
     WHERE p.status = :status
     ORDER BY c.id ASC'
);
$c_stmt->execute([':status' => 'published']);
$categories = $c_stmt->fetchAll();

$total = count($posts); // 記事の総数

$page_title       = 'Works — Suzuki Portfolio';
$page_description = '鈴木優太郎のWeb制作実績一覧。WordPress・JavaScript・SCSSによるWebサイト制作を掲載しています。';
$og_url           = SITE_URL . '/works.php';
$body_id          = 'works-page';
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

<main>

    <!-- ヒーロー -->
    <div class="work-hero work-hero--list">
        <h1 class="work-hero-title">Works</h1>
    </div>

    <div class="hero-divider"></div>

    <!-- カテゴリフィルター + 表示件数 -->
    <div class="works-filter-bar">

        <?php if (!empty($categories)): ?>
        <div class="works-filter">
            <button class="works-filter__btn is-active" data-filter="all">All</button>
            <?php foreach ($categories as $cat): ?>
                <button class="works-filter__btn" data-filter="<?= h($cat['name']) ?>">
                    <?= h($cat['name']) ?>
                </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <span class="works-count" id="works-count"><?= $total ?> / <?= $total ?></span>
    </div>

    <!-- 記事グリッド -->
    <div class="works-list">
        <div class="works__grid" id="works-grid">

            <?php if (empty($posts)): ?>
                <p class="works-list__empty">まだ公開されている作品はありません。</p>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                <a class="works__card"
                   href="single.php?id=<?= h($post['id']) ?>"
                   data-category="<?= h($post['category_name'] ?? '') ?>">

                    <div class="works__card-img">
                        <?php if ($post['thumbnail']): ?>
                            <img src="<?= UPLOAD_URL . h($post['thumbnail']) ?>"
                                 alt="<?= h($post['title']) ?>">
                        <?php else: ?>
                            <div class="works__card-img-bg"></div>
                        <?php endif; ?>
                    </div>

                    <div class="works__card-body">
                        <?php if (!empty($post['tags'])): ?>
                            <div class="works__card-tags">
                                <?php foreach (explode(',', $post['tags']) as $tag): ?>
                                    <span class="tag"><?= h(trim($tag)) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="works__card-title"><?= h($post['title']) ?></div>
                        <div class="works__card-period"><?= h($post['period']) ?></div>
                    </div>

                </a>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </div>

</main>

<script>
const FADE_MS = 220; // フィルターアニメーションの時間（ms）

const filterBtns = document.querySelectorAll('.works-filter__btn');
const cards       = document.querySelectorAll('.works__card[data-category]');
const countEl     = document.getElementById('works-count');
const total       = cards.length;

filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        filterBtns.forEach(b => b.classList.remove('is-active'));
        btn.classList.add('is-active');

        const filter = btn.dataset.filter;
        let visible  = 0;

        cards.forEach(card => {
            const match = filter === 'all' || card.dataset.category === filter;

            if (match) {
                // 非表示 → 表示：display を戻してからフェードイン
                card.style.display = '';
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => card.classList.remove('is-hiding'));
                });
                visible++;
            } else {
                // 表示 → 非表示：フェードアウト後に display:none
                card.classList.add('is-hiding');
                setTimeout(() => {
                    if (card.classList.contains('is-hiding')) {
                        card.style.display = 'none';
                    }
                }, FADE_MS);
            }
        });

        // 件数カウンターを更新
        if (countEl) countEl.textContent = visible + ' / ' + total;
    });
});
</script>

<?php require 'footer.php'; ?>
