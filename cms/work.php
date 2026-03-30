<?php
$works = json_decode(file_get_contents(__DIR__ . '/works.json'), true) ?? [];

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$work = null;
foreach ($works as $w) {
  if ($w['id'] === $id) { $work = $w; break; }
}

if (!$work) {
  http_response_code(404);
  echo '<!DOCTYPE html><html lang="ja"><head><meta charset="UTF-8"><title>404</title></head><body style="background:#080808;color:#ede8df;font-family:monospace;padding:60px;text-align:center"><p style="font-size:11px;letter-spacing:.3em;color:#7a7a72">WORK NOT FOUND</p><p style="margin-top:24px"><a href="admin.php" style="color:#b8a88a">← 管理画面に戻る</a></p></body></html>';
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
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

    :root {
      --white:  #ede8df;
      --gray:   #7a7a72;
      --accent: #b8a88a;
      --bg:     #080808;
    }

    html { scroll-behavior: smooth; }

    body {
      background: var(--bg);
      color: var(--white);
      font-family: 'Space Mono', monospace;
      overflow-x: hidden;
    }

    /* ── 背景パーティクル ── */
    #canvas-container {
      position: fixed; inset: 0;
      z-index: 0; opacity: 0.15;
      pointer-events: none;
    }
    #bg-canvas { display: block; width: 100%; height: 100%; }

    /* ── Header ── */
    header {
      position: fixed; top: 0; left: 0; right: 0;
      padding: 22px 52px;
      display: flex; justify-content: space-between; align-items: center;
      z-index: 200;
      background: rgba(8,8,8,0.75);
      backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
      border-bottom: 1px solid rgba(255,255,255,0.05);
    }

    .back-link {
      display: flex; align-items: center; gap: 10px;
      font-size: 9px; letter-spacing: 0.3em; text-transform: uppercase;
      color: var(--gray); text-decoration: none;
      transition: color 0.3s;
    }
    .back-link:hover { color: var(--accent); }
    .back-link svg { width: 14px; height: 14px; fill: none; stroke: currentColor; stroke-width: 1.5; }

    .header-logo {
      font-family: 'Cormorant Garamond', serif;
      font-size: 16px; letter-spacing: 0.4em;
      color: rgba(237,232,223,0.4); text-transform: uppercase;
    }

    /* ── Main ── */
    main { position: relative; z-index: 10; padding-top: 80px; }

    /* ── Hero ── */
    .work-hero {
      min-height: 70vh;
      display: flex; flex-direction: column;
      justify-content: flex-end;
      padding: 0 9vw 80px;
    }

    .work-hero-eyebrow {
      font-size: 9px; letter-spacing: 0.45em;
      text-transform: uppercase; color: var(--accent);
      margin-bottom: 20px;
    }

    .work-hero-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: clamp(64px, 11vw, 160px);
      font-weight: 300; line-height: 0.92;
      color: var(--white);
    }

    .work-hero-sub {
      margin-top: 28px; font-size: 10px;
      letter-spacing: 0.3em; text-transform: uppercase; color: var(--gray);
    }

    .hero-divider {
      margin: 0 9vw;
      height: 1px;
      background: rgba(255,255,255,0.07);
    }

    /* ── Meta bar ── */
    .work-meta-bar {
      padding: 40px 9vw;
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      border-bottom: 1px solid rgba(255,255,255,0.06);
    }

    .work-meta-item { padding: 0 32px 0 0; }
    .work-meta-item:not(:first-child) {
      border-left: 1px solid rgba(255,255,255,0.06);
      padding-left: 32px;
    }

    .work-meta-label {
      font-size: 8px; letter-spacing: 0.35em;
      text-transform: uppercase; color: var(--accent);
      margin-bottom: 10px;
    }

    .work-meta-value { font-size: 11px; line-height: 1.9; color: rgba(237,232,223,0.7); }

    .tag-list { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 4px; }
    .tag {
      font-size: 8px; letter-spacing: 0.18em; text-transform: uppercase;
      color: var(--accent); border: 1px solid rgba(184,168,138,0.28);
      padding: 3px 8px;
    }

    /* ── Content ── */
    .work-content { padding: 80px 9vw 120px; }

    .content-grid {
      display: grid;
      grid-template-columns: 1fr 2fr;
      gap: 80px; align-items: start;
    }

    /* ── Sidebar ── */
    .sidebar { position: sticky; top: 120px; }

    .sidebar-nav { list-style: none; }
    .sidebar-nav li { margin-bottom: 4px; }
    .sidebar-nav a {
      font-size: 9px; letter-spacing: 0.25em; text-transform: uppercase;
      color: var(--gray); text-decoration: none;
      display: flex; align-items: center; gap: 10px;
      padding: 8px 0;
      border-bottom: 1px solid rgba(255,255,255,0.04);
      transition: color 0.3s;
    }
    .sidebar-nav a::before {
      content: '';
      width: 20px; height: 1px;
      background: var(--accent);
      opacity: 0;
      transition: opacity 0.3s;
    }
    .sidebar-nav a:hover { color: var(--accent); }
    .sidebar-nav a:hover::before { opacity: 1; }

    /* ── Article blocks ── */
    .article-block { margin-bottom: 72px; }
    .article-block:last-child { margin-bottom: 0; }

    .block-label {
      font-size: 9px; letter-spacing: 0.4em;
      text-transform: uppercase; color: var(--accent);
      margin-bottom: 20px;
      display: flex; align-items: center; gap: 14px;
    }
    .block-label::after {
      content: '';
      flex: 1; height: 1px;
      background: rgba(255,255,255,0.07);
    }

    .block-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: clamp(26px, 3vw, 40px);
      font-weight: 300; line-height: 1.2;
      color: var(--white); margin-bottom: 24px;
    }

    .block-body {
      font-size: 11px; line-height: 2.2;
      color: rgba(237,232,223,0.65);
    }
    .block-body p + p { margin-top: 16px; }

    /* ── Mock image area ── */
    .mock-img {
      width: 100%; aspect-ratio: 16/9;
      background: linear-gradient(135deg, #0b0b14 0%, #131328 100%);
      border: 1px solid rgba(255,255,255,0.06);
      display: flex; align-items: center; justify-content: center;
      margin-bottom: 40px; overflow: hidden; position: relative;
    }
    .mock-img img {
      position: absolute; inset: 0;
      width: 100%; height: 100%;
      object-fit: cover;
    }
    .mock-img.twuku  { background: linear-gradient(135deg, #090e0b, #0f1a10); }
    .mock-img.seal   { background: linear-gradient(135deg, #120b0b, #1e0f0f); }
    .mock-img.video  { background: linear-gradient(135deg, #0c0c0b, #1a1806); }

    .mock-img-label {
      font-family: 'Cormorant Garamond', serif;
      font-size: 80px; font-weight: 300;
      color: rgba(255,255,255,0.04);
      letter-spacing: -0.02em; user-select: none;
    }

    /* ── Highlight box ── */
    .highlight-box {
      background: rgba(184,168,138,0.06);
      border-left: 2px solid var(--accent);
      padding: 20px 24px; margin: 28px 0;
      font-size: 11px; line-height: 2;
      color: rgba(237,232,223,0.7);
    }

    /* ── CTA ── */
    .work-cta {
      margin-top: 40px;
      display: flex; gap: 16px; flex-wrap: wrap; align-items: center;
    }

    .btn-primary {
      display: inline-flex; align-items: center; gap: 10px;
      padding: 14px 28px;
      background: transparent;
      border: 1px solid rgba(184,168,138,0.5);
      color: var(--accent);
      font-family: 'Space Mono', monospace;
      font-size: 9px; letter-spacing: 0.3em; text-transform: uppercase;
      text-decoration: none; cursor: pointer;
      transition: background 0.3s, border-color 0.3s, color 0.3s;
    }
    .btn-primary:hover {
      background: rgba(184,168,138,0.1);
      border-color: var(--accent); color: var(--white);
    }
    .btn-primary svg { width: 12px; height: 12px; fill: none; stroke: currentColor; stroke-width: 1.5; }

    /* ── Next work ── */
    .next-work {
      border-top: 1px solid rgba(255,255,255,0.06);
      padding: 64px 9vw;
      display: flex; justify-content: space-between; align-items: center;
      text-decoration: none; color: inherit;
      position: relative; z-index: 10;
      transition: background 0.3s;
    }
    .next-work:hover { background: rgba(255,255,255,0.02); }

    .next-label { font-size: 8px; letter-spacing: 0.35em; text-transform: uppercase; color: var(--gray); margin-bottom: 8px; }
    .next-title { font-family: 'Cormorant Garamond', serif; font-size: clamp(24px, 3vw, 38px); font-weight: 300; }
    .next-arrow { font-size: 28px; color: var(--accent); }

    /* ── Footer ── */
    footer {
      text-align: center; padding: 24px;
      font-size: 8px; letter-spacing: 0.25em;
      color: rgba(122,122,114,0.35);
      border-top: 1px solid rgba(255,255,255,0.04);
      position: relative; z-index: 10;
    }

    /* ── Admin bar ── */
    .admin-bar {
      position: fixed; bottom: 0; left: 0; right: 0;
      background: rgba(15,15,15,0.95);
      border-top: 1px solid rgba(184,168,138,0.2);
      padding: 10px 32px;
      display: flex; align-items: center; gap: 16px;
      z-index: 300;
    }
    .admin-bar span { font-size: 8px; letter-spacing: 0.3em; text-transform: uppercase; color: var(--accent); }
    .admin-bar a {
      font-size: 8px; letter-spacing: 0.2em; text-transform: uppercase;
      color: var(--gray); text-decoration: none;
      border: 1px solid rgba(255,255,255,0.1);
      padding: 5px 12px;
      transition: color 0.2s, border-color 0.2s;
    }
    .admin-bar a:hover { color: var(--white); border-color: var(--gray); }

    /* ── Responsive ── */
    @media (max-width: 768px) {
      header { padding: 16px 20px; }
      .work-hero { padding: 0 6vw 60px; }
      .hero-divider { margin: 0 6vw; }
      .work-meta-bar {
        padding: 32px 6vw;
        grid-template-columns: 1fr;
      }
      .work-meta-item {
        padding: 20px 0;
        border-bottom: 1px solid rgba(255,255,255,0.06);
      }
      .work-meta-item:not(:first-child) { border-left: none; padding-left: 0; }
      .work-meta-item:last-child { border-bottom: none; }
      .work-content { padding: 48px 6vw 80px; }
      .content-grid { grid-template-columns: 1fr; gap: 40px; }
      .sidebar { position: static; }
      .sidebar-nav { display: flex; flex-wrap: wrap; border-top: 1px solid rgba(255,255,255,0.06); }
      .sidebar-nav li { flex: 1 0 50%; }
      .next-work { padding: 40px 6vw; flex-direction: column; gap: 16px; align-items: flex-start; }
    }

    @media (max-width: 480px) {
      .work-hero-sub { font-size: 9px; letter-spacing: 0.2em; }
      .sidebar-nav li { flex: 1 0 100%; }
    }
  </style>
</head>
<body>

<div id="canvas-container">
  <canvas id="bg-canvas"></canvas>
</div>

<header>
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

<!-- Admin bar -->
<div class="admin-bar">
  <span>CMS Preview</span>
  <a href="admin.php">管理画面</a>
  <a href="preview.php" target="_blank">一覧プレビュー</a>
</div>

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
