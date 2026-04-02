<?php
$works = json_decode(file_get_contents(__DIR__ . '/works.json'), true) ?? [];

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$work = null;
foreach ($works as $w) {
  if ($w['id'] === $id) { $work = $w; break; }
}

if (!$work) {
  http_response_code(404);
  echo '<!DOCTYPE html><html lang="ja"><head><meta charset="UTF-8"><title>404</title><link rel="stylesheet" href="../css/style.css"></head><body class="not-found"><p class="not-found__msg">WORK NOT FOUND</p><p class="not-found__link"><a href="admin.php">← 管理画面に戻る</a></p></body></html>';
  exit;
}

$d = $work['detail'] ?? [];
$sections = $d['sections'] ?? [];

// 配列内の位置を取得
$workIndex = array_search($id, array_column($works, 'id'));

// Eyebrow を自動生成（例: 01 — Works）
$eyebrow = sprintf('%02d — Works', $workIndex + 1);

// Next work：配列の次のインデックスの作品（最後なら最初に戻る）
$nextIndex = ($workIndex + 1) % count($works);
$nextWork  = $works[$nextIndex] ?? null;

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function bodyToHtml($text) {
  $paras = preg_split('/\n\n+/', trim((string)$text));
  return implode('', array_map(fn($p) => '<p>' . nl2br(h($p)) . '</p>', $paras));
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h($work['title']) ?> — Suzuki Portfolio</title>
  <?php
    $ogImg = '';
    if (!empty($work['image'])) {
      $ogImg = str_starts_with($work['image'], 'http')
        ? $work['image']
        : 'https://susuki-island.heavy.jp/myportfolio/' . $work['image'];
    }
    $ogDesc = '';
    if (!empty($d['sections'][0]['body'])) {
      $ogDesc = mb_strimwidth(strip_tags($d['sections'][0]['body']), 0, 120, '…');
    }
  ?>
  <meta name="description" content="<?= h($ogDesc ?: $work['title'] . ' — Suzuki Yutaro Portfolio') ?>">
  <meta property="og:type"        content="article">
  <meta property="og:title"       content="<?= h($work['title']) ?> — Suzuki Portfolio">
  <meta property="og:description" content="<?= h($ogDesc ?: $work['title']) ?>">
  <meta property="og:url"         content="https://susuki-island.heavy.jp/myportfolio/cms/work.php?id=<?= (int)$work['id'] ?>">
  <?php if ($ogImg): ?>
  <meta property="og:image"       content="<?= h($ogImg) ?>">
  <?php endif; ?>
  <meta name="twitter:card"       content="summary_large_image">
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div id="canvas-container">
  <canvas id="bg-canvas"></canvas>
</div>

<header class="header--work">
  <a href="../index.php" class="back-link">
    <svg viewBox="0 0 24 24"><path d="M19 12H5M5 12l7 7M5 12l7-7"/></svg>
    Back to Portfolio
  </a>
  <div class="header-logo">Suzuki Portfolio</div>
</header>

<main>

  <!-- Hero -->
  <div class="work-hero">
    <div class="work-hero-eyebrow"><?= h($eyebrow) ?></div>
    <h1 class="work-hero-title"><?= h($d['hero_title'] ?? $work['title']) ?></h1>
  </div>

  <div class="hero-divider"></div>

  <!-- Meta bar -->
  <div class="work-meta-bar">
    <div class="work-meta-item">
      <div class="work-meta-label">制作期間</div>
      <div class="work-meta-value"><?= nl2br(h($d['meta_period'] ?? $work['period'])) ?></div>
    </div>
    <div class="work-meta-item">
      <div class="work-meta-label">使用技術</div>
      <div class="work-meta-value">
        <div class="tag-list">
          <?php foreach ($work['tags'] as $tag): ?>
            <span class="tag"><?= h($tag) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <div class="work-meta-item">
      <div class="work-meta-label">種別</div>
      <div class="work-meta-value"><?= nl2br(h($d['meta_type'] ?? '')) ?></div>
    </div>
  </div>

  <!-- Content -->
  <div class="work-content">
    <div class="content-grid">

      <!-- Sidebar -->
      <aside class="sidebar">
        <ul class="sidebar-nav">
          <?php foreach ($sections as $sec): ?>
            <li><a href="#<?= h($sec['id']) ?>"><?= h($sec['label']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </aside>

      <!-- Article -->
      <article>

        <div class="mock-img <?= h($work['theme'] ?? '') ?>">
          <?php if (!empty($work['image'])):
            $imgSrc = str_starts_with($work['image'], 'http')
              ? $work['image']
              : '../' . $work['image'];
          ?>
            <img src="<?= h($imgSrc) ?>" alt="<?= h($work['title']) ?>">
          <?php else: ?>
            <span class="mock-img-label"><?= h($work['label'] ?? $work['title']) ?></span>
          <?php endif; ?>
        </div>

        <?php foreach ($sections as $sec): ?>
          <div class="article-block" id="<?= h($sec['id']) ?>">
            <div class="block-label"><?= h($sec['label']) ?></div>
            <h2 class="block-title"><?= h($sec['title']) ?></h2>
            <?php if (!empty($sec['image'])):
              $secImgSrc = str_starts_with($sec['image'], 'http') ? $sec['image'] : '../' . $sec['image'];
            ?>
            <div class="block-img">
              <img src="<?= h($secImgSrc) ?>" alt="<?= h($sec['title']) ?>">
            </div>
            <?php endif; ?>
            <div class="block-body">
              <?= bodyToHtml($sec['body'] ?? '') ?>
            </div>
            <?php if (!empty($sec['highlight'])): ?>
              <div class="highlight-box"><?= nl2br(h($sec['highlight'])) ?></div>
            <?php endif; ?>
            <?php if (!empty($sec['cta_text']) && !empty($sec['cta_url'])): ?>
              <div class="work-cta">
                <a href="<?= h($sec['cta_url']) ?>" target="_blank" rel="noopener" class="btn-primary">
                  <?= h($sec['cta_text']) ?>
                  <svg viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                </a>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>

      </article>
    </div>
  </div>

</main>

<!-- Next work -->
<?php if ($nextWork): ?>
  <a href="work.php?id=<?= (int)$nextWork['id'] ?>" class="next-work">
    <div>
      <div class="next-label">Next Work</div>
      <div class="next-title"><?= h($nextWork['title']) ?></div>
    </div>
    <div class="next-arrow">→</div>
  </a>
<?php endif; ?>

<footer>&copy; 2026 Suzuki Yutaro — All Rights Reserved</footer>


<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script>
const scene    = new THREE.Scene();
const camera   = new THREE.PerspectiveCamera(60, innerWidth / innerHeight, 0.1, 1000);
const renderer = new THREE.WebGLRenderer({
  canvas: document.getElementById('bg-canvas'), antialias: true, alpha: true
});
renderer.setSize(innerWidth, innerHeight);
renderer.setPixelRatio(Math.min(devicePixelRatio, 2));
camera.position.z = 5;

const count = 300;
const positions = new Float32Array(count * 3);
for (let i = 0; i < count; i++) {
  positions[i * 3]     = (Math.random() - 0.5) * 20;
  positions[i * 3 + 1] = (Math.random() - 0.5) * 20;
  positions[i * 3 + 2] = (Math.random() - 0.5) * 10;
}
const pGeo = new THREE.BufferGeometry();
pGeo.setAttribute('position', new THREE.BufferAttribute(positions, 3));
const pMat = new THREE.PointsMaterial({ color: 0xb8a88a, size: 0.04, transparent: true, opacity: 0.6 });
scene.add(new THREE.Points(pGeo, pMat));

let t = 0;
(function animate() {
  requestAnimationFrame(animate);
  t += 0.001;
  scene.rotation.y = t * 0.1;
  scene.rotation.x = Math.sin(t) * 0.05;
  renderer.render(scene, camera);
})();

window.addEventListener('resize', () => {
  camera.aspect = innerWidth / innerHeight;
  camera.updateProjectionMatrix();
  renderer.setSize(innerWidth, innerHeight);
}, { passive: true });
</script>

</body>
</html>
