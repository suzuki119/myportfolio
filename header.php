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
  <meta property="og:image"       content="<?= SITE_URL.'img/ogp.webp' ?>">
  <meta property="og:site_name"   content="Suzuki Yutaro Portfolio">
  <meta name="twitter:card"       content="summary_large_image">
  <?php if (!empty($extra_head)) echo $extra_head; ?>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/the-new-css-reset/css/reset.min.css">
  <link rel="stylesheet" href="css/style.css">
</head>
<body<?= !empty($body_id) ? ' id="' . h($body_id) . '"' : '' ?>>
    <header class="header">
    <div class="header__logo">Portfolio</div>
    <button class="header__toggle" id="nav-toggle" aria-label="メニューを開く">
      <span></span><span></span><span></span>
    </button>
      <nav id="main-nav" class="header__nav">
    <a href="#about">About</a>
    <a href="#works">Works</a>
    <a href="#skill">Skill</a>
    <a href="#timeline">Timeline</a>
    <a href="#contact">Contact</a>
  </nav>
  </header>
