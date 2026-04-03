# ポートフォリオ CMS 設計書

> 作成日：2026-04-03
> 目的：PHPの学習を兼ねたポートフォリオ用CMS構築
> 環境：MAMP（Apache + PHP + MySQL） / phpMyAdmin

---

## 目次

1. [システム概要](#1-システム概要)
2. [環境・技術メモ](#2-環境技術メモ)
3. [ディレクトリ構成](#3-ディレクトリ構成)
4. [データベース設計](#4-データベース設計)
5. [画面一覧](#5-画面一覧)
6. [ファイル別の役割](#6-ファイル別の役割)
7. [SQL・PHP 用語メモ](#7-sqlphp-用語メモ)
8. [実装ログ](#8-実装ログ)

---

## 1. システム概要

### 機能一覧

| 機能 | 説明 | 状態 |
|------|------|------|
| ユーザー認証 | 管理者のログイン・ログアウト | ✅ 完了 |
| 記事投稿 | 記事の作成・編集・削除 | 🔲 未着手 |
| 記事一覧 | 管理画面での記事一覧表示 | 🔲 未着手 |
| カテゴリ管理 | カテゴリの作成・削除・記事への紐付け | 🔲 未着手 |
| 画像アップロード | サムネイル画像のアップロード・保存 | 🔲 未着手 |

### 開発ステップ

| Step | 内容 | 状態 |
|------|------|------|
| Step 1 | DB接続・設定ファイル（config.php） | ✅ 完了 |
| Step 2 | 管理者ユーザー登録・ログイン | ✅ 完了 |
| Step 3 | 記事一覧（管理画面トップ） | 🔲 未着手 |
| Step 4 | 記事の投稿・編集・削除 | 🔲 未着手 |
| Step 5 | カテゴリ管理・記事への紐付け | 🔲 未着手 |
| Step 6 | 画像アップロード | 🔲 未着手 |

---

## 2. 環境・技術メモ

### 開発環境

| 項目 | 内容 |
|------|------|
| ローカル環境 | MAMP（Mac） |
| Webサーバー | Apache |
| 言語 | PHP |
| DB | MySQL |
| DB管理ツール | phpMyAdmin |
| エディタ | VSCode |
| URL | `http://localhost:8888/myportfolio/` |

### 各ツールの役割

| ツール | 役割 | 例え |
|--------|------|------|
| MAMP | Apache・PHP・MySQLをまとめてインストール・起動する | 調理器具セット |
| Apache | ブラウザのリクエストを受け取りPHPに渡す | ウェイター |
| PHP | プログラムを処理してHTMLを生成する | 料理人 |
| MySQL | データを保存・管理する | 倉庫 |
| Homebrew | ソフトをインストールするための道具（今回は不使用） | Amazon |

### ローカル環境 vs レンタルサーバー

| | MAMP（ローカル） | レンタルサーバー（本番） |
|--|-----------------|------------------------|
| アクセス | 自分のMacの中だけ | 世界中からアクセス可能 |
| URL | `localhost:8888` | `https://example.com` |
| 費用 | 無料 | 月額数百円〜 |
| 用途 | 開発・テスト | 完成したサービスの公開 |

> **開発の流れ：** MAMPで開発・テスト → 完成したらレンタルサーバーにアップして公開

---

## 3. ディレクトリ構成

```
myportfolio/
├── index.html                  ポートフォリオTOP
├── single.html                 作品詳細ページ
│
└── cms/                        CMS本体
    ├── config.php              DB接続・共通関数（全ファイルからrequire）
    ├── login.php               ログイン画面
    ├── logout.php              ログアウト処理
    ├── setup.php               初回管理者ユーザー登録（使用後削除）
    │
    ├── admin/                  管理画面（ログイン必須）
    │   ├── index.php           記事一覧
    │   ├── post-new.php        記事新規作成
    │   ├── post-edit.php       記事編集
    │   └── categories.php      カテゴリ管理
    │
    └── uploads/                アップロード画像の保存先
```

---

## 3. データベース設計

### テーブル一覧

| テーブル名 | 役割 |
|-----------|------|
| users | 管理者のログイン情報 |
| posts | 記事のタイトル・本文など |
| categories | カテゴリの一覧 |
| post_categories | 記事とカテゴリの紐付け（中間テーブル） |

---

### users テーブル

管理者のログイン情報を管理する。

| カラム名 | 型 | 説明 |
|---------|-----|------|
| id | INT / PK / AUTO_INCREMENT | ユーザーID |
| username | VARCHAR(50) / UNIQUE | ログイン名 |
| password | VARCHAR(255) | ハッシュ化したパスワード |
| email | VARCHAR(100) | メールアドレス |
| created_at | TIMESTAMP | 作成日時 |

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

### posts テーブル

記事の内容を管理する。`author_id` で users テーブルと紐付く。

| カラム名 | 型 | 説明 |
|---------|-----|------|
| id | INT / PK / AUTO_INCREMENT | 記事ID |
| title | VARCHAR(255) | タイトル |
| content | LONGTEXT | 本文（HTML） |
| thumbnail | VARCHAR(255) | サムネイル画像のファイル名 |
| status | ENUM('draft','published') | 下書き or 公開 |
| author_id | INT / FK → users.id | 投稿者 |
| created_at | TIMESTAMP | 作成日時 |
| updated_at | TIMESTAMP | 更新日時（自動更新） |

```sql
CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content LONGTEXT,
    thumbnail VARCHAR(255),
    status ENUM('draft', 'published') DEFAULT 'draft',
    author_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id)
);
```

> **現在のテーブルとの差分：** `thumbnail` カラムが未追加。Step 6 で `ALTER TABLE` を使って追加する。

---

### categories テーブル

カテゴリの一覧を管理する。

| カラム名 | 型 | 説明 |
|---------|-----|------|
| id | INT / PK / AUTO_INCREMENT | カテゴリID |
| name | VARCHAR(100) | カテゴリ名（例：JavaScript） |
| slug | VARCHAR(100) / UNIQUE | URL用スラッグ（例：javascript） |

```sql
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE
);
```

---

### post_categories テーブル（中間テーブル）

記事とカテゴリの多対多の関係を管理する。
1つの記事が複数カテゴリを持てる。1つのカテゴリに複数記事が属せる。

| カラム名 | 型 | 説明 |
|---------|-----|------|
| post_id | INT / FK → posts.id | 記事ID |
| category_id | INT / FK → categories.id | カテゴリID |

```sql
CREATE TABLE post_categories (
    post_id INT,
    category_id INT,
    PRIMARY KEY (post_id, category_id),
    FOREIGN KEY (post_id) REFERENCES posts(id),
    FOREIGN KEY (category_id) REFERENCES categories(id)
);
```

**多対多の関係図：**
```
posts          post_categories     categories
-----          ---------------     ----------
id      ←───  post_id
               category_id  ───→  id
```

---

## 5. 画面一覧

| 画面名 | URL | ログイン必須 | 説明 |
|--------|-----|-------------|------|
| ログイン | /cms/login.php | 不要 | 管理者ログイン |
| 記事一覧 | /cms/admin/index.php | 必要 | 記事の一覧・削除 |
| 記事作成 | /cms/admin/post-new.php | 必要 | 新規記事の作成 |
| 記事編集 | /cms/admin/post-edit.php?id=1 | 必要 | 既存記事の編集 |
| カテゴリ管理 | /cms/admin/categories.php | 必要 | カテゴリの追加・削除 |

---

## 6. ファイル別の役割

### cms/config.php ✅

全ページから `require_once 'config.php'` で読み込む設定ファイル。

| 関数・定数 | 説明 |
|-----------|------|
| `DB_HOST` 他 | DB接続情報の定数 |
| `SITE_URL` | サイトのベースURL |
| `UPLOAD_DIR` | 画像保存先の絶対パス |
| `db()` | PDO接続を返す関数。同一リクエスト内で使い回す |
| `h($str)` | XSS対策のエスケープ関数 |
| `require_login()` | 未ログインならログイン画面へリダイレクト |

**学んだこと：**
- `define()` で定数を定義する
- `PDO` でDBに接続する
- `static` 変数で接続を使い回す
- `htmlspecialchars()` でXSS対策をする

---

### cms/setup.php ✅

管理者ユーザーを1人だけ登録する初回セットアップ画面。**使用後は削除すること。**

| 処理 | 説明 |
|------|------|
| `password_hash($password, PASSWORD_DEFAULT)` | パスワードをハッシュ化 |
| `INSERT INTO users ...` | プリペアドステートメントでDBに保存 |
| `SELECT COUNT(*) FROM users` | 既存ユーザーがいれば登録を拒否 |

---

### cms/login.php ✅

管理者のログインフォームと処理。

| 処理 | 説明 |
|------|------|
| `SELECT * FROM users WHERE username = ?` | ユーザー名でDB検索 |
| `password_verify($password, $user['password'])` | 入力値とハッシュを照合 |
| `session_regenerate_id(true)` | セッションIDを再生成（ハイジャック対策） |
| `$_SESSION['user_id']` | ログイン情報をセッションに保存 |

---

### cms/logout.php ✅

ログアウト処理。セッションを完全に破棄する。

| 処理 | 説明 |
|------|------|
| `$_SESSION = []` | セッション変数をすべて削除 |
| `setcookie(session_name(), '', ...)` | ブラウザのセッションクッキーを削除 |
| `session_destroy()` | サーバー側のセッションデータを破棄 |

---

## 7. SQL・PHP 用語メモ

### SQL キーワード

| キーワード | 意味 |
|-----------|------|
| `CREATE TABLE` | テーブルを新しく作る |
| `DROP TABLE` | テーブルを削除する |
| `ALTER TABLE` | テーブルの構造を変更する（カラム追加など） |
| `INT` | 数値を入れる列 |
| `VARCHAR(n)` | 最大n文字のテキストを入れる列 |
| `LONGTEXT` | 長い文章を入れる列（記事本文など） |
| `TIMESTAMP` | 日時を入れる列 |
| `ENUM('a','b')` | 指定した値しか入れられない列 |
| `AUTO_INCREMENT` | 自動で 1, 2, 3... と番号を振る |
| `PRIMARY KEY` | その行を唯一識別するキー（重複・空欄不可） |
| `UNIQUE` | 同じ値を2つ登録できない |
| `NOT NULL` | 空欄禁止 |
| `DEFAULT 値` | 何も入れなければこの値が自動で入る |
| `ON UPDATE CURRENT_TIMESTAMP` | レコード更新のたびに現在日時が自動で入る |
| `FOREIGN KEY (列) REFERENCES テーブル(列)` | 別テーブルのデータと紐付ける |

### PRIMARY KEY の種類

| 種類 | 書き方 | 使う場面 |
|------|--------|---------|
| 通常 | `id INT PRIMARY KEY` | ほとんどのテーブル |
| 複合 | `PRIMARY KEY (post_id, category_id)` | 中間テーブル（組み合わせの重複を防ぐ） |

### テーブルの関係（リレーション）

```
users ──────── posts ─────── post_categories ──── categories
（1人）        （複数記事）    （中間テーブル）       （複数カテゴリ）

  1対多                           多対多
```

- **1対多**：1人のユーザーが複数の記事を書ける（`posts.author_id → users.id`）
- **多対多**：1つの記事が複数カテゴリに属せる。`post_categories` が橋渡しをする

### PHP キーワード

| キーワード | 意味 |
|-----------|------|
| `define('KEY', 値)` | 定数を定義する（変更不可） |
| `require_once 'ファイル'` | 別ファイルを1回だけ読み込む |
| `PDO` | PHPからDBに接続するクラス |
| `static $変数` | 関数内で値を保持し続ける（接続を使い回す） |
| `htmlspecialchars()` | HTMLタグを無害化（XSS対策） |
| `password_hash()` | パスワードをハッシュ化して保存用文字列に変換 |
| `password_verify()` | 入力パスワードとハッシュが一致するか検証 |
| `session_start()` | セッションを開始する |
| `$_SESSION['key']` | ページをまたいで値を保持するセッション変数 |

### セキュリティ対策まとめ

| 脅威 | 対策 | 実装 |
|------|------|------|
| SQLインジェクション | プリペアドステートメント | `$pdo->prepare()` + `execute()` |
| XSS | 出力エスケープ | `h()` 関数（`htmlspecialchars()`） |
| パスワード漏洩 | ハッシュ化 | `password_hash()` / `password_verify()` |
| セッションハイジャック | セッションID再生成 | `session_regenerate_id(true)` |
| 不正アクセス | ログインチェック | `require_login()` 関数 |

---

## 8. 実装ログ

### 2026-04-03 Step 0 完了：環境構築・DB作成

- MAMP インストール・起動確認
- phpMyAdmin で `myportfolio` データベース作成
- 4テーブル作成：`users` / `posts` / `categories` / `post_categories`
- `test.php` で PHP の動作確認
- 不要なテーブルを `DROP TABLE` で削除

### 2026-04-03 Step 1 完了：DB接続・設定ファイル

- `cms/config.php` 作成
- `cms/test-db.php` でDB接続確認 → `DB接続成功！ usersテーブルのレコード数: 0`
- DB接続：PDO使用、エラーは例外で検知

### 2026-04-03 Step 2 完了：管理者ユーザー登録・ログイン

- `cms/setup.php` 作成：フォームから管理者を1人だけ登録できる初回セットアップ画面
  - `password_hash()` でパスワードをハッシュ化してDBに保存
  - 既にユーザーが存在する場合は登録を拒否（2人目を防ぐ）
  - 登録後はファイルを削除する必要あり
- `cms/login.php` 作成：ログインフォーム + 処理
  - `password_verify()` でハッシュと照合
  - `session_regenerate_id(true)` でセッションハイジャック対策
  - `$_SESSION['user_id']` / `$_SESSION['username']` にログイン情報を保存
  - エラーは「ユーザー名またはパスワードが正しくありません」と統一（情報漏洩防止）
- `cms/logout.php` 作成：セッション変数削除 → クッキー削除 → `session_destroy()`
- `cms/admin/index.php` 作成：管理画面の仮トップページ（Step 3 で本実装）
- `cms/admin/` / `cms/uploads/` ディレクトリ作成

**学んだこと：**
- `password_hash()` は同じパスワードでも毎回違うハッシュを生成する（ソルト自動付与）
- `password_verify($入力値, $ハッシュ)` で照合する（文字列比較では不可）
- `session_regenerate_id(true)` でログイン時にIDを変えてセッションハイジャックを防ぐ
- エラーメッセージは「ユーザー名が違う」「パスワードが違う」と分けてはいけない（攻撃者にヒントを与える）

---

*このファイルはStep完了のたびに更新する*
