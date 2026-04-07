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

if (!$post) {
    header('Location: ' . SITE_URL . '/cms/admin/index.php');
    exit;
}

// カテゴリ一覧を取得
$c_stmt = $pdo->prepare('SELECT * FROM categories ORDER BY id ASC');
$c_stmt->execute();
$categories = $c_stmt->fetchAll();

// この記事に現在付与されているカテゴリIDを取得
$pc_stmt = $pdo->prepare('SELECT category_id FROM post_categories WHERE post_id = :post_id');
$pc_stmt->execute([':post_id' => $id]);
$post_category_id  = $pc_stmt->fetch();
$currentCategoryId = $post_category_id ? $post_category_id['category_id'] : null;

// 既存セクションを取得
$sec_stmt = $pdo->prepare('SELECT * FROM post_sections WHERE post_id = :post_id ORDER BY sort_order ASC');
$sec_stmt->execute([':post_id' => $id]);
$sections = $sec_stmt->fetchAll();

// ===================================================
//  更新処理
// ===================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title        = trim($_POST['title']        ?? '');
    $status       = $_POST['status']            ?? 'draft';
    $thumbnail    = $post['thumbnail'];
    $category_id  = $_POST['category_id']       ?? '';
    $period       = trim($_POST['period']       ?? '');
    $type         = trim($_POST['type']         ?? '');
    $external_url = trim($_POST['external_url'] ?? '');
    $tags         = trim($_POST['tags']         ?? '');

    if ($title === '') {
        $error = 'タイトルは必須です。';
    } else {
        // ===================================================
        //  画像アップロード処理
        // ===================================================
        if (!empty($_FILES['thumbnail']['name'])) {
            $file    = $_FILES['thumbnail'];
            $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)); // [組み込み] 拡張子を小文字で取得
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (!in_array($ext, $allowed)) {
                $error = '画像はjpg・png・gif・webpのみ使用できます。';
            } elseif ($file['size'] > 2 * 1024 * 1024) {
                $error = '画像サイズは2MB以下にしてください。';
            } else {
                $filename = uniqid() . '.' . $ext; // [組み込み] uniqid()=ユニークなIDを生成
                $savePath = UPLOAD_DIR . $filename;

                if (move_uploaded_file($file['tmp_name'], $savePath)) { // [組み込み] 一時ファイルを指定場所に移動
                    if ($post['thumbnail'] && file_exists(UPLOAD_DIR . $post['thumbnail'])) {
                        unlink(UPLOAD_DIR . $post['thumbnail']); // [組み込み] 古い画像を削除
                    }
                    $thumbnail = $filename;
                } else {
                    $error = '画像の保存に失敗しました。';
                }
            }
        }

        if (!empty($_POST['delete_thumbnail']) && $post['thumbnail']) {
            if (file_exists(UPLOAD_DIR . $post['thumbnail'])) {
                unlink(UPLOAD_DIR . $post['thumbnail']);
            }
            $thumbnail = null;
        }

        if ($error === '') {
            // posts テーブルを更新
            $stmt = $pdo->prepare(
                'UPDATE posts SET
                    title = :title, thumbnail = :thumbnail, status = :status,
                    period = :period, type = :type,
                    external_url = :external_url, tags = :tags
                 WHERE id = :id'
            );
            $stmt->execute([
                ':title'        => $title,
                ':thumbnail'    => $thumbnail,
                ':status'       => $status,
                ':period'       => $period,
                ':type'         => $type,
                ':external_url' => $external_url,
                ':tags'         => $tags,
                ':id'           => $id,
            ]);

            // カテゴリの紐付けを更新（全削除 → 入れ直し）
            $pc_stmt = $pdo->prepare('DELETE FROM post_categories WHERE post_id = :post_id');
            $pc_stmt->execute([':post_id' => $id]);

            if (!empty($category_id)) {
                $pc_stmt = $pdo->prepare('INSERT INTO post_categories (post_id, category_id) VALUES (:post_id, :category_id)');
                $pc_stmt->execute([':post_id' => $id, ':category_id' => $category_id]);
            }

            // セクションの更新（全削除 → 入れ直し）
            $pdo->prepare('DELETE FROM post_sections WHERE post_id = :post_id')
                ->execute([':post_id' => $id]);

            $sec_titles = $_POST['section_title'] ?? []; // [組み込み] ??=nullなら空配列
            $sec_bodies = $_POST['section_body']  ?? [];

            foreach ($sec_titles as $i => $t) {
                if (trim($t) === '') continue; // タイトルが空のセクションはスキップ
                $s_stmt = $pdo->prepare(
                    'INSERT INTO post_sections (post_id, sort_order, title, body)
                     VALUES (:post_id, :sort_order, :title, :body)'
                );
                $s_stmt->execute([
                    ':post_id'    => $id,
                    ':sort_order' => $i,
                    ':title'      => trim($t),
                    ':body'       => trim($sec_bodies[$i] ?? ''),
                ]);
            }

            header('Location: ' . SITE_URL . '/cms/admin/index.php');
            exit;
        }
    }

    // エラー時：フォームの入力値を保持する
    $post['title']        = $title;
    $post['status']       = $status;
    $post['period']       = $period;
    $post['type']         = $type;
    $post['external_url'] = $external_url;
    $post['tags']         = $tags;
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
        input[type="text"], input[type="url"], textarea, select { width: 100%; padding: 8px; box-sizing: border-box; margin-top: 6px; border: 1px solid #ccc; font-size: 1rem; }
        textarea { height: 200px; resize: vertical; font-family: monospace; }
        .actions { margin-top: 24px; display: flex; gap: 12px; align-items: center; }
        button[type="submit"] { padding: 10px 24px; background: #222; color: #fff; border: none; cursor: pointer; font-size: 1rem; }
        a.back { font-size: .9rem; color: #666; }
        .error { margin-top: 16px; padding: 10px; background: #fdecea; border-left: 4px solid #c0392b; font-size: .9rem; }
        .meta { margin-top: 8px; font-size: .8rem; color: #999; }
        .thumbnail-preview img { max-width: 200px; margin-top: 8px; display: block; }
        .thumbnail-preview label { font-weight: normal; font-size: .85rem; color: #c0392b; margin-top: 6px; }
        .section-block { border: 1px solid #ddd; padding: 16px; margin-top: 16px; position: relative; }
        .section-block label { margin-top: 10px; }
        .section-block textarea { height: 120px; }
        .section-delete-btn { position: absolute; top: 10px; right: 10px; background: none; border: none; color: #c0392b; cursor: pointer; font-size: .85rem; }
        .add-section-btn { margin-top: 12px; padding: 8px 16px; background: #555; color: #fff; border: none; cursor: pointer; font-size: .9rem; }
        .section-heading { font-size: 1rem; font-weight: bold; margin-top: 32px; margin-bottom: 8px; }
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

        <label>一覧用期間（例：2025.06 – 08）
            <input type="text" name="period" value="<?= h($post['period'] ?? '') ?>">
        </label>

        <label>種別（例：個人制作 / ブログサイト）
            <input type="text" name="type" value="<?= h($post['type'] ?? '') ?>">
        </label>

        <label>外部リンクURL
            <input type="url" name="external_url" value="<?= h($post['external_url'] ?? '') ?>">
        </label>

        <label>使用技術タグ（カンマ区切り 例：WordPress,SCSS,JavaScript）
            <input type="text" name="tags" value="<?= h($post['tags'] ?? '') ?>">
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
                            <?= h($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <!-- セクション管理 -->
        <div class="section-heading">セクション</div>

        <div id="sections-wrap">
            <?php foreach ($sections as $i => $sec): ?>
                <div class="section-block">
                    <button type="button" class="section-delete-btn" onclick="deleteSection(this)">削除</button>
                    <label>見出し
                        <input type="text" name="section_title[]" value="<?= h($sec['title']) ?>">
                    </label>
                    <label>本文
                        <textarea name="section_body[]"><?= h($sec['body']) ?></textarea>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>

        <button type="button" class="add-section-btn" onclick="addSection()">＋ セクションを追加</button>

        <div class="actions">
            <button type="submit">更新する</button>
            <a class="back" href="<?= SITE_URL ?>/cms/admin/index.php">← 一覧へ戻る</a>
        </div>
    </form>

    <script>
    function addSection() {
        const wrap = document.getElementById('sections-wrap');
        const block = document.createElement('div');
        block.className = 'section-block';
        block.innerHTML = `
            <button type="button" class="section-delete-btn" onclick="deleteSection(this)">削除</button>
            <label>見出し<input type="text" name="section_title[]" style="width:100%;padding:8px;box-sizing:border-box;margin-top:6px;border:1px solid #ccc;font-size:1rem;"></label>
            <label style="margin-top:10px;">本文<textarea name="section_body[]" style="width:100%;padding:8px;box-sizing:border-box;margin-top:6px;border:1px solid #ccc;font-size:1rem;height:120px;resize:vertical;font-family:monospace;"></textarea></label>
        `;
        wrap.appendChild(block);
    }

    function deleteSection(btn) {
        // ボタンの親要素（.section-block）をDOMから削除する
        btn.closest('.section-block').remove();
    }
    </script>
</body>
</html>
