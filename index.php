<?php
require_once 'cms/config.php';
$pdo = db();

// 公開済み記事を新しい順に取得
$stmt = $pdo->prepare(
    'SELECT * FROM posts WHERE status = :status ORDER BY created_at DESC'
);
$stmt->execute([':status' => 'published']);
$posts = $stmt->fetchAll(); // [PDO組み込み] 全行を配列で取得
?>

<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <title>Suzuki Yutaro — Portfolio</title>
  <link
    href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300&family=Space+Mono:wght@400;700&display=swap"
    rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/the-new-css-reset/css/reset.min.css">
  <link rel="stylesheet" href="css/style.css">

</head>

<body id="index">

  <header class="header">
    <div class="header__logo">SuzukiPortfolio</div>
    <button class="header__toggle" id="nav-toggle" aria-label="メニューを開く">
      <span></span><span></span><span></span>
    </button>
      <nav id="main-nav" class="header__nav">
    <a href="#about">About</a>
    <a href="#works">Works</a>
    <a href="#skill">Skill</a>
    <a href="#timeline">Timeline</a>
    <a href="#contact">Contact</a>
  </nav>
  </header>



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

    </section>

    <!-- ④ SKILL ────────────────────────── -->
    <section id="skill" class="sec">
      <div class="sec__head">
        <h2 class="sec__title">Skills</h2>
      </div>

      <!-- コーディング -->
      <div class="skill__block">
        <div class="skill__block-title">コーディング</div>
        <div class="skill__layout">
          <div class="skill__table-box">
            <table class="skill__table">
              <thead>
                <tr>
                  <th>名称</th>
                  <th>詳細</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>
                    <div class="skill__name">HTML</div>
                    <div class="skill__years">2年間</div>
                  </td>
                  <td>
                    <div class="skill__detail">今年1月に<br>Webクリエイター能力認定試験HTML5 エキスパートを合格しました</div>
                  </td>
                </tr>
                <tr>
                  <td>
                    <div class="skill__name">CSS / SCSS</div>
                    <div class="skill__years">2年間</div>
                  </td>
                  <td>
                    <div class="skill__detail">現在、CSSは、SCSSで記述しています</div>
                  </td>
                </tr>
                <tr>
                  <td>
                    <div class="skill__name">JavaScript</div>
                    <div class="skill__years">1年間</div>
                  </td>
                  <td>
                    <div class="skill__detail">Three.js・アニメーション実装。<br>JSON/API連携は勉強中</div>
                  </td>
                </tr>
                <tr>
                  <td>
                    <div class="skill__name">C言語</div>
                    <div class="skill__years">2年間</div>
                  </td>
                  <td>
                    <div class="skill__detail">個人で宝探しゲームを制作した経験あり</div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- デザイン -->
      <div class="skill__block">
        <div class="skill__block-title">デザイン</div>
        <div class="skill__layout">
          <div class="skill__table-box">
            <table class="skill__table">
              <thead>
                <tr>
                  <th>名称</th>
                  <th>詳細</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>
                    <div class="skill__name">Illustrator</div>
                    <div class="skill__years">2年間</div>
                  </td>
                  <td>
                    <div class="skill__detail">アイコン・イラストの制作に使用</div>
                  </td>
                </tr>
                <tr>
                  <td>
                    <div class="skill__name">Photoshop</div>
                    <div class="skill__years">1年間</div>
                  </td>
                  <td>
                    <div class="skill__detail">写真の調整・補正に使用</div>
                  </td>
                </tr>
                <tr>
                  <td>
                    <div class="skill__name">Figma</div>
                    <div class="skill__years">半年間</div>
                  </td>
                  <td>
                    <div class="skill__detail">ワイヤーフレーム・プロトタイプ制作</div>
                  </td>
                </tr>
                <tr>
                  <td>
                    <div class="skill__name">Canva</div>
                    <div class="skill__years">半年間</div>
                  </td>
                  <td>
                    <div class="skill__detail">プレゼン・名刺制作に使用</div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- その他 -->
      <div class="skill__block">
        <div class="skill__block-title">その他</div>
        <div class="skill__layout">
          <div class="skill__table-box">
            <table class="skill__table">
              <thead>
                <tr>
                  <th>名称</th>
                  <th>詳細</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>
                    <div class="skill__name">Blender</div>
                    <div class="skill__years">1年間</div>
                  </td>
                  <td>
                    <div class="skill__detail">高校時代に3DCG制作を経験</div>
                  </td>
                </tr>
                <tr>
                  <td>
                    <div class="skill__name">Premiere Pro</div>
                    <div class="skill__years">2年間</div>
                  </td>
                  <td>
                    <div class="skill__detail">新校舎紹介動画の編集・完成</div>
                  </td>
                </tr>
                <tr>
                  <td>
                    <div class="skill__name">WordPress</div>
                    <div class="skill__years">半年</div>
                  </td>
                  <td>
                    <div class="skill__detail">テーマ開発・カスタマイズで複数サイト制作</div>
                  </td>
                </tr>
                <tr>
                  <td>
                    <div class="skill__name">Git / GitHub</div>
                    <div class="skill__years">半年</div>
                  </td>
                  <td>
                    <div class="skill__detail">バージョン管理・チーム開発の基礎</div>
                  </td>
                </tr>
                <tr>
                  <td>
                    <div class="skill__name">Office</div>
                    <div class="skill__years">2年間</div>
                  </td>
                  <td>
                    <div class="skill__detail">Word・Excel・PowerPointを日常的に使用</div>
                  </td>
                </tr>
                <tr>
                  <td>
                    <div class="skill__name">After Effects</div>
                    <div class="skill__years">1年間</div>
                  </td>
                  <td>
                    <div class="skill__detail">モーショングラフィックスの基礎</div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </section>

    <!-- ⑤ TIMELINE ──────────────────────── -->
    <section id="timeline" class="sec">
      <div class="sec__head">
        <h2 class="sec__title">Timeline</h2>
      </div>
      <div class="tl__wrap">

        <!-- 左 -->
        <div class="tl__entry">
          <div class="tl__side tl__side--right">
            <div class="tl__card">
              <div class="tl__date">2025.03</div>
              <div class="tl__role">名古屋市立工芸高等学校 卒業</div>
              <div class="tl__desc">Blenderでの3DCG制作・Officeスキルを習得。高校生活を締めくくる。</div>
            </div>
          </div>
          <div class="tl__center">
            <div class="tl__dot"></div>
          </div>
          <div class="tl__side"></div>
        </div>

        <!-- 右 -->
        <div class="tl__entry">
          <div class="tl__side"></div>
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

        <!-- 左 -->
        <div class="tl__entry">
          <div class="tl__side tl__side--right">
            <div class="tl__card">
              <div class="tl__date">2025.06 – 08</div>
              <div class="tl__role">MYBLOG 制作</div>
              <div class="tl__desc">WordPressとローカルサーバーを初めて使用し、宇宙テーマのオリジナルブログを制作・公開。</div>
            </div>
          </div>
          <div class="tl__center">
            <div class="tl__dot"></div>
          </div>
          <div class="tl__side"></div>
        </div>

        <!-- 右 -->
        <div class="tl__entry">
          <div class="tl__side"></div>
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

        <!-- 左 -->
        <div class="tl__entry">
          <div class="tl__side tl__side--right">
            <div class="tl__card">
              <div class="tl__date">2025.12 – 2026.03</div>
              <div class="tl__role">新校舎紹介動画 制作</div>
              <div class="tl__desc">校長・設計者へのインタビューを含む新校舎紹介動画をPremiere Proで編集・完成。</div>
            </div>
          </div>
          <div class="tl__center">
            <div class="tl__dot"></div>
          </div>
          <div class="tl__side"></div>
        </div>

      </div>
    </section>

    <!-- ⑥ CONTACT ──────────────────────── -->
    <section id="contact" class="sec">
      <div class="sec__head">
        <h2 class="sec__title">Contact</h2>
      </div>
      <a href="mailto:suzukiyutaro119@gmail.com" class="contact__email">suzukiyutaro119@gmail.com</a>
      <div class="contact__links">
        <a href="https://github.com/suzuki119/" class="contact__link">GitHub</a>
      </div>
    </section>

  </main>

  <footer>&copy; 2026 Suzuki Yutaro — All Rights Reserved</footer>

  <!-- Three.js -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

  <script src="script.js"></script>

</body>

</html>
