<?php
require_once 'cms/config.php';
$pdo = db();

$sk_stmt = $pdo->prepare('SELECT * FROM skill ORDER BY id ASC');
$sk_stmt->execute();
$skills = $sk_stmt->fetchAll();

$page_title       = 'Skills — Suzuki Yutaro';
$page_description = '鈴木優太郎のスキル一覧。プログラミング・デザイン・その他ツール。';
$og_url           = 'https://susuki-island.heavy.jp/myportfolio/skill.php';
$body_id          = 'skill-page';

require 'header.php';
?>

  <main>

    <section id="skill">
      <h2>Skills</h2>

      <?php if (!empty($skills)): ?>

        <?php
        $categoryOrder = ['プログラミング', 'デザイン', 'その他'];
        $grouped = [];
        foreach ($skills as $s) {
            $cat = $s['category'] ?? 'その他';
            $grouped[$cat][] = $s;
        }
        ?>

        <?php foreach ($categoryOrder as $cat): ?>
          <?php if (!empty($grouped[$cat])): ?>

            <h3 class="skill__category"><?= h($cat) ?></h3>

            <div class="skill__grid">
              <?php foreach ($grouped[$cat] as $skill): ?>
                <div class="skill__card">
                  <div class="skill__card-icon">
                    <?php if (!empty($skill['image_url'])): ?>
                      <img src="<?= h($skill['image_url']) ?>" alt="<?= h($skill['title']) ?>">
                    <?php endif; ?>
                  </div>
                  <div class="skill__card-meta">
                    <div class="skill__name"><?= h($skill['title']) ?></div>
                    <?php if (!empty($skill['period'])): ?>
                      <div class="skill__years"><?= h($skill['period']) ?></div>
                    <?php endif; ?>
                  </div>
                  <?php if (!empty($skill['body'])): ?>
                    <div class="skill__detail"><?= h($skill['body']) ?></div>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>

          <?php endif; ?>
        <?php endforeach; ?>

      <?php else: ?>
        <p style="color:#999; text-align:center;">スキルはまだ登録されていません。</p>
      <?php endif; ?>

    </section>

  </main>

<?php require 'footer.php'; ?>
