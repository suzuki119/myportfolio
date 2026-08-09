<?php
// ===================================================
//  管理画面 トップ（記事一覧）
// ===================================================
require_once '../config.php'; // [組み込み] 1つ上の階層のconfig.phpを読み込む
$pdo = db(); // [自作] DB接続を取得
require_login();              // [自作] 未ログインならログイン画面へ飛ばす



// ===================================================
//  削除処理（POSTで id が送られてきたとき）
// ===================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['delete_id'])) {//ブラウザがどの方法でアクセスしてきたかを判定 / $_POST はフォームの送信値
    $delete_id = $_POST['delete_id'];

    // 外部キー制約があるため、子テーブルを先に削除してから posts を削除する
    // （逆順にすると「1451 Cannot delete or update a parent row」エラーになる）
    $pdo->prepare('DELETE FROM post_categories WHERE post_id = :id') // ① 関連するカテゴリの紐付けを削除
        ->execute([':id' => $delete_id]);
    $pdo->prepare('DELETE FROM post_sections WHERE post_id = :id')   // ② 関連するセクションを削除
        ->execute([':id' => $delete_id]);
    $pdo->prepare('DELETE FROM posts WHERE id = :id')                // ③ 最後に記事本体を削除
        ->execute([':id' => $delete_id]);

    header('Location: ' . SITE_URL . '/cms/admin/index.php'); // 削除後にリロード
    exit;
}

// ===================================================
//  並び替え処理（順番の数字が入力されたとき）
//
//  仕組み：「入れ替え（swap）」ではなく「抜き取って差し込む（insert）」方式。
//  例）1番の記事を5番に移動 → 元2〜5番が1つずつ繰り上がり、移動した記事が5番に入る。
//  最後に全件へ 1,2,3… を振り直すので、番号の重複や抜けが起きない。
// ===================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['position_id'])) {
    $position_id = (int)$_POST['position_id'];       // 移動させたい記事のID
    $new_position = (int)$_POST['position'];         // 入力された「何番にしたいか」（1始まり）

    // 現在の並び順で全記事のIDを取得
    // 第2キーに id を入れておくと、sort_order が同じ値でも並びが毎回同じになる
    $order_stmt = $pdo->query('SELECT id FROM posts ORDER BY sort_order ASC, id ASC');
    $ids = $order_stmt->fetchAll(PDO::FETCH_COLUMN); // [PDO組み込み] 1列目だけを配列で取得

    // 移動対象が今どこにいるか（配列の何番目か）を調べる
    $from = array_search($position_id, $ids); // [組み込み] array_search()=値を探して添字を返す。無ければ false

    if ($from !== false) {
        // 入力値を 1〜件数 の範囲に丸める（0や999が入っても壊れないように）
        $new_position = max(1, min(count($ids), $new_position));
        $to = $new_position - 1; // 表示は1始まり、配列は0始まりなので合わせる

        // ① 移動対象を配列から抜き取る
        array_splice($ids, $from, 1); // [組み込み] array_splice()=指定位置から要素を削除する
        // ② 目的の位置に差し込む（後ろの記事は自動的に1つずつずれる）
        array_splice($ids, $to, 0, [$position_id]); // 第3引数0=削除しない、第4引数=挿入する値

        // ③ 並び終わった配列の順番どおりに 1,2,3… を振り直す
        //    途中で失敗しても中途半端な番号が残らないようトランザクションで囲む
        $pdo->beginTransaction(); // [PDO組み込み] ここから先の更新をまとめて確定/取消できるようにする
        try {
            $up_stmt = $pdo->prepare('UPDATE posts SET sort_order = :order WHERE id = :id');
            foreach ($ids as $index => $id) {
                $up_stmt->execute([':order' => $index + 1, ':id' => $id]);
            }
            $pdo->commit();   // [PDO組み込み] 全部成功したので確定
        } catch (PDOException $e) {
            $pdo->rollBack(); // [PDO組み込み] 失敗したので更新前の状態に戻す
            throw $e;
        }
    }

    header('Location: ' . SITE_URL . '/cms/admin/index.php');
    exit;
}

// ===================================================
//  記事一覧を取得
// ===================================================
// sort_order が同じ値でも並びがぶれないよう、第2キーに id を指定する
$stmt = $pdo->prepare('SELECT * FROM posts ORDER BY sort_order ASC, id ASC');
$stmt->execute();
$posts = $stmt->fetchAll(); // [PDO組み込み] 全行を配列で取得

// カテゴリ一覧を取得（selectボックスの選択肢に使う）
$c_stmt = $pdo->prepare('SELECT * FROM categories ORDER BY id ASC');
$c_stmt->execute();
$categories = $c_stmt->fetchAll();

// この記事に現在付与されているカテゴリIDを取得
$pc_stmt = $pdo->prepare('SELECT category_id FROM post_categories ORDER BY post_id ASC');
$pc_stmt->execute();
$post_category_id  = $pc_stmt->fetchAll(); // 紐付けがなければ false
$currentCategoryId = $post_category_id ? $post_category_id[0]['category_id'] : null;
// [組み込み] 三項演算子：fetch()がfalseのとき null にする（Warningを防ぐ）

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
        .type-simple { color: #2980b9; font-weight: bold; }
        .type-normal { color: #999; }
        .actions form { display: inline; }
        .actions a { margin-right: 8px; color: #333; font-size: .85rem; }
        .actions button { background: none; border: none; color: #c0392b; cursor: pointer; font-size: .85rem; }
        .sort-form { display: flex; align-items: center; gap: 4px; }
        .sort-input { width: 52px; padding: 4px 6px; border: 1px solid #ccc; text-align: center; font-size: .9rem; }
        .sort-btn { padding: 4px 8px; background: #f5f5f5; border: 1px solid #ccc; color: #555; cursor: pointer; font-size: .8rem; }
        .sort-btn:hover { background: #e8e8e8; }
        .empty { padding: 40px; text-align: center; color: #999; }
    </style>
</head>
<body>
    <div class="header">
        <h1>記事一覧</h1>
        <a class="button" href="<?= SITE_URL ?>/cms/admin/post-new.php">+ 新規作成</a>
        <a class="button" href="<?= SITE_URL ?>/cms/admin/categories.php">+ カテゴリー</a>
        <a class="button" href="<?= SITE_URL ?>/cms/admin/skill.php">+ スキル</a>
        <a href="<?= SITE_URL ?>/index.php" class="back-link">
            Back to Portfolio
        </a>
    </div>

    <?php if (empty($posts)): // [組み込み] 配列が空かどうか調べる ?>
        <p class="empty">記事がまだありません。</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>順番</th>
                    <th>タイトル</th>
                    <th>表示形式</th>
                    <th>公開状態</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($posts as $i => $post): // [組み込み] 配列をループする ?>
                <tr>
                    <td>
                        <!-- 何番目にしたいかを直接入力する。Enterまたは「移動」で確定 -->
                        <form method="post" class="sort-form">
                            <input type="hidden" name="position_id" value="<?= h($post['id']) ?>">
                            <input type="number" name="position" class="sort-input"
                                   value="<?= h($i + 1) ?>" min="1" max="<?= count($posts) ?>" required>
                            <button type="submit" class="sort-btn">移動</button>
                        </form>

                    </td>
                    <td><?= h($post['title']) ?></td><?php // [自作] h()=XSS対策 ?>
                    <td>
                        <?php if (!empty($post['simple'])): ?>
                            <span class="type-simple">シンプル</span>
                        <?php else: ?>
                            <span class="type-normal">通常</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($post['status'] === 'published'): ?>
                            <span class="status-published">公開</span>
                        <?php else: ?>
                            <span class="status-draft">下書き</span>
                        <?php endif; ?>
                    </td>
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
