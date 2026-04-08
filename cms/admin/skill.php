<?php

require_once '../config.php';
require_login();

$pdo   = db();
$error = '';

// ===================================================
//  追加処理
// ===================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {

    // スキル追加
    if ($_POST['action'] === 'add') {
        $name = trim($_POST['name'] ?? '');

        if ($name === '') {
            $error = 'スキル名を入力してください。';
        } else {
            $stmt = $pdo->prepare('INSERT INTO skills (name) VALUES (:name)');
            $stmt->execute([':name' => $name]);

            header('Location: ' . SITE_URL . '/cms/admin/skills.php');
            exit;
        }
    }

    // スキル削除
    if ($_POST['action'] === 'delete' && !empty($_POST['delete_id'])) {
        $stmt = $pdo->prepare('DELETE FROM skills WHERE id = :id');
        $stmt->execute([':id' => $_POST['delete_id']]);

        header('Location: ' . SITE_URL . '/cms/admin/skills.php');
        exit;
    }
}

// ===================================================
//  SKILL一覧を取得
// ===================================================
$stmt = $pdo->prepare('SELECT * FROM skills ORDER BY id ASC');
$stmt->execute();
$skills = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>スキル管理 | 管理画面</title>
    <style>
        body { font-family: sans-serif; max-width: 900px; margin: 40px auto; padding: 0 20px; }
        h1 { font-size: 1.4rem; margin-bottom: 24px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        a.button { padding: 8px 16px; background: #222; color: #fff; text-decoration: none; font-size: .9rem; }
        table { width: 100%; border-collapse: collapse; font-size: .9rem; }
        table th, table td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        .actions { display: flex; gap: 8px; }
        .actions form { display: inline; }
    </style>
</head>

<body>
  <h1>スキル管理</h1>
  <div class="header">
    <a href="skill_add.php" class="button">スキルを追加</a>
  </div>
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>スキル名</th>
        <th>操作</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($skills as $skill): ?>
      <tr>
        <td><?= htmlspecialchars($skill['id']) ?></td>
        <td><?= htmlspecialchars($skill['name']) ?></td>
        <td class="actions">
          <form method="post" action="">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="delete_id" value="<?= $skill['id'] ?>">
            <button type="submit" onclick="return confirm('本当に削除しますか？');">削除</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</body>
