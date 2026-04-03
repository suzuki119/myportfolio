<?php
// ===================================================
//  データベース接続設定
//  全ページで require して使う
// ===================================================

// --- DB接続情報 ---
// MAMPのデフォルト値。変更している場合はここを書き換える
define('DB_HOST', 'localhost');
define('DB_NAME', 'myportfolio');   // phpMyAdminで作ったDB名に変更する
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_CHARSET', 'utf8mb4');

// --- サイト設定 ---
define('SITE_URL', 'http://localhost:8888/myportfolio'); // MAMPのポートに合わせる
define('UPLOAD_DIR', __DIR__ . '/uploads/');             // 画像保存先（絶対パス）
define('UPLOAD_URL', SITE_URL . '/cms/uploads/');        // 画像のURL

// ===================================================
//  PDO でDB接続する関数
// ===================================================

/**
 * DB接続を返す
 * 使い方： $pdo = db();
 */
function db(): PDO
{
    // static にすることで、同じリクエスト内で何度呼んでも1回しか接続しない
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // エラーを例外として投げる
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // 連想配列で取得
            PDO::ATTR_EMULATE_PREPARES   => false,                  // 本物のプリペアドステートメントを使う
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // 本番では詳細を表示しない。開発中は詳細を出す
            exit('DB接続エラー: ' . $e->getMessage());
        }
    }

    return $pdo;
}

// ===================================================
//  共通ヘルパー関数
// ===================================================

/**
 * XSS対策：HTML特殊文字をエスケープして出力
 * 使い方： echo h($変数);
 */
function h(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * ログイン済みかチェック。未ログインならログイン画面へ飛ばす
 * 使い方： require_login();
 */
function require_login(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . SITE_URL . '/cms/login.php');
        exit;
    }
}
