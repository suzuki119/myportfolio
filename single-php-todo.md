# single.php → single.html 化 TODO

## ① DB カラム追加（phpMyAdmin で ALTER TABLE）

`posts` テーブルに以下を追加する。

```sql
ALTER TABLE posts
  ADD COLUMN period       VARCHAR(100) NULL AFTER content,
  ADD COLUMN tags         VARCHAR(255) NULL AFTER period,
  ADD COLUMN type         VARCHAR(100) NULL AFTER tags,
  ADD COLUMN external_url VARCHAR(255) NULL AFTER type,
  ADD COLUMN next_post_id INT          NULL AFTER external_url;
```

| カラム | 内容 | 例 |
|---|---|---|
| `period` | 制作期間 | `2025年6月 – 8月（約3ヶ月）` |
| `tags` | 使用技術（カンマ区切り） | `WordPress,SCSS,JavaScript` |
| `type` | 種別 | `個人制作\nブログサイト` |
| `external_url` | サイトを見るURL | `https://example.com` |
| `next_post_id` | 次の作品の記事ID | `2` |

---

## ② CMS 編集画面に入力欄追加

`cms/admin/post-edit.php` と `cms/admin/post-new.php` に以下のフォームを追加する。

- 制作期間（`<input type="text" name="period">`）
- 使用技術（`<input type="text" name="tags">` カンマ区切り入力）
- 種別（`<textarea name="type">`）
- 外部リンク（`<input type="url" name="external_url">`）
- 次の作品（`<select name="next_post_id">` 記事一覧から選択）

### $_POST 変数の受け取り（更新処理部分に追加）

既存の変数宣言に以下を追加する。

```php
$title        = trim($_POST['title']        ?? '');
$content      = trim($_POST['content']      ?? '');
$status       = $_POST['status']            ?? 'draft';
$thumbnail    = $post['thumbnail'];
$category_id  = $_POST['category_id']       ?? '';
// ↓ 追加分
$period       = trim($_POST['period']       ?? '');
$meta_period  = trim($_POST['meta_period']  ?? '');
$meta_type    = trim($_POST['meta_type']    ?? '');
$external_url = trim($_POST['external_url'] ?? '');
$tags         = trim($_POST['tags']         ?? '');
```

### UPDATE文にも追加する（post-edit.php）

```php
$stmt = $pdo->prepare(
    'UPDATE posts SET
        title = :title, content = :content, thumbnail = :thumbnail, status = :status,
        period = :period, meta_period = :meta_period, meta_type = :meta_type,
        external_url = :external_url, tags = :tags
     WHERE id = :id'
);
$stmt->execute([
    ':title'        => $title,
    ':content'      => $content,
    ':thumbnail'    => $thumbnail,
    ':status'       => $status,
    ':period'       => $period,
    ':meta_period'  => $meta_period,
    ':meta_type'    => $meta_type,
    ':external_url' => $external_url,
    ':tags'         => $tags,
    ':id'           => $id,
]);
```

### post_sections の更新（post-edit.php のみ）

`post_categories` と同じ「全削除 → 入れ直し」パターンで更新する。

```php
// 既存セクションを全削除
$pdo->prepare('DELETE FROM post_sections WHERE post_id = :post_id')
    ->execute([':post_id' => $id]);

// フォームから送られた sections を順番に INSERT
$titles = $_POST['section_title']    ?? [];
$bodies = $_POST['section_body']     ?? [];
$cta_t  = $_POST['section_cta_text'] ?? [];
$cta_u  = $_POST['section_cta_url']  ?? [];

foreach ($titles as $i => $t) {
    if (trim($t) === '') continue; // タイトルが空のセクションはスキップ
    $s_stmt = $pdo->prepare(
        'INSERT INTO post_sections (post_id, sort_order, title, body, cta_text, cta_url)
         VALUES (:post_id, :sort_order, :title, :body, :cta_text, :cta_url)'
    );
    $s_stmt->execute([
        ':post_id'    => $id,
        ':sort_order' => $i,
        ':title'      => trim($t),
        ':body'       => trim($bodies[$i] ?? ''),
        ':cta_text'   => trim($cta_t[$i]  ?? ''),
        ':cta_url'    => trim($cta_u[$i]  ?? ''),
    ]);
}
```

フォーム側は `name="section_title[]"` のように `[]` をつけることで、複数セクションを配列として受け取れる。

---

## ③ コンテンツのセクション構造

single.html ではコンテンツが Overview / Purpose / Ingenuity / Reflection の複数ブロックに分かれている。

**方針（シンプル案）**：
CMS の `content` 欄に以下の HTML をそのまま書く。
`<?= $post['content'] ?>` でそのまま出力されるので追加実装は不要。

```html
<div class="article-block" id="overview">
  <div class="block-label">Overview</div>
  <h2 class="block-title">〇〇〇</h2>
  <div class="block-body">
    <p>本文...</p>
  </div>
</div>

<div class="article-block" id="purpose">
  ...
</div>
```

---

## ④ single.php の HTML 構造を差し替え

以下の構造を single.html に合わせて書き換える。

```
現在                         →  変更後
─────────────────────────────────────────────
<header class="header">      →  <header class="header--work">（back-link付き）
<main style="...">           →  canvas背景 + work-hero + hero-divider
                                + work-meta-bar（期間/技術/種別）
                                + content-grid（sidebar + article）
                             →  .next-work セクション
なし                         →  <footer>
```

### 追加する主要要素

| 要素 | 内容 |
|---|---|
| `<div id="canvas-container">` | canvas 背景アニメーション |
| `header.header--work` | ← Back to Portfolio リンク付きヘッダー |
| `.work-hero` | eyebrow（`01 — Works`）＋ `<h1>` タイトル |
| `.hero-divider` | 区切り線 |
| `.work-meta-bar` | 期間 / 技術 / 種別の3カラム |
| `.sidebar` | セクションへのアンカーリンク（`#overview` 等）|
| `.next-work` | 次の作品リンク |
| `<footer>` | コピーライト |

---

## 作業順序

1. `ALTER TABLE` でカラム追加（✅ 完了）
2. CMS 編集画面に入力欄追加（post-edit.php / post-new.php）
3. single.php の HTML 構造を差し替え（✅ 完了）
4. 既存記事のデータを CMS から入力する

### post_sections テーブルの作成（phpMyAdmin で実行）

phpMyAdmin → データベース選択 → 「SQL」タブ → 以下を貼り付けて実行

```sql
CREATE TABLE post_sections (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  post_id    INT NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  title      VARCHAR(255) NULL,
  body       TEXT         NULL
);
```

| カラム | 内容 |
|---|---|
| `id` | セクションID（主キー・自動採番）|
| `post_id` | どの記事のセクションか（posts.id と紐づく）|
| `sort_order` | 表示順（0, 1, 2...）|
| `title` | セクションの見出し |
| `body` | セクションの本文（改行あり）|

> CTAボタン（「サイトを見る」等）は `posts.external_url` を使うため、`post_sections` には不要。

---

## Step 3 詳細：single.php の HTML 構造を書き換える

### 3-1. DBから取得するデータを追加

現在は `posts` のみだが、`post_sections` も取得する。

```php
// 既存：記事を取得
$stmt = $pdo->prepare('SELECT * FROM posts WHERE id = :id AND status = :status LIMIT 1');
$stmt->execute([':id' => $id, ':status' => 'published']);
$post = $stmt->fetch();

// 追加：セクションを sort_order 順に取得
$s_stmt = $pdo->prepare('SELECT * FROM post_sections WHERE post_id = :post_id ORDER BY sort_order ASC');
$s_stmt->execute([':post_id' => $id]);
$sections = $s_stmt->fetchAll();

// 追加：tags をカンマ区切りから配列に変換
$tags = $post['tags'] ? explode(',', $post['tags']) : [];
// [組み込み] explode('区切り文字', 文字列) = 文字列を区切って配列にする
```

### 3-2. canvas 背景を追加

`<body>` 直後に追加する。

```html
<div id="canvas-container">
  <canvas id="bg-canvas"></canvas>
</div>
```

### 3-3. ヘッダーを差し替え

```html
<!-- 変更前 -->
<header class="header">
  <div class="header__logo">...</div>
</header>

<!-- 変更後 -->
<header class="header--work">
  <a href="./index.php" class="back-link">
    <svg viewBox="0 0 24 24"><path d="M19 12H5M5 12l7 7M5 12l7-7"/></svg>
    Back to Portfolio
  </a>
  <div class="header-logo">Suzuki Portfolio</div>
</header>
```

### 3-4. `<main>` 内を全面差し替え

```html
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
      <div class="work-meta-value"><?= h($post['meta_period']) ?></div>
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
      <div class="work-meta-value"><?= nl2br(h($post['meta_type'])) ?></div>
    </div>
  </div>

  <!-- Content -->
  <div class="work-content">
    <article>

      <!-- サムネイル -->
      <?php if ($post['thumbnail']): ?>
        <div class="mock-img">
          <img src="<?= UPLOAD_URL . h($post['thumbnail']) ?>" alt="<?= h($post['title']) ?>">
        </div>
      <?php endif; ?>

      <!-- セクション -->
      <?php foreach ($sections as $section): ?>
        <div class="article-block">
          <h2 class="block-title"><?= h($section['title']) ?></h2>
          <div class="block-body">
            <?= nl2br(h($section['body'])) ?>
          </div>
          <?php if (!empty($section['cta_url'])): ?>
            <div class="work-cta">
              <a href="<?= h($section['cta_url']) ?>" target="_blank" rel="noopener" class="btn-primary">
                <?= h($section['cta_text']) ?>
                <svg viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
              </a>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>

    </article>
  </div>

</main>
```

### 3-5. footer を追加

```html
<footer>2026 Suzuki Yutaro — All Rights Reserved</footer>
```

### ポイントまとめ

| コード | 意味 |
|---|---|
| `nl2br(h($section['body']))` | 本文の改行を `<br>` に変換。`h()` でエスケープしてから `nl2br()` をかける |
| `explode(',', $post['tags'])` | `"WordPress,SCSS"` → `["WordPress", "SCSS"]` に分割 |
| `foreach ($sections as $section)` | `post_sections` の全行をループ出力 |
| `!empty($section['cta_url'])` | CTAURLが空のときはボタンを非表示 |
| サイドバーなし | セクションIDを表示しない方針のため省略 |
