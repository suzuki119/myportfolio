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
| 記事一覧 | 管理画面での記事一覧表示 | ✅ 完了 |
| カテゴリ管理 | カテゴリの作成・削除・記事への紐付け | 🔲 未着手 |
| 画像アップロード | サムネイル画像のアップロード・保存 | 🔲 未着手 |

### 開発ステップ

| Step | 内容 | 状態 |
|------|------|------|
| Step 1 | DB接続・設定ファイル（config.php） | ✅ 完了 |
| Step 2 | 管理者ユーザー登録・ログイン | ✅ 完了 |
| Step 3 | 記事一覧（管理画面トップ） | ✅ 完了 |
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

### ロリポップへの公開手順

#### 独自CMSの場合（このプロジェクト）

**Step 1：DBを作成する（サーバーパネル）**

```
ユーザー専用ページにログイン
→ サービス・契約 → データベース
→「データベースを追加する」
```

作成すると以下の情報が発行される：

| config.phpの定数 | 内容 |
|----------------|------|
| `DB_HOST` | `mysql**.lolipop.jp`（発行されたホスト名） |
| `DB_NAME` | `アカウント名_myportfolio` のような形式 |
| `DB_USER` | アカウント名と同じことが多い |
| `DB_PASS` | 作成時に自分で設定したパスワード |

**Step 2：phpMyAdminを開く**

```
「データベース」ページ → 作成したDBの横の「phpMyAdmin」ボタン
```

WordPressと違い、ロリポップが用意したphpMyAdminを使う。自分でインストールする必要はない。

**Step 3：テーブルをインポートする**

```
ローカルのphpMyAdmin → エクスポート → .sql ファイルを保存
         ↓
ロリポップのphpMyAdmin → インポート → その .sql ファイルを選択
```

テーブルの構造だけでなく、記事・カテゴリなどのデータも一緒にエクスポートできる。

**Step 4：ファイルをアップロードする（FTP）**

FTPソフト（FileZillaなど）でロリポップのサーバーにファイルを転送する。

```
myportfolio/ フォルダ全体をアップロード
（cms/uploads/ の画像ファイルも含む）
```

**Step 5：config.php の接続情報を書き換える**

```php
// ローカル（開発中）
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'root');

// 本番（ロリポップ）← Step 1 で発行された値に変える
define('DB_HOST', 'mysql**.lolipop.jp');
define('DB_NAME', 'アカウント名_myportfolio');
define('DB_USER', 'アカウント名');
define('DB_PASS', '設定したパスワード');
```

> ⚠️ `config.php` にはパスワードが書かれているため、GitHubなどに公開しないよう `.gitignore` に追加しておくこと。

---

#### WordPressとの比較

WordPressも同じPHP＋MySQLの仕組みだが、DB周りの作業の多くが自動化されている。

| 作業 | 独自CMS（このプロジェクト） | WordPress |
|------|--------------------------|-----------|
| テーブル作成 | 自分でSQLを書く | インストール時に自動で作られる |
| テーブルのエクスポート | phpMyAdminで手動 | プラグイン（All-in-One WP Migration など）で自動 |
| 接続情報の設定 | `config.php` を直接編集 | `wp-config.php` を編集（内容はほぼ同じ） |
| phpMyAdminへのアクセス | サーバーパネル経由 | サーバーパネル経由（同じ） |
| 画像の移行 | `uploads/` フォルダをFTPでそのまま転送 | プラグインで自動、または `wp-content/uploads/` をFTPで転送 |
| インストール作業 | なし（ファイルをアップするだけ） | ブラウザでインストール画面を実行する必要がある |

**WordPressの `wp-config.php`（参考）**

```php
// WordPressの接続設定ファイル。独自CMSのconfig.phpと役割は同じ
define( 'DB_NAME', 'データベース名' );
define( 'DB_USER', 'ユーザー名' );
define( 'DB_PASSWORD', 'パスワード' );
define( 'DB_HOST', 'localhost' );
```

`define()` の使い方もほぼ同じ。WordPressも内部は同じPHPの仕組みで動いている。

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

**DB接続の仕組み（詳細）：**

`db()` 関数は3つのブロックで構成される。

**① 定数（接続情報）**

```php
define('DB_HOST', 'localhost'); // DBサーバーの場所（MAMPは自分のMac内なのでlocalhost）
define('DB_NAME', 'myportfolio'); // phpMyAdminで作ったDB名
define('DB_USER', 'root');     // MAMPのデフォルトユーザー
define('DB_PASS', 'root');     // MAMPのデフォルトパスワード
define('DB_CHARSET', 'utf8mb4'); // 日本語を含む全言語対応の文字コード
```

**② static変数で接続を使い回す**

通常の変数は関数が終わると消えるが、`static` をつけると次の呼び出し時も値が残る。

```php
// 通常の変数：毎回リセットされる
function test() {
    $count = 0;
    $count++;
    echo $count;
}
test(); // 1
test(); // 1（リセットされる）

// static変数：値が残る
function test() {
    static $count = 0;
    $count++;
    echo $count;
}
test(); // 1
test(); // 2（残っている）
```

`db()` では接続済みの `$pdo` を残すために使っている。

**③ if ($pdo === null) の中でやっていること**

```php
static $pdo = null;   // 最初は null（空）
if ($pdo === null) {  // null のときだけ中を実行（初回のみ）
    // DSNを作る
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    // DBに接続して $pdo に保存
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
}
return $pdo; // 接続済みオブジェクトを返す
```

```
1回目の呼び出し → $pdo が null → if の中を実行 → DBに接続 → $pdo に保存
2回目以降      → $pdo に値がある → if をスキップ → そのまま return
```

つまり「何度 `db()` を呼んでもDB接続は1回しか行わない」という効率的な設計。

**④ DSN（接続先を表す文字列）**

```
mysql:host=localhost;dbname=myportfolio;charset=utf8mb4
  ↑DB種別  ↑サーバー    ↑DB名              ↑文字コード
```

`new PDO()` に渡す「どのDBのどこに接続するか」をまとめた文字列。

**⑤ try〜catch**

「失敗するかもしれない処理」を `try` に書き、失敗したときの対処を `catch` に書く。

```php
try {
    // ここを試す（失敗するかもしれない処理）
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // 失敗したときここが実行される
    exit('DB接続エラー: ' . $e->getMessage());
}
```

| 部分 | 意味 |
|------|------|
| `PDOException` | DB接続失敗時に発生するエラーの種類 |
| `$e` | 発生したエラーの情報が入る変数（慣習的に `$e` と書く） |
| `$e->getMessage()` | エラーの詳細メッセージを取り出す |
| `exit()` | メッセージを表示してPHPの実行を止める |

失敗するケース：DBが起動していない・パスワードが違う・DB名が違うなど。

JavaScriptにも同じ仕組みがある。書き方はほぼ同じで、`catch()` の中にエラーの種類を指定するかどうかが違う。

```javascript
// JavaScript
try {
    const res = await fetch('/api/data');
} catch (e) {
    console.log('エラー:', e.message);
}
```

**⑥ $options の3つの設定**

`[]` はPHPの配列。`キー => 値` の形で複数の設定をまとめて `new PDO()` に渡す。

```php
$options = [
    キー1 => 値1,
    キー2 => 値2,
    キー3 => 値3,
];
```

| オプション | 意味 |
|-----------|------|
| `ERRMODE_EXCEPTION` | SQLエラーを例外として発生させる。これがないとエラーを無視して処理が続いてしまう |
| `FETCH_ASSOC` | 取得結果を `$row['id']` のような連想配列で受け取る。設定しないとカラム名と番号の両方が入り冗長になる |
| `EMULATE_PREPARES => false` | 本物のプリペアドステートメントを使う。`false` にしないとSQLインジェクション対策が不完全になる |

`PDO::` はPDOクラスが持つ定数へのアクセス方法。値は内部的にただの数値だが、名前で書くことで意味が分かりやすくなる。

**使い方：**

```php
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
$stmt->execute([':id' => 1]);
$user = $stmt->fetch();
```

---

**ヘルパー関数：h()**

XSS（クロスサイトスクリプティング）対策の関数。画面に値を出力するときに必ず使う。

XSSとは、フォームに `<script>` などを入力してページ上で実行させる攻撃。`htmlspecialchars()` でHTMLの特殊文字を無害な文字列に変換することで防ぐ。

```php
'<script>'  →  '&lt;script&gt;'  // ブラウザがタグとして認識しない
'"'         →  '&quot;'
"'"         →  '&#039;'
```

`h()` という短い名前にしているのは、出力のたびに毎回書くため。

```php
echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); // 長い
echo h($user['username']); // h() にまとめてシンプルに
```

`htmlspecialchars()` の引数：

```php
htmlspecialchars( $str,       ENT_QUOTES, 'UTF-8' )
//               ↑変換したい文字列 ↑変換モード  ↑文字コード
```

`ENT_QUOTES` はPHPが用意した定数（`PDO::ATTR_ERRMODE` と同じ種類）。どの文字を変換するかを指定する。

| 定数 | 変換対象 |
|------|---------|
| `ENT_COMPAT`（デフォルト） | `"` だけ変換。`'` は変換しない |
| `ENT_QUOTES` | `"` も `'` も両方変換 |
| `ENT_NOQUOTES` | どちらも変換しない |

`'` も攻撃に使われる可能性があるため `ENT_QUOTES` を使う。第3引数の `'UTF-8'` は文字コードの指定。日本語サイトでは必須。

---

**ヘルパー関数：require_login()**

管理画面ページの先頭で呼び出し、未ログインならログイン画面へ強制移動させる関数。

```php
require_login(); // これだけでログインチェック完了
```

中でやっていること：

① セッションが始まっていなければ開始する
```php
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // セッションは使う前に開始が必要
}
```

`PHP_SESSION_NONE` はPHPが用意した定数。`session_status()` の戻り値と比較する。

| 定数 | 意味 |
|------|------|
| `PHP_SESSION_DISABLED` | セッション機能が無効 |
| `PHP_SESSION_NONE` | セッション未開始 |
| `PHP_SESSION_ACTIVE` | セッション開始済み |

実態はただの数値（0・1・2）だが、名前をつけることで意味が明確になる。`PDO::ATTR_ERRMODE` や `ENT_QUOTES` と同じ種類の定数。

② `$_SESSION['user_id']` が空なら未ログインと判断してリダイレクト
```php
if (empty($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . '/cms/login.php'); // リダイレクト先を指定
    exit; // 以降の処理を止める
}
```

---

**関数の型宣言**

```php
function h(string $str): string  // 引数が string、戻り値も string
function require_login(): void   // 戻り値なし（void）
```

引数と戻り値の型を明示することで、間違った使い方をしたときにエラーで気づける。JavaScriptにはない機能（TypeScriptにはある）。

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

**なぜ `require_login()` を使わないのか**

`require_login()` は「未ログインならログイン画面へ飛ばす」関数。`login.php` でやりたいのは逆の「ログイン済みなら管理画面へ飛ばす」処理。`require_login()` を呼ぶと未ログインのユーザーが `login.php` に戻ってきて無限ループになる。

| ファイル | 目的 | 使う関数 |
|---------|------|---------|
| `admin/index.php` など | 未ログインを締め出す | `require_login()` |
| `login.php` | ログイン済みを管理画面へ誘導する | 手動でチェック |

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

### PHPの関数・定数の種類

| 種類 | 例 | 説明 |
|------|-----|------|
| PHPの組み込み関数 | `session_start()` `htmlspecialchars()` `header()` `empty()` `trim()` | PHPに最初から入っている。自分で定義せず使える |
| PHPの組み込み定数 | `PHP_SESSION_NONE` `ENT_QUOTES` `PASSWORD_DEFAULT` | PHPに最初から入っている定数 |
| クラスの定数 | `PDO::ATTR_ERRMODE` `PDO::FETCH_ASSOC` | PDOなどのクラスが持つ定数 |
| 自分で定義したもの | `db()` `h()` `require_login()` `DB_HOST` | config.phpで自分で作ったもの |

---

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

### echo（出力）

画面（ブラウザ）に文字を出力する命令。

```php
echo 'こんにちは';          // こんにちは
echo $name;                // 変数の中身を出力
echo 'Hello ' . $name;    // . で文字列をつなげる
echo '<p>HTMLも書ける</p>'; // HTMLタグもそのまま出力される
```

**JavaScriptとの比較：**

| | PHP | JavaScript |
|--|-----|-----------|
| 出力命令 | `echo '文字';` | `console.log('文字')` |
| 表示場所 | ブラウザのページ上に直接表示 | ブラウザの開発者ツール（コンソール）のみ |

---

### prepare() と execute()

セットで使うPDOのメソッド。SQLインジェクション対策のために2段階に分ける。

```php
$stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username');
// ↑ :username はプレースホルダ（値の穴）。まだ値は入っていない

$stmt->execute([':username' => $username]);
// ↑ プレースホルダに実際の値を渡して実行
```

**なぜ2段階に分けるのか：**

```php
// 危険：ユーザーの入力をSQLに直接埋め込む
$pdo->query("SELECT * FROM users WHERE username = '$username'");
// $username に "' OR '1'='1" などを入れられると全件取得されてしまう

// 安全：prepare → execute で分ける
$stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username');
$stmt->execute([':username' => $username]);
// 値がどんな文字列でもSQLの命令として解釈されない
```

```
prepare()  → MySQLに「このSQL構造を使う」と伝える
execute()  → MySQLに「この値で実行して」と値だけ送る（値は命令として解釈されない）
```

---

### -> と => の違い

| 記号 | 用途 | JavaScript相当 |
|------|------|---------------|
| `->` | オブジェクトのメソッド・プロパティ呼び出し | `.`（ドット） |
| `=>` | 配列のキーと値の対応 | `:`（コロン） |

```php
// -> オブジェクトのメソッドを呼ぶ
$pdo->prepare(...);   // $pdo オブジェクトの prepare を呼ぶ
$e->getMessage();     // $e オブジェクトの getMessage を呼ぶ

// => 配列のキーと値を対応させる
$options = ['name' => 'suzuki'];          // キー: 'name'、値: 'suzuki'
$stmt->execute([':username' => $username]); // プレースホルダ: 実際の値
```

---

### 変数と定数

PHPの変数は必ず `$` から始める。型の宣言は不要。

```php
$name = 'suzuki';  // 文字列
$age  = 25;        // 数値
$flag = true;      // 真偽値
```

`define()` で作った**定数**は `$` なしで使う。

```php
define('SITE_URL', 'http://localhost:8888');

echo $name;    // 変数：$あり
echo SITE_URL; // 定数：$なし
```

`config.php` の `DB_HOST` などが `$` なしなのはこのため。

**他の言語との比較：**

| 言語 | 変数宣言 |
|------|---------|
| PHP | `$name = 'suzuki';` |
| JavaScript | `let name = 'suzuki';` |
| Python | `name = 'suzuki'` |
| Java | `String name = "suzuki";` |

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

### 2026-04-06 Step 3 完了：記事一覧ページ

- `cms/admin/index.php` 実装
- DBから記事を全件取得して一覧表示（タイトル・ステータス・作成日・操作）
- 削除ボタン：POSTで `delete_id` を送信 → `DELETE FROM posts` で削除 → リロード
- 記事がない場合は「記事がまだありません」を表示

**学んだこと：**
- `fetchAll()` で全行を配列で取得（`fetch()` は1行だけ）
- `foreach` で配列をループする
- `ORDER BY created_at DESC` で新しい順に並べる
- 削除はGETではなくPOSTで行う（URLに直接アクセスされて消えるのを防ぐ）
- `confirm()` でJavaScriptの確認ダイアログを出す

---

*このファイルはStep完了のたびに更新する*
