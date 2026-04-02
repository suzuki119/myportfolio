<?php
$works = json_decode(file_get_contents(__DIR__ . '/works.json'), true) ?? [];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Works プレビュー</title>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/admin.css">
</head>
<body class="preview-page">

<div class="top-bar">
  <span>Works プレビュー（<?= count($works) ?>件）</span>
  <a href="admin.php">← 管理画面に戻る</a>
</div>

<h1 class="preview-heading">
  <small>02 — Works</small>
  制作実績
</h1>

<div class="works-grid">
  <?php if (empty($works)): ?>
    <div class="empty">作品がまだありません</div>
  <?php else: ?>
    <?php foreach ($works as $w): ?>
      <a class="work-card"
         href="<?= htmlspecialchars($w['url'] ?? '#') ?>"
         <?= isset($w['url']) && str_starts_with($w['url'], 'http') ? 'target="_blank" rel="noopener"' : '' ?>>
        <div class="work-card__img">
          <div class="work-card__img-bg <?= htmlspecialchars($w['theme'] ?? '') ?>"></div>
          <span class="work-card__img-label"><?= htmlspecialchars($w['label'] ?? $w['title']) ?></span>
        </div>
        <div class="work-card__body">
          <div class="work-card__tags">
            <?php foreach ($w['tags'] as $tag): ?>
              <span class="work-card__tag"><?= htmlspecialchars($tag) ?></span>
            <?php endforeach; ?>
          </div>
          <div class="work-card__title"><?= htmlspecialchars($w['title']) ?></div>
          <div class="work-card__period"><?= htmlspecialchars($w['period']) ?></div>
        </div>
      </a>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

</body>
</html>
