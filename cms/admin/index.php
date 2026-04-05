<?php
// ===================================================
//  管理画面 トップ（記事一覧）
// ===================================================
require_once '../config.php'; // [組み込み] 1つ上の階層のconfig.phpを読み込む
require_login();              // [自作] 未ログインならログイン画面へ飛ばす

$pdo = db(); // [自作] DB接続を取得

// ===================================================
//  削除処理（POSTで id が送られてきたとき）
// ===================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['delete_id'])) {
    $stmt = $pdo->prepare('DELETE FROM posts WHERE id = :id'); // [PDO組み込み] SQLを準備する
    $stmt->execute([':id' => $_POST['delete_id']]);            // [PDO組み込み] SQLを実行する
    header('Location: ' . SITE_URL . '/cms/admin/index.php'); // 削除後にリロード
    exit;
}

// ===================================================
//  記事一覧を取得
// ===================================================
$stmt = $pdo->prepare('SELECT * FROM posts ORDER BY created_at DESC'); // DESC=新しい順
$stmt->execute();
$posts = $stmt->fetchAll(); // [PDO組み込み] 全行を配列で取得
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>記事一覧 | 管理画面</title>
    <style>
        body { font-family: sans-serif; max-width: 900px; margin: 40px auto; padding: 0 20px; }
        h1 { font-size: 1.4rem; margin-bottom: 24px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        a.button { padding: 8px 16px; background: #222; color: #fff; text-decoration: none; font-size: .9rem; }
        table { width: 100%; border-collapse: collapse; font-size: .9rem; }
        th, td { padding: 10px 12px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background: #f5f5f5; }
        .status-published { color: #27ae60; font-weight: bold; }
        .status-draft { color: #999; }
        .actions form { display: inline; }
        .actions a { margin-right: 8px; color: #333; font-size: .85rem; }
        .actions button { background: none; border: none; color: #c0392b; cursor: pointer; font-size: .85rem; }
        .empty { padding: 40px; text-align: center; color: #999; }
    </style>
</head>
<body>
    <div class="header">
        <h1>記事一覧</h1>
        <a class="button" href="<?= SITE_URL ?>/cms/admin/post-new.php">+ 新規作成</a>
    </div>

    <?php if (empty($posts)): // [組み込み] 配列が空かどうか調べる ?>
        <p class="empty">記事がまだありません。</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>タイトル</th>
                    <th>ステータス</th>
                    <th>作成日</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($posts as $post): // [組み込み] 配列をループする ?>
                <tr>
                    <td><?= h($post['title']) ?></td><?php // [自作] h()=XSS対策 ?>
                    <td>
                        <?php if ($post['status'] === 'published'): ?>
                            <span class="status-published">公開</span>
                        <?php else: ?>
                            <span class="status-draft">下書き</span>
                        <?php endif; ?>
                    </td>
                    <td><?= h($post['created_at']) ?></td>
                    <td class="actions">
                        <a href="<?= SITE_URL ?>/cms/admin/post-edit.php?id=<?= h($post['id']) ?>">編集</a>
                        <form method="post" onsubmit="return confirm('削除しますか？');">
                            <input type="hidden" name="delete_id" value="<?= h($post['id']) ?>">
                            <button type="submit">削除</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p style="margin-top: 40px; font-size: .85rem;">
        <a href="<?= SITE_URL ?>/cms/logout.php">ログアウト</a>
    </p>
</body>
</html>
