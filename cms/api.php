<?php
require_once __DIR__ . '/config.php';
session_name(CMS_SESSION);
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// 認証チェック
if (!isset($_SESSION['cms_admin_logged_in'])) {
  http_response_code(401);
  echo json_encode(['error' => 'Unauthorized']);
  exit;
}

$FILE = __DIR__ . '/works.json';

function loadWorks($file) {
  if (!file_exists($file)) return [];
  return json_decode(file_get_contents($file), true) ?? [];
}

function saveWorks($file, $works) {
  // 保存前にバックアップ
  if (file_exists($file)) {
    copy($file, $file . '.bak');
  }
  file_put_contents($file, json_encode($works, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

$method  = $_SERVER['REQUEST_METHOD'];
$action  = $_GET['action'] ?? '';

// ── GET: 一覧取得 ──
if ($method === 'GET' && $action === 'list') {
  echo json_encode(loadWorks($FILE), JSON_UNESCAPED_UNICODE);
  exit;
}

// ── POST: ファイルアップロード ──
if ($method === 'POST' && $action === 'upload') {
  $uploadDir = __DIR__ . '/uploads/';
  if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

  $file = $_FILES['file'] ?? null;
  if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['ok' => false, 'error' => 'アップロードに失敗しました']);
    exit;
  }

  // 拡張子チェック
  $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
  $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
  if (!in_array($ext, $allowed)) {
    echo json_encode(['ok' => false, 'error' => '許可されていないファイル形式です（jpg/png/webp/gif のみ）']);
    exit;
  }

  // ファイルサイズチェック（10MB まで）
  if ($file['size'] > 10 * 1024 * 1024) {
    echo json_encode(['ok' => false, 'error' => 'ファイルサイズは10MB以下にしてください']);
    exit;
  }

  $filename = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
  if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
    echo json_encode(['ok' => false, 'error' => '保存に失敗しました']);
    exit;
  }

  echo json_encode(['ok' => true, 'path' => 'cms/uploads/' . $filename]);
  exit;
}

// ── POST: JSON操作 ──
if ($method === 'POST') {
  $body  = json_decode(file_get_contents('php://input'), true);
  $works = loadWorks($FILE);

  // 追加・更新
  if ($action === 'save') {
    $item = $body;
    $idx  = array_search($item['id'], array_column($works, 'id'));
    if ($idx !== false) {
      $works[$idx] = $item;
    } else {
      $item['id'] = time();
      $works[]    = $item;
    }
    saveWorks($FILE, $works);
    echo json_encode(['ok' => true, 'id' => $item['id']]);
    exit;
  }

  // 削除
  if ($action === 'delete') {
    $id    = $body['id'];
    $works = array_values(array_filter($works, fn($w) => $w['id'] !== $id));
    saveWorks($FILE, $works);
    echo json_encode(['ok' => true]);
    exit;
  }

  // 並び替え
  if ($action === 'reorder') {
    $ids = $body['ids'];
    $map = [];
    foreach ($works as $w) $map[$w['id']] = $w;
    $works = array_map(fn($id) => $map[$id], $ids);
    saveWorks($FILE, array_values($works));
    echo json_encode(['ok' => true]);
    exit;
  }
}

http_response_code(400);
echo json_encode(['error' => 'Invalid request']);
