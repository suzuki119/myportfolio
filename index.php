<?php
$works = json_decode(file_get_contents(__DIR__ . '/cms/works.json'), true) ?? [];
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Suzuki Yutaro — Portfolio</title>
  <meta name="description" content="鈴木優太郎のポートフォリオ。フロントエンドエンジニア志望。WordPress・JavaScript・SCSSによるWeb制作実績を掲載しています。">
  <meta property="og:type"        content="website">
  <meta property="og:title"       content="Suzuki Yutaro — Portfolio">
  <meta property="og:description" content="鈴木優太郎のポートフォリオ。フロントエンドエンジニア志望。Web制作実績を掲載しています。">
  <meta property="og:url"         content="https://susuki-island.heavy.jp/myportfolio/">
  <meta property="og:image"       content="https://susuki-island.heavy.jp/myportfolio/ogp.png">
  <meta property="og:site_name"   content="Suzuki Yutaro Portfolio">
  <meta name="twitter:card"       content="summary_large_image">
  <link
    href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300&family=Space+Mono:wght@400;700&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
</head>

<body>

  <div id="progressBar"></div>

  <header>
    <div class="header__logo">Suzuki&nbsp;&nbsp;Portfolio</div>
    <button class="header__toggle" id="nav-toggle" aria-label="メニューを開く">
      <span></span><span></span><span></span>
    </button>
  </header>

  <nav id="main-nav" class="header__nav">
    <a href="#about">About</a>
    <a href="#works">Works</a>
    <a href="#skill">Skill</a>
    <a href="#timeline">Timeline</a>
    <a href="#contact">Contact</a>
  </nav>



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
          <div class="about__name">鈴木 優太郎</div>
          <div class="about__name-en">Suzuki Yutaro</div>
          <div class="about__icons">
            <!-- GitHub -->
            <a href="https://github.com/suzuki119/" target="_blank" rel="noopener" class="about__icon-link"
              title="GitHub">
              <svg class="fill" viewBox="0 0 24 24">
                <path
                  d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0 1 12 6.844a9.59 9.59 0 0 1 2.504.337c1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.02 10.02 0 0 0 22 12.017C22 6.484 17.522 2 12 2z" />
              </svg>
            </a>
          </div>
          <div class="about__meta">
            トライデントコンピュータ専門学校<br>
            Webデザイン学科 1年（19歳）<br><br>
            出身：<span class="hl">愛知県（日間賀島）</span><br>
            志望：<span class="hl">フロントエンドエンジニア</span>
          </div>
          <p class="about__body">
            JavaScript・CSSアニメーション実装に興味を持ち、日々制作に取り組んでいます。UIの見やすさと実装の再現性を両立できるエンジニアを目指し、思いついたものはすぐ手を動かして形にするようにしています。
          </p>
        </div>
        <div class="about__photo">
          <img src="./img/about.jpg" alt="鈴木 優太郎">
        </div>
      </div>
    </section>

    <!-- ③ WORKS ────────────────────────── -->
    <section id="works" class="sec">
      <div class="sec__head">
        <h2 class="sec__title">Works</h2>
      </div>
      <div class="works__grid">

        <?php foreach ($works as $w): ?>
        <a class="works__card"
           href="cms/work.php?id=<?= (int)$w['id'] ?>">
          <div class="works__card-img">
            <?php if (!empty($w['image'])): ?>
              <img src="<?= h($w['image']) ?>" alt="<?= h($w['title']) ?>">
            <?php else: ?>
              <div class="works__card-img-bg"></div>
              <span class="works__card-img-label"><?= h($w['title']) ?></span>
            <?php endif; ?>
          </div>
          <div class="works__card-body">
            <div class="works__card-tags">
              <?php foreach ($w['tags'] as $tag): ?>
              <span class="works__card-tag"><?= h($tag) ?></span>
              <?php endforeach; ?>
            </div>
            <div class="works__card-title"><?= h($w['title']) ?></div>
            <div class="works__card-period"><?= h($w['period']) ?></div>
          </div>
        </a>
        <?php endforeach; ?>

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
          <div class="skill__radar-box">
            <div class="skill__radar-canvas">
              <canvas id="radarCoding"></canvas>
            </div>
          </div>
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
          <div class="skill__radar-box">
            <div class="skill__radar-canvas">
              <canvas id="radarDesign"></canvas>
            </div>
          </div>
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
          <div class="skill__radar-box">
            <div class="skill__radar-canvas">
              <canvas id="radarOther"></canvas>
            </div>
          </div>
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
        <a href="https://github.com/suzuki119/" target="_blank" rel="noopener" class="contact__link">GitHub</a>
        <a href="mailto:suzukiyutaro119@gmail.com" class="contact__link">Mail</a>

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
