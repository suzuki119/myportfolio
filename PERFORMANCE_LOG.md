# パフォーマンス改善ログ

---

## 2026-04-20 PageSpeed Insights 初回計測（スマートフォン）

| 指標 | スコア |
|------|--------|
| パフォーマンス | 42 |
| ユーザー補助 | 100 |
| おすすめの方法 | 96 |
| SEO | 100 |

| 指標 | 値 |
|------|----|
| FCP（First Contentful Paint） | 2.6 秒 |
| LCP（Largest Contentful Paint） | 4.7 秒 |
| TBT（Total Blocking Time） | 17,350 ms |
| CLS（Cumulative Layout Shift） | 0 |
| SI（Speed Index） | 7.7 秒 |

---

## 2026-04-20 改善施策①（コード修正のみ）

### 実施内容

#### 1. JSに `defer` を追加（`footer.php`）

**対象ファイル：** `footer.php`

```html
<!-- 変更前 -->
<script src=".../three.min.js"></script>
<script src=".../chart.umd.min.js"></script>
<script src="script.js"></script>

<!-- 変更後 -->
<script src=".../three.min.js" defer></script>
<script src=".../chart.umd.min.js" defer></script>
<script src="script.js" defer></script>
```

**狙い：** Three.js（118KB）・Chart.js（69KB）がメインスレッドをブロックしTBTが17,350msになっていた。`defer` によりHTML解析をブロックせず読み込み・実行する。

---

#### 2. `preconnect` を追加（`header.php`）

**対象ファイル：** `header.php`

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://cdn.jsdelivr.net">
<link rel="preconnect" href="https://cdnjs.cloudflare.com">
```

**狙い：** Google Fonts（750ms）・jsDelivr reset.min.css（760ms）がレンダリングをブロックしていた。事前接続でDNS解決・TCPハンドシェイクの待ち時間を短縮する。

---

#### 3. LCP画像に `fetchpriority="high"` を追加（`index.php`）

**対象ファイル：** `index.php`

```html
<!-- 変更前 -->
<img src="./img/portfolio-text.webp" alt="Portfolio">

<!-- 変更後 -->
<img src="./img/portfolio-text.webp" alt="Portfolio" fetchpriority="high">
```

**狙い：** PageSpeed InsightsでLCP画像（portfolio-text.webp）の優先度が低いと指摘されていた。`fetchpriority="high"` でブラウザに最優先で取得させLCPを改善する。

---

### 未対応の指摘事項（画像系）

| 指摘 | 内容 | 対応状況 |
|------|------|---------|
| 画像サイズ最適化 | about.jpg が895KBで表示サイズより大きい | 未対応 |
| 画像形式変換 | jpg→WebP/AVIF への変換推奨 | 未対応 |
| キャッシュ設定 | 全画像のキャッシュTTLが未設定（1,489KB） | 未対応 |
| レスポンシブ画像 | `srcset` で解像度に応じた画像を出し分け | 未対応 |
