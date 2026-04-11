<?php

// 各ページで以下の変数を設定してから require してください:
// $page_title       – <title> および og:title
// $page_description – <meta name="description"> および og:description
// $og_url           – og:url（省略可）
// $og_image         – og:image（省略時は SITE_URL/ogp.png）
// $body_id          – <body id="...">（省略可）
// $extra_head       – <head>内の追加コード（省略可。Google Fontsなど）
?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h($page_title ?? 'Suzuki Yutaro — Portfolio') ?></title>
  <meta name="description" content="<?= h($page_description ?? '') ?>">
  <meta property="og:type"        content="website">
  <meta property="og:title"       content="<?= h($page_title ?? 'Suzuki Yutaro — Portfolio') ?>">
  <meta property="og:description" content="<?= h($page_description ?? '') ?>">
  <meta property="og:url"         content="<?= $og_url ?? '' ?>">
  <meta property="og:image"       content="<?= $og_image ?? SITE_URL.'img/ogp.webp' ?>">
  <meta property="og:site_name"   content="Suzuki Yutaro Portfolio">
  <meta name="twitter:card"       content="summary_large_image">
  <link rel="icon" href="./img/logo.webp" type="image/x-icon">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
  <?php if (!empty($extra_head)) echo $extra_head; ?>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/the-new-css-reset/css/reset.min.css">
  <link rel="stylesheet" href="css/style.css">
</head>
<body<?= !empty($body_id) ? ' id="' . h($body_id) . '"' : '' ?>>
    <header class="header">
        <a href="index.php" class="back-link">
        <div class="header__logo">
          <img src="./img/portfolio.webp" alt="logo">
        </div>
      </a>
    <button class="header__toggle" id="nav-toggle" aria-label="メニューを開く">
      <span></span><span></span><span></span>
    </button>
      <nav id="main-nav" class="header__nav">
    <a href="index.php">TOP</a>
    <a href="works.php">Works</a>
    <a href="skill.php">Skill</a>
  </nav>
  </header>
