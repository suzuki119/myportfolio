<?php
// ===================================================
//  記事新規作成
// ===================================================
require_once '../config.php'; // [組み込み] 1つ上の階層のconfig.phpを読み込む
require_login();              // [自作] 未ログインならログイン画面へ飛ばす

$pdo   = db();
$error = '';

// カテゴリ一覧を取得
$c_stmt = $pdo->prepare('SELECT * FROM categories ORDER BY id ASC');
$c_stmt->execute();
$categories = $c_stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']       ?? '');
    $content     = trim($_POST['content']     ?? '');
    $status      = $_POST['status']           ?? 'draft';
    $category_id = $_POST['category_id']      ?? '';

    // バリデーション
    if ($title === '') {
        $error = 'タイトルは必須です。';
    } else {
        // ===================================================
        //  画像アップロード処理
        // ===================================================
        $thumbnail = null; // アップロードなしの場合は null

        if (!empty($_FILES['thumbnail']['name'])) { // $_FILES=アップロードファイルの情報
            $file     = $_FILES['thumbnail'];
            $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            // [組み込み] strtolower()=小文字に変換 / pathinfo()=ファイルパスの情報を取得

            $allowed  = ['jpg', 'jpeg', 'png', 'gif', 'webp']; // 許可する拡張子

            if (!in_array($ext, $allowed)) {
                // [組み込み] in_array()=配列に値が含まれるか調べる
                $error = '画像はjpg・png・gif・webpのみ使用できます。';
            } elseif ($file['size'] > 2 * 1024 * 1024) {
                // 2MB以上は拒否（2 * 1024 * 1024 = 2097152バイト）
                $error = '画像サイズは2MB以下にしてください。';
            } else {
                // ファイル名をユニークな名前に変更して保存（重複防止）
                $filename  = uniqid() . '.' . $ext;
                // [組み込み] uniqid()=現在時刻ベースのユニークなIDを生成

                $savePath  = UPLOAD_DIR . $filename; // [自作定数] 保存先の絶対パス

                if (move_uploaded_file($file['tmp_name'], $savePath)) {
                    // [組み込み] move_uploaded_file()=アップロードされた一時ファイルを指定場所に移動
                    $thumbnail = $filename; // 保存成功したらファイル名をセット
                } else {
                    $error = '画像の保存に失敗しました。';
                }
            }
        }

        if ($error === '') {
            // ① 記事を INSERT して発行されたIDを取得
            $stmt = $pdo->prepare(
                'INSERT INTO posts (title, content, thumbnail, status, author_id) VALUES (:title, :content, :thumbnail, :status, :author_id)'
            );
            $stmt->execute([
                ':title'     => $title,
                ':content'   => $content,
                ':thumbnail' => $thumbnail,
                ':status'    => $status,
                ':author_id' => $_SESSION['user_id'],
            ]);

            $newPostId = $pdo->lastInsertId(); // [PDO組み込み] 直前のINSERTで発行されたIDを取得

            // ② カテゴリが選択されていれば post_categories に INSERT
            if (!empty($category_id)) {
                $pc_stmt = $pdo->prepare(
                    'INSERT INTO post_categories (post_id, category_id) VALUES (:post_id, :category_id)'
                );
                $pc_stmt->execute([
                    ':post_id'     => $newPostId,
                    ':category_id' => $category_id,
                ]);
            }

            header('Location: ' . SITE_URL . '/cms/admin/index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>記事新規作成 | 管理画面</title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 40px auto; padding: 0 20px; }
        h1 { font-size: 1.4rem; margin-bottom: 24px; }
        label { display: block; margin-top: 20px; font-size: .9rem; font-weight: bold; }
        input[type="text"], textarea, select { width: 100%; padding: 8px; box-sizing: border-box; margin-top: 6px; border: 1px solid #ccc; font-size: 1rem; }
        textarea { height: 300px; resize: vertical; font-family: monospace; }
        .actions { margin-top: 24px; display: flex; gap: 12px; align-items: center; }
        button { padding: 10px 24px; background: #222; color: #fff; border: none; cursor: pointer; font-size: 1rem; }
        a.back { font-size: .9rem; color: #666; }
        .error { margin-top: 16px; padding: 10px; background: #fdecea; border-left: 4px solid #c0392b; font-size: .9rem; }
    </style>
</head>
<body>
    <h1>記事新規作成</h1>

    <?php if ($error !== ''): ?>
        <div class="error"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
    <?php // enctype="multipart/form-data" ← ファイルを送信するフォームに必須の属性 ?>

        <label>タイトル
            <input type="text" name="title" value="<?= h($_POST['title'] ?? '') ?>" required>
        </label>

        <label>本文
            <textarea name="content"><?= h($_POST['content'] ?? '') ?></textarea>
        </label>

        <label>サムネイル画像（任意）
            <input type="file" name="thumbnail" accept="image/*">
        </label>

        <label>ステータス
            <select name="status">
                <option value="draft"     <?= ($_POST['status'] ?? 'draft') === 'draft'     ? 'selected' : '' ?>>下書き</option>
                <option value="published" <?= ($_POST['status'] ?? 'draft') === 'published' ? 'selected' : '' ?>>公開</option>
            </select>
        </label>

        <div class="categories">
            <label>カテゴリー
                <select name="category_id">
                    <option value="">選択してください</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= h($category['id']) ?>"
                            <?= ($_POST['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                            <?= h($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <div class="actions">
            <button type="submit">保存する</button>
            <a class="back" href="<?= SITE_URL ?>/cms/admin/index.php">← 一覧へ戻る</a>
        </div>
    </form>
</body>
</html>
