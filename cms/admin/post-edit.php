<?php
// ===================================================
//  記事編集
// ===================================================
require_once '../config.php';
require_login();

$pdo   = db();
$error = '';

$id = (int)($_GET['id'] ?? 0);

if ($id === 0) {
    header('Location: ' . SITE_URL . '/cms/admin/index.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM posts WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$post = $stmt->fetch();

// カテゴリ一覧を取得（selectボックスの選択肢に使う）
$c_stmt = $pdo->prepare('SELECT * FROM categories ORDER BY id ASC');
$c_stmt->execute();
$categories = $c_stmt->fetchAll();

// この記事に現在付与されているカテゴリIDを取得
$pc_stmt = $pdo->prepare('SELECT category_id FROM post_categories WHERE post_id = :post_id');
$pc_stmt->execute([':post_id' => $id]);
$post_category_id  = $pc_stmt->fetch(); // 紐付けがなければ false
$currentCategoryId = $post_category_id ? $post_category_id['category_id'] : null;
// [組み込み] 三項演算子：fetch()がfalseのとき null にする（Warningを防ぐ）

if (!$post) {
    header('Location: ' . SITE_URL . '/cms/admin/index.php');
    exit;
}

// ===================================================
//  更新処理
// ===================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title     = trim($_POST['title']   ?? '');
    $content   = trim($_POST['content'] ?? '');
    $status    = $_POST['status'] ?? 'draft';// 下書きがデフォルト
    $thumbnail = $post['thumbnail']; // 既存のファイル名を初期値にする
    $category_id = $_POST['category_id'] ?? ''; // 未選択なら空文字


    // 追加の情報
    $period      = trim($_POST['period']      ?? '');
    $meta_period = trim($_POST['meta_period'] ?? '');
    $meta_type   = trim($_POST['meta_type']   ?? '');
    $external_url = trim($_POST['external_url'] ?? '');
    $tags        = trim($_POST['tags']         ?? '');

    if ($title === '') {
        $error = 'タイトルは必須です。';
    } else {
        // ===================================================
        //  画像アップロード処理
        // ===================================================
        if (!empty($_FILES['thumbnail']['name'])) { // $_FILES=アップロードファイルの情報
            $file    = $_FILES['thumbnail'];
            $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));// [組み込み] strtolower()=小文字に変換 / pathinfo()=ファイルパスの情報を取得
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (!in_array($ext, $allowed)) {// [組み込み] in_array()=配列に値が含まれるか調べる
                $error = '画像はjpg・png・gif・webpのみ使用できます。';
            } elseif ($file['size'] > 2 * 1024 * 1024) {
                $error = '画像サイズは2MB以下にしてください。';
            } else {
                $filename = uniqid() . '.' . $ext;// [組み込み] uniqid()=現在時刻ベースのユニークなIDを生成
                $savePath = UPLOAD_DIR . $filename;

                if (move_uploaded_file($file['tmp_name'], $savePath)) {// [組み込み] move_uploaded_file()=アップロードされた一時ファイルを指定場所に移動
                    // 新しい画像を保存できたら古い画像を削除する
                    if ($post['thumbnail'] && file_exists(UPLOAD_DIR . $post['thumbnail'])) {// [組み込み] file_exists()=ファイルが存在するか確認
                        unlink(UPLOAD_DIR . $post['thumbnail']);// [組み込み] unlink()=ファイルを削除する
                    }
                    $thumbnail = $filename;
                } else {
                    $error = '画像の保存に失敗しました。';
                }
            }
        }

        // サムネイルを削除するチェックボックスが入った場合
        if (!empty($_POST['delete_thumbnail']) && $post['thumbnail']) {
            if (file_exists(UPLOAD_DIR . $post['thumbnail'])) {
                unlink(UPLOAD_DIR . $post['thumbnail']);
            }
            $thumbnail = null;
        }

        if ($error === '') {
            $stmt = $pdo->prepare(
                'UPDATE posts SET title = :title, content = :content, thumbnail = :thumbnail, status = :status, period = :period, meta_period = :meta_period, meta_type = :meta_type, external_url = :external_url, tags = :tags WHERE id = :id'
            );
            $stmt->execute([
                ':title'     => $title,
                ':content'   => $content,
                ':thumbnail' => $thumbnail,
                ':status'    => $status,
                ':period'    => $period,
                ':meta_period' => $meta_period,
                ':meta_type'   => $meta_type,
                ':external_url' => $external_url,
                ':tags'        => $tags,
                ':id'          => $id,
            ]);

            // ===================================================
            //  カテゴリの紐付けを更新（全削除 → 入れ直し）
            // ===================================================
            $pc_stmt = $pdo->prepare('DELETE FROM post_categories WHERE post_id = :post_id');
            $pc_stmt->execute([':post_id' => $id]); // この記事の既存カテゴリをすべて削除

            if (!empty($category_id)) { // カテゴリが選択されているときだけ INSERT
                $pc_stmt = $pdo->prepare('INSERT INTO post_categories (post_id, category_id) VALUES (:post_id, :category_id)');
                $pc_stmt->execute([':post_id' => $id, ':category_id' => $category_id]);
            }

            header('Location: ' . SITE_URL . '/cms/admin/index.php');
            exit;
        }
    }

    $post['title']   = $title;
    $post['content'] = $content;
    $post['status']  = $status;// フォームの内容で上書きしておく（エラーがあってもフォームに入力値を保持するため）

    // 追加の情報も上書きしておく
    $post['period']      = $period;
    $post['meta_period'] = $meta_period;
    $post['meta_type']   = $meta_type;
    $post['external_url'] = $external_url;
    $post['tags']        = $tags;
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>記事編集 | 管理画面</title>
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
        .meta { margin-top: 8px; font-size: .8rem; color: #999; }
        .thumbnail-preview img { max-width: 200px; margin-top: 8px; display: block; }
        .thumbnail-preview label { font-weight: normal; font-size: .85rem; color: #c0392b; margin-top: 6px; }
    </style>
</head>
<body>
    <h1>記事編集</h1>
    <p class="meta">ID: <?= h($post['id']) ?> ／ 作成日: <?= h($post['created_at']) ?></p>

    <?php if ($error !== ''): ?>
        <div class="error"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">

        <label>タイトル
            <input type="text" name="title" value="<?= h($post['title']) ?>" required>
        </label>

        <label>本文
            <textarea name="content"><?= h($post['content']) ?></textarea>

        </label>

        <label>サムネイル画像
            <?php if ($post['thumbnail']): ?>
                <div class="thumbnail-preview">
                    <img src="<?= UPLOAD_URL . h($post['thumbnail']) ?>" alt="現在のサムネイル">
                    <label>
                        <input type="checkbox" name="delete_thumbnail" value="1">
                        この画像を削除する
                    </label>
                </div>
            <?php endif; ?>
            <input type="file" name="thumbnail" accept="image/*" style="margin-top:8px;">
        </label>

        <label>ステータス
            <select name="status">
                <option value="draft"     <?= $post['status'] === 'draft'     ? 'selected' : '' ?>>下書き</option>
                <option value="published" <?= $post['status'] === 'published' ? 'selected' : '' ?>>公開</option>
            </select>
        </label>

        <div class="categories">
            <label>カテゴリー
                <select name="category_id">
                    <option value="">選択してください</option>
                    <?php foreach ($categories as $category): ?>

                        <option value="<?= h($category['id']) ?>"
                            <?= $category['id'] == $currentCategoryId ? 'selected' : '' ?>>
                            <?php // $currentCategoryId と一致するものに selected をつける ?>
                            <?= h($category['name']) ?>
                        </option>

                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <div class="actions">
            <button type="submit">更新する</button>
            <a class="back" href="<?= SITE_URL ?>/cms/admin/index.php">← 一覧へ戻る</a>
        </div>
    </form>
</body>
</html>
