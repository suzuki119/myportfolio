# single.php → single.html 化 TODO

## 現在の posts テーブルとの差分

| JSONのキー | 意味 | 現在のDBに存在？ |
|---|---|---|
| `title` | タイトル | ✅ あり |
| `image` | サムネイル画像 | ✅ `thumbnail` カラムとして存在 |
| `period` | 一覧表示用の短い期間（例：`2025.06 – 08`） | ❌ なし |
| `tags[]` | 使用技術タグ（例：WordPress, SCSS）| ❌ なし（現カテゴリとは別物） |
| `detail.meta_period` | 詳細ページ用の長い期間（例：`2025年6月 – 8月（約3ヶ月）`）| ❌ なし |
| `detail.meta_type` | 種別（例：`個人制作\nブログサイト`）| ❌ なし |
| `detail.external_url` | 外部リンクURL | ❌ なし |
| `detail.sections[]` | セクション配列（Overview/Purpose 等）| ❌ なし（現 content はただのテキスト）|

---

## ① posts テーブルに追加するカラム

```sql
ALTER TABLE posts
  ADD COLUMN period      VARCHAR(50)  NULL AFTER content,
  ADD COLUMN meta_period VARCHAR(100) NULL AFTER period,
  ADD COLUMN meta_type   VARCHAR(100) NULL AFTER meta_period,
  ADD COLUMN external_url VARCHAR(255) NULL AFTER meta_type;
```

| カラム | 格納する値の例 |
|---|---|
| `period` | `2025.06 – 08`（カード一覧に表示） |
| `meta_period` | `2025年6月 – 8月（約3ヶ月）`（詳細ページのメタバーに表示） |
| `meta_type` | `個人制作\nブログサイト`（詳細ページのメタバーに表示） |
| `external_url` | `https://example.com`（「サイトを見る」ボタンのリンク） |

---

## ② tags の扱い

JSONの `tags` は使用技術（WordPress, SCSS, JavaScript）で、現在の `categories` テーブルとは**別の概念**。

**方針（シンプル案）**：`posts` テーブルにカンマ区切りで保存する。

```sql
ALTER TABLE posts
  ADD COLUMN tags VARCHAR(255) NULL AFTER external_url;
```

格納値の例：`WordPress,SCSS,JavaScript`

PHP側で `explode(',', $post['tags'])` で配列にして `foreach` で `<span class="tag">` を出力する。

---

## ③ sections の扱い（最重要・最も複雑）

JSONの `detail.sections[]` は以下の構造：

```json
{
  "id":        "overview",
  "label":     "Overview",
  "title":     "自身の学生生活を発信するブログ",
  "body":      "本文テキスト...",
  "highlight": "目標：...",
  "cta_text":  "サイトを見る",
  "cta_url":   "https://..."
}
```

また 島トゥク の `design・coding` セクションには `image` キーも存在している。

**方針（JSONカラム案）**：`posts` テーブルに `sections` を TEXT（JSON文字列）として保存する。

```sql
ALTER TABLE posts
  ADD COLUMN sections MEDIUMTEXT NULL AFTER tags;
```

- CMS入力欄：ひとまず大きな `<textarea>` にJSON文字列を直接貼り付ける
- PHP側：`json_decode($post['sections'], true)` で配列に変換して `foreach` で出力

> セクションごとにフォームを作る「本格案」は、実装コストが高いため後回し推奨。

---

## ④ next_post_id（次の作品リンク）

single.html の「Next Work → 島トゥク」は現在ハードコード。JSONには存在しないが、以下の方法で対応できる。

**方針（シンプル案）**：次の作品は `id` 順に自動で取得する。

```sql
-- 次の記事を取得するSELECT（single.phpに追加）
SELECT id, title FROM posts
WHERE id > :current_id AND status = 'published'
ORDER BY id ASC
LIMIT 1
```

`next_post_id` カラムの追加は不要。

---

## 作業順序

### Step 1：DBカラム追加（phpMyAdmin）
```sql
ALTER TABLE posts
  ADD COLUMN period       VARCHAR(50)   NULL AFTER content,
  ADD COLUMN meta_period  VARCHAR(100)  NULL AFTER period,
  ADD COLUMN meta_type    VARCHAR(100)  NULL AFTER meta_period,
  ADD COLUMN external_url VARCHAR(255)  NULL AFTER meta_type,
  ADD COLUMN tags         VARCHAR(255)  NULL AFTER external_url,
  ADD COLUMN sections     MEDIUMTEXT    NULL AFTER tags;
```

### Step 2：CMS 編集画面に入力欄追加（post-edit.php / post-new.php）

| 入力欄 | 対応カラム | フォーム部品 |
|---|---|---|
| 一覧用期間 | `period` | `<input type="text">` |
| 詳細用期間 | `meta_period` | `<input type="text">` |
| 種別 | `meta_type` | `<input type="text">` |
| 外部リンク | `external_url` | `<input type="url">` |
| 使用技術タグ | `tags` | `<input type="text">` カンマ区切り |
| セクションJSON | `sections` | `<textarea>` |

### Step 3：single.php の HTML 構造を single.html に合わせて書き換える

### Step 4：既存記事データを CMS から入力する
