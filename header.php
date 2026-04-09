<?php
require_once 'cms/config.php';
$pdo = db();

// 公開済み記事を新しい順に取得
$stmt = $pdo->prepare(
    'SELECT * FROM posts WHERE status = :status ORDER BY created_at DESC'
);
$stmt->execute([':status' => 'published']);
$posts = $stmt->fetchAll();

// スキル一覧を取得
$sk_stmt = $pdo->prepare('SELECT * FROM skill ORDER BY id ASC');
$sk_stmt->execute();
$skills = $sk_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Suzuki Yutaro — Portfolio</title>
  <meta name="description" content="鈴木優太郎のポートフォリオ。フロントエンドエンジニア志望。WordPress・JavaScript・SCSSによるWeb制作実績を掲載しています。">
  <meta property="og:type"        content="website">
  <meta property="og:title"       content="Suzuki Yutaro — Portfolio">
  <meta property="og:description" content="鈴木優太郎のポートフォリオ。フロントエンドエンジニア志望。Web制作実績を掲載しています。">
  <meta property="og:url"         content="https://susuki-island.heavy.jp/myportfolio/">
  <meta property="og:image"       content="https://susuki-island.heavy.jp/myportfolio/ogp.png">
  <meta property="og:site_name"   content="Suzuki Yutaro Portfolio">
  <meta name="twitter:card"       content="summary_large_image">
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/the-new-css-reset/css/reset.min.css">
</head>