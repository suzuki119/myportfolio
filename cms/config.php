<?php
// ===================================================
//  データベース接続設定
//  全ページで require して使う
// ===================================================

// --- DB接続情報 ---
// MAMPのデフォルト値。変更している場合はここを書き換える
define('DB_HOST', 'localhost');        // [組み込み] define()=定数を定義する
define('DB_NAME', 'myportfolio');      // phpMyAdminで作ったDB名に変更する
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_CHARSET', 'utf8mb4');

// --- サイト設定 ---
define('SITE_URL', 'http://localhost:8888/myportfolio'); // MAMPのポートに合わせる
define('UPLOAD_DIR', __DIR__ . '/uploads/');             // [組み込み定数] __DIR__=このファイルがあるディレクトリの絶対パス
define('UPLOAD_URL', SITE_URL . '/cms/uploads/');        // 画像のURL

// define('DB_HOST', 'mysql323.phy.lolipop.lan');        // [組み込み] define()=定数を定義する
// define('DB_NAME', 'LAA1671269-myportfolio');      // phpMyAdminで作ったDB名に変更する
// define('DB_USER', 'LAA1671269');
// define('DB_PASS', 'Tarabagani119');
// define('DB_CHARSET', 'utf8mb4');

// // --- サイト設定 ---
// define('SITE_URL', 'https://susuki-island.heavy.jp/myportfolio'); // MAMPのポートに合わせる
// define('UPLOAD_DIR', __DIR__ . '/uploads/');             // [組み込み定数] __DIR__=このファイルがあるディレクトリの絶対パス
// define('UPLOAD_URL', SITE_URL . '/cms/uploads/');        // 画像のURL

// ===================================================
//  PDO でDB接続する関数
// ===================================================

/**
 * DB接続を返す
 * 使い方： $pdo = db();
 */
function db(): PDO // [組み込みクラス] PDO=PHPからDBに接続するクラス
{
    // static にすることで、同じリクエスト内で何度呼んでも1回しか接続しない
    static $pdo = null; // [組み込み] static=関数が終わっても値が残る

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // [クラス定数] SQLエラーを例外として発生させる
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // [クラス定数] 取得結果を連想配列で受け取る
            PDO::ATTR_EMULATE_PREPARES   => false,                  // 本物のプリペアドステートメントを使う（SQLインジェクション対策）
        ];

        try { // [組み込み] 失敗するかもしれない処理を囲む
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options); // [組み込み] new=クラスからオブジェクトを作る
        } catch (PDOException $e) { // [組み込み] 接続失敗時に発生する例外を受け取る / $e=エラー情報が入る変数
            // 本番では詳細を表示しない。開発中は詳細を出す
            exit('DB接続エラー: ' . $e->getMessage()); // [組み込み] exit=処理を止める / getMessage()=エラー内容を取得
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
    // [組み込み] htmlspecialchars()=HTMLの特殊文字を無害化
    // [組み込み定数] ENT_QUOTES=" と ' の両方を変換
}

/**
 * ログイン済みかチェック。未ログインならログイン画面へ飛ばす
 * 使い方： require_login();
 */
function require_login(): void
{
    if (session_status() === PHP_SESSION_NONE) { // [組み込み] セッションの状態を返す / [組み込み定数] セッション未開始
        session_start(); // [組み込み] セッションを開始する
    }
    if (empty($_SESSION['user_id'])) { // [組み込み] 変数が空かどうか調べる
        header('Location: ' . SITE_URL . '/cms/login.php'); // [組み込み] 別URLへリダイレクト
        exit;
    }
}
