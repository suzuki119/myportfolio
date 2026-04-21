# Suzuki Yutaro — Portfolio

フロントエンドエンジニア志望・鈴木優太郎のポートフォリオサイト。  
PHP / MySQL による自作CMSを組み合わせた作品紹介サイトです。

**URL：** https://susuki-island.heavy.jp/myportfolio/

---

## 使用技術

| カテゴリ | 技術 |
|---------|------|
| フロントエンド | HTML / SCSS / JavaScript / Three.js / Chart.js |
| バックエンド | PHP |
| データベース | MySQL |
| ローカル環境 | MAMP（Apache） |
| 本番サーバー | ロリポップ |
| バージョン管理 | Git / GitHub |

---

## 機能

### ポートフォリオサイト
- Three.js による背景アニメーション
- 作品一覧（カテゴリフィルター付き）
- スキルページ（Chart.js によるスキルチャート）
- タイムライン

### 自作CMS（管理画面）
- 管理者ログイン認証（セッション管理）
- 記事の作成・編集・削除
- 記事の並び替え（↑↓ボタン）
- カテゴリ管理・記事への紐付け（多対多）
- 画像アップロード（サムネイル）
- CKEditor 5 による本文入力
- スキルデータの管理

---

## ディレクトリ構成

```
myportfolio/
├── index.php          # トップページ
├── works.php          # 作品一覧
├── single.php         # 作品詳細
├── skill.php          # スキルページ
├── header.php         # 共通ヘッダー
├── footer.php         # 共通フッター
├── script.js          # フロントエンドJS
├── css/               # コンパイル済みCSS
├── sass/              # SCSS ソース
├── img/               # 静的画像
└── cms/
    ├── config.php     # DB接続設定（.gitignore 対象）
    ├── config.sample.php  # 設定ファイルのテンプレート
    ├── login.php      # ログイン画面
    ├── logout.php     # ログアウト処理
    └── admin/         # 管理画面
        ├── index.php        # 記事一覧
        ├── post-new.php     # 記事作成
        ├── post-edit.php    # 記事編集
        ├── categories.php   # カテゴリ管理
        ├── skill.php        # スキル一覧
        └── skill-edit.php   # スキル編集
```

---

## ローカル環境でのセットアップ

### 必要なもの
- MAMP（Apache + PHP + MySQL）

### 手順

**1. リポジトリをクローン**

```bash
git clone https://github.com/suzuki119/myportfolio.git
```

**2. 設定ファイルを作成**

```bash
cp cms/config.sample.php cms/config.php
```

`cms/config.php` を開いてDB接続情報を書き換える。

**3. データベースを作成**

phpMyAdmin で `myportfolio` というDBを作成し、以下のテーブルを作成する。

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    thumbnail VARCHAR(255),
    status ENUM('draft', 'published') DEFAULT 'draft',
    author_id INT,
    period VARCHAR(100),
    type VARCHAR(100),
    external_url VARCHAR(2083),
    tags TEXT,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id)
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE post_categories (
    post_id INT,
    category_id INT,
    PRIMARY KEY (post_id, category_id),
    FOREIGN KEY (post_id) REFERENCES posts(id),
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE TABLE post_sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    title VARCHAR(255) NOT NULL,
    body LONGTEXT,
    FOREIGN KEY (post_id) REFERENCES posts(id)
);

CREATE TABLE skill (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    period VARCHAR(100),
    body TEXT,
    image_url VARCHAR(255),
    category VARCHAR(100)
);
```

**4. 管理者ユーザーを登録**

`http://localhost:8888/myportfolio/cms/setup.php` にアクセスして管理者アカウントを作成する。

**5. アップロードフォルダを作成**

```bash
mkdir cms/uploads
```

---

## 注意事項

- `cms/config.php` は `.gitignore` により管理外です。`cms/config.sample.php` を参考に作成してください。
- `cms/uploads/` 内のアップロード画像も `.gitignore` 対象です。
