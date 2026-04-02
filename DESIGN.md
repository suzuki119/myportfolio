# ポートフォリオサイト 設計書

> **対象者：** このコードを初めて読む自分自身  
> **目的：** 構成・ルール・データの流れを理解する

---

## 目次

1. [プロジェクト概要](#1-プロジェクト概要)
2. [ディレクトリ構成](#2-ディレクトリ構成)
3. [技術スタック](#3-技術スタック)
4. [データの流れ](#4-データの流れ)
5. [ファイル別の役割](#5-ファイル別の役割)
6. [データ構造（JSON スキーマ）](#6-データ構造json-スキーマ)
7. [SCSS の設計ルール](#7-scss-の設計ルール)
8. [JavaScript の仕組み](#8-javascript-の仕組み)
9. [CMS の仕組み](#9-cms-の仕組み)
10. [セキュリティ対策](#10-セキュリティ対策)
11. [よくある作業と手順](#11-よくある作業と手順)

---

## 1. プロジェクト概要

### サイトの構成

このサイトは **2種類のページ** で成り立っている。

| ページ | URL | 役割 |
|--------|-----|------|
| ポートフォリオ（一覧） | `/index.php` | 自己紹介・作品一覧・スキル |
| 作品詳細 | `/cms/work.php?id=<ID>` | 各作品の詳しい説明 |

### 管理機能（CMS）

| ページ | URL | 役割 |
|--------|-----|------|
| ログイン | `/cms/login.php` | 管理者認証 |
| 管理画面 | `/cms/admin.php` | 作品の追加・編集・削除 |
| ログアウト | `/cms/logout.php` | セッション破棄 |

---

## 2. ディレクトリ構成

```
myportfolio/
│
├── index.php          ← ポートフォリオのトップページ（メイン）
├── script.js          ← フロントエンドの JavaScript
│
├── css/
│   ├── style.css      ← SCSS をコンパイルした CSS（直接編集しない）
│   ├── style.css.map  ← ソースマップ（デバッグ用）
│   └── admin.css      ← 管理画面用 CSS（直接編集しない）
│
├── sass/
│   ├── style.scss     ← フロントエンドのスタイル（ここを編集する）
│   └── admin.scss     ← 管理画面のスタイル（ここを編集する）
│
├── cms/
│   ├── config.php     ← パスワード・定数の設定
│   ├── api.php        ← REST API（データの読み書き処理）
│   ├── admin.php      ← 管理画面の HTML + JavaScript
│   ├── login.php      ← ログイン画面
│   ├── logout.php     ← ログアウト処理
│   ├── work.php       ← 作品詳細ページ
│   ├── preview.php    ← 作品一覧のプレビュー
│   ├── works.json     ← 作品データベース（ここに作品データが入る）
│   ├── works.json.bak ← 作品データの自動バックアップ
│   └── uploads/       ← アップロードした画像の保存先
│
└── img/
    └── about.jpg      ← About セクションの写真
```

### 重要なルール

- **CSS を直接編集してはいけない**  
  `css/style.css` は SCSS から自動生成されるファイル。  
  スタイルを変えたいときは `sass/style.scss` を編集して再コンパイルする。

- **SCSS のコンパイル方法**
  ```bash
  # 監視モード（保存するたびに自動コンパイル）
  sass --watch sass/style.scss:css/style.css
  ```

---

## 3. 技術スタック

| 役割 | 使用技術 | バージョン | CDN/npm |
|------|---------|-----------|---------|
| マークアップ | HTML5 / PHP | PHP 7/8 | - |
| スタイル | SCSS → CSS | - | - |
| インタラクション | JavaScript (Vanilla) | ES2020 | - |
| 3D 背景アニメーション | Three.js | r128 | CDN |
| レーダーチャート | Chart.js | 4.4.0 | CDN |
| フォント | Google Fonts | - | CDN |
| データ保存 | JSON ファイル | - | - |
| 認証 | PHP セッション | - | - |

### フォント

| 変数名 | フォント名 | 用途 |
|--------|-----------|------|
| `$font-serif` | Cormorant Garamond | 見出し・ブランド名（優雅な印象） |
| `$font-mono` | Space Mono | 本文・ラベル・CMS（技術的な印象） |

---

## 4. データの流れ

### トップページ（index.php）

```
ブラウザがアクセス
        ↓
[index.php] が works.json を読み込む（PHP）
        ↓
作品カードの HTML を生成（PHP の foreach ループ）
        ↓
HTML をブラウザに送信
        ↓
JavaScript が起動
  ├─ Three.js で背景の3D アニメーション開始
  ├─ Chart.js でレーダーチャート描画
  └─ スクロールイベントでタイムライン・プログレスバー更新
```

### 作品詳細ページ（work.php）

```
カードをクリック → work.php?id=1234567890 にアクセス
        ↓
[work.php] が URL の ?id を受け取る
        ↓
works.json から該当 ID の作品を検索
        ↓
セクションデータを HTML に変換して表示
```

### 管理画面でデータを保存する流れ

```
管理者が admin.php でフォームを入力
        ↓
「保存」ボタンをクリック（JavaScript）
        ↓
Fetch API で api.php にリクエスト送信
        ↓
[api.php] がセッションを確認（ログイン済みか？）
        ↓
works.json を更新（バックアップも自動作成）
        ↓
結果を JSON で返す
        ↓
管理画面にトースト通知を表示
```

---

## 5. ファイル別の役割

### index.php

ポートフォリオのメインページ。6つのセクションで構成。

| セクション | 内容 | データの元 |
|-----------|------|-----------|
| Hero | タイトル・キャッチコピー | HTML に直接記述 |
| About | 自己紹介・写真 | HTML に直接記述 |
| Works | 作品カード一覧 | `works.json` を PHP で読み込む |
| Skills | レーダーチャート | JavaScript に直接記述 |
| Timeline | 経歴の時系列 | HTML に直接記述 |
| Contact | メール・GitHub | HTML に直接記述 |

**Works セクションの PHP コード（例）**
```php
// works.json を読み込む
$works = json_decode(file_get_contents('cms/works.json'), true);

// 作品ごとにカードを生成
foreach ($works as $w) {
    echo '<a href="cms/work.php?id=' . $w['id'] . '">';
    echo '<h3>' . h($w['title']) . '</h3>';
    // ... タグや画像も出力
    echo '</a>';
}
```

---

### cms/api.php

フロントエンド（admin.php）からの Ajax リクエストを処理する **REST API**。

| アクション | メソッド | 処理内容 |
|-----------|---------|---------|
| `?action=list` | GET | 作品一覧を JSON で返す |
| `?action=save` | POST | 作品を追加または更新 |
| `?action=delete` | POST | 作品を削除 |
| `?action=reorder` | POST | 作品の順番を変更 |
| `?action=upload` | POST | 画像ファイルをアップロード |

**api.php の呼び出し方（admin.php での例）**
```javascript
// 作品一覧を取得
const res = await fetch('api.php?action=list');
const data = await res.json();

// 作品を保存
await fetch('api.php?action=save', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: 123, title: '作品名', ... })
});
```

---

### cms/work.php

作品詳細ページ。URL パラメータ `?id` で表示する作品を決定する。

**URL 例：** `cms/work.php?id=1748000000`

ページの構成：
```
┌─────────────────────────────────┐
│  Hero: 作品タイトル・期間・タグ  │
├────────────┬────────────────────┤
│  Sidebar   │   Article          │
│  ・セクション  │   ・各セクションの内容  │
│    ナビゲーション │   ・本文・画像・CTA   │
├────────────┴────────────────────┤
│  Next Work: 次の作品へのリンク   │
└─────────────────────────────────┘
```

---

## 6. データ構造（JSON スキーマ）

`cms/works.json` に全作品データが配列で保存される。

```json
[
  {
    "id": 1748000000,
    "title": "作品名",
    "period": "2025.06 – 08",
    "tags": ["WordPress", "SCSS", "JavaScript"],
    "image": "cms/uploads/画像ファイル名.jpg",
    "detail": {
      "hero_title": "詳細ページのメインタイトル",
      "meta_period": "2025年6月 – 8月（約3ヶ月）",
      "meta_type": "個人制作\nブログサイト",
      "external_url": "https://example.com",
      "sections": [
        {
          "id": "overview",
          "label": "ナビに表示するラベル",
          "title": "セクションの見出し",
          "body": "本文テキスト（改行で段落を分ける）",
          "image": "画像パス（省略可）",
          "highlight": "ハイライトボックスのテキスト（省略可）",
          "cta_text": "ボタンのテキスト（省略可）",
          "cta_url": "ボタンのリンク先（省略可）"
        }
      ]
    }
  }
]
```

### フィールドの説明

| フィールド | 必須 | 説明 |
|-----------|------|------|
| `id` | 必須 | タイムスタンプ（Unix 時刻）。作品を識別するユニークな番号 |
| `title` | 必須 | 一覧ページに表示する作品名 |
| `period` | 必須 | 一覧ページに表示する制作期間 |
| `tags` | 必須 | 使用技術タグの配列 |
| `image` | 推奨 | 一覧ページのサムネイル画像パス |
| `detail` | 推奨 | 詳細ページのデータ（省略すると詳細ページが空になる） |
| `detail.sections` | 推奨 | 詳細ページのセクション配列 |

---

## 7. SCSS の設計ルール

### 変数（`$` で始まる）

```scss
// カラー
$color-white:  #d8d8d8;   // テキスト色
$color-gray:   #7a7a72;   // サブテキスト色
$color-accent: #b8a88a;   // アクセント（ウォームブラウン）
$color-bg:     #090909;   // 背景色（ほぼ黒）

// フォント
$font-serif: 'Cormorant Garamond', serif;  // 見出し用
$font-mono:  'Space Mono', monospace;     // 本文・ラベル用
```

### CSS カスタムプロパティ（ダーク/ライトテーマ）

変数と似ているが、**JavaScript からも変更できる**ため、テーマ切り替えに使用。

```scss
:root {
  --bg: #090909;       // 背景色
  --text: #d8d8d8;     // テキスト色
  --accent: #b8a88a;   // アクセント色
}

// ライトテーマ（data-theme="light" が html タグに付いたとき）
[data-theme="light"] {
  --bg: #f0ede8;
  --text: #1a1a18;
  --accent: #8a7855;
}
```

### Mixin（よく使うスタイルのまとまり）

```scss
// レスポンシブ対応
@mixin mq($bp: md) {
  @if $bp == md { @media (max-width: 900px) { @content; } }  // タブレット以下
  @if $bp == sm { @media (max-width: 480px) { @content; } }  // スマホ以下
}

// ガラス効果（frosted glass）
@mixin glass-card() {
  background: var(--glass-55);
  backdrop-filter: blur(16px);
  border: 1px solid var(--glass-border);
}

// 使い方
.works__card {
  @include glass-card();
  @include mq(sm) { padding: rem(16); }
}
```

### BEM 記法の命名ルール

**BEM = Block__Element--Modifier**

```
Block   → 独立したコンポーネント（例: .hero, .works, .about）
Element → Block の中の要素（例: .hero__title, .works__card）
Modifier→ 状態・バリエーション（例: .works__card--active）
```

**実例（Works セクション）：**
```scss
.works {          // Block: Works セクション全体
  &__grid { }     // Element: カードのグリッドレイアウト
  &__card { }     // Element: 個々の作品カード
    &-img { }     // Element: カードの画像エリア
    &-body { }    // Element: カードのテキストエリア
    &-tag { }     // Element: 技術タグ
    &-title { }   // Element: 作品名
}
```

**各セクションの Block 名：**

| セクション | Block 名 | 主な Element |
|-----------|---------|-------------|
| ヘッダー | `.header` | `__logo`, `__nav`, `__toggle` |
| ヒーロー | `.hero` | `__title`, `__eyebrow`, `__sub` |
| About | `.about` | `__grid`, `__card`, `__photo` |
| Works | `.works` | `__grid`, `__card`, `__card-title` |
| Skills | `.skill` | `__block`, `__radar-box`, `__table` |
| Timeline | `.tl` | `__wrap`, `__entry`, `__card` |
| Contact | `.contact` | `__email`, `__links` |

### Z-index の管理

重なり順を一元管理（数値が大きいほど前面）。

| 変数 | 値 | 要素 |
|------|----|------|
| `$z-canvas` | 0 | Three.js の 3D 背景 |
| `$z-main` | 10 | メインコンテンツ |
| `$z-header` | 50 | ヘッダー |
| `$z-toggle` | 210 | ハンバーガーメニューボタン |
| `$z-progress` | 300 | プログレスバー |

---

## 8. JavaScript の仕組み

`script.js` は 5 つの機能で構成されている。

### ① プログレスバー

ページ上部に表示される細い線。スクロール量に応じて伸びる。

```javascript
const bar = document.getElementById('progressBar');
window.addEventListener('scroll', () => {
  const max = document.body.scrollHeight - innerHeight;
  bar.style.width = (max > 0 ? scrollY / max * 100 : 0) + '%';
});
```

### ② Three.js 背景アニメーション

ページ背景の 3D オブジェクト（クリスタル + ガラスパネル）。

| オブジェクト | 説明 |
|-------------|------|
| クリスタル | ダイヤモンド形の宝石、中央でゆっくり回転 |
| ガラスパネル | 半透明の板 80 枚が複数層になって回転 |

**`canvas` 要素への描画なので、HTML コンテンツの下レイヤーに配置される。**

### ③ Chart.js レーダーチャート

Skills セクションの 3 つのチャート。画面内に入ったとき（Intersection Observer）に初期化される。

| チャート | 対象スキル |
|---------|-----------|
| コーディング | HTML, CSS/SCSS, JavaScript, C言語 |
| デザイン | Illustrator, Photoshop, Figma, Canva |
| その他 | Blender, Premiere Pro, WordPress, Git など |

スキルレベルは `script.js` 内の配列を直接編集する：
```javascript
// data: の値を 0〜5 で設定する
data: [5, 4, 4, 2]  // HTML, CSS/SCSS, JavaScript, C言語
```

### ④ タイムライン スクロールアニメーション

スクロールに合わせて、タイムラインの縦線が伸びていくアニメーション。

**仕組み：**
1. JavaScript がスクロール量を計算して CSS カスタムプロパティを更新
2. SCSS の疑似要素（`::before`）の `height` がその値を参照

```javascript
// JS 側: CSS カスタムプロパティを更新
tlWrap.style.setProperty('--tl-line-height', height + 'px');
```
```scss
// SCSS 側: 疑似要素で縦線を描画
.tl__wrap::before {
  height: var(--tl-line-height, 0);
}
```

### ⑤ ハンバーガーメニュー

モバイル表示時のナビゲーション開閉。

- 3 本線ボタンをクリック → メニュー開く
- ナビのリンクをクリック → メニュー自動で閉じる
- `aria-label` でアクセシビリティ対応

---

## 9. CMS の仕組み

### 認証フロー

```
login.php でパスワード入力
        ↓
PHP が config.php の CMS_PASSWORD と照合
        ↓
一致 → セッション変数にフラグをセット → admin.php にリダイレクト
不一致 → エラーメッセージ表示
```

**パスワードの変更方法：**  
`cms/config.php` の `CMS_PASSWORD` を書き換える。

```php
define('CMS_PASSWORD', '新しいパスワード');
```

### 管理画面のレイアウト

```
┌────────────────────────────────────────────┐
│  Works CMS  |  ポートフォリオ  |  ログアウト  │
├───────────────────┬────────────────────────┤
│  左：作品一覧      │  右：編集フォーム        │
│  ┌──────────────┐  │  ┌────────────────┐   │
│  │ ① MYBLOG     │  │  │ [基本情報] [詳細] │  │
│  │ ② 島トゥク    │  │  │                │   │
│  │ ③ シール帳    │  │  │  タイトル:      │   │
│  │ ④ 新校舎動画  │  │  │  期間:         │   │
│  └──────────────┘  │  │  タグ:          │   │
│  [+ 新規作成]      │  │  画像:          │   │
│                    │  └────────────────┘   │
└───────────────────┴────────────────────────┘
```

### 作品の追加手順

1. `admin.php` にアクセス（要ログイン）
2. 左側の「+ 新規作成」をクリック
3. 「基本情報」タブ：タイトル・期間・タグ・画像を入力して「保存」
4. 一覧に作品が追加される
5. 作品をクリックして「詳細ページ」タブ：セクションを追加・編集
6. 「詳細を保存」をクリック

### 画像のアップロード

- 管理画面から画像をアップロードすると `cms/uploads/` に保存される
- 画像の URL は自動的にフォームにセットされる
- 対応形式：JPG, PNG, GIF, WEBP
- サイズ制限：10MB まで

### バックアップ

作品データを保存するたびに `works.json.bak` が自動作成される。  
データが壊れた場合は `.bak` ファイルを `works.json` にコピーして復元できる。

---

## 10. セキュリティ対策

| 脅威 | 対策 | 実装箇所 |
|------|------|---------|
| XSS（スクリプト挿入） | `htmlspecialchars()` でエスケープ | `h()` 関数（index.php, work.php） |
| 不正アクセス | セッション認証チェック | `api.php`, `admin.php` の先頭 |
| ファイルアップロード攻撃 | 拡張子チェック・サイズ制限 | `api.php` の upload 処理 |
| データ破損 | 自動バックアップ | `saveWorks()` 関数 |

---

## 11. よくある作業と手順

### コンテンツを更新したい

| 変更内容 | ファイル | 方法 |
|---------|---------|------|
| 作品を追加・編集 | `works.json` | 管理画面 (`admin.php`) から操作 |
| About の自己紹介文 | `index.php` | 直接 HTML を編集 |
| スキルの値 | `script.js` | `data: [...]` の配列を変更 |
| タイムラインの経歴 | `index.php` | `.tl__entry` の HTML を追加・編集 |
| About の写真 | `img/about.jpg` | 同じファイル名で上書き |

### スタイルを変更したい

1. `sass/style.scss` を編集
2. `sass --watch sass/style.scss:css/style.css` でコンパイル
3. `css/style.css` が自動更新される

### 新しいセクションを追加したい

1. `index.php` に HTML を追加（BEM 記法でクラス名をつける）
2. `sass/style.scss` にスタイルを追加
3. 必要なら `script.js` に JavaScript を追加

**BEM クラス名の例（新しいセクション「Gallery」を追加する場合）：**
```html
<section class="gallery">
  <div class="gallery__grid">
    <div class="gallery__item">
      <img class="gallery__img" src="...">
    </div>
  </div>
</section>
```

### パスワードを変更したい

`cms/config.php` の `CMS_PASSWORD` を変更する。

```php
define('CMS_PASSWORD', '新しいパスワード');
```

---

*この設計書は 2026-04-02 時点のコードを元に作成。*
