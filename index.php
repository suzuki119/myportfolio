<?php
require_once 'cms/config.php';
$pdo = db();

// 公開済み記事を新しい順に取得
$stmt = $pdo->prepare(
    'SELECT * FROM posts WHERE status = :status ORDER BY created_at DESC'
);
$stmt->execute([':status' => 'published']);
$posts = $stmt->fetchAll();

// スキル一覧を取得
$sk_stmt = $pdo->prepare('SELECT * FROM skill ORDER BY id ASC');
$sk_stmt->execute();
$skills = $sk_stmt->fetchAll();

$page_title       = 'Suzuki Yutaro — Portfolio';
$page_description = '鈴木優太郎のポートフォリオ。フロントエンドエンジニア志望。WordPress・JavaScript・SCSSによるWeb制作実績を掲載しています。';
$og_url           = 'https://susuki-island.heavy.jp/myportfolio/';
$og_image         = 'https://susuki-island.heavy.jp/myportfolio/ogp.png';
$body_id          = 'index';

require 'header.php';
?>

  <div id="canvas-container">
    <canvas id="backcanvas"></canvas>
  </div>

  <main>

    <!-- ① HERO ────────────────────────── -->
    <section id="hero">
      <h1 class="hero__title">Suzuki<br><em>Portfolio</em></h1>
    </section>

    <!-- ② ABOUT ───────────────────────── -->
    <section id="about" class="sec">
      <div class="sec__head">
        <h2 class="sec__title">About</h2>
      </div>

        <div class="about__grid">

          <div class="about__card">
            <h3 class="about__name">鈴木 優太郎</h3>
            <div class="about__name-en">Suzuki Yutaro</div>

            <div class="about__icons">
              <a href="https://github.com/suzuki119/" target="_blank" rel="noopener" class="about__git-link"
                title="GitHub">
                <img src="./img/github_logo_icon.webp" alt="GitHub" class="about__icon-link__img">
              </a>
              <a href="https://susuki-island.heavy.jp/blog/" target="_blank" rel="noopener" class="about__blog-link"
                title="ブログ">
                <img src="./img/icon-1.png" alt="ブログ" class="about__icon-link__img">
              </a>
            </div>

            <div class="about__info">
              トライデントコンピュータ専門学校<br>
              Webデザイン学科 1年（19歳）<br><br>
              出身：<span class="hl">愛知県（日間賀島）</span><br>
              志望：<span class="hl">フロントエンドエンジニア</span>
            </div>
          </div>

          <div class="about__photo">
            <img src="./img/about.jpg" alt="鈴木 優太郎">
          </div>

        </div>

          <p class="about__body">
            JavaScript・CSSアニメーション実装に興味を持ち、日々制作に取り組んでいます。UIの見やすさと実装の再現性を両立できるエンジニアを目指し、思いついたものはすぐ手を動かして形にするようにしています。
          </p>

          <h4 class="about__mypr">自己PR</h4>
          <p class="about__body">
            私の強みは、好奇心と継続力です。

            自己紹介で挙げたアニメーション実装や、blenderのように、私は様々なものに興味を示し、そして一度興味を持った物事については、途中で辞めることなく、区切りがつくまでは継続することができるからです。

            この特性を活かして、進級展のWebサイト制作では、こだわり抜いたアニメーションを実装し、完成度の高い作品に仕上げることができました。

            <br>今後はReact/Three.jsなどにも挑戦したりチーム開発経験を増やせるイベントに参加する予定で、それをまた自分の成長に繋げられればと感じています。
          </p>
    </section>

    <!-- ③ WORKS ────────────────────────── -->
    <section id="works" class="sec">

      <div class="sec__head">
        <h2 class="sec__title">Works</h2>
      </div>

      <div class="works__grid">

        <?php foreach ($posts as $post): // [組み込み] 配列をループして1件ずつ処理する ?>

        <a class="works__card" href="single.php?id=<?= h($post['id']) ?>">

          <div class="works__card-img">
            <?php if ($post['thumbnail']): // サムネイルがあれば画像を表示 ?>
              <img src="<?= UPLOAD_URL . h($post['thumbnail']) ?>"
                   alt="<?= h($post['title']) ?>"
                   style="width:100%;height:100%;object-fit:cover;">
            <?php else: // なければ背景色のみ ?>
              <div class="works__card-img-bg"></div>
            <?php endif; ?>
          </div>

          <div class="works__card-body">
            <?php if (!empty($post['tags'])): ?>

              <div class="works__card-tags">
                <?php foreach (explode(',', $post['tags']) as $tag): // [組み込み] explode()=カンマ区切り文字列を配列に変換。JSのsplit()に相当 ?>
                  <span class="tag"><?= h(trim($tag)) ?></span>
                <?php endforeach; ?>
              </div>

            <?php endif; ?>
            <div class="works__card-title"><?= h($post['title']) ?></div>
            <div class="works__card-period"><?= h($post['period']) ?></div><?php // postsテーブルのperiodカラム（例：2025.06 – 08） ?>
          </div>

        </a>
        <?php endforeach; ?>

        <?php if (empty($posts)): // [組み込み] 配列が空かどうか調べる ?>
          <p style="color:#999;">記事はまだありません。</p>
        <?php endif; ?>

      </div>
      <a href="works.php" class="btn">すべての作品を見る</a>

    </section>

    <!-- ④ SKILL ────────────────────────── -->
    <section id="skill" class="sec">
      <div class="sec__head">
        <h2 class="sec__title">Skills</h2>
      </div>



<?php if (!empty($skills)): ?>

  <?php
  // 表示順を固定（DBの値と一致させる）
  $categoryOrder = ['プログラミング', 'デザイン', 'その他'];

  // カテゴリごとにグループ化
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

    <!-- ⑤ TIMELINE ──────────────────────── -->
    <section id="timeline" class="sec">
      <div class="sec__head">
        <h2 class="sec__title">Timeline</h2>
      </div>
      <div class="tl__wrap">

        <div class="tl__entry">
          <div class="tl__center">
            <div class="tl__dot"></div>
          </div>
          <div class="tl__side">
            <div class="tl__card">
              <div class="tl__date">2006.11-2022</div>
              <div class="tl__role">日間賀島に産まれる。</div>
              <div class="tl__desc">小中学校は日間賀島で過ごす。中学校卒業後</div>
            </div>
          </div>
        </div>

        <div class="tl__entry">
          <div class="tl__center">
            <div class="tl__dot"></div>
          </div>
          <div class="tl__side">
            <div class="tl__card">
              <div class="tl__date">2025.03</div>
              <div class="tl__role">名古屋市立工芸高等学校 卒業</div>
              <div class="tl__desc">Blenderでの3DCG制作・Officeスキルを習得。高校生活を締めくくる。</div>
            </div>
          </div>
        </div>

        <div class="tl__entry">
          <div class="tl__center">
            <div class="tl__dot"></div>
          </div>
          <div class="tl__side">
            <div class="tl__card">
              <div class="tl__date">2025.04</div>
              <div class="tl__role">トライデントコンピュータ専門学校 入学</div>
              <div class="tl__desc">Webデザイン学科へ入学。HTML / CSS / JavaScript を本格的に学び始める。</div>
            </div>
          </div>
        </div>

        <div class="tl__entry">
          <div class="tl__center">
            <div class="tl__dot"></div>
          </div>
          <div class="tl__side">
            <div class="tl__card">
              <div class="tl__date">2025.06 – 08</div>
              <div class="tl__role">MYBLOG 制作</div>
              <div class="tl__desc">WordPressとローカルサーバーを初めて使用し、宇宙テーマのオリジナルブログを制作・公開。</div>
            </div>
          </div>
        </div>

        <div class="tl__entry">
          <div class="tl__center">
            <div class="tl__dot"></div>
          </div>
          <div class="tl__side">
            <div class="tl__card">
              <div class="tl__date">2025.09 – 2026.01</div>
              <div class="tl__role">島トゥク Webサイト制作</div>
              <div class="tl__desc">日間賀島のクライアントから依頼。観光フォトサービスのWebサイトを4ヶ月かけて制作・公開。</div>
            </div>
          </div>
        </div>

        <div class="tl__entry">
          <div class="tl__center">
            <div class="tl__dot"></div>
          </div>
          <div class="tl__side">
            <div class="tl__card">
              <div class="tl__date">2025.12 – 2026.03</div>
              <div class="tl__role">新校舎紹介動画 制作</div>
              <div class="tl__desc">校長・設計者へのインタビューを含む新校舎紹介動画をPremiere Proで編集・完成。</div>
            </div>
          </div>
        </div>

      </div>
    </section>



<?php require 'footer.php'; ?>
